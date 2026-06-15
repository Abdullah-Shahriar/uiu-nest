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
    <title>Sign Up — UIU Nest</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <script>
        window.APP_URL = "<?= APP_URL ?>";
        (function(){
            var t = localStorage.getItem('uiu-theme');
            if (!t) {
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    t = 'dark';
                } else {
                    t = 'light';
                }
            }
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body>
<div class="auth-page">
    <div class="auth-card" style="max-width:500px;">
        <div class="logo" style="justify-content:center;margin-bottom:20px;">
            <div class="sidebar-logo-icon" style="width:42px;height:42px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;"><path d="M3 9.5L12 3l9 6.5V21H3V9.5z" fill="rgba(255,255,255,0.15)"/><rect x="9" y="14" width="6" height="7" rx="1" fill="rgba(255,255,255,0.35)"/></svg>
            </div>
            <span class="logo-text" style="font-family:'Outfit',sans-serif;font-weight:800;font-size:1.2rem;color:var(--text-primary);">UIU Nest</span>
        </div>

        <div id="regError" style="display:none;padding:10px;background:var(--danger-light);color:var(--danger);border-radius:var(--radius-sm);margin-bottom:16px;font-size:0.85rem;"></div>

        <div class="role-toggle-wrap" style="display:flex;gap:0;border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;margin-bottom:24px;">
            <button id="btnStudent" class="role-toggle-btn active" onclick="switchRole('student')" type="button" style="display:flex;align-items:center;justify-content:center;gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                I'm a Student
            </button>
            <button id="btnOwner" class="role-toggle-btn" onclick="switchRole('owner_apply')" type="button" style="display:flex;align-items:center;justify-content:center;gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
                I'm an Owner
            </button>
        </div>

        <div id="studentPanel">
            <h2 style="margin-bottom:4px;">Create Account</h2>
            <p style="font-size:0.85rem;color:var(--text-tertiary);margin-bottom:20px;">Sign up with your UIU email address</p>

            <form id="registerForm" onsubmit="handleRegister(event)">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input class="form-control" type="text" id="regName" required>
                </div>
                <div class="form-group">
                    <label class="form-label">University Email</label>
                    <input class="form-control" type="email" id="regEmail" placeholder="you@uiu.ac.bd" required>
                    <div class="form-hint">Must be a UIU email address</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input class="form-control" type="password" id="regPass" placeholder="Min 6 characters" required minlength="6">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Student ID</label>
                        <input class="form-control" type="text" id="regStudentId" placeholder="011221001">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select class="form-control" id="regDept">
                            <option value="">Select</option>
                            <option>CSE</option><option>EEE</option><option>BBA</option>
                            <option>Civil</option><option>Economics</option><option>English</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Year</label>
                        <select class="form-control" id="regYear">
                            <option value="">Select</option>
                            <option value="1">1st Year</option><option value="2">2nd Year</option>
                            <option value="3">3rd Year</option><option value="4">4th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select class="form-control" id="regGender">
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg" style="width:100%" id="regBtn">Create Account</button>
            </form>
        </div>

        <div id="ownerPanel" style="display:none;">
            <h2 style="margin-bottom:4px;">Apply to be an Owner</h2>
            <p style="font-size:0.85rem;color:var(--text-tertiary);margin-bottom:20px;">Submit your details — admin will review and approve your account</p>

            <div id="ownerSuccess" style="display:none;text-align:center;padding:30px 20px;">
                <div style="margin-bottom:16px;display:flex;justify-content:center;">
                    <div style="width:56px;height:56px;border-radius:50%;background:var(--success-light);display:flex;align-items:center;justify-content:center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:28px;height:28px;"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                </div>
                <h3>Application Submitted!</h3>
                <p style="margin-top:8px;color:var(--text-tertiary);">The admin will review your application and contact you at the email provided.</p>
                <a href="<?= APP_URL ?>/pages/login.php" class="btn btn-primary" style="margin-top:20px;">Back to Login</a>
            </div>

            <form id="ownerApplyForm" onsubmit="handleOwnerApply(event)" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input class="form-control" type="text" id="ownName" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input class="form-control" type="email" id="ownEmail" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone *</label>
                        <input class="form-control" type="tel" id="ownPhone" placeholder="01XXXXXXXXX" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Property Address *</label>
                    <textarea class="form-control" id="ownAddress" rows="2" required placeholder="Full address of your property"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">NID / National ID (photo) *</label>
                    <input class="form-control" type="file" id="ownNID" accept="image/*,.pdf" required style="padding:8px;">
                </div>
                <div class="form-group">
                    <label class="form-label">Electricity Bill (photo) *</label>
                    <input class="form-control" type="file" id="ownBill" accept="image/*,.pdf" required style="padding:8px;">
                </div>
                <div class="form-group">
                    <label class="form-label">Your Photo *</label>
                    <input class="form-control" type="file" id="ownPhoto" accept="image/*" required style="padding:8px;">
                </div>
                <div class="form-group">
                    <label class="form-label">Anything else you want to tell us</label>
                    <textarea class="form-control" id="ownExtra" rows="2" placeholder="Optional — describe your property briefly"></textarea>
                </div>
                <div style="background:var(--warning-light);border:1px solid var(--warning);border-radius:var(--radius-sm);padding:12px;margin-bottom:16px;font-size:0.82rem;color:var(--warning);display:flex;align-items:flex-start;gap:8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;flex-shrink:0;margin-top:1px;"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Your application will be reviewed by the admin. You will be notified once approved.
                </div>
                <button type="submit" class="btn btn-primary btn-lg" style="width:100%" id="ownBtn">Submit Application</button>
            </form>
        </div>

        <div class="auth-footer">
            Already have an account? <a href="<?= APP_URL ?>/pages/login.php">Sign in</a>
        </div>
    </div>
</div>

<style>
.role-toggle-btn {
    flex: 1;
    padding: 12px;
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 500;
    font-family: inherit;
    color: var(--text-secondary);
    transition: all 0.2s;
}
.role-toggle-btn.active {
    background: var(--accent);
    color: #fff;
}
.role-toggle-btn:hover:not(.active) {
    background: var(--bg-tertiary);
}
</style>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script>
function switchRole(role) {
    if (role === 'student') {
        document.getElementById('studentPanel').style.display = 'block';
        document.getElementById('ownerPanel').style.display = 'none';
        document.getElementById('btnStudent').classList.add('active');
        document.getElementById('btnOwner').classList.remove('active');
    } else {
        document.getElementById('studentPanel').style.display = 'none';
        document.getElementById('ownerPanel').style.display = 'block';
        document.getElementById('btnOwner').classList.add('active');
        document.getElementById('btnStudent').classList.remove('active');
    }
    document.getElementById('regError').style.display = 'none';
}

if (window.location.hash === '#owner') {
    switchRole('owner_apply');
}

async function handleRegister(e) {
    e.preventDefault();
    var btn = document.getElementById('regBtn');
    var errEl = document.getElementById('regError');
    btn.disabled = true;
    btn.textContent = 'Creating account...';
    errEl.style.display = 'none';

    var body = {
        full_name: document.getElementById('regName').value,
        email: document.getElementById('regEmail').value,
        password: document.getElementById('regPass').value,
        role: 'student',
        student_id: document.getElementById('regStudentId').value,
        department: document.getElementById('regDept').value,
        year_of_study: document.getElementById('regYear').value,
        gender: document.getElementById('regGender').value
    };

    try {
        var resp = await fetch('<?= APP_URL ?>/api/auth.php?action=register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(body)
        });
        var data = await resp.json();
        if (data.success) {
            window.location.href = '<?= APP_URL ?>/pages/dashboard.php';
        } else {
            errEl.textContent = data.message;
            errEl.style.display = 'block';
        }
    } catch (err) {
        errEl.textContent = 'Connection error. Please try again.';
        errEl.style.display = 'block';
    }

    btn.disabled = false;
    btn.textContent = 'Create Account';
}

