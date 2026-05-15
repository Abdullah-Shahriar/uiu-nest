<?php
/** UIU Nest — My Profile (role-aware) */
$pageName = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$db  = getDB();
$uid = (int)$_SESSION['user_id'];

$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch();

// For house managers: fetch current room/property
$currentRoom = null;
if (hasRole('tenant')) {
    $stmt = $db->prepare(
        'SELECT rt.*, r.room_number, r.rent_amount, r.capacity,
                p.name AS property_name, p.address, p.id AS property_id
         FROM room_tenants rt
         JOIN rooms r ON r.id = rt.room_id
         JOIN properties p ON p.id = r.property_id
         WHERE rt.user_id = ? AND rt.moved_out_at IS NULL LIMIT 1'
    );
    $stmt->execute([$uid]);
    $currentRoom = $stmt->fetch();
}

// For owners: fetch property count & room count
$ownerStats = null;
if (hasRole('owner')) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM properties WHERE owner_id = ? AND is_active = 1');
    $stmt->execute([$uid]);
    $propCount = $stmt->fetchColumn();

    $stmt = $db->prepare(
        'SELECT COUNT(r.id) FROM rooms r JOIN properties p ON p.id = r.property_id
         WHERE p.owner_id = ? AND r.is_active = 1'
    );
    $stmt->execute([$uid]);
    $roomCount = $stmt->fetchColumn();

    $stmt = $db->prepare(
        'SELECT COUNT(l.id) FROM listings l
         JOIN rooms r ON r.id = l.room_id
         JOIN properties p ON p.id = r.property_id
         WHERE p.owner_id = ? AND l.status = "published" AND l.deleted_at IS NULL'
    );
    $stmt->execute([$uid]);
    $activeListings = $stmt->fetchColumn();

    $ownerStats = ['properties' => $propCount, 'rooms' => $roomCount, 'listings' => $activeListings];
}

// For students/applicants: fetch application count
$appCount = 0;
if (hasAnyRole(['student', 'tenant'])) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM applications WHERE applicant_id = ? AND deleted_at IS NULL');
    $stmt->execute([$uid]);
    $appCount = (int)$stmt->fetchColumn();
}

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

