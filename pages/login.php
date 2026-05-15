<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/pages/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to UIU Nest — Student Accommodation Management System">
    <title>Sign In — UIU Nest</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <script>
        (function() {
            var t = localStorage.getItem('uiu-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
    <style>
        .login-wrap {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 480px;
        }
        .login-hero {
            background: linear-gradient(145deg, #0f1e3c 0%, #1e3a5f 40%, #1d4ed8 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        .login-hero::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: rgba(37,99,235,0.15);
            top: -100px; right: -100px;
        }
        .login-hero::after {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: rgba(14,165,233,0.10);
            bottom: -50px; left: -50px;
        }
        .login-hero-content { position: relative; z-index: 1; text-align: center; }
        .hero-logo-icon {
            width: 72px; height: 72px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.20);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.2rem;
            backdrop-filter: blur(10px);
        }
        .hero-name {
            font-family: 'Outfit', sans-serif;
            font-size: 3rem;
            font-weight: 900;
            color: #fff;
            letter-spacing: -0.03em;
            line-height: 1;
            margin-bottom: 8px;
        }
        .hero-name span { color: #60a5fa; }
        .hero-tagline {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.45);
            letter-spacing: 0.18em;
            text-transform: uppercase;
            margin-bottom: 32px;
        }
        .hero-features { display: flex; flex-direction: column; gap: 14px; text-align: left; }
        .hero-feat {
            display: flex; align-items: center; gap: 12px;
            color: rgba(255,255,255,0.75);
            font-size: 0.9rem;
        }
        .hero-feat-icon {
            width: 36px; height: 36px;
            background: rgba(255,255,255,0.08);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .login-form-panel {
            background: var(--bg-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 48px;
        }
        .login-form-inner { width: 100%; max-width: 360px; }
        .login-form-header { margin-bottom: 32px; }
        .login-form-header h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 6px;
        }
        .login-form-header p { font-size: 0.875rem; color: var(--text-tertiary); }

        .pw-wrap { position: relative; }
        .pw-eye {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--text-tertiary); font-size: 1rem; padding: 0;
        }
        .pw-eye:hover { color: var(--text-primary); }

        .login-error {
            display: none;
            padding: 11px 14px;
            background: var(--danger-light);
            color: var(--danger);
            border-radius: var(--radius-sm);
            margin-bottom: 16px;
            font-size: 0.85rem;
            border-left: 3px solid var(--danger);
        }
        .login-divider {
            text-align: center;
            font-size: 0.78rem;
            color: var(--text-tertiary);
            margin: 20px 0;
            position: relative;
        }
        .login-divider::before {
            content: '';
            position: absolute;
            top: 50%; left: 0; right: 0;
            height: 1px;
            background: var(--border);
        }
        .login-divider span {
            background: var(--bg-secondary);
            padding: 0 12px;
            position: relative;
        }

        @media (max-width: 768px) {
            .login-wrap { grid-template-columns: 1fr; }
            .login-hero { display: none; }
            .login-form-panel { padding: 32px 20px; align-items: flex-start; padding-top: 48px; }
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <!-- Hero panel -->
    <div class="login-hero">
        <div class="login-hero-content">
            <div class="hero-logo-icon"></div>
            <div class="hero-name">UIU<span>·</span>Nest</div>
            <div class="hero-tagline">Student Accommodation Management</div>
            <div class="hero-features">
                <div class="hero-feat">
                    <div class="hero-feat-icon"></div>
                    <span>Verified properties near UIU campus</span>
                </div>
                <div class="hero-feat">
                    <div class="hero-feat-icon"></div>
                    <span>Streamlined application process</span>
                </div>
                <div class="hero-feat">
                    <div class="hero-feat-icon"></div>
                    <span>Secure identity verification</span>
                </div>
                <div class="hero-feat">
                    <div class="hero-feat-icon">📌</div>
                    <span>Interactive map with distance filter</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Form panel -->
    <div class="login-form-panel">
        <div class="login-form-inner">
            <div class="login-form-header">
                <h2>Welcome back</h2>
                <p>Sign in to your account to continue</p>
            </div>

            <div class="login-error" id="loginError"></div>

            <form id="loginForm" onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label class="form-label" for="loginEmail">Email Address</label>
                    <input class="form-control" type="email" id="loginEmail"
                        placeholder="you@uiu.ac.bd or you@gmail.com"
                        required autocomplete="email">
                </div>

                <div class="form-group">
                    <label class="form-label" for="loginPassword">Password</label>
                    <div class="pw-wrap">
                        <input class="form-control" type="password" id="loginPassword"
                            placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="pw-eye" onclick="togglePw()" id="pwEye">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" id="loginBtn" style="width:100%;margin-top:8px;">
                    Sign In
                </button>
            </form>

            <div class="login-divider"><span>New to UIU Nest?</span></div>

            <a href="<?= APP_URL ?>/pages/register.php" class="btn btn-outline btn-lg" style="width:100%;text-align:center;">
                Create an Account
            </a>

            <p style="text-align:center;margin-top:16px;font-size:0.8rem;color:var(--text-tertiary);">
                Your role (Student / Owner / Admin) is detected automatically from your account.
            </p>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script>
var pwVisible = false;
function togglePw() {
    var input = document.getElementById('loginPassword');
    pwVisible = !pwVisible;
    input.type = pwVisible ? 'text' : 'password';
}

async function handleLogin(e) {
    e.preventDefault();
    var btn   = document.getElementById('loginBtn');
    var errEl = document.getElementById('loginError');
    btn.disabled = true;
    btn.textContent = 'Signing in...';
    errEl.style.display = 'none';

    var email    = document.getElementById('loginEmail').value.trim();
    var password = document.getElementById('loginPassword').value;

    try {
        var resp = await fetch('<?= APP_URL ?>/api/auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ email: email, password: password })
        });
        var data = await resp.json();
        if (data.success) {
            // Redirect based on role returned from server
            var roleRedirects = {
                admin:   '<?= APP_URL ?>/pages/admin.php',
                owner:   '<?= APP_URL ?>/pages/manage-properties.php',
                tenant:  '<?= APP_URL ?>/pages/applications.php',
                student: '<?= APP_URL ?>/pages/dashboard.php'
            };
            window.location.href = roleRedirects[data.user.role] || '<?= APP_URL ?>/pages/dashboard.php';
        } else {
            errEl.textContent = data.message;
            errEl.style.display = 'block';
        }
    } catch (err) {
        errEl.textContent = 'Connection error. Please try again.';
        errEl.style.display = 'block';
    }
    btn.disabled = false;
    btn.textContent = 'Sign In';
}
</script>
</body>
</html>
