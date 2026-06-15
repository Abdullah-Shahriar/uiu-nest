<?php
/**
 * UIU Nest — Applications (standalone section)
 * - Students/Applicants: see their own submitted applications
 * - Owners/House Managers: see all incoming applications for their listings
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$pageName = 'Applications';
require_once __DIR__ . '/../includes/header.php';

$db  = getDB();
$uid = (int)$_SESSION['user_id'];

// ——— Incoming applications (owner / house manager view) ——————————————————————
$incomingApps = [];
if (hasAnyRole(['owner', 'tenant', 'admin'])) {
    $stmt = $db->prepare(
        'SELECT a.*, l.title AS listing_title, l.listing_type,
                r.rent_amount, r.room_number,
                p.name AS property_name, p.id AS property_id,
                u.full_name AS applicant_name, u.email AS applicant_email,
                u.student_id, u.department, u.year_of_study, u.gender, u.role AS applicant_role
         FROM applications a
         JOIN listings l ON l.id = a.listing_id
         JOIN rooms r    ON r.id = l.room_id
         JOIN properties p ON p.id = r.property_id
         JOIN users u    ON u.id = a.applicant_id
         WHERE l.created_by = ? AND a.deleted_at IS NULL
         ORDER BY a.applied_at DESC'
    );
    $stmt->execute([$uid]);
    $incomingApps = $stmt->fetchAll();
}

// â”€â”€ My submitted applications (student / applicant view) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$myApps = [];
if (hasAnyRole(['student', 'tenant'])) {
    $stmt = $db->prepare(
        'SELECT a.*, l.title AS listing_title, l.listing_type,
                r.rent_amount, r.room_number,
                p.name AS property_name
         FROM applications a
         JOIN listings l ON l.id = a.listing_id
         JOIN rooms r    ON r.id = l.room_id
         JOIN properties p ON p.id = r.property_id
         WHERE a.applicant_id = ? AND a.deleted_at IS NULL
         ORDER BY a.applied_at DESC'
    );
    $stmt->execute([$uid]);
    $myApps = $stmt->fetchAll();
}

// â”€â”€ Detail view: single application â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$detailApp  = null;
$detailDocs = null;
$detailUser = null;
$detailListing = null;
$appId = (int)($_GET['app_id'] ?? 0);
if ($appId) {
    $stmt = $db->prepare(
        'SELECT a.*,
                l.title AS listing_title, l.listing_type, l.room_id,
                r.rent_amount, r.room_number, r.capacity,
                p.name AS property_name, p.address, p.id AS property_id,
                u.full_name AS applicant_name, u.email AS applicant_email,
                u.student_id, u.department, u.year_of_study, u.gender,
                u.phone AS applicant_phone, u.bio AS applicant_bio,
                u.avatar_path, u.role AS applicant_role,
                owner.full_name AS listing_owner_name
         FROM applications a
         JOIN listings l  ON l.id = a.listing_id
         JOIN rooms r     ON r.id = l.room_id
         JOIN properties p ON p.id = r.property_id
         JOIN users u     ON u.id = a.applicant_id
         JOIN users owner ON owner.id = l.created_by
         WHERE a.id = ?'
    );
    $stmt->execute([$appId]);
    $detailApp = $stmt->fetch();

    if ($detailApp) {
        // Verify access: must be the applicant OR the listing creator OR admin
        $canView = ($detailApp['applicant_id'] == $uid)
                || ($detailApp['created_by']    == $uid)
                || hasRole('admin');
        if (!$canView) {
            $detailApp = null;
        } else {
            // Docs
            $stmt = $db->prepare('SELECT * FROM application_documents WHERE application_id = ? LIMIT 1');
            $stmt->execute([$appId]);
            $detailDocs = $stmt->fetch();
        }
    }
}

function appStatusBadge(string $s): string {
    $map = [
        'pending_tenant_review' => ['Pending Tenant Review', 'badge-pending'],
        'pending_owner_review'  => ['Pending Owner Review',  'badge-pending'],
        'accepted'              => ['Accepted',              'badge-published'],
        'enrolled'              => ['Enrolled',              'badge-enrolled'],
        'rejected_by_tenant'    => ['Rejected',              'badge-rejected'],
        'rejected_by_owner'     => ['Rejected',              'badge-rejected'],
        'withdrawn'             => ['Withdrawn',             'badge-closed'],
    ];
    [$label, $cls] = $map[$s] ?? [$s, 'badge-default'];
    return '<span class="badge ' . $cls . '">' . $label . '</span>';
}
?>

<?php if ($detailApp): // â”€â”€ DETAIL VIEW â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
?>

<a href="<?= APP_URL ?>/pages/applications.php" class="btn btn-ghost btn-sm" style="margin-bottom:16px;">â† Back to Applications</a>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">

  <!-- Left: Applicant Profile Card -->
  <div>
    <div class="card" style="margin-bottom:20px;">
      <div class="profile-header" style="background:linear-gradient(135deg,var(--accent-light),transparent);">
        <?php if (!empty($detailApp['avatar_path']) && file_exists(APP_ROOT . '/' . $detailApp['avatar_path'])): ?>
          <img src="<?= APP_URL . '/' . htmlspecialchars($detailApp['avatar_path']) ?>" class="profile-avatar-lg" alt="Avatar" style="border-radius:50%;object-fit:cover;">
        <?php else: ?>
          <div class="profile-avatar-lg"><?= strtoupper(substr($detailApp['applicant_name'], 0, 1)) ?></div>
        <?php endif; ?>
        <div class="profile-details">
          <h2><?= htmlspecialchars($detailApp['applicant_name']) ?></h2>
          <p style="color:var(--text-tertiary);"><?= htmlspecialchars($detailApp['applicant_email']) ?></p>
          <?php $roleLabel = match($detailApp['applicant_role'] ?? 'student') {
              'tenant'  => 'House Manager', 'owner' => 'Owner',
              'student' => 'Applicant', 'admin' => 'Admin', default => 'User'
          }; ?>
          <span class="badge badge-published" style="margin-top:6px;"><?= $roleLabel ?></span>
        </div>
      </div>

      <div class="profile-grid">
        <?php if ($detailApp['student_id']): ?>
        <div class="profile-field">
          <label>Student ID</label>
          <div class="value"><?= htmlspecialchars($detailApp['student_id']) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($detailApp['department']): ?>
        <div class="profile-field">
          <label>Department</label>
          <div class="value"><?= htmlspecialchars($detailApp['department']) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($detailApp['year_of_study']): ?>
        <div class="profile-field">
          <label>Year of Study</label>
          <div class="value">Year <?= $detailApp['year_of_study'] ?></div>
        </div>
        <?php endif; ?>
        <?php if ($detailApp['gender']): ?>
        <div class="profile-field">
          <label>Gender</label>
          <div class="value"><?= ucfirst($detailApp['gender']) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($detailApp['applicant_phone'])): ?>
        <div class="profile-field">
          <label>Phone</label>
          <div class="value"><?= htmlspecialchars($detailApp['applicant_phone']) ?></div>
        </div>
        <?php endif; ?>
        <div class="profile-field">
          <label>Applied On</label>
          <div class="value"><?= date('F j, Y', strtotime($detailApp['applied_at'])) ?></div>
        </div>
      </div>

      <?php if (!empty($detailApp['applicant_bio'])): ?>
      <div style="padding:0 24px 20px;">
        <div class="profile-field">
          <label>Bio</label>
          <div class="value" style="font-weight:400;"><?= nl2br(htmlspecialchars($detailApp['applicant_bio'])) ?></div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($detailApp['cover_message']): ?>
      <div style="padding:0 24px 20px;">
        <div class="profile-field">
          <label>Cover Message</label>
          <div class="value" style="font-weight:400;font-style:italic;color:var(--text-secondary);">
            "<?= nl2br(htmlspecialchars($detailApp['cover_message'])) ?>"
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Verification Documents -->
    <?php if ($detailDocs): ?>
    <div class="card">
      <div class="card-body">
        <h3 style="margin-bottom:16px;">Identity Documents</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;">
          <?php
          $docs = [
            'ID Card (Front)' => $detailDocs['id_card_front'],
            'ID Card (Back)'  => $detailDocs['id_card_back'],
            'Selfie Photo'    => $detailDocs['selfie_path'],
          ];
          foreach ($docs as $label => $path):
            if ($path && file_exists(APP_ROOT . '/' . $path)):
          ?>
          <div>
            <div style="font-size:0.75rem;color:var(--text-tertiary);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.05em;"><?= $label ?></div>
            <a href="<?= APP_URL . '/' . htmlspecialchars($path) ?>" target="_blank">
              <img src="<?= APP_URL . '/' . htmlspecialchars($path) ?>" alt="<?= $label ?>"
                   style="width:100%;border-radius:var(--radius-sm);border:1px solid var(--border);cursor:pointer;transition:transform 0.2s;"
                   onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
            </a>
          </div>
          <?php endif; endforeach; ?>
          <?php if ($detailDocs['video_path'] && file_exists(APP_ROOT . '/' . $detailDocs['video_path'])): ?>
          <div>
            <div style="font-size:0.75rem;color:var(--text-tertiary);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.05em;">Verification Video</div>
            <video controls style="width:100%;border-radius:var(--radius-sm);border:1px solid var(--border);" preload="metadata">
              <source src="<?= APP_URL . '/' . htmlspecialchars($detailDocs['video_path']) ?>">
            </video>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Right: Application Summary + Actions -->
  <div>
    <div class="card card-glass" style="position:sticky;top:80px;">
      <div class="card-body">
        <h3 style="margin-bottom:16px;">Application Details</h3>
        <div style="display:flex;flex-direction:column;gap:12px;font-size:0.875rem;">
          <div><strong>Listing:</strong><br><?= htmlspecialchars($detailApp['listing_title']) ?></div>
          <div><strong>Property:</strong><br><?= htmlspecialchars($detailApp['property_name']) ?></div>
          <div><strong>Room:</strong> <?= htmlspecialchars($detailApp['room_number']) ?></div>
          <div><strong>Rent:</strong> <span style="color:var(--accent);font-weight:700;">৳<?= number_format($detailApp['rent_amount']) ?>/mo</span></div>
          <div><strong>Type:</strong>
            <?= $detailApp['listing_type'] === 'roommate_needed' ? ' Roommate Needed' : 'ðŸ  Owner Direct' ?>
          </div>
          <div><strong>Status:</strong> <?= appStatusBadge($detailApp['status']) ?></div>
          <div><strong>Listed by:</strong> <?= htmlspecialchars($detailApp['listing_owner_name']) ?></div>
        </div>

        <!-- Action buttons for owner/house-manager -->
        <?php if (hasAnyRole(['owner','tenant','admin']) && $detailApp['created_by'] == $uid): ?>
        <div style="margin-top:20px;display:flex;flex-direction:column;gap:10px;">
          <?php if (str_contains($detailApp['status'], 'pending')): ?>
            <button class="btn btn-success" style="width:100%;" onclick="updateStatus(<?= $appId ?>, 'enrolled')">
              Enroll Applicant
            </button>
            <button class="btn btn-danger" style="width:100%;" onclick="updateStatus(<?= $appId ?>, 'rejected_by_owner')">
              âŒ Reject Application
            </button>
          <?php elseif ($detailApp['status'] === 'enrolled'): ?>
            <div style="padding:10px;background:var(--success-light);color:var(--success);border-radius:var(--radius-sm);text-align:center;">
              Enrolled
            </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Withdraw button for applicant -->
        <?php if ($detailApp['applicant_id'] == $uid && str_contains($detailApp['status'], 'pending')): ?>
        <div style="margin-top:20px;">
          <button class="btn btn-ghost" style="width:100%;color:var(--danger);" onclick="withdrawApp(<?= $appId ?>)">
            ðŸ—‘ï¸ Withdraw Application
          </button>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
async function updateStatus(appId, status) {
    if (!confirm('Confirm status change?')) return;
    try {
        const data = await fetchAPI(`${APP_URL}/api/applications.php`, {
            method: 'PUT',
            body: JSON.stringify({ application_id: appId, status })
        });
        if (data.success) { Toast.show('Status updated!', 'success'); setTimeout(() => location.reload(), 800); }
        else Toast.show(data.error || 'Failed', 'error');
    } catch(e) {}
}

async function withdrawApp(id) {
    if (!confirm('Withdraw this application?')) return;
    await fetchAPI(`${APP_URL}/api/applications.php`, {
        method: 'DELETE',
        body: JSON.stringify({ application_id: id })
    });
    Toast.show('Application withdrawn', 'info');
    setTimeout(() => window.location.href = `${APP_URL}/pages/applications.php`, 800);
}
</script>

<?php else: // â”€â”€ LIST VIEW â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
?>

<!-- Tabs: Incoming vs My Applications -->
<?php if (hasAnyRole(['owner','tenant']) && hasAnyRole(['student','tenant'])): ?>
<div class="tabs">
    <button class="tab-btn <?= !isset($_GET['tab']) || $_GET['tab'] === 'incoming' ? 'active' : '' ?>"
            onclick="switchTab('incoming')">Incoming Applications</button>
    <button class="tab-btn <?= isset($_GET['tab']) && $_GET['tab'] === 'my' ? 'active' : '' ?>"
            onclick="switchTab('my')">My Applications</button>
</div>
<?php endif; ?>

<!-- Incoming Applications (owner / house manager) -->
<?php if (hasAnyRole(['owner','tenant','admin']) && (!isset($_GET['tab']) || $_GET['tab'] === 'incoming')): ?>
<div id="incomingTab">
  <div class="section-header">
    <h2>Incoming Applications</h2>
    <span style="color:var(--text-tertiary);font-size:0.9rem;"><?= count($incomingApps) ?> total</span>
  </div>

  <?php if (empty($incomingApps)): ?>
  <div class="empty-state">
    <div class="empty-state-icon"></div>
    <h3>No applications yet</h3>
    <p>Applications for your listings will appear here.</p>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:14px;">
    <?php foreach ($incomingApps as $app): ?>
    <div class="card app-card" onclick="window.location='<?= APP_URL ?>/pages/applications.php?app_id=<?= $app['id'] ?>'" style="cursor:pointer;">
      <div class="card-body" style="display:flex;align-items:center;gap:16px;">
        <div class="profile-avatar-lg" style="width:48px;height:48px;font-size:1.2rem;flex-shrink:0;">
          <?= strtoupper(substr($app['applicant_name'], 0, 1)) ?>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:600;font-size:0.95rem;"><?= htmlspecialchars($app['applicant_name']) ?></div>
          <div style="font-size:0.8rem;color:var(--text-tertiary);">
            <?= htmlspecialchars($app['applicant_email']) ?>
            <?= $app['department'] ? ' · ' . htmlspecialchars($app['department']) : '' ?>
            <?= $app['year_of_study'] ? ' · Year ' . $app['year_of_study'] : '' ?>
          </div>
          <div style="font-size:0.85rem;margin-top:4px;">
            ðŸ¢ <strong><?= htmlspecialchars($app['property_name']) ?></strong>
            · <?= htmlspecialchars($app['listing_title']) ?>
          </div>
        </div>
        <div style="text-align:right;flex-shrink:0;">
          <?= appStatusBadge($app['status']) ?>
          <div style="font-size:0.75rem;color:var(--text-tertiary);margin-top:4px;">
            <?= date('M j, Y', strtotime($app['applied_at'])) ?>
          </div>
          <div style="margin-top:8px;font-weight:700;color:var(--accent);">৳<?= number_format($app['rent_amount']) ?>/mo</div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- My Submitted Applications (student / applicant) -->
<?php if (hasAnyRole(['student','tenant']) && isset($_GET['tab']) && $_GET['tab'] === 'my'): ?>
<div id="myTab">
  <div class="section-header">
    <h2>My Applications</h2>
    <span style="color:var(--text-tertiary);font-size:0.9rem;"><?= count($myApps) ?> total</span>
  </div>

  <?php if (empty($myApps)): ?>
  <div class="empty-state">
    <div class="empty-state-icon"></div>
    <h3>No applications yet</h3>
    <p>Browse listings and apply for a room to see them here.</p>
    <a href="<?= APP_URL ?>/pages/dashboard.php" class="btn btn-primary" style="margin-top:16px;">Browse Listings</a>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:14px;">
    <?php foreach ($myApps as $app): ?>
    <div class="card app-card" onclick="window.location='<?= APP_URL ?>/pages/applications.php?app_id=<?= $app['id'] ?>'" style="cursor:pointer;">
      <div class="card-body" style="display:flex;align-items:center;gap:16px;">
        <div style="font-size:2.5rem;opacity:0.5;">ðŸ </div>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:600;font-size:0.95rem;"><?= htmlspecialchars($app['listing_title']) ?></div>
          <div style="font-size:0.8rem;color:var(--text-tertiary);">
            ðŸ¢ <?= htmlspecialchars($app['property_name']) ?> · Room <?= htmlspecialchars($app['room_number']) ?>
          </div>
        </div>
        <div style="text-align:right;flex-shrink:0;">
          <?= appStatusBadge($app['status']) ?>
          <div style="font-size:0.75rem;color:var(--text-tertiary);margin-top:4px;">
            <?= date('M j, Y', strtotime($app['applied_at'])) ?>
          </div>
          <div style="margin-top:8px;font-weight:700;color:var(--accent);">৳<?= number_format($app['rent_amount']) ?>/mo</div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php elseif (hasRole('student') && !isset($_GET['tab'])): ?>
<!-- Student-only view: just show their apps directly -->
<div class="section-header">
  <h2>My Applications</h2>
  <span style="color:var(--text-tertiary);font-size:0.9rem;"><?= count($myApps) ?> total</span>
</div>
<?php if (empty($myApps)): ?>
<div class="empty-state">
  <div class="empty-state-icon"></div>
  <h3>No applications yet</h3>
  <p>Browse listings and apply for a room to see them here.</p>
  <a href="<?= APP_URL ?>/pages/dashboard.php" class="btn btn-primary" style="margin-top:16px;">Browse Listings</a>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:14px;">
  <?php foreach ($myApps as $app): ?>
  <div class="card app-card" onclick="window.location='<?= APP_URL ?>/pages/applications.php?app_id=<?= $app['id'] ?>'" style="cursor:pointer;">
    <div class="card-body" style="display:flex;align-items:center;gap:16px;">
      <div style="font-size:2.5rem;opacity:0.5;">ðŸ </div>
      <div style="flex:1;min-width:0;">
        <div style="font-weight:600;font-size:0.95rem;"><?= htmlspecialchars($app['listing_title']) ?></div>
        <div style="font-size:0.8rem;color:var(--text-tertiary);">
          ðŸ¢ <?= htmlspecialchars($app['property_name']) ?> · Room <?= htmlspecialchars($app['room_number']) ?>
        </div>
      </div>
      <div style="text-align:right;flex-shrink:0;">
        <?= appStatusBadge($app['status']) ?>
        <div style="font-size:0.75rem;color:var(--text-tertiary);margin-top:4px;">
          <?= date('M j, Y', strtotime($app['applied_at'])) ?>
        </div>
        <div style="margin-top:8px;font-weight:700;color:var(--accent);">৳<?= number_format($app['rent_amount']) ?>/mo</div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<script>
function switchTab(tab) {
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.location.href = url.toString();
}
</script>

<?php endif; // end list/detail toggle ?>

<style>
.app-card { transition: all 0.2s; }
.app-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); border-color: var(--accent); }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

