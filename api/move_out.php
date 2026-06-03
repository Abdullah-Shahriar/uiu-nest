<?php
/**
 * UIU Nest — Move-Out Lifecycle API
 *
 * POST /api/move_out.php?action=request_leave
 *      Tenant submits a move-out request
 *
 * POST /api/move_out.php?action=owner_accept
 *      Owner accepts the request (no review yet)
 *
 * POST /api/move_out.php?action=owner_reject
 *      Owner rejects the request
 *
 * POST /api/move_out.php?action=submit_tenant_review
 *      Owner submits review of the tenant (1-5 stars)
 *      → Sets status to 'owner_review_done', triggers tenant's turn
 *
 * POST /api/move_out.php?action=submit_property_review
 *      Tenant submits public review of the property (1-5 stars)
 *      → Finalises the cycle: sets moved_out_at in room_tenants (transaction)
 *
 * GET  /api/move_out.php?action=owner_requests
 *      Owner: list pending/accepted requests for their properties
 *
 * GET  /api/move_out.php?action=my_request
 *      Tenant: get their own pending move-out request
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?: [];

requireLogin();

/* ─────────────────────────────────────────────────────────────────────────────
   HELPER: Get the active room_tenant record for a user
   ─────────────────────────────────────────────────────────────────────────── */
