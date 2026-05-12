<?php
/**
 * UIU Nest — Admin API (admin-only management)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['admin']);

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// ═══════════════════════════════════════════
// USER MANAGEMENT
// ═══════════════════════════════════════════

if ($action === 'create_owner' && $method === 'POST') {
    $name = trim($input['full_name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $phone = trim($input['phone'] ?? '');
    $pass = $input['password'] ?? '';

    if (empty($name) || empty($email) || empty($pass)) {
        jsonResponse(['error' => 'Name, email and password are required'], 400);
    }
    if (strlen($pass) < 6) {
        jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
    }

    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Email already registered'], 400);
    }

    $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, phone, email_verified_at)
         VALUES (?, ?, ?, "owner", ?, NOW())'
    );
    $stmt->execute([$name, $email, $hash, $phone ?: null]);
    jsonResponse(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
}

if ($action === 'change_role' && $method === 'POST') {
    $userId = (int)($input['user_id'] ?? 0);
    $role = $input['role'] ?? '';
    if (!in_array($role, ['student','tenant','owner','admin'])) {
        jsonResponse(['error' => 'Invalid role'], 400);
    }
    if ($userId == $_SESSION['user_id']) {
        jsonResponse(['error' => 'Cannot change your own role'], 400);
    }
    $db->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $userId]);
    jsonResponse(['success' => true]);
}

if ($action === 'toggle_user' && $method === 'POST') {
    $userId = (int)($input['user_id'] ?? 0);
    $active = (int)($input['is_active'] ?? 0);
    if ($userId == $_SESSION['user_id']) {
        jsonResponse(['error' => 'Cannot disable yourself'], 400);
    }
    $db->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$active, $userId]);
    jsonResponse(['success' => true]);
}

// ═══════════════════════════════════════════
// LISTING MANAGEMENT
// ═══════════════════════════════════════════

if ($action === 'manage_listing' && $method === 'POST') {
    $listingId = (int)($input['listing_id'] ?? 0);
    $act = $input['action'] ?? '';

    if ($act === 'close') {
        $db->prepare("UPDATE listings SET status = 'closed' WHERE id = ?")->execute([$listingId]);
    } elseif ($act === 'delete') {
        $db->prepare('UPDATE listings SET deleted_at = NOW() WHERE id = ?')->execute([$listingId]);
    } else {
        jsonResponse(['error' => 'Invalid action'], 400);
    }
    jsonResponse(['success' => true]);
}

// ═══════════════════════════════════════════
// PROPERTY MANAGEMENT
// ═══════════════════════════════════════════

if ($action === 'toggle_property' && $method === 'POST') {
    $propId = (int)($input['property_id'] ?? 0);
    $active = (int)($input['is_active'] ?? 0);
    $db->prepare('UPDATE properties SET is_active = ? WHERE id = ?')->execute([$active, $propId]);
    jsonResponse(['success' => true]);
}

// ═══════════════════════════════════════════
// AMENITY MANAGEMENT
// ═══════════════════════════════════════════

if ($action === 'create_amenity' && $method === 'POST') {
    $slug = trim($input['slug'] ?? '');
    $label = trim($input['label'] ?? '');
    $icon = trim($input['icon'] ?? '✨');

    if (!$slug || !$label) {
        jsonResponse(['error' => 'Slug and label are required'], 400);
    }

    // Check duplicate slug
    $stmt = $db->prepare('SELECT id FROM amenities WHERE slug = ?');
    $stmt->execute([$slug]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Slug already exists'], 400);
    }

    // Get next sort order
    $maxOrder = (int)$db->query('SELECT COALESCE(MAX(sort_order),0) FROM amenities')->fetchColumn();

    $stmt = $db->prepare('INSERT INTO amenities (slug, label, icon, sort_order) VALUES (?, ?, ?, ?)');
    $stmt->execute([$slug, $label, $icon, $maxOrder + 1]);
    jsonResponse(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
}

if ($action === 'toggle_amenity' && $method === 'POST') {
    $amenityId = (int)($input['amenity_id'] ?? 0);
    $active = (int)($input['is_active'] ?? 0);
    $db->prepare('UPDATE amenities SET is_active = ? WHERE id = ?')->execute([$active, $amenityId]);
    jsonResponse(['success' => true]);
}

if ($action === 'delete_amenity' && $method === 'POST') {
    $amenityId = (int)($input['amenity_id'] ?? 0);
    $db->prepare('DELETE FROM amenities WHERE id = ?')->execute([$amenityId]);
    jsonResponse(['success' => true]);
}

// ═══════════════════════════════════════════
// DOMAIN MANAGEMENT
// ═══════════════════════════════════════════

if ($action === 'add_domain' && $method === 'POST') {
    $domain = strtolower(trim($input['domain'] ?? ''));
    if (!$domain) jsonResponse(['error' => 'Domain is required'], 400);
    $stmt = $db->prepare('INSERT IGNORE INTO allowed_domains (domain, added_by) VALUES (?, ?)');
    $stmt->execute([$domain, $_SESSION['user_id']]);
    jsonResponse(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
}

if ($action === 'remove_domain' && $method === 'POST') {
    $id = (int)($input['domain_id'] ?? 0);
    $db->prepare('DELETE FROM allowed_domains WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Invalid action'], 400);
