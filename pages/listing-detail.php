<?php
/** UIU Nest — Listing Detail Page */
$pageName = 'Listing Detail';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$listingId = (int)($_GET['id'] ?? 0);
if (!$listingId) { redirect(APP_URL . '/pages/dashboard.php'); }

// Fetch listing with joins
$stmt = $db->prepare(
    'SELECT l.*, r.rent_amount, r.capacity, r.current_occupancy, r.amenities_json, r.room_number, r.description AS room_desc,
            p.name AS property_name, p.address, p.location_lat, p.location_lng, p.description AS prop_desc,
            u.full_name AS creator_name, u.role AS creator_role
     FROM listings l
     JOIN rooms r ON r.id = l.room_id
     JOIN properties p ON p.id = r.property_id
     JOIN users u ON u.id = l.created_by
     WHERE l.id = ?'
);
$stmt->execute([$listingId]);
$listing = $stmt->fetch();
if (!$listing) { echo '<div class="empty-state"><h3>Listing not found</h3></div>'; require_once __DIR__.'/../includes/footer.php'; exit; }

// Requirements
$reqStmt = $db->prepare('SELECT * FROM listing_requirements WHERE listing_id = ?');
$reqStmt->execute([$listingId]);
$req = $reqStmt->fetch();

// Check if user already applied
$alreadyApplied = false;
$userApp = null;
if (isLoggedIn()) {
    $appStmt = $db->prepare('SELECT * FROM applications WHERE listing_id = ? AND applicant_id = ? AND deleted_at IS NULL');
    $appStmt->execute([$listingId, $_SESSION['user_id']]);
    $userApp = $appStmt->fetch();
    $alreadyApplied = (bool) $userApp;
}

// Check saved
$isSaved = false;
if (isLoggedIn()) {
    $svStmt = $db->prepare('SELECT id FROM saved_listings WHERE user_id = ? AND listing_id = ?');
    $svStmt->execute([$_SESSION['user_id'], $listingId]);
    $isSaved = (bool) $svStmt->fetch();
}

$amenities = json_decode($listing['amenities_json'] ?: '[]', true);
$dist = distanceFromUIU((float)$listing['location_lat'], (float)$listing['location_lng']);
$canApply = isLoggedIn() && !$alreadyApplied && $listing['status'] === 'published' && ($_SESSION['user_id'] ?? 0) != $listing['created_by'];

// Applications (for owner/tenant who owns this listing)
$showApps = false;
$applications = [];
if (isLoggedIn() && ($listing['created_by'] == $_SESSION['user_id'] || hasAnyRole(['owner','admin']))) {
    $showApps = true;
    $apStmt = $db->prepare(
        'SELECT a.*, u.full_name, u.email, u.student_id, u.department
         FROM applications a JOIN users u ON u.id = a.applicant_id
         WHERE a.listing_id = ? AND a.deleted_at IS NULL ORDER BY a.application_date DESC'
    );
    $apStmt->execute([$listingId]);
    $applications = $apStmt->fetchAll();
}
?>

