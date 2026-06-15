<?php
/** UIU Nest — My Profile (role-aware) */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$pageName = 'My Profile';
require_once __DIR__ . '/../includes/header.php';


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

$hasAvatar = !empty($user['avatar_path']) && file_exists(APP_ROOT . '/' . $user['avatar_path']);
$avatarUrl = $hasAvatar ? APP_URL . '/' . htmlspecialchars($user['avatar_path']) : '';
?>

<style>
/* ═══════════════════════════════════════════════════════
   Profile Page — Complete Redesign
   ═══════════════════════════════════════════════════════ */

/* Hide legacy cover strip */
.profile-hero-bg { display: none !important; }

/* ── Hero card: left avatar column + right info column ── */
.profile-hero {
    border-top: 4px solid var(--accent) !important;
    overflow: visible !important;
}
.profile-hero-content {
    display: grid !important;
    grid-template-columns: auto 1fr auto !important;
    align-items: center !important;
    gap: 28px !important;
    padding: 28px 32px !important;
    margin-top: 0 !important;
    flex-wrap: nowrap !important;
}

/* Avatar — always 1:1 square, displayed circular */
.profile-avatar-wrap { position: relative; flex-shrink: 0; }
.profile-avatar-xl {
    width: 110px !important;
    height: 110px !important;
    border-radius: var(--radius-full) !important;
    border: 3px solid var(--border) !important;
    object-fit: cover !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 2.6rem !important;
    font-weight: 700 !important;
    color: #fff !important;
    background: var(--accent-gradient) !important;
    aspect-ratio: 1 / 1 !important;
    flex-shrink: 0 !important;
}

/* Avatar lightbox trigger */
.avatar-lightbox-trigger {
    display: block;
    border-radius: var(--radius-full);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    line-height: 0;
}
.avatar-lightbox-trigger:hover {
    transform: scale(1.04);
    box-shadow: 0 0 0 4px rgba(56,189,248,0.35), 0 8px 24px rgba(0,0,0,0.2);
}

/* Avatar edit button */
.avatar-edit-btn {
    position: absolute;
    bottom: 3px; right: 3px;
    width: 32px; height: 32px;
    border-radius: var(--radius-full);
    background: var(--bg-secondary);
    border: 2px solid var(--border-strong);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: all var(--transition);
    box-shadow: var(--shadow-sm);
    z-index: 2;
}
.avatar-edit-btn:hover { background: var(--accent); border-color: var(--accent); color: #fff; }

/* Info block */
.profile-hero-info { padding-top: 0 !important; min-width: 0; }
.profile-hero-name { font-size: 1.55rem; font-weight: 800; margin-bottom: 6px; letter-spacing: -0.02em; }
.profile-hero-meta {
    display: flex; align-items: center; flex-wrap: wrap; gap: 8px;
    margin-top: 4px;
}
.profile-hero-meta .badge { font-size: 0.78rem; }
.profile-hero-bio {
    margin-top: 10px;
    font-size: 0.88rem;
    color: var(--text-tertiary);
    line-height: 1.5;
    max-width: 500px;
}

/* Stats row — inline pills inside hero */
.profile-hero-stats {
    display: flex; gap: 20px; margin-top: 14px; flex-wrap: wrap;
}
.profile-stat-pill {
    display: flex; flex-direction: column; align-items: center;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 8px 20px;
    min-width: 72px;
    text-align: center;
    transition: border-color var(--transition), box-shadow var(--transition);
}
.profile-stat-pill:hover { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-light); }
.profile-stat-pill .psp-val {
    font-size: 1.25rem; font-weight: 800;
    color: var(--accent);
    line-height: 1;
}
.profile-stat-pill .psp-lbl {
    font-size: 0.68rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.06em;
    color: var(--text-tertiary);
    margin-top: 3px;
}

/* Edit profile button — align to top-right of hero */
.profile-hero-edit-btn {
    align-self: flex-start;
}

