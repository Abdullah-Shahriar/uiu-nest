<?php
/**
 * UIU Nest — My Home Page
 * Standalone tenant dashboard/portal
 */
$pageName = 'My Home';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

// DB fetch for tenancy and pending move-out requests
$db     = getDB();
$userId = (int)$_SESSION['user_id'];

// 1. Fetch active tenancy
$tenancyStmt = $db->prepare(
    'SELECT rt.id AS rt_id, rt.moved_in_at,
            r.id AS room_id, r.room_number, r.rent_amount, r.amenities_json,
            p.id AS property_id, p.name AS property_name, p.address,
            p.image_path, p.cover_photo_position,
            u.id AS owner_id, u.full_name AS owner_name,
            u.avatar_path AS owner_avatar, u.phone AS owner_phone, u.email AS owner_email
       FROM room_tenants rt
       JOIN rooms r      ON r.id  = rt.room_id
       JOIN properties p ON p.id  = r.property_id
       JOIN users u      ON u.id  = p.owner_id
      WHERE rt.user_id       = :uid
        AND rt.moved_out_at IS NULL
      LIMIT 1'
);
$tenancyStmt->execute([':uid' => $userId]);
$tenancy = $tenancyStmt->fetch(PDO::FETCH_ASSOC);

if (!$tenancy) {
    // Show a beautiful empty state if not a tenant
    ?>
    <div class="section-header">
        <h2>My Home</h2>
    </div>
    <div class="empty-state" style="padding: 60px 20px; text-align: center; max-width: 600px; margin: 40px auto; background: var(--bg-secondary); border-radius: var(--radius-lg); border: 1px solid var(--border);">
        <div class="empty-state-icon" style="font-size: 4.5rem; margin-bottom: 24px;">🏠</div>
        <h3 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 12px; font-family: 'Outfit', sans-serif;">No Active Tenancy Found</h3>
        <p style="margin: 0 auto 24px; color: var(--text-secondary); line-height: 1.6; max-width: 440px;">
            You are not currently registered as a resident in any property. Once your housing application is accepted and the owner assigns you to a room, your home dashboard will appear here.
        </p>
        <a href="<?= APP_URL ?>/pages/dashboard.php" class="btn btn-primary">Browse Properties</a>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// 2. Fetch pending move-out request
$pendingMOR = null;
try {
    $morStmt = $db->prepare(
        'SELECT mor.id, mor.status,
                tr.id  AS tenant_review_done,
                pr.id  AS property_review_done
           FROM move_out_requests mor
           LEFT JOIN tenant_reviews   tr ON tr.move_out_req_id = mor.id
           LEFT JOIN property_reviews pr ON pr.move_out_req_id = mor.id
          WHERE mor.tenant_id = :uid
            AND mor.status NOT IN ("completed","rejected")
          LIMIT 1'
    );
    $morStmt->execute([':uid' => $userId]);
    $pendingMOR = $morStmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $e) {
    $pendingMOR = null;
}

$movedInDate = new DateTime($tenancy['moved_in_at']);
$now         = new DateTime();
$diff        = $movedInDate->diff($now);
$stayDuration = ($diff->y > 0 ? $diff->y . ' yr ' : '') . $diff->m . ' mo';
?>

<style>
.my-home-container {
    display: flex;
    flex-direction: column;
    gap: 24px;
    max-width: 1200px;
    margin: 0 auto;
    padding-bottom: 40px;
    animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.home-hero-card {
    position: relative;
    border-radius: var(--radius-lg);
    overflow: hidden;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
}

.home-hero-cover {
    height: 280px;
    position: relative;
    background: var(--accent-gradient);
    overflow: hidden;
}

.home-hero-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: <?= htmlspecialchars($tenancy['cover_photo_position'] ?? '50% 50%') ?>;
}

.home-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, transparent 20%, rgba(0, 0, 0, 0.75) 100%);
}

.home-hero-badge {
    position: absolute;
    top: 20px;
    left: 24px;
    background: rgba(15, 30, 60, 0.75);
    backdrop-filter: var(--glass-blur);
    color: var(--accent);
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding: 6px 14px;
    border-radius: var(--radius-full);
    border: 1px solid var(--border-strong);
    box-shadow: var(--shadow-sm);
}

