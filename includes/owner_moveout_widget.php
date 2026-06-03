<?php
/**
 * UIU Nest — Owner Move-Out Dashboard (Task 5)
 *
 * Shows an owner all pending move-out requests.
 * - Accept / Reject controls
 * - "Review Tenant" modal that forces the tenant_review submission
 *   before the cycle can complete.
 *
 * Embed this on manage-properties.php or as its own page.
 * Requires the owner to be logged in.
 */

if (!function_exists('isLoggedIn') || !isLoggedIn() || !hasRole('owner')) return;

$db      = getDB();
$ownerId = (int)$_SESSION['user_id'];

/* ── Fetch this owner's move-out requests ──────────────────── */
$stmt = $db->prepare(
    'SELECT mor.*,
            u_t.full_name   AS tenant_name,
            u_t.email       AS tenant_email,
            u_t.avatar_path AS tenant_avatar,
            u_t.department, u_t.student_id,
            p.name          AS property_name,
            r.room_number,
            tr.id           AS tenant_review_id,
            pr.id           AS property_review_id
       FROM move_out_requests mor
       JOIN users u_t         ON u_t.id = mor.tenant_id
       JOIN rooms r           ON r.id   = mor.room_id
       JOIN properties p      ON p.id   = r.property_id
       LEFT JOIN tenant_reviews   tr ON tr.move_out_req_id = mor.id
       LEFT JOIN property_reviews pr ON pr.move_out_req_id = mor.id
      WHERE mor.owner_id = ?
        AND mor.status   NOT IN ("completed","rejected")
      ORDER BY mor.created_at DESC'
);
$stmt->execute([$ownerId]);
$moveOutRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($moveOutRequests)) return; // Nothing to show
?>

<style>
.mor-card {
    border: 1.5px solid rgba(245,158,11,0.3);
    border-radius: var(--radius-lg);
    background: var(--bg-secondary);
    padding: 18px 20px;
    margin-bottom: 14px;
    position: relative;
}
.mor-card::before {
    content: '';
    position: absolute; top: 0; left: 0;
    width: 4px; height: 100%;
    border-radius: var(--radius-lg) 0 0 var(--radius-lg);
    background: #f59e0b;
}
.mor-tenant-row {
    display: flex; align-items: center; gap: 12px; margin-bottom: 12px;
}
.mor-avatar {
    width: 42px; height: 42px; border-radius: 50%;
    background: var(--accent); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; flex-shrink: 0; overflow: hidden;
}
.mor-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
.mor-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
</style>