/* ── Details grid below hero ── */
.profile-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: start;
}

/* Profile field rows */
.profile-field {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
}
.profile-field:last-child { border-bottom: none; }
.profile-field label {
    font-size: 0.7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.08em;
    color: var(--text-tertiary);
}
.profile-field .value {
    font-size: 0.92rem; font-weight: 500;
    color: var(--text-primary);
}

/* ═══════════════════════════════════════════════════════
   Feature 1: Avatar Crop Modal
   ═══════════════════════════════════════════════════════ */
#avatarCropModal {
    position: fixed;
    inset: 0;
    z-index: 9000;
    background: rgba(4, 9, 16, 0.92);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}
#avatarCropModal.open { opacity: 1; visibility: visible; }
.crop-modal-inner {
    display: flex; flex-direction: column; align-items: center;
    gap: 20px; width: 100%; max-width: 420px; padding: 24px;
}
.crop-modal-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.2rem; font-weight: 700; color: #fff;
    letter-spacing: -0.01em;
}
.crop-stage {
    position: relative; width: 300px; height: 300px;
    border-radius: var(--radius); overflow: hidden;
    background: #111; touch-action: none; cursor: grab;
}
.crop-stage:active { cursor: grabbing; }
#cropCanvas { position: absolute; top: 0; left: 0; width: 300px; height: 300px; }
.crop-mask { position: absolute; inset: 0; pointer-events: none; z-index: 2; }
.crop-mask svg { width: 100%; height: 100%; }
.crop-zoom-wrap {
    width: 100%; display: flex; align-items: center; gap: 12px;
}
.crop-zoom-wrap svg { width: 16px; height: 16px; color: rgba(255,255,255,0.5); flex-shrink: 0; }
#cropZoom {
    flex: 1; -webkit-appearance: none; appearance: none;
    height: 4px; border-radius: 2px;
    background: rgba(255,255,255,0.15); outline: none; cursor: pointer;
}
#cropZoom::-webkit-slider-thumb {
    -webkit-appearance: none; appearance: none;
    width: 18px; height: 18px; border-radius: 50%;
    background: var(--accent);
    box-shadow: 0 0 0 3px rgba(56,189,248,0.25);
    cursor: pointer; transition: box-shadow 0.2s;
}
#cropZoom::-webkit-slider-thumb:hover { box-shadow: 0 0 0 5px rgba(56,189,248,0.35); }
.crop-actions { display: flex; gap: 12px; width: 100%; }
.crop-actions .btn { flex: 1; }
#cropSaveBtn {
    background: var(--accent-gradient); color: #fff;
    box-shadow: 0 4px 18px rgba(56,189,248,0.3);
}
#cropSaveBtn:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(56,189,248,0.45); }
#cropCancelBtn {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.7);
}
#cropCancelBtn:hover { background: rgba(255,255,255,0.12); color: #fff; }
/* Crop hint */
.crop-hint {
    font-size: 0.75rem; color: rgba(255,255,255,0.4);
    text-align: center; line-height: 1.4;
}

/* ═══════════════════════════════════════════════════════
   Feature 2: Avatar Lightbox
   ═══════════════════════════════════════════════════════ */
