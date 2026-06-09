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

$hasActiveTenancy = false;
if (isLoggedIn()) {
    try {
        $db = getDB();
        $tenantStmt = $db->prepare('SELECT 1 FROM room_tenants WHERE user_id = ? AND moved_out_at IS NULL LIMIT 1');
        $tenantStmt->execute([$_SESSION['user_id']]);
        $hasActiveTenancy = (bool)$tenantStmt->fetchColumn();
    } catch (Exception $e) {
        $hasActiveTenancy = false;
    }
}

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
        window.APP_URL = "<?= APP_URL ?>";
    </script>
    <script src="<?= APP_URL ?>/assets/js/lightbox.js" defer></script>

    <style>
    /* ══════════════════════════════════════════════
       Google Translate Custom CSS
       ══════════════════════════════════════════════ */
    #google_translate_element {
        display: none !important;
    }
    .goog-te-gadget {
        font-family: 'Inter', sans-serif !important;
        color: transparent !important; /* hide 'powered by' text */
    }
    .goog-te-gadget .goog-te-combo {
        background: var(--bg-secondary) !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--radius-sm) !important;
        color: var(--text-primary) !important;
        padding: 4px 8px !important;
        font-size: 0.8rem !important;
        cursor: pointer !important;
        outline: none !important;
        transition: border-color 0.2s !important;
    }
    .goog-te-gadget .goog-te-combo:hover {
        border-color: var(--accent) !important;
    }
    /* Hide Google Top Frame Banner */
    .goog-te-banner-frame,
    .goog-te-banner-frame.skiptranslate,
    iframe.goog-te-banner-frame {
        display: none !important;
        visibility: hidden !important;
    }
    .skiptranslate > iframe {
        display: none !important;
    }
    body {
        top: 0px !important; 
    }
    /* Hide Google tooltip */
    #goog-gt-tt, .goog-tooltip, .goog-tooltip:hover {
        display: none !important;
    }
    .goog-text-highlight {
        background-color: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    /* ══════════════════════════════════════════════
       Theme & Utility Classes
       ══════════════════════════════════════════════ */
    /* ══════════════════════════════════════════════
       Feature 4: Notification Bell — Topbar
       ══════════════════════════════════════════════ */
    .notif-bell-wrap {
        position: relative;
    }
    .notif-bell-btn {
        position: relative;
        width: 38px;
        height: 38px;
        border-radius: var(--radius-sm);
        background: var(--bg-secondary);
        border: 1px solid var(--border-strong);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
        transition: all var(--transition);
    }
    .notif-bell-btn:hover {
        background: var(--accent-light);
        border-color: var(--accent);
        color: var(--accent);
        transform: translateY(-1px);
    }
    .notif-bell-btn svg {
        width: 17px;
        height: 17px;
        transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1);
    }
    .notif-bell-btn.ringing svg {
        animation: bellRing 0.5s ease;
    }
    @keyframes bellRing {
        0%,100% { transform: rotate(0deg); }
        20%      { transform: rotate(-15deg); }
        40%      { transform: rotate(15deg); }
        60%      { transform: rotate(-10deg); }
        80%      { transform: rotate(10deg); }
    }
    .notif-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        min-width: 16px;
        height: 16px;
        padding: 0 4px;
        background: var(--danger);
        color: #fff;
        font-size: 0.62rem;
        font-weight: 700;
        border-radius: var(--radius-full);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--bg-primary);
        line-height: 1;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.25s cubic-bezier(0.34,1.56,0.64,1);
        pointer-events: none;
    }
    .notif-badge.visible {
        opacity: 1;
        transform: scale(1);
    }
    /* Dropdown panel */
    .notif-dropdown {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        width: 320px;
        background: var(--bg-glass-strong);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xl);
        z-index: 500;
        overflow: hidden;
        /* slide-down animation */
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px) scale(0.97);
        transform-origin: top right;
        transition:
            opacity 0.22s cubic-bezier(0.4,0,0.2,1),
            transform 0.22s cubic-bezier(0.4,0,0.2,1),
            visibility 0.22s;
    }
    [data-theme="dark"] .notif-dropdown {
        border-color: rgba(56,189,248,0.14);
        box-shadow: 0 0 0 1px rgba(56,189,248,0.08), var(--shadow-xl);
    }
    .notif-dropdown.open {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
    }
    .notif-dropdown-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        border-bottom: 1px solid var(--border);
    }
    .notif-dropdown-header h4 {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 7px;
    }
    .notif-dropdown-header h4 svg {
        width: 15px;
        height: 15px;
        color: var(--accent);
    }
    .notif-mark-read {
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--accent);
        background: none;
        border: none;
        cursor: pointer;
        padding: 3px 6px;
        border-radius: var(--radius-xs);
        transition: background var(--transition);
    }
    .notif-mark-read:hover {
        background: var(--accent-light);
    }
    .notif-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 36px 24px 30px;
        gap: 12px;
        text-align: center;
    }
    .notif-empty-icon {
        width: 64px;
        height: 64px;
        border-radius: var(--radius-full);
        background: var(--accent-light);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .notif-empty-icon svg {
        width: 30px;
        height: 30px;
        color: var(--accent);
    }
    .notif-empty-title {
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--text-primary);
    }
    .notif-empty-sub {
        font-size: 0.78rem;
        color: var(--text-tertiary);
        line-height: 1.5;
        max-width: 220px;
    }
    .notif-list {
        max-height: 340px;
        overflow-y: auto;
    }
    .notif-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 18px;
        border-bottom: 1px solid var(--border);
        transition: background var(--transition);
        cursor: pointer;
    }
    .notif-item:last-child { border-bottom: none; }
    .notif-item:hover { background: var(--accent-light); }
    .notif-item.unread { background: var(--accent-light); }
    .notif-item-icon {
        width: 32px; height: 32px;
        border-radius: var(--radius-sm);
        background: var(--bg-tertiary);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        color: var(--accent);
    }
    .notif-item-icon svg { width: 15px; height: 15px; }
    .notif-item-body { flex: 1; min-width: 0; }
    .notif-item-text { font-size: 0.82rem; color: var(--text-primary); line-height: 1.45; }
    .notif-item-time { font-size: 0.72rem; color: var(--text-tertiary); margin-top: 2px; }
    .notif-unread-dot {
        width: 7px; height: 7px;
        border-radius: 50%;
        background: var(--accent);
        flex-shrink: 0;
        margin-top: 5px;
    }
    </style>
    <script src="<?= APP_URL ?>/assets/js/cover-photo.js" defer></script>
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
            <!-- Complaints Page -->
            <a href="<?= APP_URL ?>/pages/complaints.php"
               class="nav-item <?= $pageName === 'Complaints' ? 'active' : '' ?>" id="nav-complaints">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                </span>
                <span class="nav-label" data-i18n="nav_complaints">Complaints</span>
            </a>
            <?php endif; ?>

            <a href="<?= APP_URL ?>/pages/saved-listings.php"
               class="nav-item <?= $pageName === 'Saved Listings' || $pageName === 'Saved' ? 'active' : '' ?>" id="nav-saved">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                </span>
                <span class="nav-label">Favourite Properties</span>
            </a>

            <?php if ($hasActiveTenancy): ?>
            <a href="<?= APP_URL ?>/pages/my-home.php"
               class="nav-item <?= $pageName === 'My Home' ? 'active' : '' ?>" id="nav-my-home">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9.5L12 3l9 6.5V21H3V9.5z"/>
                        <rect x="9" y="14" width="6" height="7" rx="1"/>
                    </svg>
                </span>
                <span class="nav-label">My Home</span>
            </a>
            <?php endif; ?>

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
                         style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;" alt="Avatar">
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
                <!-- Hidden Google Translate Widget -->
                <div id="google_translate_element"></div>
                <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                    <span class="theme-icon sun">☀️</span>
                    <span class="theme-icon moon">🌙</span>
                </button>
                <?php if (isLoggedIn()): ?>
                    <!-- Feature 4: Notification Bell -->
                    <?php $notifCount = 0; /* future: fetch from DB */ ?>
                    <div class="notif-bell-wrap" id="notifBellWrap">
                        <button class="notif-bell-btn" id="notifBellBtn" onclick="toggleNotifDropdown()" aria-label="Notifications" title="Notifications">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 01-3.46 0"/>
                            </svg>
                            <span class="notif-badge <?= $notifCount > 0 ? 'visible' : '' ?>" id="notifBadge"><?= $notifCount > 0 ? $notifCount : '' ?></span>
                        </button>
                        <!-- Notification Dropdown -->
                        <div class="notif-dropdown" id="notifDropdown" role="dialog" aria-label="Notifications panel">
                            <div class="notif-dropdown-header">
                                <h4>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                                        <path d="M13.73 21a2 2 0 01-3.46 0"/>
                                    </svg>
                                    Notifications
                                </h4>
                                <button class="notif-mark-read" onclick="markAllRead()" title="Mark all as read">Mark all read</button>
                            </div>
                            <?php if ($notifCount === 0): ?>
                            <div class="notif-empty">
                                <div class="notif-empty-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                                        <path d="M13.73 21a2 2 0 01-3.46 0"/>
                                        <line x1="2" y1="2" x2="22" y2="22" stroke="var(--accent)" stroke-width="1.5"/>
                                    </svg>
                                </div>
                                <div class="notif-empty-title">You're all caught up!</div>
                                <div class="notif-empty-sub">No new notifications right now. Check back later.</div>
                            </div>
                            <?php else: ?>
                            <div class="notif-list" id="notifList">
                                <!-- Dynamic notifications would render here -->
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="<?= APP_URL ?>/pages/profile.php" class="topbar-user-avatar" title="My Profile">
                        <?php if (!empty($currentUser['avatar_path']) && file_exists(APP_ROOT . '/' . $currentUser['avatar_path'])): ?>
                            <img src="<?= APP_URL . '/' . htmlspecialchars($currentUser['avatar_path']) ?>"
                                 style="width:34px;height:34px;border-radius:50%;object-fit:cover;" alt="Avatar">
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
            <script type="text/javascript">
                function googleTranslateElementInit() {
                  new google.translate.TranslateElement({
                      pageLanguage: 'en',
                      includedLanguages: 'en,bn', // Only English and Bangla
                      layout: google.translate.TranslateElement.InlineLayout.SIMPLE
                  }, 'google_translate_element');
                }
            </script>
            <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

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
            <!-- Task 4: Targeted property dropdown -->
            <div class="form-group">
                <label class="form-label">Related Property (Optional)</label>
                <select class="form-control" id="complaintProperty">
                    <option value="">— General / No specific property —</option>
                    <?php
                    /* Fetch properties related to this user:
                       - As a tenant: the property they live/lived in
                       - As a student: published properties they've applied to
                    */
                    $cpDb = getDB();
                    $cpStmt = $cpDb->prepare(
                        'SELECT DISTINCT p.id, p.name, u_owner.full_name AS owner_name
                           FROM properties p
                           JOIN users u_owner ON u_owner.id = p.owner_id
                          WHERE p.id IN (
                              SELECT r.property_id
                                FROM room_tenants rt
                                JOIN rooms r ON r.id = rt.room_id
                               WHERE rt.user_id = ?
                              UNION
                              SELECT r.property_id
                                FROM applications a
                                JOIN listings l ON l.id = a.listing_id
                                JOIN rooms r    ON r.id = l.room_id
                               WHERE a.applicant_id = ? AND a.deleted_at IS NULL
                          )
                          AND p.is_active = 1
                          ORDER BY p.name'
                    );
                    $cpStmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
                    $cpProps = $cpStmt->fetchAll();
                    foreach ($cpProps as $cp):
                    ?>
                    <option value="<?= $cp['id'] ?>">
                        <?= htmlspecialchars($cp['name']) ?> (Owner: <?= htmlspecialchars($cp['owner_name']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
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
<?php endif; // end complaint modal ?>

<!-- Notification bell JS (all logged-in users) -->
<?php if (isLoggedIn()): ?>
<script>
/* ══════════════════════════════════════════════════════
   Feature 4: Notification Bell JS
   ══════════════════════════════════════════════════════ */
(function() {
    let _open = false;

    window.toggleNotifDropdown = function() {
        const dropdown = document.getElementById('notifDropdown');
        const btn      = document.getElementById('notifBellBtn');
        if (!dropdown) return;
        _open = !_open;
        if (_open) {
            dropdown.classList.add('open');
            btn.classList.add('ringing');
            setTimeout(() => btn.classList.remove('ringing'), 500);
        } else {
            dropdown.classList.remove('open');
        }
    };

    window.markAllRead = function() {
        const badge = document.getElementById('notifBadge');
        if (badge) {
            badge.classList.remove('visible');
            badge.textContent = '';
        }
        // future: POST to mark-read API
    };

    // Close on outside click or Escape
    document.addEventListener('click', function(e) {
        const wrap = document.getElementById('notifBellWrap');
        if (wrap && !wrap.contains(e.target) && _open) {
            const dd = document.getElementById('notifDropdown');
            if (dd) dd.classList.remove('open');
            _open = false;
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && _open) {
            const dd = document.getElementById('notifDropdown');
            if (dd) dd.classList.remove('open');
            _open = false;
        }
    });
})();
</script>
<?php endif; ?>

<?php if (isLoggedIn() && !hasRole('admin')): ?>
<script>
async function submitComplaint() {
    var subj     = document.getElementById('complaintSubject').value.trim();
    var desc     = document.getElementById('complaintDesc').value.trim();
    var anon     = document.getElementById('complaintAnon').checked;
    var propSel  = document.getElementById('complaintProperty');
    var propId   = propSel && propSel.value ? parseInt(propSel.value) : null;
    if (!subj || !desc) { Toast.show('Please fill all fields.', 'error'); return; }
    var btn = document.getElementById('complaintSubmitBtn');
    btn.disabled = true;
    try {
        var r = await fetchAPI(window.APP_URL + '/api/complaints.php', {
            method: 'POST',
            body: JSON.stringify({
                category:    document.getElementById('complaintCategory').value,
                subject:     subj,
                description: desc,
                is_anonymous: anon,
                property_id:  propId
            })
        });
        if (r.success) {
            Toast.show('Complaint submitted successfully.', 'success');
            Modal.close('complaintModal');
            document.getElementById('complaintSubject').value = '';
            document.getElementById('complaintDesc').value = '';
            if (propSel) propSel.value = '';
        }
    } catch(e) { Toast.show('Submission failed. Please try again.', 'error'); }
    btn.disabled = false;
}
</script>
<?php endif; ?>
