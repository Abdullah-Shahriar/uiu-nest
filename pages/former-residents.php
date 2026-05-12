<?php
/**
 * UIU Nest — Former Residents (History Page)
 * Shows past tenants with property/room info and a link to their profile.
 */
$pageName = 'Former Residents';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$db = getDB();

// Fetch all move-out records with user + property + room info
$stmt = $db->prepare(
    'SELECT rt.id AS tenure_id,
            rt.moved_in_at, rt.moved_out_at,
            u.id AS user_id, u.full_name, u.email, u.student_id,
            u.department, u.year_of_study, u.gender, u.avatar_path, u.role,
            r.room_number, r.rent_amount,
            p.id AS property_id, p.name AS property_name, p.address
     FROM room_tenants rt
     JOIN users u      ON u.id  = rt.user_id
     JOIN rooms r      ON r.id  = rt.room_id
     JOIN properties p ON p.id  = r.property_id
     WHERE rt.moved_out_at IS NOT NULL
     ORDER BY rt.moved_out_at DESC'
);
$stmt->execute();
$records = $stmt->fetchAll();

// Owners can only see their own property history
if (hasRole('owner') && !hasRole('admin')) {
    $records = array_filter($records, function($r) use ($db) {
        $s = $db->prepare('SELECT owner_id FROM properties WHERE id = ?');
        $s->execute([$r['property_id']]);
        $p = $s->fetch();
        return $p && $p['owner_id'] == $_SESSION['user_id'];
    });
}

// Group by property
$byProperty = [];
foreach ($records as $r) {
    $byProperty[$r['property_id']]['name']    = $r['property_name'];
    $byProperty[$r['property_id']]['address'] = $r['address'];
    $byProperty[$r['property_id']]['tenants'][] = $r;
}

// Search / filter
$search = trim($_GET['q'] ?? '');
if ($search) {
    $sq = strtolower($search);
    $filteredRecords = array_filter($records, fn($r) =>
        str_contains(strtolower($r['full_name']), $sq)
     || str_contains(strtolower($r['email']), $sq)
     || str_contains(strtolower($r['student_id'] ?? ''), $sq)
     || str_contains(strtolower($r['property_name']), $sq)
    );
} else {
    $filteredRecords = $records;
}

function roleLabel(string $role): string {
    return match($role) {
        'tenant'  => 'House Manager',
        'owner'   => 'Owner',
        'student' => 'Applicant',
        'admin'   => 'Admin',
        default   => ucfirst($role),
    };
}
?>

<div class="section-header" style="margin-bottom:20px;">
  <h2>🕰️ Former Residents</h2>
  <span style="color:var(--text-tertiary);font-size:0.9rem;"><?= count($records) ?> records</span>
</div>

<!-- Search Bar -->
<div class="filter-bar" style="margin-bottom:24px;">
  <input type="text" class="form-control" id="residentSearch" placeholder="🔍 Search by name, email, student ID, or property..." value="<?= htmlspecialchars($search) ?>" oninput="filterResidents(this.value)" style="max-width:460px;">
  <select class="form-control" id="viewToggle" onchange="toggleView(this.value)" style="width:auto;">
    <option value="cards">Card View</option>
    <option value="table">Table View</option>
  </select>
</div>

<?php if (empty($records)): ?>
<div class="empty-state">
  <div class="empty-state-icon">🕰️</div>
  <h3>No former residents yet</h3>
  <p>Once tenants move out, their history will appear here.</p>
</div>
<?php else: ?>