#avatarLightbox {
    position: fixed; inset: 0; z-index: 8500;
    background: rgba(4, 9, 16, 0.88);
    backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; visibility: hidden;
    transition: opacity 0.28s ease, visibility 0.28s ease;
    cursor: pointer;
}
#avatarLightbox.open { opacity: 1; visibility: visible; }
#avatarLightbox img {
    width: clamp(200px, 60vmin, 520px);
    height: clamp(200px, 60vmin, 520px);
    border-radius: var(--radius-full);
    box-shadow: 0 24px 80px rgba(0,0,0,0.7);
    object-fit: cover;
    transform: scale(0.85);
    transition: transform 0.32s cubic-bezier(0.34,1.56,0.64,1);
    cursor: default;
    border: 4px solid rgba(255,255,255,0.12);
}
#avatarLightbox.open img { transform: scale(1); }
.lightbox-close {
    position: absolute; top: 20px; right: 20px;
    width: 38px; height: 38px;
    border-radius: var(--radius-full);
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.18);
    color: rgba(255,255,255,0.85);
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all var(--transition); backdrop-filter: blur(8px);
}
.lightbox-close:hover { background: rgba(239,68,68,0.25); border-color: rgba(239,68,68,0.4); color: #f87171; }
.lightbox-close svg { width: 14px; height: 14px; }

/* ═══════════════════════════════════════════════════════
   Feature 3: Calendar blur — FIXED
   Uses a fullscreen overlay injected over the page.
   Native browser date pickers render outside the DOM
   so we can't blur siblings; instead we overlay the page.
   ═══════════════════════════════════════════════════════ */
#calendarBlurOverlay {
    position: fixed;
    inset: 0;
    z-index: 4000;
    background: rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.25s ease, visibility 0.25s ease;
}
#calendarBlurOverlay.active {
    opacity: 1;
    visibility: visible;
    pointer-events: none; /* still let clicks through to the date picker */
}

/* ═══════════════════════════════════════════════════════
   Feature 1: Avatar Crop Modal
   ═══════════════════════════════════════════════════════ */
#avatarCropModal {
    position: fixed;
    inset: 0;
    z-index: 9000;
    background: rgba(4, 9, 16, 0.92);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}
#avatarCropModal.open {
    opacity: 1;
    visibility: visible;
}
.crop-modal-inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
    width: 100%;
    max-width: 420px;
    padding: 24px;
}
.crop-modal-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: -0.01em;
}
.crop-stage {
    position: relative;
    width: 300px;
    height: 300px;
    border-radius: var(--radius);
    overflow: hidden;
    background: #000;
    touch-action: none;
    cursor: grab;
}
.crop-stage:active { cursor: grabbing; }
#cropCanvas {
    position: absolute;
    top: 0; left: 0;
    width: 300px;
    height: 300px;
    border-radius: var(--radius);
}
/* Circular mask overlay */
.crop-mask {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 2;
}
.crop-mask svg {
    width: 100%;
    height: 100%;
}
.crop-zoom-wrap {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
}
.crop-zoom-wrap svg {
    width: 16px;
    height: 16px;
    color: rgba(255,255,255,0.5);
    flex-shrink: 0;
}
#cropZoom {
    flex: 1;
    -webkit-appearance: none;
    appearance: none;
    height: 4px;
    border-radius: 2px;
    background: rgba(255,255,255,0.15);
    outline: none;
    cursor: pointer;
}
#cropZoom::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--accent);
    box-shadow: 0 0 0 3px rgba(56,189,248,0.25);
    cursor: pointer;
    transition: box-shadow 0.2s;
}
#cropZoom::-webkit-slider-thumb:hover {
    box-shadow: 0 0 0 5px rgba(56,189,248,0.35);
}
.crop-actions {
    display: flex;
    gap: 12px;
    width: 100%;
}
.crop-actions .btn {
    flex: 1;
}
#cropSaveBtn {
    background: var(--accent-gradient);
    color: #fff;
    box-shadow: 0 4px 18px rgba(56,189,248,0.3);
}
#cropSaveBtn:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(56,189,248,0.45); }
#cropCancelBtn {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.7);
}
#cropCancelBtn:hover { background: rgba(255,255,255,0.12); color: #fff; }

/* ═══════════════════════════════════════════════════════
   Feature 2: Avatar Lightbox
   ═══════════════════════════════════════════════════════ */
