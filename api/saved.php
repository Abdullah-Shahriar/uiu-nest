<?php
/**
 * UIU Nest — Saved Listings API (toggle/fetch favorites)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// ─── GET: Fetch saved listings ───
if ($method === 'GET') {
    requireLogin();
    $stmt = $db->prepare(
        'SELECT l.*, r.rent_amount, r.capacity, r.amenities_json,
                p.name AS property_name, p.location_lat, p.location_lng,
                u.full_name AS created_by_name, 1 AS is_saved
         FROM saved_listings sl
         JOIN listings l ON l.id = sl.listing_id
         JOIN rooms r ON r.id = l.room_id
         JOIN properties p ON p.id = r.property_id
         JOIN users u ON u.id = l.created_by
         WHERE sl.user_id = ? AND l.deleted_at IS NULL
         ORDER BY sl.created_at DESC'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $listings = $stmt->fetchAll();

    foreach ($listings as &$l) {
        $l['distance_km'] = calculateDistance(UIU_LAT, UIU_LNG, (float)$l['location_lat'], (float)$l['location_lng']);
        $l['is_saved'] = true;
    }

    jsonResponse(['listings' => $listings]);
}

// ─── POST: Toggle save/unsave ───
if ($method === 'POST') {
    requireLogin();
    $input = json_decode(file_get_contents('php://input'), true);
    $listingId = (int)($input['listing_id'] ?? 0);
    $userId = $_SESSION['user_id'];

    if (!$listingId) jsonResponse(['error' => 'Listing ID required'], 400);

    // Check if already saved
    $stmt = $db->prepare('SELECT id FROM saved_listings WHERE user_id = ? AND listing_id = ?');
    $stmt->execute([$userId, $listingId]);

    if ($stmt->fetch()) {
        $db->prepare('DELETE FROM saved_listings WHERE user_id = ? AND listing_id = ?')
           ->execute([$userId, $listingId]);
        jsonResponse(['saved' => false]);
    } else {
        $db->prepare('INSERT INTO saved_listings (user_id, listing_id) VALUES (?, ?)')
           ->execute([$userId, $listingId]);
        jsonResponse(['saved' => true]);
    }
}
