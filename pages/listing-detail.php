<?php
/** UIU Nest — Listing Detail Page */
$pageName = 'Listing Detail';
require_once __DIR__ . '/../includes/header.php';

$db        = getDB();
$listingId = (int)($_GET['id'] ?? 0);
if (!$listingId) { redirect(APP_URL . '/pages/dashboard.php'); }

$stmt = $db->prepare(
    'SELECT l.*, r.rent_amount, r.capacity, r.current_occupancy, r.amenities_json, r.room_number, r.description AS room_desc,
            p.name AS property_name, p.id AS property_id, p.address, p.location_lat, p.location_lng, p.description AS prop_desc,
            u.full_name AS creator_name, u.role AS creator_role, u.avatar_path AS creator_avatar
     FROM listings l
     JOIN rooms r ON r.id = l.room_id
     JOIN properties p ON p.id = r.property_id
     JOIN users u ON u.id = l.created_by
     WHERE l.id = ?'
);
$stmt->execute([$listingId]);
$listing = $stmt->fetch();
if (!$listing) {
    echo '<div class="empty-state"><h3>Listing not found</h3></div>';
    require_once __DIR__ . '/../includes/footer.php'; exit;
}

// Property images
$imgStmt = $db->prepare('SELECT image_path, is_cover FROM property_images WHERE property_id = ? ORDER BY is_cover DESC, sort_order ASC');
$imgStmt->execute([$listing['property_id']]);
$propertyImages = $imgStmt->fetchAll();

// Requirements
$reqStmt = $db->prepare('SELECT * FROM listing_requirements WHERE listing_id = ?');
$reqStmt->execute([$listingId]);
$req = $reqStmt->fetch();

// Already applied?
$alreadyApplied = false; $userApp = null;
if (isLoggedIn()) {
    $appStmt = $db->prepare('SELECT * FROM applications WHERE listing_id = ? AND applicant_id = ? AND deleted_at IS NULL');
    $appStmt->execute([$listingId, $_SESSION['user_id']]);
    $userApp = $appStmt->fetch();
    $alreadyApplied = (bool)$userApp;
}

// Saved?
$isSaved = false;
if (isLoggedIn()) {
    $svStmt = $db->prepare('SELECT id FROM saved_listings WHERE user_id = ? AND listing_id = ?');
    $svStmt->execute([$_SESSION['user_id'], $listingId]);
    $isSaved = (bool)$svStmt->fetch();
}

// Reviews (resident_reviews table not yet seeded — show empty)
$reviews = [];
$reviewMeta = ['avg' => null, 'total' => 0];

$amenities = json_decode($listing['amenities_json'] ?: '[]', true);
$dist      = distanceFromUIU((float)$listing['location_lat'], (float)$listing['location_lng']);
$canApply  = isLoggedIn() && !$alreadyApplied && $listing['status'] === 'published'
             && ($_SESSION['user_id'] ?? 0) != $listing['created_by'];