#avatarLightbox {
    position: fixed;
    inset: 0;
    z-index: 8500;
    background: rgba(4, 9, 16, 0.88);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.28s ease, visibility 0.28s ease;
    cursor: pointer;
}
#avatarLightbox.open {
    opacity: 1;
    visibility: visible;
}
#avatarLightbox img {
    max-width: 80vw;
    max-height: 80vh;
    border-radius: var(--radius-lg);
    box-shadow: 0 24px 80px rgba(0,0,0,0.7);
    object-fit: contain;
    transform: scale(0.88);
    transition: transform 0.32s cubic-bezier(0.34,1.56,0.64,1);
    cursor: default;
}
#avatarLightbox.open img {
    transform: scale(1);
}
.lightbox-close {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 38px;
    height: 38px;
    border-radius: var(--radius-full);
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.18);
    color: rgba(255,255,255,0.85);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition);
    backdrop-filter: blur(8px);
}
.lightbox-close:hover {
    background: rgba(239,68,68,0.25);
    border-color: rgba(239,68,68,0.4);
    color: #f87171;
}
.lightbox-close svg { width: 14px; height: 14px; }

/* Avatar clickable wrapper */
.avatar-lightbox-trigger {
    cursor: pointer;
    border-radius: var(--radius-full);
    display: block;
    transition: transform var(--transition), box-shadow var(--transition);
}
.avatar-lightbox-trigger:hover {
    transform: scale(1.04);
    box-shadow: 0 0 0 4px rgba(56,189,248,0.3);
}

/* ═══════════════════════════════════════════════════════
   Feature 3: Calendar blur / date-input focus depth effect
   ═══════════════════════════════════════════════════════ */
body.calendar-open .main-content,
body.calendar-open .sidebar {
    filter: blur(3px);
    transition: filter 0.25s ease;
    pointer-events: none;
}
body.calendar-open .modal,
body.calendar-open .card {
    filter: none;
    pointer-events: auto;
}
/* Smooth restore */
body:not(.calendar-open) .main-content,
body:not(.calendar-open) .sidebar {
    filter: none;
    transition: filter 0.25s ease;
}
</style>

<!-- Calendar blur overlay (injected once, lives at body level) -->
<div id="calendarBlurOverlay"></div>

