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
$allApps = $db->query('SELECT a.*, l.title AS listing_title, u.full_name AS applicant_name, u.email AS applicant_email FROM applications a JOIN listings l ON l.id = a.listing_id JOIN users u ON u.id = a.applicant_id WHERE a.deleted_at IS NULL ORDER BY a.application_date DESC')->fetchAll();
$allProps = $db->query('SELECT p.*, u.full_name AS owner_name FROM properties p JOIN users u ON u.id = p.owner_id ORDER BY p.created_at DESC')->fetchAll();
$amenities = $db->query('SELECT * FROM amenities ORDER BY sort_order')->fetchAll();

$ownerApps = $db->query('SELECT * FROM owner_applications ORDER BY created_at DESC')->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-value"><?= $stats['users'] ?></div><div class="stat-label">Total Users</div></div>
    <div class="stat-card"><div class="stat-value"><?= $stats['students'] ?></div><div class="stat-label">Students</div></div>
    <div class="stat-card"><div class="stat-value"><?= $stats['owners'] ?></div><div class="stat-label">Owners</div></div>
    <div class="stat-card"><div class="stat-value"><?= $stats['listings'] ?></div><div class="stat-label">Listings</div></div>
</div>

<div class="tabs" style="flex-wrap:wrap;">
    <button class="tab-btn active" onclick="showTab('users',this)">👥 Users</button>
    <button class="tab-btn" onclick="showTab('owner_apps',this)">🏢 Owner Requests</button>
    <button class="tab-btn" onclick="showTab('listings',this)">🏘️ Listings</button>
    <button class="tab-btn" onclick="showTab('apps',this)">📋 Apps</button>
    <button class="tab-btn" onclick="showTab('properties',this)">🏢 Props</button>
    <button class="tab-btn" onclick="showTab('amenities',this)">✨ Amenities</button>
    <button class="tab-btn" onclick="showTab('domains',this)">🌐 Domains</button>
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