.home-hero-content {
    padding: 30px;
    position: relative;
    margin-top: -80px;
    z-index: 2;
}

.home-hero-title-box {
    color: #fff;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
}

.home-hero-title-box h1 {
    font-size: 2.2rem;
    font-weight: 800;
    margin-bottom: 8px;
    font-family: 'Outfit', sans-serif;
}

.home-hero-title-box p {
    color: rgba(255, 255, 255, 0.85);
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.home-hero-title-box p svg {
    width: 16px;
    height: 16px;
    stroke: var(--accent);
}

.home-grid {
    display: grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 24px;
}

@media (max-width: 900px) {
    .home-grid {
        grid-template-columns: 1fr;
    }
    .home-hero-cover {
        height: 200px;
    }
    .home-hero-content {
        margin-top: 0;
        background: var(--bg-secondary);
        padding: 20px;
    }
    .home-hero-title-box {
        color: var(--text-primary);
        text-shadow: none;
    }
    .home-hero-title-box p {
        color: var(--text-secondary);
    }
}

.bento-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 24px;
    box-shadow: var(--shadow-sm);
    display: flex;
    flex-direction: column;
    transition: transform var(--transition), box-shadow var(--transition);
}

.bento-card:hover {
    box-shadow: var(--shadow);
}

.bento-card-title {
    font-size: 1.15rem;
    font-weight: 700;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-primary);
    font-family: 'Outfit', sans-serif;
    border-bottom: 1px solid var(--border);
    padding-bottom: 12px;
}