<div class="profile-page">

  <!-- ── Profile Hero Card ── -->
  <div class="card profile-hero" style="margin-bottom:24px;">
    <div class="profile-hero-bg"></div>
    <div class="profile-hero-content">

      <!-- Avatar -->
      <div class="profile-avatar-wrap">
        <?php if ($hasAvatar): ?>
          <span class="avatar-lightbox-trigger" onclick="openAvatarLightbox()" title="View full size" role="button" tabindex="0" aria-label="View avatar full size">
            <img src="<?= $avatarUrl ?>" class="profile-avatar-xl" alt="Avatar" id="profileAvatarImg">
          </span>
        <?php else: ?>
          <div class="profile-avatar-xl profile-avatar-initials" id="profileAvatarImg">
            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
          </div>
        <?php endif; ?>
        <label class="avatar-edit-btn" title="Change photo" for="avatarUpload">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
        </label>
        <input type="file" id="avatarUpload" accept="image/*" style="display:none">
      </div>

      <!-- Name, role, bio, inline stats -->
      <div class="profile-hero-info">
        <h2 class="profile-hero-name"><?= htmlspecialchars($user['full_name']) ?></h2>
        <div class="profile-hero-meta">
          <span class="badge <?= $roleBadgeClass ?>"><?= $roleLabel ?></span>
          <?php if (!empty($user['email'])): ?>
            <span style="font-size:0.82rem;color:var(--text-tertiary);display:inline-flex;align-items:center;gap:4px;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
              <?= htmlspecialchars($user['email']) ?>
            </span>
          <?php endif; ?>
          <?php if (!empty($user['department'])): ?>
            <span style="font-size:0.82rem;color:var(--text-tertiary);display:inline-flex;align-items:center;gap:4px;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
              <?= htmlspecialchars($user['department']) ?><?= $user['year_of_study'] ? ' · Year ' . $user['year_of_study'] : '' ?>
            </span>
          <?php endif; ?>
          <?php if (!empty($user['phone'])): ?>
            <span style="font-size:0.82rem;color:var(--text-tertiary);display:inline-flex;align-items:center;gap:4px;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.67A2 2 0 012.18 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.56a16 16 0 006.53 6.53l1.62-1.62a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
              <?= htmlspecialchars($user['phone']) ?>
            </span>
          <?php endif; ?>
        </div>
        <p class="profile-hero-bio"><?= htmlspecialchars($user['bio'] ?? 'No bio added yet. Click Edit Profile to add one.') ?></p>

        <!-- Inline stat pills -->
        <div class="profile-hero-stats">
          <?php if ($ownerStats): ?>
            <div class="profile-stat-pill"><div class="psp-val"><?= $ownerStats['properties'] ?></div><div class="psp-lbl">Properties</div></div>
            <div class="profile-stat-pill"><div class="psp-val"><?= $ownerStats['rooms'] ?></div><div class="psp-lbl">Rooms</div></div>
            <div class="profile-stat-pill"><div class="psp-val"><?= $ownerStats['listings'] ?></div><div class="psp-lbl">Listings</div></div>
          <?php else: ?>
            <div class="profile-stat-pill"><div class="psp-val"><?= $appCount ?></div><div class="psp-lbl">Applications</div></div>
            <?php if ($user['student_id']): ?>
              <div class="profile-stat-pill"><div class="psp-val" style="font-size:1rem;"><?= htmlspecialchars($user['student_id']) ?></div><div class="psp-lbl">Student ID</div></div>
            <?php endif; ?>
            <?php if ($user['gender']): ?>
              <div class="profile-stat-pill"><div class="psp-val" style="font-size:1rem;"><?= ucfirst($user['gender']) ?></div><div class="psp-lbl">Gender</div></div>
            <?php endif; ?>
          <?php endif; ?>
          <div class="profile-stat-pill"><div class="psp-val" style="font-size:0.9rem;"><?= date('M Y', strtotime($user['created_at'])) ?></div><div class="psp-lbl">Member Since</div></div>
        </div>
      </div>

      <!-- Edit Button -->
      <button class="btn btn-outline btn-sm profile-hero-edit-btn" onclick="Modal.open('editProfileModal')" style="display:flex;align-items:center;gap:6px;white-space:nowrap;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Edit Profile
      </button>
    </div>
  </div>

  <!-- Two-column detail cards -->
  <div class="profile-details-grid" style="margin-top:20px;">

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

<!-- ════════════════════════════════════════════════════
     Feature 1: Avatar Crop Modal
     ════════════════════════════════════════════════════ -->
<div id="avatarCropModal" role="dialog" aria-modal="true" aria-label="Crop your photo">
  <div class="crop-modal-inner">
    <div class="crop-modal-title">Adjust Your Photo</div>

    <!-- Canvas stage with circular mask -->
    <div class="crop-stage" id="cropStage">
      <canvas id="cropCanvas" width="300" height="300"></canvas>
      <!-- SVG circular mask: dark surround + bright circle ring -->
      <div class="crop-mask">
        <svg viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <mask id="circleMask">
              <rect width="300" height="300" fill="white"/>
              <circle cx="150" cy="150" r="148" fill="black"/>
            </mask>
          </defs>
          <!-- Dark overlay outside circle -->
          <rect width="300" height="300" fill="rgba(0,0,0,0.55)" mask="url(#circleMask)"/>
          <!-- Circle ring -->
          <circle cx="150" cy="150" r="148" fill="none" stroke="rgba(56,189,248,0.7)" stroke-width="2"/>
        </svg>
      </div>
    </div>

    <!-- Zoom slider -->
    <div class="crop-zoom-wrap">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="range" id="cropZoom" min="1" max="3" step="0.01" value="1">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
    </div>

    <!-- Action buttons -->
    <div class="crop-actions">
      <button class="btn" id="cropCancelBtn" onclick="closeCropModal()">Cancel</button>
      <button class="btn" id="cropSaveBtn" onclick="saveCroppedAvatar()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Save Photo
      </button>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════════════
     Feature 2: Avatar Lightbox
     ════════════════════════════════════════════════════ -->
