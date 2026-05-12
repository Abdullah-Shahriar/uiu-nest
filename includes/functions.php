<?php
/**
 * UIU Nest — Shared Utility Functions
 */
require_once __DIR__ . '/../config/database.php';

/** Haversine distance in km */
function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2)**2;
    return round($R * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
}

function distanceFromUIU(float $lat, float $lng): float {
    return calculateDistance(UIU_LAT, UIU_LNG, $lat, $lng);
}

function sanitizeInput(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function formatRent(float $amount): string {
    return '৳' . number_format($amount, 0);
}

function getListingStatusBadge(string $status): string {
    $map = [
        'draft'                  => ['Draft', 'badge-draft'],
        'pending_owner_approval' => ['Pending Approval', 'badge-pending'],
        'published'              => ['Published', 'badge-published'],
        'closed'                 => ['Closed', 'badge-closed'],
        'rejected'               => ['Rejected', 'badge-rejected'],
    ];
    $info = $map[$status] ?? [$status, 'badge-default'];
    return '<span class="badge ' . $info[1] . '">' . $info[0] . '</span>';
}

function getAppStatusBadge(string $status): string {
    $map = [
        'pending_tenant_review'  => ['Pending Tenant Review', 'badge-pending'],
        'pending_owner_review'   => ['Pending Owner Review', 'badge-pending'],
        'accepted'               => ['Accepted', 'badge-published'],
        'enrolled'               => ['Enrolled', 'badge-enrolled'],
        'rejected_by_tenant'     => ['Rejected', 'badge-rejected'],
        'rejected_by_owner'      => ['Rejected', 'badge-rejected'],
        'withdrawn'              => ['Withdrawn', 'badge-closed'],
    ];
    $info = $map[$status] ?? [$status, 'badge-default'];
    return '<span class="badge ' . $info[1] . '">' . $info[0] . '</span>';
}



function canUserApply(int $userId, int $listingId): array {
    $db = getDB();

    // Check not already applied
    $stmt = $db->prepare('SELECT id FROM applications WHERE listing_id = ? AND applicant_id = ? AND deleted_at IS NULL');
    $stmt->execute([$listingId, $userId]);
    if ($stmt->fetch()) {
        return ['can' => false, 'reason' => 'You have already applied to this listing.'];
    }

    // Check listing is published
    $stmt = $db->prepare('SELECT l.*, r.property_id FROM listings l JOIN rooms r ON r.id = l.room_id WHERE l.id = ? AND l.deleted_at IS NULL');
    $stmt->execute([$listingId]);
    $listing = $stmt->fetch();
    if (!$listing || $listing['status'] !== 'published') {
        return ['can' => false, 'reason' => 'This listing is not available.'];
    }

    // Can't apply to own listing
    if ($listing['created_by'] == $userId) {
        return ['can' => false, 'reason' => 'Cannot apply to your own listing.'];
    }

    return ['can' => true, 'reason' => ''];
}

/** State machine transitions */
function canTransitionListingStatus(string $current, string $new, string $role): bool {
    $allowed = [
        'draft' => [
            'pending_owner_approval' => ['tenant'],
            'published'              => ['owner'],
        ],
        'pending_owner_approval' => [
            'published' => ['owner', 'admin'],
            'rejected'  => ['owner', 'admin'],
        ],
        'published' => [
            'closed' => ['owner', 'tenant', 'admin'],
        ],
    ];
    return isset($allowed[$current][$new]) && in_array($role, $allowed[$current][$new]);
}

function canTransitionAppStatus(string $current, string $new, string $role, string $listingType): bool {
    if ($listingType === 'owner_direct') {
        $allowed = [
            'pending_owner_review' => [
                'enrolled'          => ['owner', 'admin'],
                'rejected_by_owner' => ['owner', 'admin'],
            ],
        ];
    } else {
        $allowed = [
            'pending_tenant_review' => [
                'pending_owner_review' => ['tenant'],
                'rejected_by_tenant'   => ['tenant'],
            ],
            'pending_owner_review' => [
                'enrolled'          => ['owner', 'admin'],
                'rejected_by_owner' => ['owner', 'admin'],
            ],
        ];
    }
    return isset($allowed[$current][$new]) && in_array($role, $allowed[$current][$new]);
}

/** Amenities — DB-driven with admin-configurable icons */
function getAmenityLabel(string $slug): string {
    static $cache = null;
    if ($cache === null) {
        $db = getDB();
        $stmt = $db->query('SELECT slug, icon, label FROM amenities WHERE is_active = 1 ORDER BY sort_order');
        $cache = [];
        foreach ($stmt as $row) {
            $cache[$row['slug']] = $row['icon'] . ' ' . $row['label'];
        }
    }
    return $cache[$slug] ?? ucfirst(str_replace('_', ' ', $slug));
}

function getAmenitiesList(): array {
    $db = getDB();
    $stmt = $db->query('SELECT slug, icon, label FROM amenities WHERE is_active = 1 ORDER BY sort_order');
    return $stmt->fetchAll();
}

function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken(?string $token): bool {
    return $token && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
