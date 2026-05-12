<?php
/**
 * UIU Nest — Profile API (update personal info + avatar upload)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$db  = getDB();
$uid = (int)$_SESSION['user_id'];

// ── Avatar Upload ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'avatar') {
    if (empty($_FILES['avatar'])) {
        jsonResponse(['error' => 'No file uploaded'], 400);
    }
    $file  = $_FILES['avatar'];
    $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];

    if (!in_array($ext, $allowed)) {
        jsonResponse(['error' => 'Invalid file type. Use JPG, PNG or WebP.'], 400);
    }
    if ($file['size'] > MAX_IMAGE_SIZE) {
        jsonResponse(['error' => 'File too large. Max 5MB.'], 400);
    }

    $dir      = UPLOAD_DIR . '/avatars';
    if (!is_dir($dir)) { mkdir($dir, 0755, true); }
    $filename = 'avatar_' . $uid . '_' . time() . '.' . $ext;
    $dest     = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonResponse(['error' => 'Upload failed'], 500);
    }

    $relativePath = 'uploads/avatars/' . $filename;
    $stmt = $db->prepare('UPDATE users SET avatar_path = ? WHERE id = ?');
    $stmt->execute([$relativePath, $uid]);

    jsonResponse(['success' => true, 'avatar_url' => APP_URL . '/' . $relativePath]);
}

// ── Profile Update ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $name  = trim($input['full_name']     ?? '');
    $phone = trim($input['phone']         ?? '');
    $bio   = trim($input['bio']           ?? '');
    $pass  = trim($input['password']      ?? '');
    $dept  = trim($input['department']    ?? '');
    $year  = (int)($input['year_of_study'] ?? 0) ?: null;
    $gender = in_array($input['gender'] ?? '', ['male','female','other','']) ? ($input['gender'] ?: null) : null;

    if (empty($name)) {
        jsonResponse(['error' => 'Name is required.'], 400);
    }

    // Build update query dynamically
    $sets = ['full_name = ?', 'phone = ?', 'bio = ?'];
    $params = [$name, $phone ?: null, $bio ?: null];

    if ($dept) { $sets[] = 'department = ?'; $params[] = $dept; }
    if ($year) { $sets[] = 'year_of_study = ?'; $params[] = $year; }
    if ($gender) { $sets[] = 'gender = ?'; $params[] = $gender; }

    if (!empty($pass)) {
        if (strlen($pass) < 6) {
            jsonResponse(['error' => 'Password must be at least 6 characters.'], 400);
        }
        $sets[]   = 'password_hash = ?';
        $params[] = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    $params[] = $uid;
    $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?';
    $db->prepare($sql)->execute($params);

    // Update session name
    $_SESSION['full_name'] = $name;

    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