.bento-card-title svg {
    width: 20px;
    height: 20px;
    stroke: var(--accent);
    fill: none;
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.detail-label {
    font-size: 0.72rem;
    color: var(--text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 700;
}

.detail-val {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
}

.amenities-section {
    border-top: 1px solid var(--border);
    padding-top: 20px;
}

.amenities-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.amenity-pill {
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    color: var(--text-secondary);
    padding: 6px 14px;
    border-radius: var(--radius-full);
    font-size: 0.8rem;
    font-weight: 500;
    transition: all var(--transition-fast);
}
.amenity-pill:hover {
    background: var(--accent-light);
    color: var(--accent);
    border-color: var(--accent);
}

.owner-profile {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 10px 0;
}

.owner-avatar-lg {
    width: 84px;
    height: 84px;
    border-radius: 50%;
    background: var(--accent-gradient);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    font-weight: 800;
    margin-bottom: 16px;
    border: 3px solid var(--border-strong);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.owner-avatar-lg img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.owner-name {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 4px;
    color: var(--text-primary);
}

.owner-role-badge {
    background: var(--accent-light);
    color: var(--accent);
    padding: 4px 12px;
    border-radius: var(--radius-full);
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 24px;
}

.owner-contact-row {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 12px;
    border-top: 1px solid var(--border);
    padding-top: 20px;
}

.contact-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 12px;
    border-radius: var(--radius);
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all var(--transition);
}

.contact-btn.btn-call {
    background: var(--accent);
    color: #fff;
}
.contact-btn.btn-call:hover {
    background: var(--accent-hover);
    box-shadow: var(--shadow-accent);
}

.contact-btn.btn-email {
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    color: var(--text-secondary);
}
.contact-btn.btn-email:hover {
    background: var(--border);
    color: var(--text-primary);
}

/* Offboarding Cards & Stepper */
.offboarding-status-section {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.stepper-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    margin: 24px 0 10px;
    padding: 0 10px;
}

.stepper-line {
    position: absolute;
    top: 20px;
    left: 24px;
    right: 24px;
    height: 4px;
    background: var(--border);
    z-index: 1;
}

.stepper-line-progress {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: var(--success);
    transition: width 0.4s ease;
    z-index: 1;
}

.step-node {
    display: flex;
    flex-direction: column;
    align-items: center;
    z-index: 2;
    position: relative;
    width: 80px;
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--bg-secondary);
    border: 3px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: var(--text-tertiary);
    font-size: 0.9rem;
    transition: all var(--transition);
}

.step-node.active .step-circle {
    border-color: var(--accent);
    color: var(--accent);
    background: var(--bg-tertiary);
    box-shadow: 0 0 12px rgba(56, 189, 248, 0.3);
}

.step-node.completed .step-circle {
    background: var(--success);
    border-color: var(--success);
    color: #fff;
}

.step-label {
    margin-top: 10px;
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--text-tertiary);
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.step-node.active .step-label {
    color: var(--text-primary);
}

.step-node.completed .step-label {
    color: var(--success);
}

.offboarding-alert-card {
    background: var(--bg-tertiary);
    border-left: 4px solid var(--accent);
    padding: 18px;
    border-radius: var(--radius);
    display: flex;
    gap: 14px;
    align-items: flex-start;
}

.offboarding-alert-card.alert-danger {
    border-left-color: var(--danger);
    background: var(--danger-light);
}

.offboarding-alert-card.alert-success {
    border-left-color: var(--success);
    background: var(--success-light);
}

.offboarding-alert-card svg {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.offboarding-alert-card h4 {
    margin-bottom: 4px;
    font-weight: 700;
    color: var(--text-primary);
}

.offboarding-alert-card p {
    font-size: 0.85rem;
    line-height: 1.5;
    margin: 0;
}
</style>

<div class="my-home-container">
    <!-- Hero card with Cover Photo -->
    <div class="home-hero-card">
        <div class="home-hero-cover">
            <?php if (!empty($tenancy['image_path'])): ?>
                <img src="<?= APP_URL . '/' . htmlspecialchars($tenancy['image_path']) ?>" alt="<?= htmlspecialchars($tenancy['property_name']) ?>">
            <?php endif; ?>
            <div class="home-hero-overlay"></div>
            <div class="home-hero-badge">Active Stay</div>
        </div>
        <div class="home-hero-content">
            <div class="home-hero-title-box">
                <h1><?= htmlspecialchars($tenancy['property_name']) ?></h1>
                <p>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <?= htmlspecialchars($tenancy['address']) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="home-grid">
        <!-- Left: Tenancy & Offboarding -->
        <div style="display:flex; flex-direction:column; gap:24px;">
            
            <!-- Tenancy details -->
            <div class="bento-card">
                <h3 class="bento-card-title">
                    <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    Tenancy Information
                </h3>
                
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Room Number</span>
                        <span class="detail-val">Room <?= htmlspecialchars($tenancy['room_number']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Monthly Rent</span>
                        <span class="detail-val">৳<?= number_format($tenancy['rent_amount'], 0) ?> / month</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Move-In Date</span>
                        <span class="detail-val"><?= $movedInDate->format('F j, Y') ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Stay Duration</span>
                        <span class="detail-val"><?= $stayDuration ?></span>
                    </div>
                </div>

                <?php 
                $amenities = json_decode($tenancy['amenities_json'] ?? '[]', true);
                if (!empty($amenities)): 
                ?>
                <div class="amenities-section">
                    <span class="detail-label">Room Amenities</span>
                    <div class="amenities-list">
                        <?php foreach ($amenities as $amenity): ?>
                            <span class="amenity-pill"><?= htmlspecialchars($amenity) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Offboarding status / Move-out card -->
            <div class="bento-card">
                <h3 class="bento-card-title">
                    <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Move-Out Status & Offboarding
                </h3>

                <div class="offboarding-status-section">
                    <?php if ($pendingMOR): ?>
                        <?php
                        // Determine stepper progress
                        $stepWidths = [
                            'pending'           => 12, // Step 1 is active/complete
                            'owner_accepted'    => 45, // Step 2 is complete
                            'owner_review_done' => 78, // Step 3 is complete
                        ];
                        $progressWidth = $stepWidths[$pendingMOR['status']] ?? 12;
                        ?>

                        <div class="stepper-container">
                            <div class="stepper-line">
                                <div class="stepper-line-progress" style="width: <?= $progressWidth ?>%;"></div>
                            </div>
                            
                            <!-- Step 1 -->
                            <div class="step-node completed">
                                <div class="step-circle">✓</div>
                                <span class="step-label">Submitted</span>
                            </div>

                            <!-- Step 2 -->
                            <div class="step-node <?= in_array($pendingMOR['status'], ['owner_accepted', 'owner_review_done']) ? 'completed' : 'active' ?>">
                                <div class="step-circle"><?= in_array($pendingMOR['status'], ['owner_accepted', 'owner_review_done']) ? '✓' : '2' ?></div>
                                <span class="step-label">Approval</span>
                            </div>

                            <!-- Step 3 -->
                            <div class="step-node <?= $pendingMOR['status'] === 'owner_review_done' ? 'completed' : ($pendingMOR['status'] === 'owner_accepted' ? 'active' : '') ?>">
                                <div class="step-circle"><?= $pendingMOR['status'] === 'owner_review_done' ? '✓' : '3' ?></div>
                                <span class="step-label">Owner Review</span>
                            </div>

                            <!-- Step 4 -->
                            <div class="step-node <?= ($pendingMOR['status'] === 'owner_review_done' && !$pendingMOR['property_review_done']) ? 'active' : '' ?>">
                                <div class="step-circle">4</div>
                                <span class="step-label">Your Review</span>
                            </div>
                        </div>

                        <?php if ($pendingMOR['status'] === 'pending'): ?>
                            <div class="offboarding-alert-card">
                                <svg viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                <div>
                                    <h4>Move-Out Request Pending</h4>
                                    <p>Your request has been sent to the owner, <strong><?= htmlspecialchars($tenancy['owner_name']) ?></strong>. You will be notified once they review and approve the request.</p>
                                </div>
                            </div>
                        <?php elseif ($pendingMOR['status'] === 'owner_accepted'): ?>
                            <div class="offboarding-alert-card">
                                <svg viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                <div>
                                    <h4>Request Accepted by Owner</h4>
                                    <p>Owner <strong><?= htmlspecialchars($tenancy['owner_name']) ?></strong> approved your request! They are currently completing their rating and private review of your stay. Once finished, you will be prompted to submit your public review of the property.</p>
                                </div>
                            </div>
                        <?php elseif ($pendingMOR['status'] === 'owner_review_done' && !$pendingMOR['property_review_done']): ?>
                            <div class="offboarding-alert-card alert-success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 8 8 12 12 16"/><line x1="16" y1="12" x2="8" y2="12"/></svg>
                                <div>
                                    <h4>Final Step: Rate Your Stay</h4>
                                    <p>Your owner has completed your review! To finalize your lease termination and officially complete the offboarding process, please submit your feedback regarding your stay at <?= htmlspecialchars($tenancy['property_name']) ?>.</p>
                                </div>
                            </div>
                            <button class="btn btn-primary" style="margin-top: 10px; width: 100%; font-size: 1rem; padding: 12px;"
                                    onclick="MyHome.openPropertyReview(<?= $pendingMOR['id'] ?>)">
                                ★ Review Property &amp; Complete Move-Out
                            </button>
                        <?php endif; ?>

                    <?php else: ?>
                        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 16px; line-height: 1.6;">
                            Planning to move out? Submit a request here. Once the owner accepts, both you and the owner will exchange ratings to complete the offboarding checklist.
                        </p>
                        <div class="offboarding-alert-card alert-danger" style="margin-bottom: 8px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <div>
                                <h4>Important Lease Notice</h4>
                                <p>Please make sure you have discussed this with your owner before requesting. Once finalized, you cannot undo a move-out process.</p>
                            </div>
                        </div>
                        <button class="btn" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; width: 100%; padding: 12px; font-weight: 600;"
                                onclick="MyHome.openLeaveConfirm()">
                            Request Move-Out
                        </button>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Right: Owner details & Guidelines -->
        <div style="display:flex; flex-direction:column; gap:24px;">
            
            <!-- Owner Profile Card -->
            <div class="bento-card">
                <h3 class="bento-card-title">
                    <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    Your Landlord
                </h3>
                
                <div class="owner-profile">
                    <div class="owner-avatar-lg">
                        <?php if (!empty($tenancy['owner_avatar']) && file_exists(APP_ROOT . '/' . $tenancy['owner_avatar'])): ?>
                            <img src="<?= APP_URL . '/' . htmlspecialchars($tenancy['owner_avatar']) ?>" alt="Owner">
                        <?php else: ?>
                            <?= strtoupper(substr($tenancy['owner_name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    
                    <span class="owner-name"><?= htmlspecialchars($tenancy['owner_name']) ?></span>
                    <span class="owner-role-badge">Property Owner</span>
                    
                    <div class="owner-contact-row">
                        <?php if (!empty($tenancy['owner_phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($tenancy['owner_phone']) ?>" class="contact-btn btn-call">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.68A2 2 0 012 .92h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                                Call Landlord
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($tenancy['owner_email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($tenancy['owner_email']) ?>" class="contact-btn btn-email">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                Email Landlord
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Guidelines Card -->
            <div class="bento-card">
                <h3 class="bento-card-title">
                    <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    Help &amp; Guidelines
                </h3>
                <ul style="color: var(--text-secondary); font-size: 0.85rem; padding-left: 20px; display: flex; flex-direction: column; gap: 10px; line-height: 1.5;">
                    <li><strong>Rent Payments:</strong> Rent is typically due by the 5th of each month. Contact your landlord directly if you require assistance or details.</li>
                    <li><strong>Complaints System:</strong> If you face issues with water, electricity, or maintenance, use the <strong>"Submit Complaint"</strong> option on the sidebar navigation.</li>
                    <li><strong>Mutual Rating:</strong> To keep UIU Nest verified and helpful, the offboarding ratings exchanged during move-out are required.</li>
                </ul>
            </div>

        </div>
    </div>
</div>

<!-- ───────────────────────────────────────────────────────────────
     MOVE-OUT CONFIRMATION MODAL
─────────────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="leaveConfirmModal" style="display:none; z-index: 1000;">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <h3 style="color:var(--danger,#ef4444); font-family: 'Outfit', sans-serif;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round"
                     style="width:18px;height:18px;display:inline;vertical-align:middle;margin-right:6px;">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                Request Move-Out
            </h3>
            <button class="modal-close" onclick="MyHome.closeLeaveConfirm()">✕</button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-secondary);font-size:0.88rem;margin-bottom:16px;">
                Submitting a move-out request will notify your owner.
                Once accepted, both you and your owner must complete reviews before your tenancy officially ends.
            </p>
            <div class="form-group">
                <label class="form-label">Message to Owner (optional)</label>
                <textarea class="form-control" id="leaveMessage" rows="3"
                          placeholder="Reason for leaving, move-out date preference..." style="border: 1px solid var(--border); border-radius: var(--radius); padding: 8px; font-size: 0.9rem; width:100%; background: var(--bg-tertiary); color: var(--text-primary);"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="MyHome.closeLeaveConfirm()">Cancel</button>
            <button class="btn" id="leaveSubmitBtn"
                    style="background:#ef4444;color:#fff;border:none;"
                    onclick="MyHome.submitLeave()">
                Submit Request
            </button>
        </div>
    </div>
</div>

<!-- ───────────────────────────────────────────────────────────────
     PROPERTY REVIEW MODAL (tenant reviews property)
─────────────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="propertyReviewModal" style="display:none; z-index: 1000;">
    <div class="modal" style="max-width:460px;">
        <div class="modal-header">
            <h3 style="font-family: 'Outfit', sans-serif;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round"
                     style="width:17px;height:17px;display:inline;vertical-align:middle;margin-right:6px;">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                Review Your Stay at <?= htmlspecialchars($tenancy['property_name']) ?>
            </h3>
            <button class="modal-close" onclick="MyHome.closePropertyReview()">✕</button>
        </div>
        <div class="modal-body">
            <p style="font-size:0.82rem;color:var(--text-tertiary);margin-bottom:16px;">
                Your review is public and helps future students make informed decisions.
            </p>
            <input type="hidden" id="prRequestId" value="">

            <div class="form-group">
                <label class="form-label">Overall Rating *</label>
                <div id="prStarRow" style="display:flex;gap:8px;margin-top:4px;">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                    <button type="button" data-star="<?= $s ?>" onclick="MyHome.setStar('pr', <?= $s ?>)"
                             style="font-size:1.8rem;background:none;border:none;cursor:pointer;
                                    color:var(--border);transition:color 0.12s;line-height:1;"
                             class="pr-star">★</button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" id="prRating" value="5">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:14px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:0.68rem;">Cleanliness</label>
                    <select class="form-control" id="prCleanliness" style="padding:6px 8px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary);">
                        <?php for ($i=5;$i>=1;$i--): ?>
                        <option value="<?=$i?>"><?=$i?>★</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:0.68rem;">Safety</label>
                    <select class="form-control" id="prSafety" style="padding:6px 8px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary);">
                        <?php for ($i=5;$i>=1;$i--): ?>
                        <option value="<?=$i?>"><?=$i?>★</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:0.68rem;">Value/Money</label>
                    <select class="form-control" id="prValue" style="padding:6px 8px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary);">
                        <?php for ($i=5;$i>=1;$i--): ?>
                        <option value="<?=$i?>"><?=$i?>★</option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Your Review</label>
                <textarea class="form-control" id="prComment" rows="4"
                          placeholder="Describe your overall experience living here..." style="border: 1px solid var(--border); border-radius: var(--radius); padding: 8px; font-size: 0.9rem; width:100%; background: var(--bg-tertiary); color: var(--text-primary);"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="MyHome.closePropertyReview()">Later</button>
            <button class="btn btn-primary" id="prSubmitBtn" onclick="MyHome.submitPropertyReview()">
                Submit Review &amp; Complete Move-Out
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    var APP = window.APP_URL || '';

    /* ── Star rating helper ─────────────────────────── */
    window.MyHome = window.MyHome || {};

    MyHome.setStar = function(prefix, val) {
        document.getElementById(prefix + 'Rating').value = val;
        document.querySelectorAll('.' + prefix + '-star').forEach(function(btn) {
            btn.style.color = parseInt(btn.dataset.star) <= val ? '#f59e0b' : 'var(--border)';
        });
    };

    /* Auto-set 5 stars on load */
    MyHome.setStar('pr', 5);

    /* ── Leave flow ──────────────────────────────────── */
    MyHome.openLeaveConfirm = function() {
        var m = document.getElementById('leaveConfirmModal');
        if (m) { m.style.display = 'flex'; }
    };

    MyHome.closeLeaveConfirm = function() {
        var m = document.getElementById('leaveConfirmModal');
        if (m) { m.style.display = 'none'; }
    };

    MyHome.submitLeave = async function() {
        var btn = document.getElementById('leaveSubmitBtn');
        var msg = (document.getElementById('leaveMessage') || {}).value || '';
        btn.disabled = true; btn.textContent = 'Submitting...';
        try {
            var r = await fetch(APP + '/api/move_out.php?action=request_leave', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ message: msg })
            });
            var d = await r.json();
            if (d.success) {
                window.Toast && Toast.show('Move-out request submitted! Your owner will be notified.', 'success');
                MyHome.closeLeaveConfirm();
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                window.Toast && Toast.show(d.error || 'Submission failed.', 'error');
            }
        } catch(e) {
            window.Toast && Toast.show('Network error. Try again.', 'error');
        }
        btn.disabled = false; btn.textContent = 'Submit Request';
    };

    /* ── Property review flow ───────────────────────── */
    MyHome.openPropertyReview = function(requestId) {
        document.getElementById('prRequestId').value = requestId;
        document.getElementById('propertyReviewModal').style.display = 'flex';
    };

    MyHome.closePropertyReview = function() {
        document.getElementById('propertyReviewModal').style.display = 'none';
    };

    MyHome.submitPropertyReview = async function() {
        var btn = document.getElementById('prSubmitBtn');
        var reqId   = parseInt(document.getElementById('prRequestId').value);
        var rating  = parseInt(document.getElementById('prRating').value);
        var comment = (document.getElementById('prComment').value || '').trim();
        var clean   = parseInt(document.getElementById('prCleanliness').value);
        var safety  = parseInt(document.getElementById('prSafety').value);
        var value   = parseInt(document.getElementById('prValue').value);

        if (!comment) { window.Toast && Toast.show('Please write a review comment.', 'error'); return; }

        btn.disabled = true; btn.textContent = 'Saving...';
        try {
            var r = await fetch(APP + '/api/move_out.php?action=submit_property_review', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    request_id: reqId,
                    rating: rating,
                    cleanliness: clean,
                    safety: safety,
                    value_for_money: value,
                    comment: comment
                })
            });
            var d = await r.json();
            if (d.success) {
                window.Toast && Toast.show('Review submitted! Your tenancy is officially complete.', 'success');
                MyHome.closePropertyReview();
                setTimeout(function() { location.reload(); }, 2000);
            } else {
                window.Toast && Toast.show(d.error || 'Submission failed.', 'error');
            }
        } catch(e) {
            window.Toast && Toast.show('Network error. Try again.', 'error');
        }
        btn.disabled = false; btn.textContent = 'Submit Review & Complete Move-Out';
    };
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
