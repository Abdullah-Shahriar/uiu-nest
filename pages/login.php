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
    <meta name="description" content="Login to UIU Nest — Student housing near United International University">
    <title>Login — UIU Nest</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <script>
        (function() {
            var saved = localStorage.getItem('uiu-theme');
            var theme = 'light';
            if (saved) {
                theme = saved;
            } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                theme = 'dark';
            }
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <style>
        .login-toggle-wrap {
            display: flex;
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: 28px;
        }
        .login-toggle-btn {
            flex: 1;
            padding: 13px 10px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: inherit;
            color: var(--text-tertiary);
            transition: background 0.2s, color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }
        .login-toggle-btn.active {
            background: var(--accent);
            color: #fff;
        }
        .login-toggle-btn:not(.active):hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        .email-hint {
            font-size: 0.78rem;
            margin-top: 5px;
            padding: 6px 10px;
            border-radius: var(--radius-sm);
        }
        .email-hint-student {
            background: var(--accent-light);
            color: var(--accent);
        }
        .email-hint-owner {
            background: var(--success-light);
            color: var(--success);
        }
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-card" style="max-width:440px;">

        <div style="text-align:center;margin-bottom:20px;">
            <a href="<?= APP_URL ?>/pages/dashboard.php" style="text-decoration:none;display:inline-block;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 210 65" width="180" height="56" aria-label="UIU Nest">
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
        </div>

        <div class="login-toggle-wrap">
            <button class="login-toggle-btn active" id="toggleStudent" onclick="switchLoginMode('student')" type="button">
                🎓 Student
            </button>
            <button class="login-toggle-btn" id="toggleOwner" onclick="switchLoginMode('owner')" type="button">
                🏢 Property Owner
            </button>
        </div>

        <div id="loginHeader">
            <h2 style="margin-bottom:4px;text-align:center;" id="loginTitle">Student Login</h2>
            <p style="text-align:center;font-size:0.85rem;color:var(--text-tertiary);margin-bottom:20px;" id="loginSub">Sign in with your university email</p>
        </div>

        <div id="loginError" style="display:none;padding:11px 14px;background:var(--danger-light);color:var(--danger);border-radius:var(--radius-sm);margin-bottom:16px;font-size:0.85rem;"></div>

        <form id="loginForm" onsubmit="handleLogin(event)">
            <div class="form-group">
                <label class="form-label" for="loginEmail">Email Address</label>
                <input class="form-control" type="email" id="loginEmail"
                    placeholder="you@uiu.ac.bd"
                    required autocomplete="email">
                <div class="email-hint email-hint-student" id="emailHint">
                    🎓 Use your UIU university email (e.g. you@uiu.ac.bd)
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="loginPassword">Password</label>
                <div style="position:relative;">
                    <input class="form-control" type="password" id="loginPassword"
                        placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" onclick="togglePw()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-tertiary);padding:0;font-size:1rem;" id="pwEye">👁️</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" id="loginBtn" style="width:100%;margin-top:4px;">
                Sign In
            </button>
        </form>

        <div style="text-align:center;margin-top:20px;font-size:0.875rem;color:var(--text-tertiary);">
            Don't have an account?
            <a href="<?= APP_URL ?>/pages/register.php" style="font-weight:600;">Sign Up</a>
        </div>

        <div id="ownerApplyLink" style="display:none;text-align:center;margin-top:12px;font-size:0.85rem;padding:12px;background:var(--bg-tertiary);border-radius:var(--radius-sm);border:1px solid var(--border);">
            New owner? <a href="<?= APP_URL ?>/pages/register.php#owner" style="font-weight:600;">Apply for owner access →</a>
        </div>
    </div>
</div>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script>
var currentMode = 'student';
var pwVisible = false;

function switchLoginMode(mode) {
    currentMode = mode;

    var studentBtn  = document.getElementById('toggleStudent');
    var ownerBtn    = document.getElementById('toggleOwner');
    var titleEl     = document.getElementById('loginTitle');
    var subEl       = document.getElementById('loginSub');
    var emailInput  = document.getElementById('loginEmail');
    var hintEl      = document.getElementById('emailHint');
    var applyLink   = document.getElementById('ownerApplyLink');
    var errEl       = document.getElementById('loginError');

    errEl.style.display = 'none';

    if (mode === 'student') {
        studentBtn.classList.add('active');
        ownerBtn.classList.remove('active');

        titleEl.textContent = 'Student Login';
        subEl.textContent = 'Sign in with your university email';

        emailInput.placeholder = 'you@uiu.ac.bd';

        hintEl.className = 'email-hint email-hint-student';
        hintEl.textContent = '🎓 Use your UIU university email (e.g. you@uiu.ac.bd)';

        applyLink.style.display = 'none';

    } else {
        ownerBtn.classList.add('active');
        studentBtn.classList.remove('active');

        titleEl.textContent = 'Owner Login';
        subEl.textContent = 'Sign in with your registered email';

        emailInput.placeholder = 'yourname@gmail.com';

        hintEl.className = 'email-hint email-hint-owner';
        hintEl.textContent = '🏢 No email restrictions for property owners';

        applyLink.style.display = 'block';
    }

    emailInput.value = '';
    emailInput.focus();
}

function togglePw() {
    var input = document.getElementById('loginPassword');
    if (pwVisible) {
        input.type = 'password';
        pwVisible = false;
    } else {
        input.type = 'text';
        pwVisible = true;
    }
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
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ email: email, password: password })
        });

        var data = await resp.json();

        if (data.success) {
            window.location.href = '<?= APP_URL ?>/pages/dashboard.php';
        } else {
            errEl.textContent = data.message;
            errEl.style.display = 'block';
        }

    } catch (err) {
        errEl.textContent = 'Connection error. Please check your internet and try again.';
        errEl.style.display = 'block';
    }

    btn.disabled = false;
    btn.textContent = 'Sign In';
}
</script>
</body>
</html>