<!-- ── CARD VIEW ── -->
<div id="cardView" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
  <?php foreach ($filteredRecords as $r): ?>
  <div class="card resident-card" data-name="<?= strtolower($r['full_name']) ?>" data-email="<?= strtolower($r['email']) ?>" data-sid="<?= strtolower($r['student_id'] ?? '') ?>" data-prop="<?= strtolower($r['property_name']) ?>">
    <div class="card-body">
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;">
        <!-- Avatar -->
        <?php if (!empty($r['avatar_path']) && file_exists(APP_ROOT . '/' . $r['avatar_path'])): ?>
          <img src="<?= APP_URL . '/' . htmlspecialchars($r['avatar_path']) ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover;" alt="Avatar">
        <?php else: ?>
          <div class="profile-avatar-lg" style="width:48px;height:48px;font-size:1.1rem;"><?= strtoupper(substr($r['full_name'], 0, 1)) ?></div>
        <?php endif; ?>
        <div>
          <div style="font-weight:600;"><?= htmlspecialchars($r['full_name']) ?></div>
          <div style="font-size:0.8rem;color:var(--text-tertiary);"><?= htmlspecialchars($r['email']) ?></div>
          <span class="badge badge-closed" style="margin-top:4px;"><?= roleLabel($r['role']) ?></span>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:8px;font-size:0.85rem;border-top:1px solid var(--border);padding-top:12px;">
        <div>🏢 <strong><?= htmlspecialchars($r['property_name']) ?></strong></div>
        <div>🚪 Room <?= htmlspecialchars($r['room_number']) ?></div>
        <?php if ($r['department']): ?>
        <div>🎓 <?= htmlspecialchars($r['department']) ?><?= $r['year_of_study'] ? ' · Year ' . $r['year_of_study'] : '' ?></div>
        <?php endif; ?>
        <?php if ($r['student_id']): ?>
        <div>🪪 <?= htmlspecialchars($r['student_id']) ?></div>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between;color:var(--text-tertiary);">
          <span>📅 In: <?= date('M j, Y', strtotime($r['moved_in_at'])) ?></span>
          <span>Out: <?= date('M j, Y', strtotime($r['moved_out_at'])) ?></span>
        </div>
        <?php
          $inDate  = new DateTime($r['moved_in_at']);
          $outDate = new DateTime($r['moved_out_at']);
          $diff    = $inDate->diff($outDate);
          $stayed  = $diff->m + ($diff->y * 12) . ' month' . ($diff->m !== 1 ? 's' : '');
        ?>
        <div style="color:var(--accent);font-size:0.8rem;">⏱️ Stayed <?= $stayed ?></div>
      </div>

      <div style="margin-top:14px;">
        <a href="<?= APP_URL ?>/pages/resident-profile.php?user_id=<?= $r['user_id'] ?>" class="btn btn-outline btn-sm" style="width:100%;">
          👤 View Profile
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── TABLE VIEW ── -->
<div id="tableView" style="display:none;">
  <div class="card">
    <div class="card-body" style="padding:0;">
      <table class="data-table" id="residentsTable">
        <thead>
          <tr>
            <th>Resident</th>
            <th>Student ID</th>
            <th>Dept / Year</th>
            <th>Property</th>
            <th>Room</th>
            <th>Moved In</th>
            <th>Moved Out</th>
            <th>Duration</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($filteredRecords as $r):
            $inDate  = new DateTime($r['moved_in_at']);
            $outDate = new DateTime($r['moved_out_at']);
            $diff    = $inDate->diff($outDate);
            $stayed  = $diff->m + ($diff->y * 12) . ' mo';
          ?>
          <tr class="resident-row"
              data-name="<?= strtolower($r['full_name']) ?>"
              data-email="<?= strtolower($r['email']) ?>"
              data-sid="<?= strtolower($r['student_id'] ?? '') ?>"
              data-prop="<?= strtolower($r['property_name']) ?>">
            <td>
              <strong><?= htmlspecialchars($r['full_name']) ?></strong><br>
              <small style="color:var(--text-tertiary);"><?= htmlspecialchars($r['email']) ?></small>
            </td>
            <td><?= htmlspecialchars($r['student_id'] ?? '—') ?></td>
            <td><?= $r['department'] ? htmlspecialchars($r['department']) . ($r['year_of_study'] ? ' Y' . $r['year_of_study'] : '') : '—' ?></td>
            <td><?= htmlspecialchars($r['property_name']) ?></td>
            <td><?= htmlspecialchars($r['room_number']) ?></td>
            <td><?= date('M j, Y', strtotime($r['moved_in_at'])) ?></td>
            <td><?= date('M j, Y', strtotime($r['moved_out_at'])) ?></td>
            <td><?= $stayed ?></td>
            <td>
              <a href="<?= APP_URL ?>/pages/resident-profile.php?user_id=<?= $r['user_id'] ?>" class="btn btn-sm btn-ghost">View</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php endif; ?>

<script>
function filterResidents(query) {
    const q = query.toLowerCase();
    // Cards
    document.querySelectorAll('.resident-card').forEach(c => {
        const match = c.dataset.name.includes(q) || c.dataset.email.includes(q)
                   || c.dataset.sid.includes(q)  || c.dataset.prop.includes(q);
        c.style.display = match ? '' : 'none';
    });
    // Table rows
    document.querySelectorAll('.resident-row').forEach(r => {
        const match = r.dataset.name.includes(q) || r.dataset.email.includes(q)
                   || r.dataset.sid.includes(q)  || r.dataset.prop.includes(q);
        r.style.display = match ? '' : 'none';
    });
}

function toggleView(val) {
    document.getElementById('cardView').style.display  = val === 'cards' ? 'grid' : 'none';
    document.getElementById('tableView').style.display = val === 'table' ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
