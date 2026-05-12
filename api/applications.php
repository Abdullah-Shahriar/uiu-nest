<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

if ($method === 'GET') {
    requireLogin();
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    if (!empty($_GET['include']) && $_GET['include'] === 'applicant_profile' && !empty($_GET['app_id'])) {
        $stmt = $db->prepare(
            'SELECT a.*, u.full_name, u.email, u.phone, u.student_id, u.department,
                    u.year_of_study, u.gender, u.bio,
                    l.title AS listing_title, l.listing_type, l.status AS listing_status,
                    r.room_number, p.name AS property_name
             FROM applications a
             JOIN users u ON u.id = a.applicant_id
             JOIN listings l ON l.id = a.listing_id
             JOIN rooms r ON r.id = l.room_id
             JOIN properties p ON p.id = r.property_id
             WHERE a.id = ?'
        );
        $stmt->execute([(int)$_GET['app_id']]);
        $app = $stmt->fetch();
        if (!$app) {
            jsonResponse(['error' => 'Not found'], 404);
        }

        $docStmt = $db->prepare('SELECT id_card_front, id_card_back, selfie_path, video_path FROM application_documents WHERE application_id = ? LIMIT 1');
        $docStmt->execute([(int)$_GET['app_id']]);
        $app['documents'] = $docStmt->fetch();
        if (!$app['documents']) {
            $app['documents'] = null;
        }

        jsonResponse(['application' => $app]);
    }

    if (hasAnyRole(['student', 'tenant'])) {
        $stmt = $db->prepare(
            'SELECT a.*, l.title, l.listing_type, r.rent_amount, p.name AS property_name
             FROM applications a
             JOIN listings l ON l.id = a.listing_id
             JOIN rooms r ON r.id = l.room_id
             JOIN properties p ON p.id = r.property_id
             WHERE a.applicant_id = ? AND a.deleted_at IS NULL
             ORDER BY a.applied_at DESC'
        );
        $stmt->execute([$userId]);
        jsonResponse(['applications' => $stmt->fetchAll()]);
    }

    if (hasAnyRole(['owner', 'tenant', 'admin'])) {
        $listingId = (int)($_GET['listing_id'] ?? 0);
        if ($listingId) {
            $stmt = $db->prepare(
                'SELECT a.*, u.full_name, u.email, u.student_id, u.department
                 FROM applications a
                 JOIN users u ON u.id = a.applicant_id
                 WHERE a.listing_id = ? AND a.deleted_at IS NULL
                 ORDER BY a.applied_at DESC'
            );
            $stmt->execute([$listingId]);
            jsonResponse(['applications' => $stmt->fetchAll()]);
        }
    }

    jsonResponse(['applications' => []]);
}

