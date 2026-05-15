<?php
$pageName = 'Admin';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin']);

$db = getDB();

$stats = [];
$stats['users'] = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$stats['students'] = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$stats['owners'] = $db->query("SELECT COUNT(*) FROM users WHERE role = 'owner'")->fetchColumn();
$stats['listings'] = $db->query("SELECT COUNT(*) FROM listings WHERE deleted_at IS NULL")->fetchColumn();
$stats['applications'] = $db->query("SELECT COUNT(*) FROM applications WHERE deleted_at IS NULL")->fetchColumn();
$stats['properties'] = $db->query("SELECT COUNT(*) FROM properties")->fetchColumn();

$domains = $db->query('SELECT * FROM allowed_domains ORDER BY domain')->fetchAll();
$allUsers = $db->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
$allListings = $db->query('SELECT l.*, r.rent_amount, p.name AS property_name, u.full_name AS creator_name FROM listings l JOIN rooms r ON r.id = l.room_id JOIN properties p ON p.id = r.property_id JOIN users u ON u.id = l.created_by ORDER BY l.created_at DESC')->fetchAll();
$allApps = $db->query('SELECT a.*, l.title AS listing_title, u.full_name AS applicant_name, u.email AS applicant_email FROM applications a JOIN listings l ON l.id = a.listing_id JOIN users u ON u.id = a.applicant_id WHERE a.deleted_at IS NULL ORDER BY a.applied_at DESC')->fetchAll();
$allProps = $db->query('SELECT p.*, u.full_name AS owner_name FROM properties p JOIN users u ON u.id = p.owner_id ORDER BY p.created_at DESC')->fetchAll();
$amenities = $db->query('SELECT * FROM amenities ORDER BY sort_order')->fetchAll();

$ownerApps   = $db->query('SELECT * FROM owner_applications ORDER BY created_at DESC')->fetchAll();
$complaints  = $db->query("SELECT c.*, p.name AS property_name FROM complaints c LEFT JOIN properties p ON p.id = c.property_id ORDER BY c.created_at DESC")->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-value"><?= $stats['users'] ?></div><div class="stat-label">Total Users</div></div>
    <div class="stat-card"><div class="stat-value"><?= $stats['students'] ?></div><div class="stat-label">Students</div></div>
    <div class="stat-card"><div class="stat-value"><?= $stats['owners'] ?></div><div class="stat-label">Owners</div></div>
    <div class="stat-card"><div class="stat-value"><?= $stats['listings'] ?></div><div class="stat-label">Listings</div></div>
</div>

