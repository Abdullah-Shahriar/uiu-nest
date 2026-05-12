<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['owner']);

$db = getDB();
$uid = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$propId = (int)($input['property_id'] ?? 0);
$managerId = (int)($input['manager_id'] ?? 0);

if (!$propId) {
    jsonResponse(['error' => 'Property ID required'], 400);
}

$check = $db->prepare('SELECT id FROM properties WHERE id = ? AND owner_id = ?');
$check->execute([$propId, $uid]);
if (!$check->fetch()) {
    jsonResponse(['error' => 'Property not found or not yours'], 404);
}

if ($managerId === 0) {
    $db->prepare('UPDATE properties SET house_manager_id = NULL WHERE id = ?')->execute([$propId]);
    jsonResponse(['success' => true, 'message' => 'House manager removed']);
}

$managerCheck = $db->prepare("SELECT id, full_name FROM users WHERE id = ? AND role = 'tenant' AND is_active = 1");
$managerCheck->execute([$managerId]);
$manager = $managerCheck->fetch();

if (!$manager) {
    jsonResponse(['error' => 'Selected user is not a valid house manager'], 400);
}

$db->prepare('UPDATE properties SET house_manager_id = ? WHERE id = ?')->execute([$managerId, $propId]);
jsonResponse(['success' => true, 'manager_name' => $manager['full_name']]);