<a href="<?= APP_URL ?>/pages/dashboard.php" class="btn btn-ghost btn-sm" style="margin-bottom:16px;">← Back to Listings</a>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">
    <!-- Main Info -->
    <div>
        <div class="card" style="overflow:hidden;">
            <div style="height:240px;background:var(--accent-gradient);display:flex;align-items:center;justify-content:center;font-size:5rem;opacity:0.3;">🏠</div>
            <div class="card-body">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px;">
                    <div>
                        <?= getListingStatusBadge($listing['status']) ?>
                        <span class="badge <?= $listing['listing_type'] === 'roommate_needed' ? 'badge-enrolled' : 'badge-draft' ?>" style="margin-left:6px;">
                            <?= $listing['listing_type'] === 'roommate_needed' ? 'Roommate' : 'Direct' ?>
                        </span>
                    </div>
                    <span class="distance-pill">📍 <?= $dist ?> km from UIU</span>
                </div>
                <h2 style="margin-bottom:8px;"><?= sanitizeInput($listing['title']) ?></h2>
                <p style="margin-bottom:16px;"><?= nl2br(sanitizeInput($listing['description'] ?? '')) ?></p>

                <h3 style="margin-bottom:12px;">Amenities</h3>
                <div class="listing-card-amenities" style="margin-bottom:20px;">
                    <?php foreach ($amenities as $a): ?>
                        <span class="amenity-tag"><?= getAmenityLabel($a) ?></span>
                    <?php endforeach; ?>
                </div>

                <?php if ($req): ?>
                <h3 style="margin-bottom:12px;">Requirements <?= $req['is_mandatory'] ? '<span class="badge badge-rejected">Mandatory</span>' : '<span class="badge badge-default">Preferred</span>' ?></h3>
                <div style="display:flex;gap:12px;flex-wrap:wrap;font-size:0.875rem;">
                    <?php if ($req['preferred_gender'] !== 'any'): ?><span class="amenity-tag">👤 <?= ucfirst($req['preferred_gender']) ?></span><?php endif; ?>
                    <?php if ($req['preferred_dept']): ?><span class="amenity-tag">🎓 <?= sanitizeInput($req['preferred_dept']) ?></span><?php endif; ?>
                    <?php if ($req['min_year']): ?><span class="amenity-tag">📅 Year <?= $req['min_year'] ?>-<?= $req['max_year'] ?: '4' ?></span><?php endif; ?>
                    <span class="amenity-tag"><?= $req['smoking_allowed'] ? '🚬 Smoking OK' : '🚭 No Smoking' ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Applications Queue -->
        <?php if ($showApps && count($applications) > 0): ?>
        <div class="card" style="margin-top:20px;">
            <div class="card-body">
                <h3 style="margin-bottom:16px;">Applications (<?= count($applications) ?>)</h3>
                <table class="data-table">
                    <thead><tr><th>Applicant</th><th>Dept</th><th>Docs</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($applications as $app): ?>
                    <tr>
                        <td>
                            <strong><?= sanitizeInput($app['full_name']) ?></strong><br>
                            <small><?= sanitizeInput($app['email']) ?></small>
                        </td>
                        <td><?= sanitizeInput($app['department'] ?? '-') ?></td>
                        <td><a href="<?= APP_URL ?>/pages/applicant-profile.php?app_id=<?= $app['id'] ?>" class="btn btn-sm btn-ghost">📎 View</a></td>
                        <td><?= getAppStatusBadge($app['status']) ?></td>
                        <td>
                            <a href="<?= APP_URL ?>/pages/applicant-profile.php?app_id=<?= $app['id'] ?>" class="btn btn-sm btn-outline">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar Info -->
    <div>
        <div class="card card-glass" style="position:sticky;top:80px;">
            <div class="card-body">
                <div style="font-size:2rem;font-weight:800;color:var(--accent);margin-bottom:4px;"><?= formatRent($listing['rent_amount']) ?></div>
                <small>/month per person</small>

                <div style="margin:20px 0;display:flex;flex-direction:column;gap:10px;font-size:0.875rem;">
                    <div>🏢 <strong><?= sanitizeInput($listing['property_name']) ?></strong></div>
                    <div>🚪 Room <?= sanitizeInput($listing['room_number']) ?></div>
                    <div>🛏️ <?= $listing['current_occupancy'] ?>/<?= $listing['capacity'] ?> occupied</div>
                    <div>📍 <?= sanitizeInput($listing['address']) ?></div>
                    <div>👤 Listed by <?= sanitizeInput($listing['creator_name']) ?> (<?= ucfirst($listing['creator_role']) ?>)</div>
                </div>

                <?php if ($canApply): ?>
                    <button class="btn btn-primary btn-lg" style="width:100%;" onclick="Modal.open('applyModal')">Apply Now</button>
                <?php elseif ($alreadyApplied): ?>
                    <div style="padding:12px;background:var(--accent-light);border-radius:var(--radius-sm);text-align:center;">
                        <?= getAppStatusBadge($userApp['status']) ?>
                        <div style="margin-top:6px;font-size:0.8rem;color:var(--text-tertiary);">Applied <?= date('M j, Y', strtotime($userApp['application_date'])) ?></div>
                    </div>
                <?php elseif (!isLoggedIn()): ?>
                    <a href="<?= APP_URL ?>/pages/login.php" class="btn btn-primary btn-lg" style="width:100%;">Login to Apply</a>
                <?php endif; ?>

                <?php if (isLoggedIn()): ?>
                <button class="btn btn-ghost" style="width:100%;margin-top:10px;" onclick="toggleSaveDetail(<?= $listingId ?>)" id="saveBtn">
                    <?= $isSaved ? '❤️ Saved' : '🤍 Save Listing' ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Apply Modal -->