<style>
.tabs { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:20px; background:var(--bg-secondary); border:1px solid var(--border); border-radius:var(--radius-lg); padding:8px; }
.tab-btn {
    flex:1; min-width:80px;
    display:flex; flex-direction:column; align-items:center; gap:5px;
    padding:10px 8px; border:none; border-radius:var(--radius-sm);
    background:transparent; cursor:pointer; color:var(--text-tertiary);
    font-size:0.7rem; font-weight:600; font-family:inherit;
    text-transform:uppercase; letter-spacing:0.04em;
    transition:all 0.18s;
}
.tab-btn svg { width:20px; height:20px; stroke:currentColor; transition:stroke 0.18s; }
.tab-btn:hover { background:var(--bg-tertiary); color:var(--text-primary); }
.tab-btn.active { background:var(--accent); color:#fff; }
.tab-btn.active svg { stroke:#fff; }
</style>
<div class="tabs">
    <button class="tab-btn active" onclick="showTab('users',this)">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
        Users
    </button>
    <button class="tab-btn" onclick="showTab('owner_apps',this)">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
        Owner Requests
    </button>
    <button class="tab-btn" onclick="showTab('listings',this)">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5L12 3l9 6.5V21H3V9.5z"/><rect x="9" y="14" width="6" height="7" rx="1"/></svg>
        Listings
    </button>
    <button class="tab-btn" onclick="showTab('apps',this)">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
        Applications
    </button>
    <button class="tab-btn" onclick="showTab('properties',this)">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
        Properties
    </button>
    <button class="tab-btn" onclick="showTab('complaints',this)">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        Complaints
    </button>
    <button class="tab-btn" onclick="showTab('amenities',this)">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        Amenities
    </button>
    <button class="tab-btn" onclick="showTab('domains',this)">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 010 20M12 2a15.3 15.3 0 000 20"/></svg>
        Domains
    </button>
</div>

<div class="tab-content" id="tab-users">
    <div class="card"><div class="card-body" style="overflow-x:auto;">
        <table class="data-table">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($allUsers as $u): ?>
            <tr>
                <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge badge-draft"><?= $u['role'] ?></span></td>
                <td><?= $u['is_active'] ? 'Active' : 'Disabled' ?></td>
                <td>
                    <button class="btn btn-sm" onclick="toggleUser(<?= $u['id'] ?>, <?= $u['is_active'] ? 0 : 1 ?>)">Toggle</button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div>

<div class="tab-content" id="tab-owner_apps" style="display:none;">
    <div class="card"><div class="card-body" style="overflow-x:auto;">
        <table class="data-table">
            <thead><tr><th>Applicant</th><th>Info</th><th>Docs</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($ownerApps as $oa): ?>
            <tr>
                <td><strong><?= htmlspecialchars($oa['full_name']) ?></strong><br><?= htmlspecialchars($oa['email']) ?><br><?= htmlspecialchars($oa['phone']) ?></td>
                <td><?= htmlspecialchars($oa['address']) ?><br><small><?= htmlspecialchars($oa['extra_info']) ?></small></td>
                <td>
                    <a href="<?= APP_URL ?>/<?= $oa['nid_path'] ?>" target="_blank">NID</a> | 
                    <a href="<?= APP_URL ?>/<?= $oa['electricity_bill_path'] ?>" target="_blank">Bill</a> | 
                    <a href="<?= APP_URL ?>/<?= $oa['photo_path'] ?>" target="_blank">Photo</a>
                </td>
                <td><span class="badge"><?= $oa['status'] ?></span></td>
                <td>
                    <?php if ($oa['status'] === 'pending'): ?>
                    <button class="btn btn-sm btn-success" onclick="approveOwner(<?= $oa['id'] ?>)">Approve</button>
                    <button class="btn btn-sm btn-danger" onclick="rejectOwner(<?= $oa['id'] ?>)">Reject</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div>

<div class="tab-content" id="tab-listings" style="display:none;">
    <div class="card"><div class="card-body" style="overflow-x:auto;">
        <table class="data-table">
            <thead><tr><th>Title</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($allListings as $l): ?>
            <tr>
                <td><?= htmlspecialchars($l['title']) ?></td>
                <td><?= $l['status'] ?></td>
                <td><button class="btn btn-sm btn-danger" onclick="adminListingAction(<?= $l['id'] ?>, 'delete')">Delete</button></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div>

<div class="tab-content" id="tab-apps" style="display:none;">
    <div class="card"><div class="card-body" style="overflow-x:auto;">
        <table class="data-table">
            <thead><tr><th>Applicant</th><th>Listing</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($allApps as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['applicant_name']) ?></td>
                <td><?= htmlspecialchars($a['listing_title']) ?></td>
                <td><?= $a['status'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div>

<div class="tab-content" id="tab-properties" style="display:none;">
    <div class="card"><div class="card-body" style="overflow-x:auto;">
        <table class="data-table">
            <thead><tr><th>Name</th><th>Owner</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($allProps as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['owner_name']) ?></td>
                <td><?= $p['is_active'] ? 'Active' : 'Inactive' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div>

<!-- ── Complaints Tab (Admin-only review, no submission) ── -->
<div class="tab-content" id="tab-complaints" style="display:none;">
    <div class="card"><div class="card-body" style="overflow-x:auto;">
        <p style="font-size:0.82rem;color:var(--text-tertiary);margin-bottom:16px;">Complaints routed exclusively to Admin. Identity shown only for non-anonymous submissions.</p>
        <?php if (count($complaints) === 0): ?>
        <div class="empty-state"><div class="empty-state-icon">📭</div><p>No complaints yet.</p></div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Date</th><th>Category</th><th>Subject</th><th>Submitter</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($complaints as $c): ?>
            <tr>
                <td><small><?= date('M j, Y', strtotime($c['created_at'])) ?></small></td>
                <td><span class="badge badge-draft"><?= ucfirst($c['category']) ?></span></td>
                <td>
                    <strong><?= htmlspecialchars($c['subject']) ?></strong><br>
                    <small style="color:var(--text-tertiary);"><?= htmlspecialchars(substr($c['description'], 0, 80)) ?>...</small>
                    <?php if ($c['admin_note']): ?>
                    <div style="margin-top:4px;font-size:0.75rem;color:var(--info);">Note: <?= htmlspecialchars($c['admin_note']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($c['submitter_name'])): ?>
                        <strong><?= htmlspecialchars($c['submitter_name']) ?></strong><br>
                        <small><?= htmlspecialchars($c['submitter_email'] ?? '') ?></small>
                    <?php else: ?>
                        <span class="badge badge-closed">Anonymous</span>
                    <?php endif; ?>
                </td>
                <td>
                    <select onchange="updateComplaint(<?= $c['id'] ?>, {status:this.value})" style="font-size:0.78rem;padding:4px 6px;border:1px solid var(--border);border-radius:4px;background:var(--bg-tertiary);color:var(--text-primary);">
                        <?php foreach (['open','under_review','resolved','dismissed'] as $s): ?>
                        <option value="<?= $s ?>" <?= $c['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <button class="btn btn-sm btn-danger" onclick="deleteComplaint(<?= $c['id'] ?>)">Delete</button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div></div>
</div>

<div class="tab-content" id="tab-amenities" style="display:none;">
    <div class="card"><div class="card-body">
        <div style="display:flex;gap:10px;margin-bottom:20px;">
            <input class="form-control" id="amIcon" placeholder="Emoji">
            <input class="form-control" id="amSlug" placeholder="slug">
            <input class="form-control" id="amLabel" placeholder="Label">
            <button class="btn btn-primary" onclick="addAmenity()">Add</button>
        </div>
        <?php foreach ($amenities as $am): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:8px;border-bottom:1px solid var(--border);">
            <span><?= $am['icon'] ?></span>
            <span style="flex:1"><?= htmlspecialchars($am['label']) ?></span>
            <button class="btn btn-sm btn-ghost" onclick="deleteAmenity(<?= $am['id'] ?>)">🗑️</button>
        </div>
        <?php endforeach; ?>
    </div></div>
</div>

<div class="tab-content" id="tab-domains" style="display:none;">
    <div class="card"><div class="card-body">
        <div style="display:flex;gap:10px;margin-bottom:16px;">
            <input class="form-control" id="newDom" placeholder="domain.com">
            <button class="btn btn-primary" onclick="addDomain()">Add</button>
        </div>
        <?php foreach ($domains as $d): ?>
        <div style="display:flex;justify-content:space-between;padding:8px;border-bottom:1px solid var(--border);">
            <span><?= htmlspecialchars($d['domain']) ?></span>
            <button class="btn btn-sm btn-ghost" onclick="removeDomain(<?= $d['id'] ?>)">Remove</button>
        </div>
        <?php endforeach; ?>
    </div></div>
</div>

<script>
function showTab(name, btn) {
    document.querySelectorAll('.tab-content').forEach(function(t) { t.style.display = 'none'; });
    document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
    document.getElementById('tab-' + name).style.display = 'block';
    btn.classList.add('active');
}

async function approveOwner(id) {
    if (!confirm('Approve this owner application?')) return;
    var resp = await fetch(window.APP_URL + '/api/admin-owner-action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, action: 'approve' })
    });
    var data = await resp.json();
    if (data.success) { location.reload(); }
}

async function rejectOwner(id) {
    if (!confirm('Reject this owner application?')) return;
    var resp = await fetch(window.APP_URL + '/api/admin-owner-action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, action: 'reject' })
    });
    var data = await resp.json();
    if (data.success) { location.reload(); }
}

async function toggleUser(id, st) {
    await fetch(window.APP_URL + '/api/admin.php?action=toggle_user', {
        method: 'POST',
        body: JSON.stringify({ user_id: id, is_active: st })
    });
    location.reload();
}

async function updateComplaint(id, data) {
    await fetch(window.APP_URL + '/api/complaints.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ id: id, ...data })
    });
    Toast && Toast.show('Updated', 'success');
}

async function deleteComplaint(id) {
    if (!confirm('Permanently delete this complaint?')) return;
    var r = await fetch(window.APP_URL + '/api/complaints.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ id: id })
    });
    var d = await r.json();
    if (d.success) location.reload();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
