<?php
require_once __DIR__ . '/../includes/header.php';
$pageName = 'Public Comments';
?>

<div class="dash-hero">
    <div>
        <div class="dash-hero-title">Public Comment Section</div>
        <div class="dash-hero-sub">View feedback and complaints from residents</div>
    </div>
</div>

<div class="card" style="margin-top: 24px;">
    <div class="card-body" id="fullComplaintsList">
        <div style="padding:40px; text-align:center; color:var(--text-tertiary);">Loading comments...</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    try {
        const res = await fetch(APP_URL + '/api/complaints.php');
        const data = await res.json();
        const list = document.getElementById('fullComplaintsList');
        if (data.complaints && data.complaints.length > 0) {
            list.innerHTML = data.complaints.map(c => `
                <div style="border-bottom:1px solid var(--border); padding-bottom:16px; margin-bottom:16px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                        <div style="font-weight:700; color:var(--text-primary); font-size: 1.1rem; display:flex; align-items:center; gap:8px;">
                            ${escapeHtml(c.subject)}
                            <span style="font-size:0.65rem; padding:3px 8px; border-radius:12px; text-transform:uppercase; font-weight:800; letter-spacing:0.05em; background:${c.status==='resolved'?'var(--success-light)':(c.status==='under_review'?'var(--warning-light)':'var(--bg-tertiary)')}; color:${c.status==='resolved'?'var(--success)':(c.status==='under_review'?'var(--warning)':'var(--text-secondary)')};">${escapeHtml(c.status).replace('_', ' ')}</span>
                        </div>
                        <span style="font-size:0.75rem; color:var(--text-tertiary); background:var(--bg-tertiary); padding:4px 8px; border-radius:12px;">
                            ${new Date(c.created_at).toLocaleDateString()}
                        </span>
                    </div>
                    <div style="color:var(--text-secondary); margin-bottom:12px; line-height: 1.5; font-size:0.95rem;">
                        ${escapeHtml(c.description)}
                    </div>
                    ${c.admin_note ? `
                    <div style="background:var(--accent-light); border-left:3px solid var(--accent); padding:10px 14px; border-radius:4px; margin-bottom:12px; font-size:0.85rem;">
                        <strong style="color:var(--accent); display:block; margin-bottom:4px; font-size:0.75rem; text-transform:uppercase;">Admin Action Taken:</strong>
                        <div style="color:var(--text-primary); line-height:1.4;">${escapeHtml(c.admin_note)}</div>
                    </div>` : ''}
                    <div style="display:flex; align-items:center; gap:8px; font-size:0.85rem; color:var(--text-tertiary);">
                        <div style="width:24px; height:24px; border-radius:50%; background:var(--border); display:flex; align-items:center; justify-content:center; color:var(--text-secondary); font-weight:bold; font-size:10px;">
                            ${(c.submitter_name||'A')[0].toUpperCase()}
                        </div>
                        <strong style="color:var(--text-primary);">${escapeHtml(c.submitter_name || 'Anonymous User')}</strong>
                        <span>&bull;</span>
                        <span>${escapeHtml(c.property_name || 'General Query')}</span>
                    </div>
                </div>
            `).join('');
        } else {
            list.innerHTML = '<div style="padding:40px; text-align:center; color:var(--text-tertiary);">No comments or complaints found.</div>';
        }
    } catch(e) {
        document.getElementById('fullComplaintsList').innerHTML = '<div style="color:var(--danger); padding:20px;">Failed to load comments.</div>';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
