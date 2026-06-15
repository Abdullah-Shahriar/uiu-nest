<?php
/** UIU Nest — My Applications */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$pageName = 'My Applications';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="section-header">
    <h2>My Applications</h2>
</div>

<div class="card">
    <div class="card-body" id="appsList" style="min-height:200px;">
        <div class="skeleton" style="height:200px;"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const data = await fetchAPI(`${APP_URL}/api/applications.php`);
        const apps = data.applications || [];
        const el = document.getElementById('appsList');

        if (!apps.length) {
            el.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><h3>No applications yet</h3><p>Browse listings and apply for rooms.</p></div>';
            return;
        }

        el.innerHTML = `<table class="data-table">
            <thead><tr><th>Listing</th><th>Property</th><th>Rent</th><th>Status</th><th>Date</th><th></th></tr></thead>
            <tbody>${apps.map(a => `<tr>
                <td><a href="${APP_URL}/pages/listing-detail.php?id=${a.listing_id}">${escapeHtml(a.title)}</a></td>
                <td>${escapeHtml(a.property_name)}</td>
                <td>৳${Number(a.rent_amount).toLocaleString()}</td>
                <td>${statusBadge(a.status)}</td>
                <td><small>${new Date(a.applied_at).toLocaleDateString()}</small></td>
                <td>${a.status.includes('pending') ? `<button class="btn btn-sm btn-ghost" onclick="withdrawApp(${a.id})">Withdraw</button>` : ''}</td>
            </tr>`).join('')}</tbody></table>`;
    } catch(e) {}
});

function statusBadge(s) {
    const map = {
        pending_tenant_review: ['Pending Tenant', 'pending'],
        pending_owner_review: ['Pending Owner', 'pending'],
        enrolled: ['Enrolled', 'enrolled'],
        rejected_by_tenant: ['Rejected', 'rejected'],
        rejected_by_owner: ['Rejected', 'rejected'],
        withdrawn: ['Withdrawn', 'closed'],
        accepted: ['Accepted', 'published'],
    };
    const [label, cls] = map[s] || [s, 'default'];
    return `<span class="badge badge-${cls}">${label}</span>`;
}

async function withdrawApp(id) {
    if (!confirm('Withdraw this application?')) return;
    await fetchAPI(`${APP_URL}/api/applications.php`, {
        method: 'DELETE',
        body: JSON.stringify({ application_id: id })
    });
    Toast.show('Application withdrawn', 'info');
    setTimeout(() => location.reload(), 800);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