<div style="margin-bottom: 20px;">
    <h3 style="font-size:0.85rem;text-transform:uppercase;letter-spacing:0.08em;
               color:var(--text-tertiary);margin-bottom:12px;font-weight:700;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"
             style="width:14px;height:14px;display:inline;vertical-align:middle;margin-right:5px;">
            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
            <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        Move-Out Requests (<?= count($moveOutRequests) ?>)
    </h3>

    <?php foreach ($moveOutRequests as $req): ?>
    <div class="mor-card" id="mor-<?= $req['id'] ?>">
        <div class="mor-tenant-row">
            <div class="mor-avatar">
                <?php if (!empty($req['tenant_avatar']) && file_exists(APP_ROOT . '/' . $req['tenant_avatar'])): ?>
                <img src="<?= APP_URL . '/' . htmlspecialchars($req['tenant_avatar']) ?>" alt="Tenant">
                <?php else: ?>
                <?= strtoupper(substr($req['tenant_name'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div>
                <div style="font-weight:600;font-size:0.88rem;">
                    <?= htmlspecialchars($req['tenant_name']) ?>
                </div>
                <div style="font-size:0.72rem;color:var(--text-tertiary);">
                    <?= htmlspecialchars($req['property_name']) ?> · Room <?= htmlspecialchars($req['room_number']) ?>
                </div>
                <?php if ($req['student_id']): ?>
                <div style="font-size:0.7rem;color:var(--text-tertiary);">
                    ID: <?= htmlspecialchars($req['student_id']) ?>
                    <?= $req['department'] ? ' · ' . htmlspecialchars($req['department']) : '' ?>
                </div>
                <?php endif; ?>
            </div>
            <span style="margin-left:auto;font-size:0.68rem;padding:3px 9px;
                         border-radius:20px;background:rgba(245,158,11,0.12);
                         color:#f59e0b;border:1px solid rgba(245,158,11,0.25);font-weight:600;">
                <?= match($req['status']) {
                    'pending'          => 'Pending Your Response',
                    'owner_accepted'   => 'Accepted — Rate Tenant',
                    'owner_review_done'=> 'Waiting for Tenant Review',
                    default            => ucfirst($req['status'])
                } ?>
            </span>
        </div>

        <?php if ($req['tenant_message']): ?>
        <div style="font-size:0.8rem;color:var(--text-secondary);
                    background:var(--bg-tertiary);border-radius:var(--radius-sm);
                    padding:8px 12px;margin-bottom:10px;font-style:italic;">
            "<?= htmlspecialchars($req['tenant_message']) ?>"
        </div>
        <?php endif; ?>

        <div class="mor-actions">
            <?php if ($req['status'] === 'pending'): ?>
            <button class="btn btn-primary btn-sm"
                    onclick="OwnerMOR.accept(<?= $req['id'] ?>)">
                Accept &amp; Continue
            </button>
            <button class="btn btn-ghost btn-sm"
                    style="border-color:rgba(239,68,68,0.3);color:#ef4444;"
                    onclick="OwnerMOR.reject(<?= $req['id'] ?>)">
                Reject
            </button>
            <?php elseif ($req['status'] === 'owner_accepted' && !$req['tenant_review_id']): ?>
            <button class="btn btn-primary btn-sm"
                    onclick="OwnerMOR.openTenantReview(<?= $req['id'] ?>, '<?= htmlspecialchars(addslashes($req['tenant_name'])) ?>')">
                ★ Rate This Tenant
            </button>
            <?php elseif ($req['status'] === 'owner_review_done'): ?>
            <span style="font-size:0.78rem;color:var(--text-tertiary);font-style:italic;">
                Waiting for tenant to review the property...
            </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ─── Tenant Review Modal (Owner rates tenant) ─── -->
<div class="modal-overlay" id="tenantReviewModal" style="display:none;">
    <div class="modal" style="max-width:460px;">
        <div class="modal-header">
            <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round"
                     style="width:17px;height:17px;display:inline;vertical-align:middle;margin-right:6px;">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                Rate Tenant: <span id="trTenantName"></span>
            </h3>
            <button class="modal-close" onclick="OwnerMOR.closeTenantReview()">✕</button>
        </div>
        <div class="modal-body">
            <p style="font-size:0.82rem;color:var(--text-tertiary);margin-bottom:16px;">
                Your review is private and used internally by the platform to track tenant conduct.
            </p>
            <input type="hidden" id="trRequestId" value="">

            <div class="form-group">
                <label class="form-label">Overall Rating *</label>
                <div id="trStarRow" style="display:flex;gap:8px;margin-top:4px;">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                    <button type="button" data-star="<?= $s ?>"
                            onclick="MyHome && MyHome.setStar('tr', <?= $s ?>)"
                            style="font-size:1.8rem;background:none;border:none;cursor:pointer;
                                   color:var(--border);transition:color 0.12s;line-height:1;"
                            class="tr-star">★</button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" id="trRating" value="5">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:14px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:0.68rem;">Cleanliness</label>
                    <select class="form-control" id="trCleanliness" style="padding:6px 8px;">
                        <?php for ($i=5;$i>=1;$i--): ?>
                        <option value="<?=$i?>"><?=$i?>★</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:0.68rem;">Behaviour</label>
                    <select class="form-control" id="trBehaviour" style="padding:6px 8px;">
                        <?php for ($i=5;$i>=1;$i--): ?>
                        <option value="<?=$i?>"><?=$i?>★</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:0.68rem;">Punctuality</label>
                    <select class="form-control" id="trPunctuality" style="padding:6px 8px;">
                        <?php for ($i=5;$i>=1;$i--): ?>
                        <option value="<?=$i?>"><?=$i?>★</option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Private Comments</label>
                <textarea class="form-control" id="trComment" rows="4"
                          placeholder="Notes about this tenant for your own records..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="OwnerMOR.closeTenantReview()">Cancel</button>
            <button class="btn btn-primary" id="trSubmitBtn" onclick="OwnerMOR.submitTenantReview()">
                Submit &amp; Notify Tenant
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    var APP = window.APP_URL || '';

    /* Expose star helper on MyHome (may already exist from my_home_widget) */
    window.MyHome = window.MyHome || {};
    if (!MyHome.setStar) {
        MyHome.setStar = function(prefix, val) {
            document.getElementById(prefix + 'Rating').value = val;
            document.querySelectorAll('.' + prefix + '-star').forEach(function(btn) {
                btn.style.color = parseInt(btn.dataset.star) <= val ? '#f59e0b' : 'var(--border)';
            });
        };
    }

    MyHome.setStar('tr', 5); /* default 5 stars */

    window.OwnerMOR = {

        accept: async function(id) {
            if (!confirm('Accept this move-out request? You will then be asked to rate the tenant.')) return;
            var r = await fetch(APP + '/api/move_out.php?action=owner_accept', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ request_id: id })
            });
            var d = await r.json();
            if (d.success) {
                window.Toast && Toast.show('Accepted! Please rate this tenant now.', 'success');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                window.Toast && Toast.show(d.error || 'Error', 'error');
            }
        },

        reject: async function(id) {
            if (!confirm('Reject this move-out request? The tenant will remain in the room.')) return;
            var r = await fetch(APP + '/api/move_out.php?action=owner_reject', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ request_id: id })
            });
            var d = await r.json();
            if (d.success) {
                window.Toast && Toast.show('Request rejected.', 'info');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                window.Toast && Toast.show(d.error || 'Error', 'error');
            }
        },

        openTenantReview: function(reqId, tenantName) {
            document.getElementById('trRequestId').value = reqId;
            document.getElementById('trTenantName').textContent = tenantName;
            MyHome.setStar('tr', 5);
            document.getElementById('tenantReviewModal').style.display = 'flex';
        },

        closeTenantReview: function() {
            document.getElementById('tenantReviewModal').style.display = 'none';
        },

        submitTenantReview: async function() {
            var btn     = document.getElementById('trSubmitBtn');
            var reqId   = parseInt(document.getElementById('trRequestId').value);
            var rating  = parseInt(document.getElementById('trRating').value);
            var clean   = parseInt(document.getElementById('trCleanliness').value);
            var behav   = parseInt(document.getElementById('trBehaviour').value);
            var punct   = parseInt(document.getElementById('trPunctuality').value);
            var comment = (document.getElementById('trComment').value || '').trim();

            btn.disabled = true; btn.textContent = 'Saving...';
            try {
                var r = await fetch(APP + '/api/move_out.php?action=submit_tenant_review', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({
                        request_id: reqId,
                        rating: rating,
                        cleanliness: clean,
                        behaviour: behav,
                        punctuality: punct,
                        comment: comment
                    })
                });
                var d = await r.json();
                if (d.success) {
                    window.Toast && Toast.show('Review saved! Tenant has been notified to review the property.', 'success');
                    OwnerMOR.closeTenantReview();
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    window.Toast && Toast.show(d.error || 'Submission failed.', 'error');
                }
            } catch(e) {
                window.Toast && Toast.show('Network error.', 'error');
            }
            btn.disabled = false; btn.textContent = 'Submit & Notify Tenant';
        }
    };
})();
</script>
