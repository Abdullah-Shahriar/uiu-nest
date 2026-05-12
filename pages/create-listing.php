<?php
/** UIU Nest — Create Listing */
$pageName = 'Create Listing';
require_once __DIR__ . '/../includes/header.php';
requireRole(['owner', 'tenant']);

$db = getDB();
$role = $_SESSION['role'];

// Get available rooms
if ($role === 'owner') {
    $stmt = $db->prepare(
        'SELECT r.*, p.name AS property_name FROM rooms r
         JOIN properties p ON p.id = r.property_id
         WHERE p.owner_id = ? AND r.is_active = 1'
    );
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $db->prepare(
        'SELECT r.*, p.name AS property_name FROM room_tenants rt
         JOIN rooms r ON r.id = rt.room_id
         JOIN properties p ON p.id = r.property_id
         WHERE rt.user_id = ? AND rt.moved_out_at IS NULL'
    );
    $stmt->execute([$_SESSION['user_id']]);
}
$rooms = $stmt->fetchAll();
?>

<div class="section-header">
    <h2><?= $role === 'owner' ? '📝 Create Direct Listing' : '🤝 Find a Roommate' ?></h2>
</div>

<?php if (empty($rooms)): ?>
<div class="empty-state">
    <div class="empty-state-icon">🚪</div>
    <h3>No rooms available</h3>
    <p><?= $role === 'owner' ? 'Add a property and rooms first.' : 'You are not assigned to any room.' ?></p>
    <?php if ($role === 'owner'): ?>
    <a href="<?= APP_URL ?>/pages/manage-properties.php" class="btn btn-primary" style="margin-top:16px;">Manage Properties</a>
    <?php endif; ?>
</div>
<?php else: ?>

<div class="card">
    <div class="card-body">
        <form id="createListingForm">
            <div class="form-group">
                <label class="form-label">Select Room</label>
                <select class="form-control" id="roomId" required>
                    <option value="">Choose a room...</option>
                    <?php foreach ($rooms as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= sanitizeInput($r['property_name']) ?> — Room <?= sanitizeInput($r['room_number']) ?> (৳<?= number_format($r['rent_amount']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Title</label>
                <input class="form-control" type="text" id="listingTitle" placeholder="e.g. Looking for a roommate in Greenview" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-control" id="listingDesc" rows="4" placeholder="Describe the room and what you're looking for..."></textarea>
            </div>

            <h3 style="margin-bottom:16px;">Requirements</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Preferred Gender</label>
                    <select class="form-control" id="reqGender">
                        <option value="any">Any</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Preferred Department</label>
                    <input class="form-control" type="text" id="reqDept" placeholder="e.g. CSE (leave empty for any)">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Min Year</label>
                    <select class="form-control" id="reqMinYear"><option value="">Any</option><option>1</option><option>2</option><option>3</option><option>4</option></select>
                </div>
                <div class="form-group">
                    <label class="form-label">Max Year</label>
                    <select class="form-control" id="reqMaxYear"><option value="">Any</option><option>1</option><option>2</option><option>3</option><option>4</option></select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="checkbox-item">
                        <input type="checkbox" id="reqSmoking"> Smoking Allowed
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox-item">
                        <input type="checkbox" id="reqMandatory"> Requirements are mandatory
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:12px;" id="createBtn">
                <?= $role === 'owner' ? 'Publish Listing' : 'Submit for Owner Approval' ?>
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('createListingForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('createBtn');
    btn.disabled = true;
    try {
        const body = {
            room_id: document.getElementById('roomId').value,
            title: document.getElementById('listingTitle').value,
            description: document.getElementById('listingDesc').value,
            requirements: {
                gender: document.getElementById('reqGender').value,
                dept: document.getElementById('reqDept').value || null,
                min_year: document.getElementById('reqMinYear').value || null,
                max_year: document.getElementById('reqMaxYear').value || null,
                smoking: document.getElementById('reqSmoking').checked ? 1 : 0,
                mandatory: document.getElementById('reqMandatory').checked ? 1 : 0,
            }
        };
        const data = await fetchAPI(`${APP_URL}/api/listings.php`, { method: 'POST', body: JSON.stringify(body) });
        Toast.show(data.status === 'published' ? 'Listing published!' : 'Submitted for owner approval!', 'success');
        setTimeout(() => window.location.href = `${APP_URL}/pages/manage-listings.php`, 1200);
    } catch(e) {}
    btn.disabled = false;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
