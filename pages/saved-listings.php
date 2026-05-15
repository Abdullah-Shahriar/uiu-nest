<?php
/** UIU Nest — Saved Listings */
$pageName = 'Saved';
$includeListingsJS = true;
require_once __DIR__ . '/../includes/header.php';
requireLogin();
?>

<div class="section-header">
    <h2>Saved Listings</h2>
</div>

<div id="savedGrid" class="listings-grid">
    <div class="card listing-card skeleton" style="height:320px"></div>
    <div class="card listing-card skeleton" style="height:320px"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const data = await fetchAPI(`${APP_URL}/api/saved.php`);
        const listings = data.listings || [];
        const grid = document.getElementById('savedGrid');

        if (!listings.length) {
            grid.innerHTML = '<div class="empty-state"><div class="empty-state-icon">♥</div><h3>No saved listings</h3><p>Click the heart icon on listings to save them here.</p></div>';
            return;
        }

        grid.innerHTML = listings.map(l => {
            const amenities = JSON.parse(l.amenities_json || '[]');
            return `<div class="card listing-card">
                <div class="listing-card-image">
                    <div style="width:100%;height:180px;background:var(--accent-gradient);display:flex;align-items:center;justify-content:center;font-size:3rem;opacity:0.3;"></div>
                    <span class="rent-badge">৳${Number(l.rent_amount).toLocaleString()}</span>
                    <button class="heart-btn saved" onclick="unsave(event,${l.id},this)">♥</button>
                </div>
                <div class="listing-card-content">
                    <a href="${APP_URL}/pages/listing-detail.php?id=${l.id}" class="listing-card-title">${escapeHtml(l.title)}</a>
                    <div class="listing-card-meta">
                        <span> ${escapeHtml(l.property_name)}</span>
                        <span class="distance-pill">📌 ${l.distance_km} km</span>
                    </div>
                    <div class="listing-card-amenities">${amenities.slice(0,3).map(a=>`<span class="amenity-tag">${a}</span>`).join('')}</div>
                </div>
                <div class="listing-card-footer">
                    <small>${escapeHtml(l.created_by_name)}</small>
                    <a href="${APP_URL}/pages/listing-detail.php?id=${l.id}" class="btn btn-sm btn-outline">View</a>
                </div>
            </div>`;
        }).join('');
    } catch(e) {}
});

async function unsave(event, id, btn) {
    event.preventDefault();
    event.stopPropagation();
    await fetchAPI(`${APP_URL}/api/saved.php`, { method:'POST', body:JSON.stringify({listing_id:id}) });
    btn.closest('.listing-card').remove();
    Toast.show('Removed from saved', 'info');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