<div id="avatarLightbox" onclick="closeAvatarLightbox()" role="dialog" aria-modal="true" aria-label="Avatar full size view">
  <button class="lightbox-close" onclick="closeAvatarLightbox()" title="Close">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
  </button>
  <img id="lightboxImg" src="" alt="Avatar full size" onclick="event.stopPropagation()">
</div>

<script>
/* ══════════════════════════════════════════════════════
   Save Profile
   ══════════════════════════════════════════════════════ */
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

/* ══════════════════════════════════════════════════════
   Feature 1: Avatar Crop/Resize
   ══════════════════════════════════════════════════════ */
(function() {
    'use strict';

    // State
    let _img      = null;
    let _zoom     = 1;
    let _offsetX  = 0;
    let _offsetY  = 0;
    let _dragging = false;
    let _lastX    = 0;
    let _lastY    = 0;

    const CANVAS_SIZE = 300;

    const modal    = document.getElementById('avatarCropModal');
    const canvas   = document.getElementById('cropCanvas');
    const ctx      = canvas.getContext('2d');
    const zoomSlider = document.getElementById('cropZoom');
    const stage    = document.getElementById('cropStage');
    const fileInput = document.getElementById('avatarUpload');

    /* Open crop modal when file selected */
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            const image = new Image();
            image.onload = () => {
                _img = image;
                _zoom = 1;
                // Center the image
                const scale = CANVAS_SIZE / Math.min(image.naturalWidth, image.naturalHeight);
                _zoom = Math.max(1, scale);
                zoomSlider.value = _zoom;
                centerImage();
                drawCanvas();
                openCropModal();
            };
            image.src = e.target.result;
        };
        reader.readAsDataURL(file);
        // Reset input so the same file can be re-selected
        this.value = '';
    });

    function centerImage() {
        if (!_img) return;
        const scaledW = _img.naturalWidth  * _zoom;
        const scaledH = _img.naturalHeight * _zoom;
        _offsetX = (CANVAS_SIZE - scaledW) / 2;
        _offsetY = (CANVAS_SIZE - scaledH) / 2;
    }

    function clampOffsets() {
        if (!_img) return;
        const scaledW = _img.naturalWidth  * _zoom;
        const scaledH = _img.naturalHeight * _zoom;
        // Don't let the image leave the canvas area (image must cover canvas)
        _offsetX = Math.min(0, Math.max(CANVAS_SIZE - scaledW, _offsetX));
        _offsetY = Math.min(0, Math.max(CANVAS_SIZE - scaledH, _offsetY));
    }

    function drawCanvas() {
        if (!_img) return;
        ctx.clearRect(0, 0, CANVAS_SIZE, CANVAS_SIZE);
        const scaledW = _img.naturalWidth  * _zoom;
        const scaledH = _img.naturalHeight * _zoom;
        ctx.drawImage(_img, _offsetX, _offsetY, scaledW, scaledH);
    }

    /* ── Zoom ── */
    zoomSlider.addEventListener('input', function() {
        const prevZoom = _zoom;
        _zoom = parseFloat(this.value);

        // Keep center point stable during zoom
        const cx = CANVAS_SIZE / 2;
        const cy = CANVAS_SIZE / 2;
        const ratio = _zoom / prevZoom;
        _offsetX = cx - ratio * (cx - _offsetX);
        _offsetY = cy - ratio * (cy - _offsetY);
        clampOffsets();
        drawCanvas();
    });

    /* ── Mouse drag ── */
    stage.addEventListener('mousedown', (e) => {
        _dragging = true;
        _lastX = e.clientX;
        _lastY = e.clientY;
        e.preventDefault();
    });
    window.addEventListener('mousemove', (e) => {
        if (!_dragging) return;
        _offsetX += e.clientX - _lastX;
        _offsetY += e.clientY - _lastY;
        _lastX = e.clientX;
        _lastY = e.clientY;
        clampOffsets();
        drawCanvas();
    });
    window.addEventListener('mouseup', () => { _dragging = false; });

    /* ── Touch drag ── */
    let _lastTouchX = 0, _lastTouchY = 0;
    stage.addEventListener('touchstart', (e) => {
        if (e.touches.length === 1) {
            _dragging = true;
            _lastTouchX = e.touches[0].clientX;
            _lastTouchY = e.touches[0].clientY;
        }
        e.preventDefault();
    }, { passive: false });
    stage.addEventListener('touchmove', (e) => {
        if (!_dragging || e.touches.length !== 1) return;
        _offsetX += e.touches[0].clientX - _lastTouchX;
        _offsetY += e.touches[0].clientY - _lastTouchY;
        _lastTouchX = e.touches[0].clientX;
        _lastTouchY = e.touches[0].clientY;
        clampOffsets();
        drawCanvas();
        e.preventDefault();
    }, { passive: false });
    stage.addEventListener('touchend', () => { _dragging = false; });

    /* ── Pinch zoom ── */
    let _lastPinchDist = 0;
    stage.addEventListener('touchstart', (e) => {
        if (e.touches.length === 2) {
            _lastPinchDist = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
            );
        }
    }, { passive: false });
    stage.addEventListener('touchmove', (e) => {
        if (e.touches.length === 2) {
            const dist = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
            );
            const delta = dist / _lastPinchDist;
            _lastPinchDist = dist;
            const newZoom = Math.min(3, Math.max(1, _zoom * delta));
            const ratio = newZoom / _zoom;
            _offsetX = CANVAS_SIZE/2 - ratio * (CANVAS_SIZE/2 - _offsetX);
            _offsetY = CANVAS_SIZE/2 - ratio * (CANVAS_SIZE/2 - _offsetY);
            _zoom = newZoom;
            zoomSlider.value = _zoom;
            clampOffsets();
            drawCanvas();
            e.preventDefault();
        }
    }, { passive: false });

    /* ── Open/Close ── */
    window.openCropModal  = function() { modal.classList.add('open'); };
    window.closeCropModal = function() {
        modal.classList.remove('open');
        ctx.clearRect(0, 0, CANVAS_SIZE, CANVAS_SIZE);
        _img = null;
    };

    /* ── Save: crop circle → JPEG blob → POST ── */
    window.saveCroppedAvatar = async function() {
        const btn = document.getElementById('cropSaveBtn');
        btn.disabled = true;
        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;animation:spin 1s linear infinite"><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" opacity=".25"/><path d="M21 12a9 9 0 00-9-9"/></svg> Saving...';

        // Create output canvas — circular crop → square JPEG
        const out = document.createElement('canvas');
        out.width  = CANVAS_SIZE;
        out.height = CANVAS_SIZE;
        const octx = out.getContext('2d');

        // Clip to circle then draw
        octx.beginPath();
        octx.arc(CANVAS_SIZE/2, CANVAS_SIZE/2, CANVAS_SIZE/2, 0, Math.PI * 2);
        octx.closePath();
        octx.clip();
        octx.drawImage(canvas, 0, 0);

        out.toBlob(async (blob) => {
            const fd = new FormData();
            fd.append('avatar', blob, 'cropped.jpg');
            try {
                const resp = await fetch(`${APP_URL}/api/profile.php?action=avatar`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                });
                const data = await resp.json();
                if (data.success) {
                    Toast.show('Avatar updated!', 'success');
                    closeCropModal();
                    // Update avatar on page without full reload
                    if (data.avatar_url) {
                        updateAvatarOnPage(data.avatar_url);
                    } else {
                        setTimeout(() => location.reload(), 800);
                    }
                } else {
                    Toast.show(data.error || 'Upload failed', 'error');
                }
            } catch(e) {
                Toast.show('Upload failed', 'error');
            }
            btn.disabled = false;
            btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save Photo';
        }, 'image/jpeg', 0.92);
    };

    function updateAvatarOnPage(url) {
        // Update profile page avatar image
        const wrap = document.querySelector('.profile-avatar-wrap');
        if (!wrap) { location.reload(); return; }
        let img = wrap.querySelector('img.profile-avatar-xl');
        if (img) {
            img.src = url + '?t=' + Date.now();
        } else {
            // Replace initials div with img
            const old = wrap.querySelector('.profile-avatar-xl');
            if (old) {
                const newImg = document.createElement('img');
                newImg.src = url + '?t=' + Date.now();
                newImg.className = 'profile-avatar-xl';
                newImg.alt = 'Avatar';
                newImg.id = 'profileAvatarImg';
                // Wrap in lightbox trigger
                const trigger = document.createElement('span');
                trigger.className = 'avatar-lightbox-trigger';
                trigger.title = 'View full size';
                trigger.setAttribute('role', 'button');
                trigger.setAttribute('tabindex', '0');
                trigger.onclick = () => openAvatarLightbox();
                trigger.appendChild(newImg);
                old.replaceWith(trigger);
            }
        }
        // Update lightbox image URL
        const lbImg = document.getElementById('lightboxImg');
        if (lbImg) lbImg.src = url + '?t=' + Date.now();
        // Update topbar/sidebar avatars
        document.querySelectorAll('.topbar-user-avatar img, .user-card-link img, .sidebar-footer img').forEach(i => {
            i.src = url + '?t=' + Date.now();
        });
    }

    // Spin animation for save button
    const style = document.createElement('style');
    style.textContent = '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
    document.head.appendChild(style);
})();

