<?php
/**
 * UIU Nest — Common Header (sidebar navigation, role-aware)
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
    'student' => 'Applicant',
    'admin'   => 'Admin',
    default   => ucfirst($userRole),
};
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="UIU Nest — Find off-campus housing near United International University">
    <title><?= htmlspecialchars($pageName) ?> — UIU Nest</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <script>
        (function(){
            const t = localStorage.getItem('uiu-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body>
    <!-- Mobile overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ═══════════════════════════════════════════════════════════
         SIDEBAR
         ═══════════════════════════════════════════════════════════ -->
    <aside class="sidebar" id="sidebar">

        <!-- Logo -->
        <div class="sidebar-header">
            <a href="<?= APP_URL ?>/pages/dashboard.php" style="text-decoration:none;display:block;padding:6px 0 10px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 210 65" width="170" height="53" aria-label="UIU Nest">
                    <g>
                        <path d="M118 4 L138 18 L158 4" stroke="#E07820" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        <path d="M122 18 L122 30 L154 30 L154 18" stroke="#E07820" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        <rect x="132" y="22" width="10" height="8" rx="1.5" stroke="#E07820" stroke-width="1.6" fill="none"/>
                    </g>
                    <text x="2" y="36" font-family="Arial Black, Arial, sans-serif" font-weight="900" font-size="28" fill="#E07820" letter-spacing="-0.5">UIU</text>
                    <text x="70" y="36" font-family="Arial, sans-serif" font-weight="300" font-size="28" fill="#E07820" letter-spacing="1.5">NEST</text>
                    <line x1="2" y1="41" x2="208" y2="41" stroke="#E07820" stroke-width="0.9" opacity="0.7"/>
                    <text x="2" y="53" font-family="Arial, sans-serif" font-weight="400" font-size="6.5" fill="#E07820" letter-spacing="2">STUDENT ACCOMMODATION MANAGEMENT SYSTEM</text>
                </svg>
            </a>
            <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar">✕</button>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">

            <!-- ── Always visible ── -->
            <a href="<?= APP_URL ?>/pages/dashboard.php"
               class="nav-item <?= $pageName === 'Dashboard' ? 'active' : '' ?>" id="nav-browse">
                <span class="nav-icon">🏘️</span><span class="nav-label">Browse Listings</span>
            </a>

            <?php if (isLoggedIn()): ?>

            <!-- ── Profile (all logged-in users) ── -->
            <a href="<?= APP_URL ?>/pages/profile.php"
               class="nav-item <?= $pageName === 'My Profile' ? 'active' : '' ?>" id="nav-profile">
                <span class="nav-icon">👤</span><span class="nav-label">My Profile</span>
            </a>

            <!-- ── Student / Applicant ── -->
            <?php if (hasAnyRole(['student'])): ?>

                <a href="<?= APP_URL ?>/pages/applications.php"
                   class="nav-item <?= $pageName === 'Applications' ? 'active' : '' ?>" id="nav-apps-student">
                    <span class="nav-icon">📋</span><span class="nav-label">Applications</span>
                </a>

                <a href="<?= APP_URL ?>/pages/saved-listings.php"
                   class="nav-item <?= $pageName === 'Saved' ? 'active' : '' ?>" id="nav-saved">
                    <span class="nav-icon">❤️</span><span class="nav-label">Saved Listings</span>
                </a>

            <?php endif; ?>

            <!-- ── House Manager (tenant) ── -->
            <?php if (hasRole('tenant')): ?>

                <div class="nav-section-label">House Manager</div>

                <a href="<?= APP_URL ?>/pages/applications.php"
                   class="nav-item <?= $pageName === 'Applications' ? 'active' : '' ?>" id="nav-apps-hm">
                    <span class="nav-icon">📋</span><span class="nav-label">Applications</span>
                </a>

                <a href="<?= APP_URL ?>/pages/manage-listings.php"
                   class="nav-item <?= $pageName === 'Manage Listings' ? 'active' : '' ?>" id="nav-listings-hm">
                    <span class="nav-icon">📝</span><span class="nav-label">Manage Listings</span>
                </a>

                <a href="<?= APP_URL ?>/pages/saved-listings.php"
                   class="nav-item <?= $pageName === 'Saved' ? 'active' : '' ?>" id="nav-saved-hm">
                    <span class="nav-icon">❤️</span><span class="nav-label">Saved Listings</span>
                </a>

                <a href="<?= APP_URL ?>/pages/former-residents.php"
                   class="nav-item <?= $pageName === 'Former Residents' ? 'active' : '' ?>" id="nav-alumni-hm">
                    <span class="nav-icon">🕰️</span><span class="nav-label">Former Residents</span>
                </a>

            <?php endif; ?>

            <!-- ── Owner ── -->
            <?php if (hasRole('owner')): ?>

                <div class="nav-section-label">Owner</div>

                <a href="<?= APP_URL ?>/pages/manage-properties.php"
                   class="nav-item <?= $pageName === 'Properties' ? 'active' : '' ?>" id="nav-props">
                    <span class="nav-icon">🏢</span><span class="nav-label">My Properties</span>
                </a>

                <a href="<?= APP_URL ?>/pages/manage-listings.php"
                   class="nav-item <?= $pageName === 'Manage Listings' ? 'active' : '' ?>" id="nav-listings">
                    <span class="nav-icon">📝</span><span class="nav-label">Manage Listings</span>
                </a>

                <a href="<?= APP_URL ?>/pages/applications.php"
                   class="nav-item <?= $pageName === 'Applications' ? 'active' : '' ?>" id="nav-apps-owner">
                    <span class="nav-icon">📋</span><span class="nav-label">Applications</span>
                </a>

                <a href="<?= APP_URL ?>/pages/former-residents.php"
                   class="nav-item <?= $pageName === 'Former Residents' ? 'active' : '' ?>" id="nav-alumni">
                    <span class="nav-icon">🕰️</span><span class="nav-label">Former Residents</span>
                </a>

            <?php endif; ?>

            <!-- ── Admin ── -->
            <?php if (hasRole('admin')): ?>

                <div class="nav-section-label">Admin</div>

                <a href="<?= APP_URL ?>/pages/admin.php"
                   class="nav-item <?= $pageName === 'Admin' ? 'active' : '' ?>" id="nav-admin">
                    <span class="nav-icon">⚙️</span><span class="nav-label">Admin Panel</span>
                </a>

                <a href="<?= APP_URL ?>/pages/applications.php"
                   class="nav-item <?= $pageName === 'Applications' ? 'active' : '' ?>" id="nav-apps-admin">
                    <span class="nav-icon">📋</span><span class="nav-label">All Applications</span>
                </a>

                <a href="<?= APP_URL ?>/pages/former-residents.php"
                   class="nav-item <?= $pageName === 'Former Residents' ? 'active' : '' ?>" id="nav-alumni-admin">
                    <span class="nav-icon">🕰️</span><span class="nav-label">Former Residents</span>
                </a>

            <?php endif; ?>

            <?php endif; // isLoggedIn ?>

        </nav>

        <!-- User card at bottom -->
        <?php if (isLoggedIn()): ?>
        <div class="sidebar-footer">
            <a href="<?= APP_URL ?>/pages/profile.php" style="text-decoration:none;" class="user-card user-card-link">
                <?php if (!empty($currentUser['avatar_path']) && file_exists(APP_ROOT . '/' . $currentUser['avatar_path'])): ?>
                    <img src="<?= APP_URL . '/' . htmlspecialchars($currentUser['avatar_path']) ?>"
                         style="width:38px;height:38px;border-radius:50%;object-fit:cover;" alt="Avatar">
                <?php else: ?>
                    <div class="user-avatar"><?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)) ?></div>
                <?php endif; ?>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($currentUser['full_name'] ?? '') ?></div>
                    <div class="user-role"><?= $roleLabel ?></div>
                </div>
            </a>
            <a href="<?= APP_URL ?>/api/auth.php?action=logout" class="btn btn-ghost btn-sm sidebar-logout" title="Sign Out">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </a>
        </div>
        <?php endif; ?>
    </aside>

    <!-- ═══════════════════════════════════════════════════════════
         MAIN WRAPPER
         ═══════════════════════════════════════════════════════════ -->
    <div class="main-wrapper">
        <!-- Top bar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu">
                    <span></span><span></span><span></span>
                </button>
                <h1 class="page-title"><?= htmlspecialchars($pageName) ?></h1>
            </div>
            <div class="topbar-right">
                <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                    <span class="theme-icon sun">☀️</span>
                    <span class="theme-icon moon">🌙</span>
                </button>
                <?php if (isLoggedIn()): ?>
                    <a href="<?= APP_URL ?>/pages/profile.php" class="topbar-user-avatar" title="My Profile">
                        <?php if (!empty($currentUser['avatar_path']) && file_exists(APP_ROOT . '/' . $currentUser['avatar_path'])): ?>
                            <img src="<?= APP_URL . '/' . htmlspecialchars($currentUser['avatar_path']) ?>"
                                 style="width:36px;height:36px;border-radius:50%;object-fit:cover;" alt="Avatar">
                        <?php else: ?>
                            <div class="user-avatar-sm"><?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)) ?></div>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <a href="<?= APP_URL ?>/pages/login.php" class="btn btn-outline btn-sm">Login</a>
                    <a href="<?= APP_URL ?>/pages/register.php" class="btn btn-primary btn-sm">Sign Up</a>
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