async function handleOwnerApply(e) {
    e.preventDefault();
    var btn = document.getElementById('ownBtn');
    var errEl = document.getElementById('regError');
    btn.disabled = true;
    btn.textContent = 'Submitting...';
    errEl.style.display = 'none';

    var fd = new FormData();
    fd.append('full_name',    document.getElementById('ownName').value);
    fd.append('email',        document.getElementById('ownEmail').value);
    fd.append('phone',        document.getElementById('ownPhone').value);
    fd.append('address',      document.getElementById('ownAddress').value);
    fd.append('extra_info',   document.getElementById('ownExtra').value);
    fd.append('nid',          document.getElementById('ownNID').files[0]);
    fd.append('bill',         document.getElementById('ownBill').files[0]);
    fd.append('photo',        document.getElementById('ownPhoto').files[0]);

    try {
        var resp = await fetch('<?= APP_URL ?>/api/owner-apply.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
        var data = await resp.json();
        if (data.success) {
            document.getElementById('ownerApplyForm').style.display = 'none';
            document.getElementById('ownerSuccess').style.display = 'block';
        } else {
            errEl.textContent = data.error || 'Submission failed. Please try again.';
            errEl.style.display = 'block';
        }
    } catch(err) {
        errEl.textContent = 'Upload failed. Check your connection.';
        errEl.style.display = 'block';
    }

    btn.disabled = false;
    btn.textContent = 'Submit Application';
}
</script>
</body>
</html>
