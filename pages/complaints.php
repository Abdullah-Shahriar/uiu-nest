<?php
/** UIU Nest — Public Complaints Page */
$pageName = 'Complaints';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$db = getDB();

// Fetch all complaints except dismissed
$stmt = $db->query(
    "SELECT c.*, 
            u.full_name AS submitter_name, u.avatar_path,
            p.name AS property_name
     FROM complaints c
     LEFT JOIN users u ON u.id = c.submitter_id
     LEFT JOIN properties p ON p.id = c.property_id
     WHERE c.status != 'dismissed'
     ORDER BY c.created_at DESC"
);
$complaints = $stmt->fetchAll();

function getTimeActionStr($created_at, $resolved_at, $status) {
    $created = new DateTime($created_at);
    if ($status === 'resolved' && $resolved_at) {
        $resolved = new DateTime($resolved_at);
        $diff = $created->diff($resolved);
        if ($diff->days > 0) return "Resolved in " . $diff->days . " days";
        if ($diff->h > 0) return "Resolved in " . $diff->h . " hours";
        if ($diff->i > 0) return "Resolved in " . $diff->i . " minutes";
        return "Resolved almost immediately";
    } else {
        $now = new DateTime();
        $diff = $created->diff($now);
        $timeStr = "Pending for ";
        if ($diff->days > 0) $timeStr .= $diff->days . " days";
        elseif ($diff->h > 0) $timeStr .= $diff->h . " hours";
        else $timeStr .= $diff->i . " minutes";
        return $timeStr;
    }
}
?>
<style>
.complaints-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.complaints-grid {
    display: grid;
    gap: 20px;
    grid-template-columns: 1fr;
}
.complaint-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.complaint-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.complaint-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #fff;
}
.complaint-meta {
    font-size: 0.8rem;
    color: var(--text-tertiary);
    display: flex;
    gap: 12px;
    align-items: center;
    margin-top: 4px;
}
.complaint-body {
    font-size: 0.95rem;
    color: var(--text-secondary);
    line-height: 1.5;
}
.complaint-action-time {
    margin-top: auto;
    font-size: 0.85rem;
    color: var(--accent);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}
.c-badge {
    padding: 4px 8px;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}
.c-open { background: rgba(234, 179, 8, 0.1); color: var(--warning); border: 1px solid rgba(234, 179, 8, 0.2); }
.c-review { background: rgba(56, 189, 248, 0.1); color: var(--accent); border: 1px solid rgba(56, 189, 248, 0.2); }
.c-resolved { background: rgba(34, 197, 94, 0.1); color: var(--success); border: 1px solid rgba(34, 197, 94, 0.2); }
</style>

<div class="content-container">
    <div class="complaints-header">
        <div>
            <h2 class="page-title">Community Complaints</h2>
            <p class="text-secondary">View and track the status of all non-dismissed complaints.</p>
        </div>
        <button class="btn btn-primary" onclick="Modal.open('complaintModal')">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px;"><path d="M12 5v14M5 12h14"/></svg>
            Submit Complaint
        </button>
    </div>

    <div class="complaints-grid">
        <?php if (empty($complaints)): ?>
            <div class="empty-state" style="padding: 40px; text-align: center; color: var(--text-tertiary);">No complaints have been filed yet.</div>
        <?php else: ?>
            <?php foreach ($complaints as $c): 
                $statusClass = match($c['status']) {
                    'resolved' => 'c-resolved',
                    'under_review' => 'c-review',
                    default => 'c-open'
                };
                $statusLabel = str_replace('_', ' ', $c['status']);
                $submitter = $c['is_anonymous'] ? 'Anonymous' : ($c['submitter_name'] ?? 'Unknown');
                $propStr = $c['property_name'] ? " • Property: " . htmlspecialchars($c['property_name']) : "";
                $timeAction = getTimeActionStr($c['created_at'], $c['resolved_at'], $c['status']);
            ?>
            <div class="complaint-card">
                <div class="complaint-header">
                    <div>
                        <div class="complaint-title"><?= htmlspecialchars($c['subject']) ?></div>
                        <div class="complaint-meta">
                            <span>By <?= htmlspecialchars($submitter) ?></span>
                            <span>• <?= date('M j, Y g:i A', strtotime($c['created_at'])) ?></span>
                            <?= $propStr ?>
                        </div>
                    </div>
                    <div class="c-badge <?= $statusClass ?>"><?= $statusLabel ?></div>
                </div>
                <div class="complaint-body">
                    <?= nl2br(htmlspecialchars($c['description'])) ?>
                </div>
                
                <?php if ($c['admin_note']): ?>
                <div style="background:rgba(255,255,255,0.05); padding:12px; border-radius:8px; font-size:0.85rem; border-left: 3px solid var(--accent); margin-top: 8px;">
                    <strong style="color:#fff;">Admin Note:</strong> <?= nl2br(htmlspecialchars($c['admin_note'])) ?>
                </div>
                <?php endif; ?>

                <div class="complaint-action-time" style="margin-top: 8px;">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?= $timeAction ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