/* ══════════════════════════════════════════════════════
   Feature 2: Avatar Lightbox
   ══════════════════════════════════════════════════════ */
(function() {
    const lightbox = document.getElementById('avatarLightbox');
    const lbImg    = document.getElementById('lightboxImg');
    const avatarUrl = <?= json_encode($avatarUrl) ?>;

    window.openAvatarLightbox = function() {
        if (!avatarUrl) return;
        lbImg.src = avatarUrl;
        lightbox.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closeAvatarLightbox = function() {
        lightbox.classList.remove('open');
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lightbox.classList.contains('open')) {
            closeAvatarLightbox();
        }
    });
})();

/* ══════════════════════════════════════════════════════
   Feature 3: Calendar blur overlay — FIXED
   Injects a fullscreen blur overlay when any date input
   gains focus. The overlay sits below the native calendar
   popup (which renders outside DOM) but above page content,
   creating a real frosted-glass depth effect.
   ══════════════════════════════════════════════════════ */
(function() {
    const overlay = document.getElementById('calendarBlurOverlay');

    function showBlur() {
        if (overlay) overlay.classList.add('active');
    }
    function hideBlur() {
        // Delay lets the calendar value register before closing
        setTimeout(() => {
            const anyFocused = document.querySelector('input[type="date"]:focus');
            if (!anyFocused && overlay) overlay.classList.remove('active');
        }, 200);
    }

    function attachDateListeners() {
        document.querySelectorAll('input[type="date"]').forEach(input => {
            if (input._calBlurAttached) return;
            input._calBlurAttached = true;
            input.addEventListener('focus', showBlur);
            input.addEventListener('blur',  hideBlur);
            // Also handle click (some browsers only fire click, not focus, for date picker)
            input.addEventListener('click', showBlur);
        });
    }

    attachDateListeners();
    // Watch for dynamically added date inputs (e.g., inside modals)
    new MutationObserver(attachDateListeners)
        .observe(document.body, { childList: true, subtree: true });

    // Also close if user clicks away from any date input
    document.addEventListener('click', (e) => {
        if (e.target.type !== 'date') hideBlur();
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