<?php if ($canApply): ?>
<div class="modal-overlay" id="applyModal">
    <div class="modal" style="max-width:560px;">
        <div class="modal-header">
            <h3>Apply for this Room</h3>
            <button class="modal-close" onclick="Modal.close('applyModal')">✕</button>
        </div>
        <div class="modal-body">
            <form id="applyForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Cover Message (optional)</label>
                    <textarea class="form-control" name="message" rows="3" placeholder="Introduce yourself briefly..."></textarea>
                </div>

                <h4 style="margin-bottom:12px;color:var(--text-secondary);">🪪 Identity Verification</h4>
                <p style="font-size:0.8rem;color:var(--text-tertiary);margin-bottom:16px;">Upload the following so the owner can verify your identity.</p>

                <div class="form-group">
                    <label class="form-label">University ID Card (Front)</label>
                    <input class="form-control" type="file" name="id_card_front" accept="image/*" required style="padding:8px;">
                </div>
                <div class="form-group">
                    <label class="form-label">University ID Card (Back)</label>
                    <input class="form-control" type="file" name="id_card_back" accept="image/*" required style="padding:8px;">
                </div>
                <div class="form-group">
                    <label class="form-label">Selfie Photo</label>
                    <input class="form-control" type="file" name="selfie" accept="image/*" required style="padding:8px;">
                    <div class="form-hint">Clear photo of your face</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Short Video (5-10 sec)</label>
                    <input class="form-control" type="file" name="video" accept="video/*" required style="padding:8px;">
                    <div class="form-hint">Say your name & student ID clearly</div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="Modal.close('applyModal')">Cancel</button>
            <button class="btn btn-primary" id="applyBtn" onclick="submitApplication()">Submit Application</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
async function submitApplication() {
    const form = document.getElementById('applyForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }
    const btn = document.getElementById('applyBtn');
    btn.disabled = true;
    btn.textContent = 'Uploading...';

    const formData = new FormData(form);
    formData.append('listing_id', <?= $listingId ?>);

    try {
        const resp = await fetch('<?= APP_URL ?>/api/applications.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        const data = await resp.json();
        if (data.success) {
            Toast.show('Application submitted!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            Toast.show(data.error || 'Failed', 'error');
        }
    } catch(e) { Toast.show('Upload failed', 'error'); }
    btn.disabled = false;
    btn.textContent = 'Submit Application';
}
async function toggleSaveDetail(id) {
    try {
        const data = await fetchAPI('<?= APP_URL ?>/api/saved.php', { method:'POST', body: JSON.stringify({listing_id:id}) });
        document.getElementById('saveBtn').innerHTML = data.saved ? '❤️ Saved' : '🤍 Save Listing';
        Toast.show(data.saved ? 'Saved!' : 'Removed', 'success');
    } catch(e) {}
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
