<?php
/**
 * UIU Nest — Public Resident Profile
 * Viewable by logged-in users. Shows a former resident's info and stay history.
 */
$pageName = 'Resident Profile';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$db     = getDB();
$userId = (int)($_GET['user_id'] ?? 0);
if (!$userId) { redirect(APP_URL . '/pages/former-residents.php'); }

$stmt = $db->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    echo '<div class="empty-state"><div class="empty-state-icon">👤</div><h3>Resident not found</h3></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Stay history
$stmt = $db->prepare(
    'SELECT rt.moved_in_at, rt.moved_out_at,
            r.room_number, r.rent_amount, r.capacity,
            p.name AS property_name, p.address, p.id AS property_id
     FROM room_tenants rt
     JOIN rooms r      ON r.id  = rt.room_id
     JOIN properties p ON p.id  = r.property_id
     WHERE rt.user_id = ?
     ORDER BY rt.moved_in_at DESC'
);
$stmt->execute([$userId]);
$history = $stmt->fetchAll();

$roleLabel = match($user['role']) {
    'tenant'  => 'House Manager',
    'owner'   => 'Owner',
    'student' => 'Applicant',
    'admin'   => 'Admin',
    default   => ucfirst($user['role']),
};
$roleBadgeClass = match($user['role']) {
    'tenant'  => 'badge-enrolled',
    'owner'   => 'badge-draft',
    'student' => 'badge-published',
    'admin'   => 'badge-pending',
    default   => 'badge-default',
};
?>

<a href="<?= APP_URL ?>/pages/former-residents.php" class="btn btn-ghost btn-sm" style="margin-bottom:16px;">← Back to Former Residents</a>

<div style="display:grid;grid-template-columns:340px 1fr;gap:24px;align-items:start;">

  <!-- Left: Profile Card -->
  <div>
    <div class="card" style="overflow:hidden;">
      <!-- Hero gradient banner -->
      <div style="height:100px;background:var(--accent-gradient);position:relative;">
        <div style="position:absolute;bottom:-40px;left:24px;">
          <?php if (!empty($user['avatar_path']) && file_exists(APP_ROOT . '/' . $user['avatar_path'])): ?>
            <img src="<?= APP_URL . '/' . htmlspecialchars($user['avatar_path']) ?>"
                 style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:4px solid var(--bg-secondary);" alt="Avatar">
          <?php else: ?>
            <div class="profile-avatar-xl" style="width:80px;height:80px;font-size:2rem;border:4px solid var(--bg-secondary);">
              <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card-body" style="padding-top:52px;">
        <h2 style="margin-bottom:4px;"><?= htmlspecialchars($user['full_name']) ?></h2>
        <span class="badge <?= $roleBadgeClass ?>"><?= $roleLabel ?></span>

        <?php if ($user['bio']): ?>
        <p style="margin-top:12px;font-size:0.9rem;color:var(--text-secondary);"><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
        <?php endif; ?>

        <div style="display:flex;flex-direction:column;gap:10px;margin-top:16px;font-size:0.875rem;">
          <div>📧 <?= htmlspecialchars($user['email']) ?></div>
          <?php if ($user['phone']): ?><div>📞 <?= htmlspecialchars($user['phone']) ?></div><?php endif; ?>
          <?php if ($user['student_id']): ?><div>🪪 <?= htmlspecialchars($user['student_id']) ?></div><?php endif; ?>
          <?php if ($user['department']): ?><div>🎓 <?= htmlspecialchars($user['department']) ?><?= $user['year_of_study'] ? ' · Year ' . $user['year_of_study'] : '' ?></div><?php endif; ?>
          <?php if ($user['gender']): ?><div>👤 <?= ucfirst($user['gender']) ?></div><?php endif; ?>
          <div>📅 Member since <?= date('F Y', strtotime($user['created_at'])) ?></div>
        </div>
      </div>
    </div>

    <!-- Stay Summary Card -->
    <div class="card" style="margin-top:16px;">
      <div class="card-body">
        <h3 style="margin-bottom:16px;">📊 Stay Summary</h3>
        <?php
          $totalMonths = 0;
          foreach ($history as $h) {
              if ($h['moved_out_at']) {
                  $in  = new DateTime($h['moved_in_at']);
                  $out = new DateTime($h['moved_out_at']);
                  $diff = $in->diff($out);
                  $totalMonths += $diff->m + ($diff->y * 12);
              }
          }
        ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="stat-card" style="text-align:center;">
            <div class="stat-value"><?= count($history) ?></div>
            <div class="stat-label">Stays</div>
          </div>
          <div class="stat-card" style="text-align:center;">
            <div class="stat-value"><?= $totalMonths ?></div>
            <div class="stat-label">Total Months</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Right: Stay History Timeline -->
  <div>
    <div class="section-header" style="margin-bottom:16px;">
      <h2>🏠 Residence History</h2>
    </div>

    <?php if (empty($history)): ?>
    <div class="empty-state">
      <div class="empty-state-icon">🏠</div>
      <h3>No residence history</h3>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:14px;">
      <?php foreach ($history as $h):
        $isCurrent = empty($h['moved_out_at']);
        $inDate    = new DateTime($h['moved_in_at']);
        $outDate   = $h['moved_out_at'] ? new DateTime($h['moved_out_at']) : new DateTime();
        $diff      = $inDate->diff($outDate);
        $duration  = $diff->m + ($diff->y * 12);
      ?>
      <div class="card" style="border-left:4px solid <?= $isCurrent ? 'var(--success)' : 'var(--border-strong)' ?>;">
        <div class="card-body">
          <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px;">
            <div>
              <h3 style="margin-bottom:4px;"><?= htmlspecialchars($h['property_name']) ?></h3>
              <div style="font-size:0.85rem;color:var(--text-tertiary);">📍 <?= htmlspecialchars($h['address']) ?></div>
            </div>
            <?php if ($isCurrent): ?>
              <span class="badge badge-published">Current</span>
            <?php else: ?>
              <span class="badge badge-closed">Past</span>
            <?php endif; ?>
          </div>
          <div style="display:flex;gap:20px;font-size:0.875rem;flex-wrap:wrap;">
            <div>🚪 Room <strong><?= htmlspecialchars($h['room_number']) ?></strong></div>
            <div>💰 <strong style="color:var(--accent);">৳<?= number_format($h['rent_amount']) ?>/mo</strong></div>
            <div>📅 <?= $inDate->format('M j, Y') ?> → <?= $isCurrent ? '<span class="badge badge-published">Present</span>' : (new DateTime($h['moved_out_at']))->format('M j, Y') ?></div>
            <div>⏱️ <?= $duration ?> month<?= $duration !== 1 ? 's' : '' ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