// Applications (owner/admin)
$showApps = false; $applications = [];
if (isLoggedIn() && ($listing['created_by'] == $_SESSION['user_id'] || hasAnyRole(['owner','admin']))) {
    $showApps = true;
    $apStmt = $db->prepare(
        'SELECT a.*, u.full_name, u.email, u.student_id, u.department
         FROM applications a JOIN users u ON u.id = a.applicant_id
         WHERE a.listing_id = ? AND a.deleted_at IS NULL ORDER BY a.applied_at DESC'
    );
    $apStmt->execute([$listingId]);
    $applications = $apStmt->fetchAll();
}
?>
<style>
.detail-grid { display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start; }
.gallery { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; border-radius: var(--radius); overflow: hidden; margin-bottom: 20px; }
.gallery-main { grid-column: 1 / 3; grid-row: 1 / 3; height: 260px; }
.gallery-thumb { height: 124px; }
.gallery img { width: 100%; height: 100%; object-fit: cover; display: block; }
.gallery-placeholder { background: var(--accent-gradient); display: flex; align-items: center; justify-content: center; font-size: 4rem; opacity: 0.25; }
.star { color: #f59e0b; font-size: 1rem; }
.review-card { padding: 14px; border: 1px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 10px; }
.review-card:last-child { margin-bottom: 0; }
.review-stars { color: #f59e0b; margin-bottom: 4px; }
.detail-aside { position: sticky; top: 80px; }
</style>

<a href="<?= APP_URL ?>/pages/dashboard.php" class="btn btn-ghost btn-sm" style="margin-bottom:16px;">← Back to Listings</a>

<div class="detail-grid">
    <!-- Main column -->
    <div>
        <div class="card" style="overflow:hidden;margin-bottom:20px;">
            <!-- Photo gallery -->
            <?php if (count($propertyImages) > 0): ?>
            <div class="gallery">
                <div class="gallery-main">
                    <img src="<?= APP_URL ?>/<?= htmlspecialchars($propertyImages[0]['image_path']) ?>" alt="Property">
                </div>
                <?php for ($i = 1; $i < min(3, count($propertyImages)); $i++): ?>
                <div class="gallery-thumb">
                    <img src="<?= APP_URL ?>/<?= htmlspecialchars($propertyImages[$i]['image_path']) ?>" alt="Photo">
                </div>
                <?php endfor; ?>
                <?php if (count($propertyImages) < 3): ?>
                <div class="gallery-thumb gallery-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:48px;height:48px;opacity:.3"><path d="M3 9.5L12 3l9 6.5V21H3V9.5z"/><rect x="9" y="14" width="6" height="7" rx="1"/></svg></div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div style="height:240px;background:var(--accent-gradient);display:flex;align-items:center;justify-content:center;opacity:0.2;"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5" style="width:80px;height:80px;"><path d="M3 9.5L12 3l9 6.5V21H3V9.5z"/><rect x="9" y="14" width="6" height="7" rx="1"/></svg></div>
            <?php endif; ?>

            <div class="card-body">
                <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
                    <div>
                        <?= getListingStatusBadge($listing['status']) ?>
                        <span class="badge <?= $listing['listing_type'] === 'roommate_needed' ? 'badge-enrolled' : 'badge-draft' ?>" style="margin-left:6px;">
                            <?= $listing['listing_type'] === 'roommate_needed' ? 'Roommate Needed' : 'Owner Direct' ?>
                        </span>
                        <?php if ($reviewMeta['total'] > 0): ?>
                        <span style="margin-left:8px;font-size:0.82rem;color:var(--warning);">
                            ★ <?= $reviewMeta['avg'] ?> (<?= $reviewMeta['total'] ?> reviews)
                        </span>
                        <?php endif; ?>
                    </div>
                    <span class="distance-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;display:inline;vertical-align:middle;"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 00-8 8c0 5.25 8 14 8 14s8-8.75 8-14a8 8 0 00-8-8z"/></svg> <?= $dist ?> km from UIU</span>
                </div>

                <h2 style="margin-bottom:8px;"><?= sanitizeInput($listing['title']) ?></h2>
                <p style="margin-bottom:16px;"><?= nl2br(sanitizeInput($listing['description'] ?? '')) ?></p>

                <h3 style="margin-bottom:10px;">Amenities</h3>
                <div class="listing-card-amenities" style="margin-bottom:20px;">
                    <?php foreach ($amenities as $a): ?>
                        <span class="amenity-tag"><?= getAmenityLabel($a) ?></span>
                    <?php endforeach; ?>
                </div>

                <?php if ($req): ?>
                <h3 style="margin-bottom:10px;">Requirements
                    <?= $req['is_mandatory'] ? '<span class="badge badge-rejected">Mandatory</span>' : '<span class="badge badge-default">Preferred</span>' ?>
                </h3>
                <div style="display:flex;gap:8px;flex-wrap:wrap;font-size:0.875rem;margin-bottom:4px;">
                    <?php if ($req['preferred_gender'] !== 'any'): ?><span class="amenity-tag"><?= ucfirst($req['preferred_gender']) ?> only</span><?php endif; ?>
                    <?php if ($req['preferred_dept']): ?><span class="amenity-tag"><?= sanitizeInput($req['preferred_dept']) ?></span><?php endif; ?>
                    <?php if ($req['min_year']): ?><span class="amenity-tag">Year <?= $req['min_year'] ?>–<?= $req['max_year'] ?: '4' ?></span><?php endif; ?>
                    <span class="amenity-tag"><?= $req['smoking_allowed'] ? 'Smoking OK' : 'No Smoking' ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Applications queue (owner/admin) -->
        <?php if ($showApps && count($applications) > 0): ?>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-body">
                <h3 style="margin-bottom:16px;">Applications (<?= count($applications) ?>)</h3>
                <table class="data-table">
                    <thead><tr><th>Applicant</th><th>Dept</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($applications as $app): ?>
                    <tr>
                        <td><strong><?= sanitizeInput($app['full_name']) ?></strong><br><small><?= sanitizeInput($app['email']) ?></small></td>
                        <td><?= sanitizeInput($app['department'] ?? '—') ?></td>
                        <td><?= getAppStatusBadge($app['status']) ?></td>
                        <td><a href="<?= APP_URL ?>/pages/applicant-profile.php?app_id=<?= $app['id'] ?>" class="btn btn-sm btn-outline">View Profile</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Former Residents Reviews -->
        <div class="card">
            <div class="card-body">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <h3>Former Residents' Reviews</h3>
                    <?php if ($reviewMeta['total'] > 0): ?>
                    <span style="font-size:1.3rem;font-weight:700;color:var(--warning);">
                        ★ <?= $reviewMeta['avg'] ?>
                        <small style="font-size:0.75rem;color:var(--text-tertiary);"> / <?= $reviewMeta['total'] ?> reviews</small>
                    </span>
                    <?php endif; ?>
                </div>

                <?php if (count($reviews) === 0): ?>
                <div class="empty-state" style="padding:30px 0;">
                    <div class="empty-state-icon">💬</div>
                    <p>No reviews yet. Former residents can share their experience.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($reviews as $r): ?>
                    <div class="review-card">
                        <div class="review-stars"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?></div>
                        <p style="margin-bottom:6px;"><?= sanitizeInput($r['comment'] ?? '') ?></p>
                        <small>— <?= sanitizeInput($r['full_name']) ?><?= $r['department'] ? ', ' . sanitizeInput($r['department']) : '' ?> · <?= date('M Y', strtotime($r['created_at'])) ?></small>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="detail-aside">
        <div class="card card-glass">
            <div class="card-body">
                <div style="font-size:2rem;font-weight:800;color:var(--accent);margin-bottom:2px;"><?= formatRent($listing['rent_amount']) ?></div>
                <small>/month per person</small>

                <div style="margin:18px 0;display:flex;flex-direction:column;gap:10px;font-size:0.875rem;">
                    <div> <strong><?= sanitizeInput($listing['property_name']) ?></strong></div>
                    <div>Room <?= sanitizeInput($listing['room_number']) ?></div>
                    <div>🛏️ <?= $listing['current_occupancy'] ?>/<?= $listing['capacity'] ?> occupied</div>
                    <div>📌 <?= sanitizeInput($listing['address']) ?></div>
                    <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
                        <?php if (!empty($listing['creator_avatar']) && file_exists(APP_ROOT . '/' . $listing['creator_avatar'])): ?>
                            <img src="<?= APP_URL . '/' . htmlspecialchars($listing['creator_avatar']) ?>" style="width:28px;height:28px;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <div style="width:28px;height:28px;border-radius:50%;background:var(--border);color:var(--text-secondary);display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;">
                                <?= strtoupper(substr($listing['creator_name'] ?? 'U', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div>Listed by <strong><?= sanitizeInput($listing['creator_name']) ?></strong> <span style="color:var(--text-tertiary);">(&#x<?= $listing['creator_role'] === 'owner' ? '1F454' : '1F464' ?>; <?= ucfirst($listing['creator_role']) ?>)</span></div>
                    </div>
                </div>

                <?php if ($canApply): ?>
                    <button class="btn btn-primary btn-lg" style="width:100%;" onclick="Modal.open('applyModal')">Apply Now</button>
                <?php elseif ($alreadyApplied): ?>
                    <div style="padding:12px;background:var(--accent-light);border-radius:var(--radius-sm);text-align:center;">
                        <?= getAppStatusBadge($userApp['status']) ?>
                        <div style="margin-top:6px;font-size:0.8rem;color:var(--text-tertiary);">Applied <?= date('M j, Y', strtotime($userApp['applied_at'])) ?></div>
                    </div>
                <?php elseif (!isLoggedIn()): ?>
                    <a href="<?= APP_URL ?>/pages/login.php" class="btn btn-primary btn-lg" style="width:100%;">Login to Apply</a>
                <?php endif; ?>

                <?php if (isLoggedIn()): ?>
                <button class="btn btn-ghost" style="width:100%;margin-top:10px;" onclick="toggleSaveDetail(<?= $listingId ?>)" id="saveBtn">
                    <?= $isSaved ? '♥ Saved' : '🤍 Save Listing' ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Apply Modal (no video field) -->
<?php if ($canApply): ?>
<div class="modal-overlay" id="applyModal">
    <div class="modal" style="max-width:520px;">
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
                <h4 style="margin-bottom:10px;color:var(--text-secondary);">ID: Identity Verification</h4>
                <p style="font-size:0.8rem;color:var(--text-tertiary);margin-bottom:14px;">Upload these so the owner can verify your identity.</p>
                <div class="form-group">
                    <label class="form-label">University ID Card (Front) *</label>
                    <input class="form-control" type="file" name="id_card_front" accept="image/*" required style="padding:8px;">
                </div>
                <div class="form-group">
                    <label class="form-label">University ID Card (Back) *</label>
                    <input class="form-control" type="file" name="id_card_back" accept="image/*" required style="padding:8px;">
                </div>
                <div class="form-group">
                    <label class="form-label">Selfie Photo *</label>
                    <input class="form-control" type="file" name="selfie" accept="image/*" required style="padding:8px;">
                    <div class="form-hint">Clear photo of your face</div>
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
    btn.disabled = true; btn.textContent = 'Uploading...';
    const fd = new FormData(form);
    fd.append('listing_id', <?= $listingId ?>);
    try {
        const r = await fetch('<?= APP_URL ?>/api/applications.php', {
            method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd
        });
        const d = await r.json();
        if (d.success) { Toast.show('Application submitted!', 'success'); setTimeout(() => location.reload(), 1000); }
        else           { Toast.show(d.error || 'Failed', 'error'); }
    } catch(e) { Toast.show('Upload failed', 'error'); }
    btn.disabled = false; btn.textContent = 'Submit Application';
}
async function toggleSaveDetail(id) {
    try {
        const d = await fetchAPI('<?= APP_URL ?>/api/saved.php', { method:'POST', body: JSON.stringify({listing_id:id}) });
        document.getElementById('saveBtn').innerHTML = d.saved ? '♥ Saved' : '🤍 Save Listing';
        Toast.show(d.saved ? 'Saved!' : 'Removed', 'success');
    } catch(e) {}
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
