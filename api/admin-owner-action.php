<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$db = getDB();
$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
$action = $input['action'] ?? '';

if (!$id || !$action) {
    jsonResponse(['error' => 'Invalid data'], 400);
}

$stmt = $db->prepare("SELECT * FROM owner_applications WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) {
    jsonResponse(['error' => 'Application not found'], 404);
}

if ($action === 'approve') {
    $db->beginTransaction();
    try {
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$app['email']]);
        if ($check->fetch()) {
            jsonResponse(['error' => 'User already exists with this email'], 400);
        }

        $tempPass = substr(md5(time()), 0, 8);
        $hash = password_hash($tempPass, PASSWORD_BCRYPT, ['cost' => 12]);

        $ins = $db->prepare("INSERT INTO users (full_name, email, password_hash, role, email_verified_at, phone) VALUES (?, ?, ?, 'owner', NOW(), ?)");
        $ins->execute([$app['full_name'], $app['email'], $hash, $app['phone']]);

        $db->prepare("UPDATE owner_applications SET status = 'approved' WHERE id = ?")->execute([$id]);

        $db->commit();
        jsonResponse(['success' => true, 'message' => 'Owner approved and account created. Temp password: ' . $tempPass]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Error: ' . $e->getMessage()], 500);
    }
} else if ($action === 'reject') {
    $db->prepare("UPDATE owner_applications SET status = 'rejected' WHERE id = ?")->execute([$id]);
    jsonResponse(['success' => true]);
} else {
    jsonResponse(['error' => 'Unknown action'], 400);
}