if ($method === 'POST') {
    requireLogin();
    $listingId = (int)($_POST['listing_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $userId = $_SESSION['user_id'];

    $check = canUserApply($userId, $listingId);
    if (!$check['can']) {
        jsonResponse(['error' => $check['reason']], 400);
    }

    $uploadDir = DOC_UPLOAD_DIR . '/' . $userId;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fields = ['id_card_front', 'id_card_back', 'selfie', 'video'];
    $paths = [];

    foreach ($fields as $field) {
        if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['error' => 'Missing file: ' . $field], 400);
        }
        $file = $_FILES[$field];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($field === 'video') {
            $allowed = ['mp4', 'webm', 'mov'];
        } else {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        }

        if (!in_array($ext, $allowed)) {
            jsonResponse(['error' => 'Invalid file type for ' . $field], 400);
        }

        if ($field === 'video') {
            $maxSize = MAX_VIDEO_SIZE;
        } else {
            $maxSize = MAX_IMAGE_SIZE;
        }

        if ($file['size'] > $maxSize) {
            jsonResponse(['error' => $field . ' file too large'], 400);
        }

        $filename = $field . '_' . time() . '.' . $ext;
        $dest = $uploadDir . '/' . $filename;
        move_uploaded_file($file['tmp_name'], $dest);
        $paths[$field] = 'uploads/documents/' . $userId . '/' . $filename;
    }

    $stmt = $db->prepare('SELECT listing_type FROM listings WHERE id = ?');
    $stmt->execute([$listingId]);
    $listing = $stmt->fetch();

    if ($listing['listing_type'] === 'roommate_needed') {
        $initialStatus = 'pending_tenant_review';
    } else {
        $initialStatus = 'pending_owner_review';
    }

    $stmt = $db->prepare('INSERT INTO applications (listing_id, applicant_id, status, cover_message, applied_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$listingId, $userId, $initialStatus, $message ?: null]);
    $applicationId = (int)$db->lastInsertId();

    $stmt = $db->prepare(
        'INSERT INTO application_documents (application_id, applicant_id, id_card_front, id_card_back, selfie_path, video_path)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$applicationId, $userId, $paths['id_card_front'], $paths['id_card_back'], $paths['selfie'], $paths['video']]);

    jsonResponse(['success' => true, 'status' => $initialStatus], 201);
}

if ($method === 'PUT') {
    requireLogin();
    $input = json_decode(file_get_contents('php://input'), true);
    $appId = (int)($input['application_id'] ?? 0);
    $newStatus = $input['status'] ?? '';
    $role = $_SESSION['role'];
    $userId = (int)$_SESSION['user_id'];

    $stmt = $db->prepare(
        'SELECT a.*, l.listing_type, l.room_id, l.created_by,
                r.property_id, r.current_occupancy, r.capacity,
                p.house_manager_id
         FROM applications a
         JOIN listings l ON l.id = a.listing_id
         JOIN rooms r ON r.id = l.room_id
         JOIN properties p ON p.id = r.property_id
         WHERE a.id = ? AND a.deleted_at IS NULL'
    );
    $stmt->execute([$appId]);
    $app = $stmt->fetch();

    if (!$app) {
        jsonResponse(['error' => 'Application not found'], 404);
    }

    $isListingOwner = ($app['created_by'] == $userId);
    $isHouseManager = ($app['house_manager_id'] == $userId);
    $isAdmin = ($role === 'admin');

    if (!$isListingOwner && !$isHouseManager && !$isAdmin) {
        jsonResponse(['error' => 'You do not have permission to update this application'], 403);
    }

    if (!canTransitionAppStatus($app['status'], $newStatus, $role, $app['listing_type'])) {
        jsonResponse(['error' => 'Invalid status transition'], 400);
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare('UPDATE applications SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $appId]);

        if ($newStatus === 'enrolled') {
            $stmt = $db->prepare('INSERT INTO room_tenants (room_id, user_id) VALUES (?, ?)');
            $stmt->execute([$app['room_id'], $app['applicant_id']]);

            $stmt = $db->prepare('UPDATE rooms SET current_occupancy = current_occupancy + 1 WHERE id = ?');
            $stmt->execute([$app['room_id']]);

            $stmt = $db->prepare("UPDATE users SET role = 'tenant' WHERE id = ? AND role = 'student'");
            $stmt->execute([$app['applicant_id']]);

            $newOccupancy = $app['current_occupancy'] + 1;
            if ($newOccupancy >= $app['capacity']) {
                $stmt = $db->prepare("UPDATE listings SET status = 'closed' WHERE room_id = ? AND status = 'published' AND deleted_at IS NULL");
                $stmt->execute([$app['room_id']]);
            }
        }

        $db->commit();
        jsonResponse(['success' => true, 'status' => $newStatus]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Failed to update application'], 500);
    }
}

if ($method === 'DELETE') {
    requireLogin();
    $input = json_decode(file_get_contents('php://input'), true);
    $appId = (int)($input['application_id'] ?? 0);

    $stmt = $db->prepare('UPDATE applications SET deleted_at = NOW(), status = "withdrawn" WHERE id = ? AND applicant_id = ? AND deleted_at IS NULL');
    $stmt->execute([$appId, $_SESSION['user_id']]);

    jsonResponse(['success' => true]);
}
