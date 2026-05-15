<?php
/** UIU Nest — Manage Listings (Owner/Tenant) */
$pageName = 'Manage Listings';
require_once __DIR__ . '/../includes/header.php';
requireRole(['owner', 'tenant', 'admin']);

$db = getDB();
$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];

if ($role === 'owner') {
    $stmt = $db->prepare(
        'SELECT l.*, r.rent_amount, r.room_number, p.name AS property_name, u.full_name AS creator_name,
                (SELECT COUNT(*) FROM applications a WHERE a.listing_id = l.id AND a.deleted_at IS NULL) AS app_count
         FROM listings l
         JOIN rooms r ON r.id = l.room_id
         JOIN properties p ON p.id = r.property_id
         JOIN users u ON u.id = l.created_by
         WHERE p.owner_id = ? AND l.deleted_at IS NULL
         ORDER BY l.created_at DESC'
    );
    $stmt->execute([$userId]);
} else {
    $stmt = $db->prepare(
        'SELECT l.*, r.rent_amount, r.room_number, p.name AS property_name, u.full_name AS creator_name,
                (SELECT COUNT(*) FROM applications a WHERE a.listing_id = l.id AND a.deleted_at IS NULL) AS app_count
         FROM listings l
         JOIN rooms r ON r.id = l.room_id
         JOIN properties p ON p.id = r.property_id
         JOIN users u ON u.id = l.created_by
         WHERE l.created_by = ? AND l.deleted_at IS NULL
         ORDER BY l.created_at DESC'
    );
    $stmt->execute([$userId]);
}
$listings = $stmt->fetchAll();
?>

<div class="section-header">
    <h2> Manage Listings</h2>
    <a href="<?= APP_URL ?>/pages/create-listing.php" class="btn btn-primary btn-sm">+ New Listing</a>
</div>

<?php if (empty($listings)): ?>
<div class="empty-state">
    <div class="empty-state-icon"></div>
    <h3>No listings yet</h3>
    <p>Create your first listing to get started.</p>
    <a href="<?= APP_URL ?>/pages/create-listing.php" class="btn btn-primary" style="margin-top:16px;">Create Listing</a>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body" style="overflow-x:auto;">
        <table class="data-table">
            <thead><tr><th>Title</th><th>Property / Room</th><th>Rent</th><th>Type</th><th>Status</th><th>Apps</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($listings as $l): ?>
            <tr>
                <td><a href="<?= APP_URL ?>/pages/listing-detail.php?id=<?= $l['id'] ?>"><?= sanitizeInput($l['title']) ?></a></td>
                <td><?= sanitizeInput($l['property_name']) ?> — <?= sanitizeInput($l['room_number']) ?></td>
                <td><?= formatRent($l['rent_amount']) ?></td>
                <td><span class="badge <?= $l['listing_type'] === 'roommate_needed' ? 'badge-enrolled' : 'badge-draft' ?>"><?= $l['listing_type'] === 'roommate_needed' ? 'Roommate' : 'Direct' ?></span></td>
                <td><?= getListingStatusBadge($l['status']) ?></td>
                <td><strong><?= $l['app_count'] ?></strong></td>
                <td style="white-space:nowrap;">
                    <a href="<?= APP_URL ?>/pages/listing-detail.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-outline">View</a>
                    <?php if ($l['status'] === 'pending_owner_approval' && $role === 'owner'): ?>
                        <button class="btn btn-sm btn-success" onclick="updateListing(<?= $l['id'] ?>,'published')">Approve</button>
                        <button class="btn btn-sm btn-danger" onclick="updateListing(<?= $l['id'] ?>,'rejected')">Reject</button>
                    <?php endif; ?>
                    <?php if ($l['status'] === 'published'): ?>
                        <button class="btn btn-sm btn-ghost" onclick="updateListing(<?= $l['id'] ?>,'closed')">Close</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
async function updateListing(id, status) {
    if (!confirm(`${status === 'published' ? 'Approve' : status === 'rejected' ? 'Reject' : 'Close'} this listing?`)) return;
    try {
        await fetchAPI(`${APP_URL}/api/listings.php`, { method: 'PUT', body: JSON.stringify({ listing_id: id, status }) });
        Toast.show('Listing updated!', 'success');
        setTimeout(() => location.reload(), 800);
    } catch(e) {}
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
