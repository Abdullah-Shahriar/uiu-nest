<?php
/**
 * UIU Nest v2 — Common Header (sidebar, topbar — emoji-free, SVG icons)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$currentUser = getCurrentUser();
$userRole    = $_SESSION['role'] ?? 'guest';
$pageName    = $pageName ?? 'Dashboard';
$csrfToken   = generateCSRFToken();

$roleLabel = match($userRole) {
    'tenant'  => 'House Manager',
    'owner'   => 'Owner',
    'student' => 'Student',
    'admin'   => 'Admin',
    default   => ucfirst($userRole),
};
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="UIU Nest — Student Accommodation Management System near United International University">
    <title><?= htmlspecialchars($pageName) ?> — UIU Nest</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <script>
        (function(){
            var t = localStorage.getItem('uiu-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', t);
            var l = localStorage.getItem('uiu-lang') || 'en';
            document.documentElement.setAttribute('data-lang', l);
        })();
    </script>
</head>
<body>
    <!-- Mobile overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ═══════════════════════════════════════ SIDEBAR ═══ -->
    <aside class="sidebar" id="sidebar">

        <!-- Logo -->
        <div class="sidebar-header">
            <a href="<?= APP_URL ?>/pages/dashboard.php" class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9.5L12 3l9 6.5V21H3V9.5z" fill="rgba(255,255,255,0.15)"/>
                        <rect x="9" y="14" width="6" height="7" rx="1" fill="rgba(255,255,255,0.35)"/>
                    </svg>
                </div>
                <div class="sidebar-logo-text">
                    <span class="logo-name">UIU<span style="color:#38bdf8;">·</span>Nest</span>
                    <span class="logo-tagline">Accommodation System</span>
                </div>
            </a>
            <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <!-- Live Clock -->
        <div class="sidebar-clock">
            <div class="clock-time" id="clockTime">--:--:--</div>
            <div class="clock-date" id="clockDate">Loading...</div>
        </div>


        <!-- Navigation -->
        <nav class="sidebar-nav">

            <!-- Always visible -->
            <a href="<?= APP_URL ?>/pages/dashboard.php"
               class="nav-item <?= $pageName === 'Dashboard' ? 'active' : '' ?>" id="nav-browse">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24"><path d="M3 9.5L12 3l9 6.5V21H3V9.5z"/><rect x="9" y="14" width="6" height="7" rx="1"/></svg>
                </span>
                <span class="nav-label" data-i18n="nav_browse">Browse Listings</span>
            </a>

            <?php if (isLoggedIn()): ?>

            <a href="<?= APP_URL ?>/pages/profile.php"
               class="nav-item <?= $pageName === 'My Profile' ? 'active' : '' ?>" id="nav-profile">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                </span>
                <span class="nav-label" data-i18n="nav_profile">My Profile</span>
            </a>

            <?php if (!hasRole('admin')): ?>
            <!-- Complaint Box (non-admin logged-in users only) -->
            <a href="#" onclick="Modal.open('complaintModal');return false;"
               class="nav-item" id="nav-complaint">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                </span>
                <span class="nav-label" data-i18n="nav_complaint">Submit Complaint</span>
            </a>
            <?php endif; ?>

            <a href="<?= APP_URL ?>/pages/saved-listings.php"
               class="nav-item <?= $pageName === 'Saved Listings' || $pageName === 'Saved' ? 'active' : '' ?>" id="nav-saved">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                </span>
                <span class="nav-label">Favourite Properties</span>
            </a>

            <!-- ── Student ── -->
            <?php if (hasAnyRole(['student'])): ?>
                <div class="nav-section-label" data-i18n="nav_sec_student">Student</div>

                <a href="<?= APP_URL ?>/pages/applications.php"
                   class="nav-item <?= $pageName === 'Applications' ? 'active' : '' ?>" id="nav-apps-student">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                    </span>
                    <span class="nav-label" data-i18n="nav_applications">My Applications</span>
                </a>
            <?php endif; ?>

            <!-- ── House Manager ── -->
            <?php if (hasRole('tenant')): ?>
                <div class="nav-section-label" data-i18n="nav_sec_manager">House Manager</div>

                <a href="<?= APP_URL ?>/pages/applications.php"
                   class="nav-item <?= $pageName === 'Applications' ? 'active' : '' ?>" id="nav-apps-hm">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                    </span>
                    <span class="nav-label" data-i18n="nav_applications">Applications</span>
                </a>

                <a href="<?= APP_URL ?>/pages/manage-listings.php"
                   class="nav-item <?= $pageName === 'Manage Listings' ? 'active' : '' ?>" id="nav-listings-hm">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </span>
                    <span class="nav-label" data-i18n="nav_manage_listings">Manage Listings</span>
                </a>

                <a href="<?= APP_URL ?>/pages/former-residents.php"
                   class="nav-item <?= $pageName === 'Former Residents' ? 'active' : '' ?>" id="nav-alumni-hm">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                    </span>
                    <span class="nav-label" data-i18n="nav_former">Former Residents</span>
                </a>

                <a href="<?= APP_URL ?>/pages/public-comments.php"
                   class="nav-item <?= $pageName === 'Public Comments' ? 'active' : '' ?>" id="nav-comments-hm">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    </span>
                    <span class="nav-label">Public Comment Section</span>
                </a>
            <?php endif; ?>

            <!-- ── Owner ── -->
            <?php if (hasRole('owner')): ?>
                <div class="nav-section-label" data-i18n="nav_sec_owner">Owner</div>

                <a href="<?= APP_URL ?>/pages/manage-properties.php"
                   class="nav-item <?= $pageName === 'Properties' ? 'active' : '' ?>" id="nav-props">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
                    </span>
                    <span class="nav-label" data-i18n="nav_properties">My Properties</span>
                </a>

                <a href="<?= APP_URL ?>/pages/manage-listings.php"
                   class="nav-item <?= $pageName === 'Manage Listings' ? 'active' : '' ?>" id="nav-listings">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </span>
                    <span class="nav-label" data-i18n="nav_manage_listings">Manage Listings</span>
                </a>

                <a href="<?= APP_URL ?>/pages/applications.php"
                   class="nav-item <?= $pageName === 'Applications' ? 'active' : '' ?>" id="nav-apps-owner">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                    </span>
                    <span class="nav-label" data-i18n="nav_applications">Applications</span>
                </a>

                <a href="<?= APP_URL ?>/pages/former-residents.php"
                   class="nav-item <?= $pageName === 'Former Residents' ? 'active' : '' ?>" id="nav-alumni">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                    </span>
                    <span class="nav-label" data-i18n="nav_former">Former Residents</span>
                </a>

                <a href="<?= APP_URL ?>/pages/public-comments.php"
                   class="nav-item <?= $pageName === 'Public Comments' ? 'active' : '' ?>" id="nav-comments">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    </span>
                    <span class="nav-label">Public Comment Section</span>
                </a>
            <?php endif; ?>

            <!-- ── Admin ── -->
            <?php if (hasRole('admin')): ?>
                <div class="nav-section-label" data-i18n="nav_sec_admin">Administration</div>

                <a href="<?= APP_URL ?>/pages/admin.php"
                   class="nav-item <?= $pageName === 'Admin' ? 'active' : '' ?>" id="nav-admin">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                    </span>
                    <span class="nav-label" data-i18n="nav_admin">Admin Panel</span>
                </a>

                <a href="<?= APP_URL ?>/pages/applications.php"
                   class="nav-item <?= $pageName === 'Applications' ? 'active' : '' ?>" id="nav-apps-admin">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                    </span>
                    <span class="nav-label" data-i18n="nav_all_apps">All Applications</span>
                </a>
            <?php endif; // end admin ?>
            <?php endif; // isLoggedIn ?>

        </nav>


        <!-- User card -->
        <?php if (isLoggedIn()): ?>
        <div class="sidebar-footer">
            <a href="<?= APP_URL ?>/pages/profile.php" style="text-decoration:none;flex:1;min-width:0;" class="user-card user-card-link">
                <?php if (!empty($currentUser['avatar_path']) && file_exists(APP_ROOT . '/' . $currentUser['avatar_path'])): ?>
                    <img src="<?= APP_URL . '/' . htmlspecialchars($currentUser['avatar_path']) ?>"
                         style="width:36px;height:36px;border-radius:10px;object-fit:cover;flex-shrink:0;" alt="Avatar">
                <?php else: ?>
                    <div class="user-avatar"><?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)) ?></div>
                <?php endif; ?>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($currentUser['full_name'] ?? '') ?></div>
                    <div class="user-role"><?= $roleLabel ?></div>
                </div>
            </a>
            <a href="<?= APP_URL ?>/api/auth.php?action=logout" class="sidebar-logout" title="Sign Out">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </a>
        </div>
        <?php endif; ?>
    </aside>

    <!-- ═══════════════════════════════════════ MAIN ═══ -->
    <div class="main-wrapper">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu">
                    <span></span><span></span><span></span>
                </button>
                <h1 class="page-title" data-i18n="page_<?= strtolower(str_replace(' ', '_', $pageName)) ?>"><?= htmlspecialchars($pageName) ?></h1>
            </div>
            <div class="topbar-right">
                <!-- Language toggle button -->
                <button class="lang-toggle-switch" id="langTopbarBtn" onclick="UIULang.toggle()" title="Switch Language">
                    <span class="lang-icon">🌍</span>
                </button>
                <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                    <span class="theme-icon sun">☀️</span>
                    <span class="theme-icon moon">🌙</span>
                </button>
                <?php if (isLoggedIn()): ?>
                    <a href="<?= APP_URL ?>/pages/profile.php" class="topbar-user-avatar" title="My Profile">
                        <?php if (!empty($currentUser['avatar_path']) && file_exists(APP_ROOT . '/' . $currentUser['avatar_path'])): ?>
                            <img src="<?= APP_URL . '/' . htmlspecialchars($currentUser['avatar_path']) ?>"
                                 style="width:34px;height:34px;border-radius:9px;object-fit:cover;" alt="Avatar">
                        <?php else: ?>
                            <div class="user-avatar-sm"><?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)) ?></div>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <a href="<?= APP_URL ?>/pages/login.php" class="btn btn-outline btn-sm" data-i18n="btn_login">Login</a>
                    <a href="<?= APP_URL ?>/pages/register.php" class="btn btn-primary btn-sm" data-i18n="btn_signup">Sign Up</a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Page content -->
        <main class="main-content">
            <input type="hidden" id="csrfToken" value="<?= $csrfToken ?>">
            <input type="hidden" id="appUrl"    value="<?= APP_URL ?>">
            <script>
                window.UIU_USER = <?= json_encode([
                    'logged_in' => isLoggedIn(),
                    'id'        => $currentUser['id'] ?? null,
                    'role'      => $userRole,
                ]) ?>;
                window.UIU_LAT  = <?= UIU_LAT ?>;
                window.UIU_LNG  = <?= UIU_LNG ?>;
                window.APP_URL  = '<?= APP_URL ?>';
            </script>

<?php /* ── Complaint Modal (non-admin logged-in users only) ── */ ?>
<?php if (isLoggedIn() && !hasRole('admin')): ?>
<div class="modal-overlay" id="complaintModal">
    <div class="modal" style="max-width:500px;">
        <div class="modal-header">
            <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                <span data-i18n="complaint_title">Submit a Complaint</span>
            </h3>
            <button class="modal-close" onclick="Modal.close('complaintModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <p style="font-size:0.82rem;color:var(--text-tertiary);margin-bottom:16px;" data-i18n="complaint_note">
                Your complaint goes directly to the Admin. Check "Submit Anonymously" to hide your identity.
            </p>
            <div class="form-group">
                <label class="form-label" data-i18n="complaint_category">Category</label>
                <select class="form-control" id="complaintCategory">
                    <option value="maintenance">Maintenance</option>
                    <option value="noise">Noise</option>
                    <option value="safety">Safety</option>
                    <option value="management">Management</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" data-i18n="complaint_subject">Subject</label>
                <input class="form-control" type="text" id="complaintSubject" placeholder="Brief subject..." maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label" data-i18n="complaint_desc">Description</label>
                <textarea class="form-control" id="complaintDesc" rows="4" placeholder="Describe the issue in detail..."></textarea>
            </div>
            <!-- Anonymous checkbox -->
            <label class="anon-checkbox-row" for="complaintAnon">
                <input type="checkbox" id="complaintAnon" checked>
                <div>
                    <strong data-i18n="complaint_anon_label">Submit Anonymously</strong>
                    <div style="font-size:0.75rem;color:var(--text-tertiary);margin-top:1px;" data-i18n="complaint_anon_hint">If unchecked, your identity will be revealed when approved.</div>
                </div>
            </label>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="Modal.close('complaintModal')" data-i18n="btn_cancel">Cancel</button>
            <button class="btn btn-primary" id="complaintSubmitBtn" onclick="submitComplaint()" data-i18n="btn_submit">Submit</button>
        </div>
    </div>
</div>
<script>
async function submitComplaint() {
    var subj = document.getElementById('complaintSubject').value.trim();
    var desc = document.getElementById('complaintDesc').value.trim();
    var anon = document.getElementById('complaintAnon').checked;
    if (!subj || !desc) { Toast.show('Please fill all fields.', 'error'); return; }
    var btn = document.getElementById('complaintSubmitBtn');
    btn.disabled = true;
    try {
        var r = await fetchAPI(window.APP_URL + '/api/complaints.php', {
            method: 'POST',
            body: JSON.stringify({
                category: document.getElementById('complaintCategory').value,
                subject: subj,
                description: desc,
                is_anonymous: anon
            })
        });
        if (r.success) {
            Toast.show('Complaint submitted successfully.', 'success');
            Modal.close('complaintModal');
            document.getElementById('complaintSubject').value = '';
            document.getElementById('complaintDesc').value = '';
        }
    } catch(e) {}
    btn.disabled = false;
}
</script>
<?php endif; ?>
