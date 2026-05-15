<?php
/**
 * UIU Nest — Property Images API (upload / delete)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['owner']);
$db  = getDB();
$uid = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// ── POST: Upload photos ──────────────────────────────────────────────────────
if ($method === 'POST') {
    $propId = (int)($_POST['property_id'] ?? 0);

    // Verify ownership
    $stmt = $db->prepare('SELECT id FROM properties WHERE id = ? AND owner_id = ?');
    $stmt->execute([$propId, $uid]);
    if (!$stmt->fetch()) {
        jsonResponse(['error' => 'Not your property'], 403);
    }

    if (empty($_FILES['photos']['name'][0])) {
        jsonResponse(['error' => 'No photos uploaded'], 400);
    }

    $dir = PROPERTY_UPLOAD_DIR . '/' . $propId;
    if (!is_dir($dir)) { mkdir($dir, 0755, true); }

    // Current photo count (for cover determination)
    $countStmt = $db->prepare('SELECT COUNT(*) FROM property_images WHERE property_id = ?');
    $countStmt->execute([$propId]);
    $currentCount = (int)$countStmt->fetchColumn();

    $uploaded = 0;
    $errors   = [];
    $allowed  = ['jpg','jpeg','png','webp'];

    $files = $_FILES['photos'];
    $fileCount = is_array($files['name']) ? count($files['name']) : 1;

    for ($i = 0; $i < $fileCount; $i++) {
        $name   = is_array($files['name'])     ? $files['name'][$i]     : $files['name'];
        $tmp    = is_array($files['tmp_name'])  ? $files['tmp_name'][$i] : $files['tmp_name'];
        $size   = is_array($files['size'])      ? $files['size'][$i]     : $files['size'];
        $error  = is_array($files['error'])     ? $files['error'][$i]    : $files['error'];

        if ($error !== UPLOAD_ERR_OK) { $errors[] = "Upload error on file $i"; continue; }
        if ($size > MAX_IMAGE_SIZE)   { $errors[] = "$name too large (max 5MB)"; continue; }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) { $errors[] = "$name: invalid type"; continue; }

        $filename = 'prop_' . $propId . '_' . time() . '_' . $i . '.' . $ext;
        $dest     = $dir . '/' . $filename;

        if (!move_uploaded_file($tmp, $dest)) { $errors[] = "Failed to save $name"; continue; }

        $relativePath = 'uploads/properties/' . $propId . '/' . $filename;
        $isCover      = ($currentCount === 0 && $uploaded === 0) ? 1 : 0;

        $ins = $db->prepare('INSERT INTO property_images (property_id, image_path, is_cover, sort_order) VALUES (?, ?, ?, ?)');
        $ins->execute([$propId, $relativePath, $isCover, $currentCount + $uploaded]);

        // Sync cover photo to properties.image_path
        if ($isCover) {
            $db->prepare('UPDATE properties SET image_path = ? WHERE id = ?')->execute([$relativePath, $propId]);
        }
        $uploaded++;
    }

    if ($uploaded > 0) {
        jsonResponse(['success' => true, 'uploaded' => $uploaded, 'errors' => $errors]);
    } else {
        jsonResponse(['error' => 'No photos saved. ' . implode(', ', $errors)], 400);
    }
}

// ── DELETE: Remove a photo ───────────────────────────────────────────────────
if ($method === 'DELETE') {
    $input  = json_decode(file_get_contents('php://input'), true);
    $imgId  = (int)($input['image_id'] ?? 0);

    // Verify ownership via join
    $stmt = $db->prepare(
        'SELECT pi.image_path, pi.is_cover, pi.property_id
         FROM property_images pi
         JOIN properties p ON p.id = pi.property_id
         WHERE pi.id = ? AND p.owner_id = ?'
    );
    $stmt->execute([$imgId, $uid]);
    $img = $stmt->fetch();
    if (!$img) { jsonResponse(['error' => 'Image not found'], 404); }

    // Delete file
    $fullPath = APP_ROOT . '/' . $img['image_path'];
    if (file_exists($fullPath)) { unlink($fullPath); }

    $db->prepare('DELETE FROM property_images WHERE id = ?')->execute([$imgId]);

    // If deleted image was cover, assign cover to next image
    if ($img['is_cover']) {
        $next = $db->prepare('SELECT id, image_path FROM property_images WHERE property_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1');
        $next->execute([$img['property_id']]);
        $nextImg = $next->fetch();
        if ($nextImg) {
            $db->prepare('UPDATE property_images SET is_cover = 1 WHERE id = ?')->execute([$nextImg['id']]);
            // Sync new cover to properties.image_path
            $db->prepare('UPDATE properties SET image_path = ? WHERE id = ?')->execute([$nextImg['image_path'], $img['property_id']]);
        } else {
            // No images left — clear properties.image_path
            $db->prepare('UPDATE properties SET image_path = NULL WHERE id = ?')->execute([$img['property_id']]);
        }
    }

    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
