<?php
/**
 * UIU Nest v2 — Complaints API
 * POST  : submit complaint (logged-in, non-admin) — optional anonymous flag
 * GET   : admin only — list all complaints
 * PUT   : admin only — update status / add note
 * DELETE: admin only — delete complaint
 *
 * Routing: ALL complaints go exclusively to Admin.
 * Admins CANNOT submit complaints (blocked server-side).
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

/* ─── POST: Submit complaint (any non-admin logged-in user) ─── */
if ($method === 'POST') {
    // Admins cannot submit complaints
    if (isLoggedIn() && hasRole('admin')) {
        jsonResponse(['error' => 'Admins do not submit complaints.'], 403);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $category    = $input['category']     ?? 'other';
    $subject     = trim($input['subject']     ?? '');
    $description = trim($input['description'] ?? '');
    $isAnonymous = !empty($input['is_anonymous']) ? 1 : 0;
    $propertyId  = !empty($input['property_id']) ? (int)$input['property_id'] : null;

    $allowed = ['maintenance','noise','safety','management','other'];
    if (!in_array($category, $allowed)) $category = 'other';

    if (!$subject || !$description) {
        jsonResponse(['error' => 'Subject and description are required.'], 400);
    }
    if (strlen($subject) > 255) {
        jsonResponse(['error' => 'Subject too long.'], 400);
    }

    // Store submitter_id only if NOT anonymous AND logged in
    $submitterId = ($isAnonymous || !isLoggedIn()) ? null : (int)$_SESSION['user_id'];

    $stmt = $db->prepare(
        'INSERT INTO complaints (category, property_id, subject, description, submitter_id, is_anonymous, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, "open", NOW())'
    );
    $stmt->execute([$category, $propertyId, $subject, $description, $submitterId, $isAnonymous]);

    jsonResponse(['success' => true], 201);
}

/* ─── GET: Read complaints ─── */
if ($method === 'GET') {
    $isAdmin = isLoggedIn() && hasRole('admin');
    $status = $_GET['status'] ?? '';

    $status = $_GET['status'] ?? '';
    $where  = '';
    $params = [];
    if ($status) {
        $where  = 'WHERE c.status = ?';
        $params = [$status];
    }

    // If not admin, fetch all complaints EXCEPT dismissed ones
    if (!$isAdmin) {
        $where = "WHERE c.status != 'dismissed'";
        $params = [];
    }

    $stmt = $db->prepare(
        "SELECT c.*,
                p.name AS property_name,
                CASE WHEN c.is_anonymous = 1 THEN NULL ELSE u.full_name END AS submitter_name,
                CASE WHEN c.is_anonymous = 1 THEN NULL ELSE u.email END AS submitter_email,
                CASE WHEN c.is_anonymous = 1 THEN NULL ELSE u.avatar_path END AS submitter_avatar
         FROM complaints c
         LEFT JOIN properties p ON p.id = c.property_id
         LEFT JOIN users u ON u.id = c.submitter_id
         $where
         ORDER BY c.created_at DESC"
    );
    $stmt->execute($params);
    jsonResponse(['complaints' => $stmt->fetchAll()]);
}

/* ─── PUT: Admin updates status or adds note ─── */
if ($method === 'PUT') {
    requireLogin();
    requireRole(['admin']);

    $input      = json_decode(file_get_contents('php://input'), true) ?: [];
    $id         = (int)($input['id'] ?? 0);
    $newStatus  = $input['status']     ?? null;
    $adminNote  = $input['admin_note'] ?? null;

    if (!$id) jsonResponse(['error' => 'Missing complaint id'], 400);

    $validStatuses = ['open','under_review','resolved','dismissed'];
    if ($newStatus && !in_array($newStatus, $validStatuses)) {
        jsonResponse(['error' => 'Invalid status'], 400);
    }

    $stmt = $db->prepare(
        "UPDATE complaints SET
            status = COALESCE(?, status),
            admin_note = COALESCE(?, admin_note),
            resolved_at = IF(? = 'resolved', NOW(), resolved_at)
         WHERE id = ?"
    );
    $stmt->execute([$newStatus, $adminNote, $newStatus, $id]);
    jsonResponse(['success' => true]);
}

/* ─── DELETE: Admin deletes complaint ─── */
if ($method === 'DELETE') {
    requireLogin();
    requireRole(['admin']);

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id    = (int)($input['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'Missing id'], 400);

    $db->prepare('DELETE FROM complaints WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
