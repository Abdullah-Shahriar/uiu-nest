<?php
/** UIU Nest â€” Applicant Profile (Owner/Tenant view) */
$pageName = 'Applicant Profile';
require_once __DIR__ . '/../includes/header.php';
requireRole(['owner', 'tenant', 'admin']);

$db = getDB();
$appId = (int)($_GET['app_id'] ?? 0);
if (!$appId) { redirect(APP_URL . '/pages/dashboard.php'); }

$stmt = $db->prepare(
    'SELECT a.*, u.full_name, u.email, u.phone, u.student_id, u.department,
            u.year_of_study, u.gender, u.bio,
            l.title AS listing_title, l.listing_type, l.id AS listing_id,
            r.room_number, p.name AS property_name
     FROM applications a
     JOIN users u ON u.id = a.applicant_id
     JOIN listings l ON l.id = a.listing_id
     JOIN rooms r ON r.id = l.room_id
     JOIN properties p ON p.id = r.property_id
     WHERE a.id = ?'
);
$stmt->execute([$appId]);
$app = $stmt->fetch();
if (!$app) { echo '<div class="empty-state"><h3>Application not found</h3></div>'; require_once __DIR__.'/../includes/footer.php'; exit; }

// Verification documents
$docStmt = $db->prepare('SELECT * FROM application_documents WHERE application_id = ? LIMIT 1');
$docStmt->execute([$appId]);
$docs = $docStmt->fetch();
?>

<a href="<?= APP_URL ?>/pages/listing-detail.php?id=<?= $app['listing_id'] ?>" class="btn btn-ghost btn-sm" style="margin-bottom:16px;">â† Back to Listing</a>

<div class="card">
    <div class="profile-header">
        <div class="profile-avatar-lg"><?= strtoupper(substr($app['full_name'], 0, 1)) ?></div>
        <div class="profile-details">
            <h2><?= sanitizeInput($app['full_name']) ?></h2>
            <p><?= sanitizeInput($app['email']) ?> <?= $app['phone'] ? 'Â· ' . sanitizeInput($app['phone']) : '' ?></p>
            <div style="margin-top:6px;">
                <?= getAppStatusBadge($app['status']) ?>
            </div>
        </div>
    </div>

    <div class="profile-grid">
        <div class="profile-field"><label>Student ID</label><div class="value"><?= sanitizeInput($app['student_id'] ?? '-') ?></div></div>
        <div class="profile-field"><label>Department</label><div class="value"><?= sanitizeInput($app['department'] ?? '-') ?></div></div>
        <div class="profile-field"><label>Year of Study</label><div class="value"><?= $app['year_of_study'] ? 'Year ' . $app['year_of_study'] : '-' ?></div></div>
        <div class="profile-field"><label>Gender</label><div class="value"><?= ucfirst($app['gender'] ?? '-') ?></div></div>
        <div class="profile-field"><label>Applied For</label><div class="value"><a href="<?= APP_URL ?>/pages/listing-detail.php?id=<?= $app['listing_id'] ?>"><?= sanitizeInput($app['listing_title']) ?></a></div></div>
        <div class="profile-field"><label>Property / Room</label><div class="value"><?= sanitizeInput($app['property_name']) ?> â€” Room <?= sanitizeInput($app['room_number']) ?></div></div>
        <div class="profile-field"><label>Applied On</label><div class="value"><?= date('M j, Y g:i A', strtotime($app['applied_at'])) ?></div></div>
    </div>

    <?php if ($app['cover_message']): ?>
    <div style="padding:0 24px 24px;">
        <label style="font-size:0.75rem;color:var(--text-tertiary);text-transform:uppercase;">Cover Message</label>
        <div style="margin-top:6px;padding:16px;background:var(--bg-tertiary);border-radius:var(--radius-sm);font-size:0.9rem;">
            <?= nl2br(sanitizeInput($app['cover_message'])) ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($app['bio']): ?>
    <div style="padding:0 24px 24px;">
        <label style="font-size:0.75rem;color:var(--text-tertiary);text-transform:uppercase;">Bio</label>
        <p style="margin-top:6px;font-size:0.9rem;"><?= nl2br(sanitizeInput($app['bio'])) ?></p>
    </div>
    <?php endif; ?>
</div>

<!-- Verification Documents -->
<?php if ($docs): ?>
<div class="card" style="margin-top:20px;">
    <div class="card-body">
        <h3 style="margin-bottom:16px;">ðŸ“Ž Verification Documents</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;">
            <div><label style="font-size:0.75rem;color:var(--text-tertiary);">ID Front</label><img src="<?= APP_URL ?>/<?= $docs['id_card_front'] ?>" style="width:100%;border-radius:var(--radius-sm);margin-top:6px;" alt="ID Front"></div>
            <div><label style="font-size:0.75rem;color:var(--text-tertiary);">ID Back</label><img src="<?= APP_URL ?>/<?= $docs['id_card_back'] ?>" style="width:100%;border-radius:var(--radius-sm);margin-top:6px;" alt="ID Back"></div>
            <div><label style="font-size:0.75rem;color:var(--text-tertiary);">Selfie</label><img src="<?= APP_URL ?>/<?= $docs['selfie_path'] ?>" style="width:100%;border-radius:var(--radius-sm);margin-top:6px;" alt="Selfie"></div>
            <div><label style="font-size:0.75rem;color:var(--text-tertiary);">Video</label><video src="<?= APP_URL ?>/<?= $docs['video_path'] ?>" controls style="width:100%;border-radius:var(--radius-sm);margin-top:6px;"></video></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Action Buttons -->
<?php if (strpos($app['status'], 'pending') !== false): ?>
<div class="card" style="margin-top:20px;">
    <div class="card-body" style="display:flex;gap:12px;justify-content:flex-end;">
        <button class="btn btn-danger" onclick="updateApp('reject')">âŒ Reject</button>
        <button class="btn btn-success" onclick="updateApp('accept')">âœ… Accept</button>
    </div>
</div>
<?php endif; ?>

<script>
async function updateApp(action) {
    const statusMap = {
        accept: '<?= $app['status'] === 'pending_tenant_review' ? 'pending_owner_review' : 'enrolled' ?>',
        reject: '<?= $app['status'] === 'pending_tenant_review' ? 'rejected_by_tenant' : 'rejected_by_owner' ?>'
    };
    if (!confirm(`${action === 'accept' ? 'Accept' : 'Reject'} this application?`)) return;
    try {
        await fetchAPI(`${APP_URL}/api/applications.php`, {
            method: 'PUT',
            body: JSON.stringify({ application_id: <?= $appId ?>, status: statusMap[action] })
        });
        Toast.show('Application updated!', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch(e) {}
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

