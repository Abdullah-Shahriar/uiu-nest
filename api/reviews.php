<?php
/**
 * UIU Nest — Resident Reviews API
 * POST  : former resident submits a review for a property
 * GET   : anyone can read reviews for a property
 * DELETE: admin or reviewer can remove
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

if ($method === 'GET') {
    $propertyId = (int)($_GET['property_id'] ?? 0);
    if (!$propertyId) jsonResponse(['error' => 'property_id required'], 400);

    $stmt = $db->prepare(
        'SELECT rr.id, rr.rating, rr.comment, rr.created_at,
                u.full_name, u.department, u.year_of_study
         FROM resident_reviews rr
         JOIN users u ON u.id = rr.reviewer_id
         WHERE rr.property_id = ? AND rr.is_visible = 1
         ORDER BY rr.created_at DESC'
    );
    $stmt->execute([$propertyId]);
    $reviews = $stmt->fetchAll();

    $avgStmt = $db->prepare(
        'SELECT ROUND(AVG(rating), 1) AS avg, COUNT(*) AS total
         FROM resident_reviews WHERE property_id = ? AND is_visible = 1'
    );
    $avgStmt->execute([$propertyId]);
    $meta = $avgStmt->fetch();

    jsonResponse(['reviews' => $reviews, 'avg_rating' => $meta['avg'], 'total' => $meta['total']]);
}

if ($method === 'POST') {
    requireLogin();
    $input      = json_decode(file_get_contents('php://input'), true) ?: [];
    $propertyId = (int)($input['property_id'] ?? 0);
    $rating     = max(1, min(5, (int)($input['rating'] ?? 3)));
    $comment    = trim($input['comment'] ?? '');
    $userId     = (int)$_SESSION['user_id'];

    if (!$propertyId) jsonResponse(['error' => 'property_id required'], 400);

    // Verify user has previously lived there (moved_out_at is set)
    $check = $db->prepare(
        'SELECT rt.id FROM room_tenants rt
         JOIN rooms r ON r.id = rt.room_id
         WHERE r.property_id = ? AND rt.user_id = ? AND rt.moved_out_at IS NOT NULL
         LIMIT 1'
    );
    $check->execute([$propertyId, $userId]);
    if (!$check->fetch()) {
        jsonResponse(['error' => 'You must have previously resided here to leave a review.'], 403);
    }

    $stmt = $db->prepare(
        'INSERT INTO resident_reviews (property_id, reviewer_id, rating, comment)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)'
    );
    $stmt->execute([$propertyId, $userId, $rating, $comment ?: null]);
    jsonResponse(['success' => true], 201);
}

if ($method === 'DELETE') {
    requireLogin();
    $input  = json_decode(file_get_contents('php://input'), true) ?: [];
    $id     = (int)($input['id'] ?? 0);
    $userId = (int)$_SESSION['user_id'];
    $role   = $_SESSION['role'];
    if (!$id) jsonResponse(['error' => 'Missing id'], 400);

    if ($role === 'admin') {
        $db->prepare('DELETE FROM resident_reviews WHERE id = ?')->execute([$id]);
    } else {
        $db->prepare('DELETE FROM resident_reviews WHERE id = ? AND reviewer_id = ?')
           ->execute([$id, $userId]);
    }
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
