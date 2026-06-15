<?php
/**
 * UIU Nest — Properties API (CRUD for owners)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// ─── GET: Fetch properties/rooms ───
if ($method === 'GET') {
    requireLogin();

    if (hasRole('owner')) {
        $stmt = $db->prepare('SELECT * FROM properties WHERE owner_id = ? AND is_active = 1 ORDER BY created_at DESC');
        $stmt->execute([$_SESSION['user_id']]);
        $props = $stmt->fetchAll();

        // Attach rooms
        foreach ($props as &$p) {
            $rStmt = $db->prepare('SELECT * FROM rooms WHERE property_id = ? AND is_active = 1');
            $rStmt->execute([$p['id']]);
            $p['rooms'] = $rStmt->fetchAll();
        }
        jsonResponse(['properties' => $props]);
    }

    // Tenant: rooms they live in
    if (hasRole('tenant')) {
        $stmt = $db->prepare(
            'SELECT r.*, p.name AS property_name, p.owner_id
             FROM room_tenants rt
             JOIN rooms r ON r.id = rt.room_id
             JOIN properties p ON p.id = r.property_id
             WHERE rt.user_id = ? AND rt.moved_out_at IS NULL'
        );
        $stmt->execute([$_SESSION['user_id']]);
        jsonResponse(['rooms' => $stmt->fetchAll()]);
    }

    jsonResponse(['properties' => []]);
}

// ─── POST: Create property or room ───
if ($method === 'POST') {
    requireRole(['owner']);
    $rawBody = file_get_contents('php://input');
    $input = $rawBody ? (json_decode($rawBody, true) ?? $_POST) : $_POST;
    if ($rawBody && json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(['error' => 'Invalid JSON body'], 400);
    }
    $type = $input['type'] ?? 'property';

    if ($type === 'property') {
        $stmt = $db->prepare('INSERT INTO properties (owner_id, name, address, location_lat, location_lng, description) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $_SESSION['user_id'],
            trim($input['name']),
            trim($input['address']),
            (float)$input['lat'],
            (float)$input['lng'],
            trim($input['description'] ?? '')
        ]);
        jsonResponse(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
    }

    if ($type === 'room') {
        // Verify ownership
        $stmt = $db->prepare('SELECT id FROM properties WHERE id = ? AND owner_id = ?');
        $stmt->execute([(int)$input['property_id'], $_SESSION['user_id']]);
        if (!$stmt->fetch()) jsonResponse(['error' => 'Not your property'], 403);

        $stmt = $db->prepare('INSERT INTO rooms (property_id, room_number, capacity, rent_amount, amenities_json, description) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            (int)$input['property_id'],
            trim($input['room_number']),
            (int)($input['capacity'] ?? 1),
            (float)$input['rent_amount'],
            json_encode($input['amenities'] ?? []),
            trim($input['description'] ?? '')
        ]);
        jsonResponse(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
    }
}

// ─── PUT: Update property or room ───
if ($method === 'PUT') {
    requireRole(['owner']);
    $rawBody = file_get_contents('php://input');
    $input = $rawBody ? (json_decode($rawBody, true) ?? $_POST) : $_POST;
    if ($rawBody && json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(['error' => 'Invalid JSON body'], 400);
    }
    $type = $input['type'] ?? 'property';

    if ($type === 'property') {
        $stmt = $db->prepare('UPDATE properties SET name=?, address=?, location_lat=?, location_lng=?, description=? WHERE id=? AND owner_id=?');
        $stmt->execute([
            trim($input['name']), trim($input['address']),
            (float)$input['lat'], (float)$input['lng'],
            trim($input['description'] ?? ''),
            (int)$input['id'], $_SESSION['user_id']
        ]);
        jsonResponse(['success' => true]);
    }

    /* Task 3 — Save cover photo position (drag-to-reposition) */
    if (isset($input['cover_photo_position'])) {
        $propId   = (int)($input['id'] ?? 0);
        $ownerId  = (int)$_SESSION['user_id'];
        $position = preg_replace('/[^0-9.% ]/', '', $input['cover_photo_position']);
        if (!$propId || !$position) jsonResponse(['error' => 'Invalid data.'], 400);

        $stmt = $db->prepare(
            'UPDATE properties SET cover_photo_position = ? WHERE id = ? AND owner_id = ?'
        );
        $stmt->execute([$position, $propId, $ownerId]);
        if ($stmt->rowCount() === 0) {
            jsonResponse(['error' => 'Not your property or not found.'], 403);
        }
        jsonResponse(['success' => true]);
    }

    if ($type === 'room') {
        $stmt = $db->prepare(
            'UPDATE rooms r JOIN properties p ON p.id = r.property_id
             SET r.room_number=?, r.capacity=?, r.rent_amount=?, r.amenities_json=?, r.description=?
             WHERE r.id=? AND p.owner_id=?'
        );
        $stmt->execute([
            trim($input['room_number']), (int)$input['capacity'],
            (float)$input['rent_amount'], json_encode($input['amenities'] ?? []),
            trim($input['description'] ?? ''),
            (int)$input['id'], $_SESSION['user_id']
        ]);
        jsonResponse(['success' => true]);
    }
}

// ─── DELETE: Soft delete property or room ───
if ($method === 'DELETE') {
    requireRole(['owner']);
    $rawBody = file_get_contents('php://input');
    $input = $rawBody ? (json_decode($rawBody, true) ?? $_POST) : $_POST;
    if ($rawBody && json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(['error' => 'Invalid JSON body'], 400);
    }
    
    $type = $input['type'] ?? 'property';
    
    if ($type === 'property') {
        $propId = (int)($input['id'] ?? 0);
        $stmt = $db->prepare('UPDATE properties SET is_active = 0 WHERE id = ? AND owner_id = ?');
        $stmt->execute([$propId, $_SESSION['user_id']]);
        jsonResponse(['success' => true]);
    }
    
    if ($type === 'room') {
        $roomId = (int)($input['id'] ?? 0);
        $stmt = $db->prepare(
            'UPDATE rooms r JOIN properties p ON p.id = r.property_id
             SET r.is_active = 0 WHERE r.id = ? AND p.owner_id = ?'
        );
        $stmt->execute([$roomId, $_SESSION['user_id']]);
        jsonResponse(['success' => true]);
    }
}
