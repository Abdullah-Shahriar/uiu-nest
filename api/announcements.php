<?php
/**
 * UIU Nest — Announcements / Calendar API
 * POST  : owner creates an event for their property
 * GET   : fetch events (owner sees own; tenant sees property they live in)
 * DELETE: owner deletes own event
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

/* ─── POST: Create announcement ─── */
if ($method === 'POST') {
    requireLogin();
    requireRole(['owner', 'admin']);

    $input       = json_decode(file_get_contents('php://input'), true) ?: [];
    $propertyId  = (int)($input['property_id'] ?? 0);
    $eventDate   = $input['event_date']  ?? '';
    $type        = $input['type']        ?? 'announcement';
    $title       = trim($input['title'] ?? '');
    $description = trim($input['description'] ?? '');
    $userId      = (int)$_SESSION['user_id'];

    if (!$propertyId || !$eventDate || !$title) {
        jsonResponse(['error' => 'property_id, event_date, and title are required.'], 400);
    }

    // Verify ownership
    $check = $db->prepare('SELECT id FROM properties WHERE id = ? AND owner_id = ?');
    $check->execute([$propertyId, $userId]);
    if (!$check->fetch() && $_SESSION['role'] !== 'admin') {
        jsonResponse(['error' => 'Not your property'], 403);
    }

    $valid = ['rent_due','announcement','maintenance','other'];
    if (!in_array($type, $valid)) $type = 'announcement';

    $stmt = $db->prepare(
        'INSERT INTO announcements (property_id, created_by, event_date, type, title, description)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$propertyId, $userId, $eventDate, $type, $title, $description]);
    jsonResponse(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
}

/* ─── GET: Fetch announcements ─── */
if ($method === 'GET') {
    requireLogin();
    $userId = (int)$_SESSION['user_id'];
    $role   = $_SESSION['role'];

    $propertyId = (int)($_GET['property_id'] ?? 0);

    if ($propertyId) {
        $stmt = $db->prepare(
            'SELECT a.*, p.name AS property_name, u.full_name AS owner_name
             FROM announcements a
             JOIN properties p ON p.id = a.property_id
             LEFT JOIN users u ON p.owner_id = u.id
             WHERE a.property_id = ?
             ORDER BY a.event_date ASC'
        );
        $stmt->execute([$propertyId]);
        jsonResponse(['announcements' => $stmt->fetchAll()]);
    } else {
        // Broadcast to everyone
        $stmt = $db->query(
            'SELECT a.*, p.name AS property_name, u.full_name AS owner_name
             FROM announcements a
             JOIN properties p ON p.id = a.property_id
             LEFT JOIN users u ON p.owner_id = u.id
             ORDER BY a.event_date ASC'
        );
        jsonResponse(['announcements' => $stmt->fetchAll()]);
    }

    jsonResponse(['announcements' => []]);
}

/* ─── DELETE: Owner removes own event ─── */
if ($method === 'DELETE') {
    requireLogin();
    $input  = json_decode(file_get_contents('php://input'), true) ?: [];
    $id     = (int)($input['id'] ?? 0);
    $userId = (int)$_SESSION['user_id'];
    $role   = $_SESSION['role'];

    if (!$id) jsonResponse(['error' => 'Missing id'], 400);

    if ($role === 'admin') {
        $db->prepare('DELETE FROM announcements WHERE id = ?')->execute([$id]);
    } else {
        $db->prepare('DELETE FROM announcements WHERE id = ? AND created_by = ?')
           ->execute([$id, $userId]);
    }
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