<div class="profile-page">

  <!-- Profile Header Card -->
  <div class="card profile-hero" style="margin-bottom:24px;overflow:hidden;">
    <div class="profile-hero-bg"></div>
    <div class="profile-hero-content">
      <!-- Avatar -->
      <div class="profile-avatar-wrap">
        <?php if (!empty($user['avatar_path']) && file_exists(APP_ROOT . '/' . $user['avatar_path'])): ?>
          <img src="<?= APP_URL . '/' . htmlspecialchars($user['avatar_path']) ?>" class="profile-avatar-xl" alt="Avatar">
        <?php else: ?>
          <div class="profile-avatar-xl profile-avatar-initials">
            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
          </div>
        <?php endif; ?>
        <label class="avatar-edit-btn" title="Change photo" for="avatarUpload">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
        </label>
        <input type="file" id="avatarUpload" accept="image/*" style="display:none" onchange="uploadAvatar(this)">
      </div>

      <!-- Name + Role -->
      <div class="profile-hero-info">
        <h2 class="profile-hero-name"><?= htmlspecialchars($user['full_name']) ?></h2>
        <span class="badge <?= $roleBadgeClass ?>" style="font-size:0.8rem;"><?= $roleLabel ?></span>
        <?php if (!empty($user['department'])): ?>
          <span style="font-size:0.85rem;color:var(--text-tertiary);margin-left:8px;display:inline-flex;align-items:center;gap:4px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            <?= htmlspecialchars($user['department']) ?>
            <?= $user['year_of_study'] ? ' · Year ' . $user['year_of_study'] : '' ?>
          </span>
        <?php endif; ?>
        <p style="margin-top:8px;font-size:0.9rem;color:var(--text-tertiary);">
          <?= htmlspecialchars($user['bio'] ?? 'No bio yet.') ?>
        </p>
      </div>

      <!-- Edit Button -->
      <button class="btn btn-outline btn-sm" onclick="Modal.open('editProfileModal')" style="margin-left:auto;align-self:start;display:flex;align-items:center;gap:6px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Edit Profile
      </button>
    </div>
  </div>

  <!-- Stats Row -->
  <div class="stats-grid" style="margin-bottom:24px;">
    <?php if ($ownerStats): ?>
      <div class="stat-card">
        <div class="stat-value"><?= $ownerStats['properties'] ?></div>
        <div class="stat-label">Properties</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $ownerStats['rooms'] ?></div>
        <div class="stat-label">Total Rooms</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $ownerStats['listings'] ?></div>
        <div class="stat-label">Active Listings</div>
      </div>
    <?php else: ?>
      <div class="stat-card">
        <div class="stat-value"><?= $appCount ?></div>
        <div class="stat-label">Applications</div>
      </div>
      <?php if ($user['student_id']): ?>
      <div class="stat-card">
        <div class="stat-value" style="font-size:1.2rem;"><?= htmlspecialchars($user['student_id']) ?></div>
        <div class="stat-label">Student ID</div>
      </div>
      <?php endif; ?>
      <?php if ($user['gender']): ?>
      <div class="stat-card">
        <div class="stat-value" style="font-size:1.2rem;"><?= ucfirst($user['gender']) ?></div>
        <div class="stat-label">Gender</div>
      </div>
      <?php endif; ?>
    <?php endif; ?>
    <div class="stat-card">
      <div class="stat-value" style="font-size:1.1rem;"><?= date('M Y', strtotime($user['created_at'])) ?></div>
      <div class="stat-label">Member Since</div>
    </div>
  </div>

  <!-- Two-column detail layout -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">

    <!-- Contact & Info -->
    <div class="card">
      <div class="card-body">
        <h3 style="margin-bottom:18px;display:flex;align-items:center;gap:8px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;color:var(--accent);"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
          Personal Information
        </h3>
        <div style="display:flex;flex-direction:column;gap:14px;">
          <div class="profile-field">
            <label>Full Name</label>
            <div class="value"><?= htmlspecialchars($user['full_name']) ?></div>
          </div>
          <div class="profile-field">
            <label>Email</label>
            <div class="value"><?= htmlspecialchars($user['email']) ?></div>
          </div>
          <?php if ($user['phone']): ?>
          <div class="profile-field">
            <label>Phone</label>
            <div class="value"><?= htmlspecialchars($user['phone']) ?></div>
          </div>
          <?php endif; ?>
          <div class="profile-field">
            <label>Role</label>
            <div class="value"><span class="badge <?= $roleBadgeClass ?>"><?= $roleLabel ?></span></div>
          </div>
          <?php if ($user['student_id']): ?>
          <div class="profile-field">
            <label>Student ID</label>
            <div class="value"><?= htmlspecialchars($user['student_id']) ?></div>
          </div>
          <?php endif; ?>
          <?php if ($user['department']): ?>
          <div class="profile-field">
            <label>Department</label>
            <div class="value"><?= htmlspecialchars($user['department']) ?></div>
          </div>
          <?php endif; ?>
          <?php if ($user['year_of_study']): ?>
          <div class="profile-field">
            <label>Year of Study</label>
            <div class="value">Year <?= $user['year_of_study'] ?></div>
          </div>
          <?php endif; ?>
          <?php if ($user['gender']): ?>
          <div class="profile-field">
            <label>Gender</label>
            <div class="value"><?= ucfirst($user['gender']) ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Right column: current accommodation or owner info -->
    <?php if ($currentRoom): ?>
    <div class="card">
      <div class="card-body">
        <h3 style="margin-bottom:18px;display:flex;align-items:center;gap:8px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;color:var(--accent);"><path d="M3 9.5L12 3l9 6.5V21H3V9.5z"/><rect x="9" y="14" width="6" height="7" rx="1"/></svg>
          Current Accommodation
        </h3>
        <div style="display:flex;flex-direction:column;gap:14px;">
          <div class="profile-field">
            <label>Property</label>
            <div class="value"><?= htmlspecialchars($currentRoom['property_name']) ?></div>
          </div>
          <div class="profile-field">
            <label>Address</label>
            <div class="value"><?= htmlspecialchars($currentRoom['address']) ?></div>
          </div>
          <div class="profile-field">
            <label>Room</label>
            <div class="value">Room <?= htmlspecialchars($currentRoom['room_number']) ?></div>
          </div>
          <div class="profile-field">
            <label>Monthly Rent</label>
            <div class="value" style="color:var(--accent);font-weight:700;"><?= formatRent((float)$currentRoom['rent_amount']) ?></div>
          </div>
          <div class="profile-field">
            <label>Moved In</label>
            <div class="value"><?= date('F j, Y', strtotime($currentRoom['moved_in_at'])) ?></div>
          </div>
        </div>
      </div>
    </div>
    <?php elseif ($ownerStats): ?>
    <div class="card">
      <div class="card-body">
        <h3 style="margin-bottom:18px;display:flex;align-items:center;gap:8px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;color:var(--accent);"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
          Owner Summary
        </h3>
        <p style="color:var(--text-tertiary);font-size:0.9rem;margin-bottom:16px;">
          Manage your properties and listings from the sidebar.
        </p>
        <a href="<?= APP_URL ?>/pages/manage-properties.php" class="btn btn-primary" style="width:100%;margin-bottom:10px;display:flex;align-items:center;justify-content:center;gap:6px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
          My Properties
        </a>
        <a href="<?= APP_URL ?>/pages/manage-listings.php" class="btn btn-outline" style="width:100%;display:flex;align-items:center;justify-content:center;gap:6px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          My Listings
        </a>
      </div>
    </div>
    <?php else: ?>
    <div class="card">
      <div class="card-body">
        <h3 style="margin-bottom:18px;display:flex;align-items:center;gap:8px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;color:var(--accent);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          Find Housing
        </h3>
        <p style="color:var(--text-tertiary);font-size:0.9rem;margin-bottom:16px;">
          Browse available listings near UIU campus and apply for a room.
        </p>
        <a href="<?= APP_URL ?>/pages/dashboard.php" class="btn btn-primary" style="width:100%;margin-bottom:10px;display:flex;align-items:center;justify-content:center;gap:6px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><path d="M3 9.5L12 3l9 6.5V21H3V9.5z"/></svg>
          Browse Listings
        </a>
        <a href="<?= APP_URL ?>/pages/applications.php" class="btn btn-outline" style="width:100%;display:flex;align-items:center;justify-content:center;gap:6px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
          My Applications
        </a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal-overlay" id="editProfileModal">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <h3>Edit Profile</h3>
      <button class="modal-close" onclick="Modal.close('editProfileModal')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:14px;height:14px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input class="form-control" id="epName" value="<?= htmlspecialchars($user['full_name']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Phone</label>
        <input class="form-control" id="epPhone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+8801...">
      </div>
      <div class="form-group">
        <label class="form-label">Bio</label>
        <textarea class="form-control" id="epBio" rows="3" placeholder="Tell others about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
      </div>
      <?php if (hasAnyRole(['student','tenant'])): ?>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Department</label>
          <select class="form-control" id="epDept">
            <option value="">Select</option>
            <?php foreach (['CSE','EEE','BBA','Civil','Economics','English'] as $d): ?>
            <option value="<?= $d ?>" <?= $user['department'] === $d ? 'selected' : '' ?>><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Year of Study</label>
          <select class="form-control" id="epYear">
            <option value="">Select</option>
            <?php for ($y = 1; $y <= 4; $y++): ?>
            <option value="<?= $y ?>" <?= $user['year_of_study'] == $y ? 'selected' : '' ?>>Year <?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Gender</label>
        <select class="form-control" id="epGender">
          <option value="">Select</option>
          <option value="male"   <?= $user['gender'] === 'male'   ? 'selected' : '' ?>>Male</option>
          <option value="female" <?= $user['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
          <option value="other"  <?= $user['gender'] === 'other'  ? 'selected' : '' ?>>Other</option>
        </select>
      </div>
      <?php endif; ?>
      <div class="form-group">
        <label class="form-label">New Password <span style="color:var(--text-tertiary);font-weight:400;">(leave blank to keep current)</span></label>
        <input class="form-control" type="password" id="epPass" placeholder="Min 6 characters">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="Modal.close('editProfileModal')">Cancel</button>
      <button class="btn btn-primary" id="saveProfileBtn" onclick="saveProfile()">Save Changes</button>
    </div>
  </div>
</div>

<script>
async function saveProfile() {
    const btn = document.getElementById('saveProfileBtn');
    btn.disabled = true; btn.textContent = 'Saving...';
    try {
        const body = {
            full_name: document.getElementById('epName').value,
            phone:     document.getElementById('epPhone').value,
            bio:       document.getElementById('epBio').value,
            password:  document.getElementById('epPass')?.value || '',
        };
        <?php if (hasAnyRole(['student','tenant'])): ?>
        body.department    = document.getElementById('epDept').value;
        body.year_of_study = document.getElementById('epYear').value;
        body.gender        = document.getElementById('epGender').value;
        <?php endif; ?>

        const resp = await fetch(`${APP_URL}/api/profile.php`, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify(body)
        });
        const data = await resp.json();
        if (data.success) {
            Toast.show('Profile updated!', 'success');
            Modal.close('editProfileModal');
            setTimeout(() => location.reload(), 800);
        } else {
            Toast.show(data.error || 'Failed', 'error');
        }
    } catch(e) { Toast.show('Error saving', 'error'); }
    btn.disabled = false; btn.textContent = 'Save Changes';
}

async function uploadAvatar(input) {
    const file = input.files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('avatar', file);
    try {
        const resp = await fetch(`${APP_URL}/api/profile.php?action=avatar`, {
            method: 'POST',
            headers: {'X-Requested-With':'XMLHttpRequest'},
            body: fd
        });
        const data = await resp.json();
        if (data.success) {
            Toast.show('Avatar updated!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            Toast.show(data.error || 'Upload failed', 'error');
        }
    } catch(e) { Toast.show('Upload failed', 'error'); }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
