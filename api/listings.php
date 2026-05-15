<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $db = getDB();
        $where = ["l.deleted_at IS NULL", "l.status = 'published'"];
        $params = [];

        if (!empty($_GET['rent_min']) && is_numeric($_GET['rent_min'])) {
            $where[] = 'r.rent_amount >= ?';
            $params[] = (float) $_GET['rent_min'];
        }
        if (!empty($_GET['rent_max']) && is_numeric($_GET['rent_max'])) {
            $where[] = 'r.rent_amount <= ?';
            $params[] = (float) $_GET['rent_max'];
        }

        // Global search by property name or address
        if (!empty($_GET['q'])) {
            $q = '%' . trim($_GET['q']) . '%';
            $where[] = '(p.name LIKE ? OR p.address LIKE ? OR l.title LIKE ?)';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        if (!empty($_GET['amenities'])) {
            $rawAmenities = explode(',', $_GET['amenities']);
            foreach ($rawAmenities as $a) {
                $a = trim($a);
                if ($a !== '') {
                    $where[] = "JSON_CONTAINS(IFNULL(r.amenities_json, '[]'), JSON_QUOTE(?))";
                    $params[] = $a;
                }
            }
        }

        $whereSQL = implode(' AND ', $where);

        $sortMap = [
            'rent_asc'     => 'r.rent_amount ASC',
            'rent_desc'    => 'r.rent_amount DESC',
            'date_desc'    => 'l.published_at DESC',
            'date_asc'     => 'l.published_at ASC',
            'distance_asc' => 'l.published_at DESC',
        ];

        $sortKey = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
        if (isset($sortMap[$sortKey])) {
            $sort = $sortMap[$sortKey];
        } else {
            $sort = 'l.published_at DESC';
        }

        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

        $sql = "SELECT l.id, l.title, l.listing_type, l.status, l.published_at,
                       r.rent_amount, r.capacity, r.current_occupancy, r.amenities_json,
                       p.name AS property_name, p.location_lat, p.location_lng, p.address, p.image_path,
                       u.full_name AS created_by_name, u.avatar_path AS creator_avatar,
                       IF(sl.id IS NOT NULL, 1, 0) AS is_saved,
                       (SELECT pi.image_path FROM property_images pi
                        WHERE pi.property_id = p.id AND pi.is_cover = 1
                        ORDER BY pi.id ASC LIMIT 1) AS cover_photo
                FROM listings l
                JOIN rooms r ON r.id = l.room_id AND r.is_active = 1
                JOIN properties p ON p.id = r.property_id AND p.is_active = 1
                JOIN users u ON u.id = l.created_by
                LEFT JOIN saved_listings sl ON sl.listing_id = l.id AND sl.user_id = ?
                WHERE {$whereSQL}
                ORDER BY {$sort}
                LIMIT 120";

        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge([$userId], $params));
        $listings = $stmt->fetchAll();

        foreach ($listings as &$l) {
            $lat = (float)$l['location_lat'];
            $lng = (float)$l['location_lng'];
            $l['distance_km'] = round(calculateDistance(UIU_LAT, UIU_LNG, $lat, $lng), 2);
            if ($l['is_saved']) {
                $l['is_saved'] = true;
            } else {
                $l['is_saved'] = false;
            }
        }
        unset($l);

        if ($sortKey === 'distance_asc') {
            usort($listings, function($a, $b) {
                if ($a['distance_km'] < $b['distance_km']) return -1;
                if ($a['distance_km'] > $b['distance_km']) return 1;
                return 0;
            });
        }

        jsonResponse(['listings' => $listings]);

    } catch (Exception $ex) {
        jsonResponse(['listings' => [], 'debug' => $ex->getMessage()], 200);
    }
}

if ($method === 'POST') {
    requireLogin();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    $db = getDB();
    $role = $_SESSION['role'];

    $roomId = (int)($input['room_id'] ?? 0);
    $title  = trim($input['title'] ?? '');
    $desc   = trim($input['description'] ?? '');

    if (!$roomId || !$title) {
        jsonResponse(['error' => 'Room and title are required'], 400);
    }

    if ($role === 'owner') {
        $listingType = 'owner_direct';
        $status = 'published';
        $stmt = $db->prepare('SELECT r.id FROM rooms r JOIN properties p ON p.id = r.property_id WHERE r.id = ? AND p.owner_id = ?');
        $stmt->execute([$roomId, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => 'Not your property'], 403);
        }
    } elseif ($role === 'tenant') {
        $listingType = 'roommate_needed';
        $status = 'pending_owner_approval';
        $stmt = $db->prepare('SELECT id FROM room_tenants WHERE room_id = ? AND user_id = ? AND moved_out_at IS NULL');
        $stmt->execute([$roomId, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => 'You are not a tenant of this room'], 403);
        }
    } else {
        jsonResponse(['error' => 'Only owners and tenants can create listings'], 403);
    }

    $publishedAt = null;
    if ($status === 'published') {
        $publishedAt = date('Y-m-d H:i:s');
    }

    $stmt = $db->prepare('INSERT INTO listings (room_id, created_by, listing_type, title, description, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$roomId, $_SESSION['user_id'], $listingType, $title, $desc, $status, $publishedAt]);
    $listingId = (int) $db->lastInsertId();

    $req = $input['requirements'] ?? null;
    if ($req) {
        $stmt = $db->prepare('INSERT INTO listing_requirements (listing_id, preferred_gender, preferred_dept, min_year, max_year, smoking_allowed, is_mandatory) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $listingId,
            $req['gender'] ?? 'any',
            $req['dept'] ?? null,
            $req['min_year'] ?? null,
            $req['max_year'] ?? null,
            $req['smoking'] ?? 1,
            $req['mandatory'] ?? 0,
        ]);
    }

    jsonResponse(['success' => true, 'listing_id' => $listingId, 'status' => $status], 201);
}

if ($method === 'PUT') {
    requireLogin();
    $input = json_decode(file_get_contents('php://input'), true);
    $db = getDB();

    $listingId = (int)($input['listing_id'] ?? 0);
    $newStatus  = $input['status'] ?? '';

    $stmt = $db->prepare('SELECT l.*, r.property_id FROM listings l JOIN rooms r ON r.id = l.room_id WHERE l.id = ? AND l.deleted_at IS NULL');
    $stmt->execute([$listingId]);
    $listing = $stmt->fetch();
    if (!$listing) {
        jsonResponse(['error' => 'Listing not found'], 404);
    }

    $role = $_SESSION['role'];

    if ($role === 'owner') {
        $stmt = $db->prepare('SELECT id FROM properties WHERE id = ? AND owner_id = ?');
        $stmt->execute([$listing['property_id'], $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => 'Not your property'], 403);
        }
    }

    if (!canTransitionListingStatus($listing['status'], $newStatus, $role)) {
        jsonResponse(['error' => 'Invalid status transition'], 400);
    }

    $publishedAt = $listing['published_at'];
    if ($newStatus === 'published') {
        $publishedAt = date('Y-m-d H:i:s');
    }

    $stmt = $db->prepare('UPDATE listings SET status = ?, published_at = ? WHERE id = ?');
    $stmt->execute([$newStatus, $publishedAt, $listingId]);

    jsonResponse(['success' => true, 'status' => $newStatus]);
}

if ($method === 'DELETE') {
    requireLogin();
    $input = json_decode(file_get_contents('php://input'), true);
    $listingId = (int)($input['listing_id'] ?? 0);
    $db = getDB();

    $stmt = $db->prepare('UPDATE listings SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$listingId]);

    jsonResponse(['success' => true]);
}