function getActiveTenancy(PDO $db, int $userId): ?array {
    $stmt = $db->prepare(
        'SELECT rt.*, r.property_id, p.owner_id
           FROM room_tenants rt
           JOIN rooms r      ON r.id = rt.room_id
           JOIN properties p ON p.id = r.property_id
          WHERE rt.user_id       = ?
            AND rt.moved_out_at IS NULL
          LIMIT 1'
    );
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/* ─────────────────────────────────────────────────────────────────────────────
   GET: Tenant — fetch their own pending move-out request
   ─────────────────────────────────────────────────────────────────────────── */
if ($method === 'GET' && $action === 'my_request') {
    $userId = (int)$_SESSION['user_id'];
    $stmt = $db->prepare(
        'SELECT mor.*, p.name AS property_name, r.room_number,
                u_owner.full_name AS owner_name,
                tr.id AS tenant_review_id,
                pr.id AS property_review_id
           FROM move_out_requests mor
           JOIN room_tenants rt  ON rt.id  = mor.room_tenant_id
           JOIN rooms r          ON r.id   = mor.room_id
           JOIN properties p     ON p.id   = r.property_id
           JOIN users u_owner    ON u_owner.id = mor.owner_id
           LEFT JOIN tenant_reviews  tr ON tr.move_out_req_id = mor.id
           LEFT JOIN property_reviews pr ON pr.move_out_req_id = mor.id
          WHERE mor.tenant_id = ?
            AND mor.status NOT IN ("completed", "rejected")
          ORDER BY mor.created_at DESC
          LIMIT 1'
    );
    $stmt->execute([$userId]);
    jsonResponse(['request' => $stmt->fetch(PDO::FETCH_ASSOC) ?: null]);
}

/* ─────────────────────────────────────────────────────────────────────────────
   GET: Owner — list move-out requests for their properties
   ─────────────────────────────────────────────────────────────────────────── */
if ($method === 'GET' && $action === 'owner_requests') {
    requireRole(['owner', 'admin']);
    $ownerId = (int)$_SESSION['user_id'];

    $stmt = $db->prepare(
        'SELECT mor.*,
                u_tenant.full_name  AS tenant_name,
                u_tenant.email      AS tenant_email,
                u_tenant.avatar_path AS tenant_avatar,
                p.name              AS property_name,
                r.room_number,
                tr.id  AS tenant_review_id,
                pr.id  AS property_review_id
           FROM move_out_requests mor
           JOIN users u_tenant       ON u_tenant.id = mor.tenant_id
           JOIN rooms r              ON r.id  = mor.room_id
           JOIN properties p         ON p.id  = r.property_id
           LEFT JOIN tenant_reviews  tr ON tr.move_out_req_id = mor.id
           LEFT JOIN property_reviews pr ON pr.move_out_req_id = mor.id
          WHERE mor.owner_id = ?
          ORDER BY mor.created_at DESC'
    );
    $stmt->execute([$ownerId]);
    jsonResponse(['requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

/* ─────────────────────────────────────────────────────────────────────────────
   POST: Tenant submits a move-out request
   ─────────────────────────────────────────────────────────────────────────── */
if ($method === 'POST' && $action === 'request_leave') {
    $userId = (int)$_SESSION['user_id'];
    $msg    = trim($input['message'] ?? '');

    $tenancy = getActiveTenancy($db, $userId);
    if (!$tenancy) {
        jsonResponse(['error' => 'You are not currently a registered tenant.'], 403);
    }

    // Check not already requested
    $exists = $db->prepare(
        'SELECT id FROM move_out_requests
          WHERE tenant_id = ? AND status NOT IN ("completed","rejected")'
    );
    $exists->execute([$userId]);
    if ($exists->fetch()) {
        jsonResponse(['error' => 'You already have a pending move-out request.'], 409);
    }

    $stmt = $db->prepare(
        'INSERT INTO move_out_requests
            (room_tenant_id, tenant_id, room_id, owner_id, status, tenant_message)
         VALUES (?, ?, ?, ?, "pending", ?)'
    );
    $stmt->execute([
        $tenancy['id'],
        $userId,
        $tenancy['room_id'],
        $tenancy['owner_id'],
        $msg ?: null,
    ]);

    jsonResponse(['success' => true, 'request_id' => (int)$db->lastInsertId()], 201);
}

/* ─────────────────────────────────────────────────────────────────────────────
   POST: Owner accepts move-out request
   ─────────────────────────────────────────────────────────────────────────── */
if ($method === 'POST' && $action === 'owner_accept') {
    requireRole(['owner', 'admin']);
    $ownerId   = (int)$_SESSION['user_id'];
    $requestId = (int)($input['request_id'] ?? 0);

    if (!$requestId) jsonResponse(['error' => 'Missing request_id'], 400);

    $stmt = $db->prepare(
        'SELECT * FROM move_out_requests WHERE id = ? AND owner_id = ? AND status = "pending"'
    );
    $stmt->execute([$requestId, $ownerId]);
    $req = $stmt->fetch();
    if (!$req) jsonResponse(['error' => 'Request not found or already processed.'], 404);

    $db->prepare(
        'UPDATE move_out_requests SET status = "owner_accepted" WHERE id = ?'
    )->execute([$requestId]);

    jsonResponse(['success' => true]);
}

/* ─────────────────────────────────────────────────────────────────────────────
   POST: Owner rejects move-out request
   ─────────────────────────────────────────────────────────────────────────── */
if ($method === 'POST' && $action === 'owner_reject') {
    requireRole(['owner', 'admin']);
    $ownerId   = (int)$_SESSION['user_id'];
    $requestId = (int)($input['request_id'] ?? 0);

    if (!$requestId) jsonResponse(['error' => 'Missing request_id'], 400);

    $stmt = $db->prepare(
        'SELECT id FROM move_out_requests WHERE id = ? AND owner_id = ? AND status = "pending"'
    );
    $stmt->execute([$requestId, $ownerId]);
    if (!$stmt->fetch()) jsonResponse(['error' => 'Not found or already acted upon.'], 404);

    $db->prepare(
        'UPDATE move_out_requests SET status = "rejected" WHERE id = ?'
    )->execute([$requestId]);

    jsonResponse(['success' => true]);
}

/* ─────────────────────────────────────────────────────────────────────────────
   POST: Owner submits TENANT review (mandatory before completion)
   ─────────────────────────────────────────────────────────────────────────── */
if ($method === 'POST' && $action === 'submit_tenant_review') {
    requireRole(['owner', 'admin']);
    $ownerId   = (int)$_SESSION['user_id'];
    $requestId = (int)($input['request_id'] ?? 0);
    $rating    = max(1, min(5, (int)($input['rating'] ?? 5)));
    $comment   = trim($input['comment'] ?? '');
    $clean     = isset($input['cleanliness']) ? max(1, min(5, (int)$input['cleanliness'])) : null;
    $behav     = isset($input['behaviour'])   ? max(1, min(5, (int)$input['behaviour']))   : null;
    $punct     = isset($input['punctuality']) ? max(1, min(5, (int)$input['punctuality'])) : null;

    if (!$requestId) jsonResponse(['error' => 'Missing request_id'], 400);

    $stmt = $db->prepare(
        'SELECT * FROM move_out_requests WHERE id = ? AND owner_id = ? AND status = "owner_accepted"'
    );
    $stmt->execute([$requestId, $ownerId]);
    $req = $stmt->fetch();
    if (!$req) jsonResponse(['error' => 'Request not found or not in correct state.'], 404);

    // Insert tenant review
    $ins = $db->prepare(
        'INSERT INTO tenant_reviews
            (move_out_req_id, reviewer_id, tenant_id, rating, cleanliness, behaviour, punctuality, comment)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([$requestId, $ownerId, $req['tenant_id'], $rating, $clean, $behav, $punct, $comment ?: null]);

    // Advance status — tenant now must submit property review
    $db->prepare(
        'UPDATE move_out_requests SET status = "owner_review_done" WHERE id = ?'
    )->execute([$requestId]);

    jsonResponse(['success' => true]);
}

/* ─────────────────────────────────────────────────────────────────────────────
   POST: Tenant submits PROPERTY review (public) — finalises the move-out
   Uses a PDO transaction to:
     1. Insert property_reviews
     2. Update move_out_requests.status → 'completed'
     3. Set room_tenants.moved_out_at = CURRENT_TIMESTAMP
     4. Decrement rooms.current_occupancy
   ─────────────────────────────────────────────────────────────────────────── */
if ($method === 'POST' && $action === 'submit_property_review') {
    $userId    = (int)$_SESSION['user_id'];
    $requestId = (int)($input['request_id'] ?? 0);
    $rating    = max(1, min(5, (int)($input['rating'] ?? 5)));
    $comment   = trim($input['comment'] ?? '');
    $clean     = isset($input['cleanliness'])     ? max(1, min(5, (int)$input['cleanliness']))     : null;
    $safety    = isset($input['safety'])          ? max(1, min(5, (int)$input['safety']))          : null;
    $value     = isset($input['value_for_money']) ? max(1, min(5, (int)$input['value_for_money'])) : null;

    if (!$requestId) jsonResponse(['error' => 'Missing request_id'], 400);

    // Validate request belongs to this tenant and is in the right state
    $stmt = $db->prepare(
        'SELECT mor.*, r.property_id
           FROM move_out_requests mor
           JOIN rooms r ON r.id = mor.room_id
          WHERE mor.id = ? AND mor.tenant_id = ? AND mor.status = "owner_review_done"'
    );
    $stmt->execute([$requestId, $userId]);
    $req = $stmt->fetch();
    if (!$req) jsonResponse(['error' => 'Request not found or owner has not yet reviewed you.'], 404);

    /* ── BEGIN TRANSACTION ─────────────────────────────────────────────────── */
    $db->beginTransaction();
    try {
        // 1. Insert property review
        $db->prepare(
            'INSERT INTO property_reviews
                (move_out_req_id, reviewer_id, property_id, rating, cleanliness, safety, value_for_money, comment, is_public)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)'
        )->execute([$requestId, $userId, $req['property_id'], $rating, $clean, $safety, $value, $comment ?: null]);

        // 2. Mark move-out request completed
        $db->prepare(
            'UPDATE move_out_requests SET status = "completed" WHERE id = ?'
        )->execute([$requestId]);

        // 3. Stamp moved_out_at on the room_tenants row
        $db->prepare(
            'UPDATE room_tenants SET moved_out_at = CURRENT_TIMESTAMP WHERE id = ?'
        )->execute([$req['room_tenant_id']]);

        // 4. Decrement current_occupancy on the room (floor at 0)
        $db->prepare(
            'UPDATE rooms
                SET current_occupancy = GREATEST(0, current_occupancy - 1)
              WHERE id = ?'
        )->execute([$req['room_id']]);

        $db->commit();
        /* ── END TRANSACTION ─────────────────────────────────────────────── */

        jsonResponse(['success' => true, 'message' => 'Move-out completed. Your tenancy has officially ended.']);

    } catch (Throwable $e) {
        $db->rollBack();
        error_log('move_out transaction failed: ' . $e->getMessage());
        jsonResponse(['error' => 'A server error occurred. Please try again.'], 500);
    }
}

jsonResponse(['error' => 'Unknown action or method not allowed.'], 405);
