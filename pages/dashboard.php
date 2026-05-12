<?php
$pageName = 'Dashboard';
$includeMapJS = true;
$includeListingsJS = true;
require_once __DIR__ . '/../includes/header.php';

$amenitiesList = getAmenitiesList();
?>

<style>
.dash-hero {
    background: var(--accent-gradient);
    border-radius: var(--radius-lg);
    padding: 22px 28px;
    margin-bottom: 22px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.dash-hero-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 2px;
}
.dash-hero-sub {
    font-size: 0.85rem;
    opacity: 0.88;
}
.dash-hero-count {
    background: rgba(255,255,255,0.18);
    border-radius: var(--radius);
    padding: 10px 20px;
    text-align: center;
    backdrop-filter: blur(10px);
}
.dash-hero-count strong {
    display: block;
    font-size: 1.6rem;
    font-weight: 800;
    line-height: 1;
}
.dash-hero-count span {
    font-size: 0.75rem;
    opacity: 0.85;
}

.pro-filter-wrap {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 18px 20px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-sm);
}
.pro-filter-top {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    flex-wrap: wrap;
}
.pro-filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
    min-width: 120px;
}
.pro-filter-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--text-tertiary);
}
.pro-filter-input {
    background: var(--bg-tertiary);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 8px 10px;
    font-size: 0.85rem;
    color: var(--text-primary);
    font-family: inherit;
    width: 100%;
    transition: border-color 0.18s;
    cursor: pointer;
}
.pro-filter-input:focus {
    border-color: var(--accent);
}
.pro-filter-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    padding-bottom: 0;
    flex-wrap: wrap;
}
.pro-filter-divider {
    height: 1px;
    background: var(--border);
    margin: 14px 0;
}
.pro-filter-amenities-wrap {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    flex-wrap: wrap;
}
.amenity-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--text-tertiary);
    padding-top: 6px;
    white-space: nowrap;
}
.amenity-pill-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    flex: 1;
}
.amenity-pill {
    padding: 5px 13px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-full);
    background: var(--bg-tertiary);
    color: var(--text-secondary);
    font-size: 0.78rem;
    cursor: pointer;
    transition: all 0.16s;
    font-family: inherit;
    font-weight: 500;
    user-select: none;
}
.amenity-pill:hover {
    border-color: var(--accent);
    color: var(--accent);
    background: var(--accent-light);
}
.amenity-pill.selected {
    background: var(--accent);
    border-color: var(--accent);
    color: #fff;
    box-shadow: 0 2px 8px rgba(224,120,32,0.25);
}
.listings-count-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
    flex-wrap: wrap;
    gap: 8px;
}
.listings-count-bar span {
    font-size: 0.82rem;
    color: var(--text-tertiary);
}
.view-toggle {
    display: flex;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    overflow: hidden;
}
.view-toggle-btn {
    padding: 7px 14px;
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 0.85rem;
    font-family: inherit;
    color: var(--text-tertiary);
    transition: all 0.16s;
}
.view-toggle-btn.active {
    background: var(--accent);
    color: #fff;
}
.view-toggle-btn:not(.active):hover {
    background: var(--bg-tertiary);
}
</style>

<div class="dash-hero">
    <div>
        <div class="dash-hero-title">Find Your Perfect Room Near UIU</div>
        <div class="dash-hero-sub">Browse verified properties — filter by rent, amenities & location</div>
    </div>
    <div class="dash-hero-count">
        <strong id="listingCountDisplay">—</strong>
        <span>listings found</span>
    </div>
</div>

<div class="pro-filter-wrap">
    <div class="pro-filter-top">
        <div class="pro-filter-group">
            <label class="pro-filter-label">Min Rent</label>
            <select class="pro-filter-input" id="filterRentMin">
                <option value="">Any</option>
                <option value="6000">৳6,000</option>
                <option value="7000">৳7,000</option>
                <option value="8000">৳8,000</option>
                <option value="9000">৳9,000</option>
                <option value="10000">৳10,000</option>
                <option value="12000">৳12,000</option>
                <option value="14000">৳14,000</option>
                <option value="16000">৳16,000</option>
                <option value="18000">৳18,000</option>
                <option value="20000">৳20,000</option>
            </select>
        </div>
        <div class="pro-filter-group">
            <label class="pro-filter-label">Max Rent</label>
            <select class="pro-filter-input" id="filterRentMax">
                <option value="">Any</option>
                <option value="8000">৳8,000</option>
                <option value="10000">৳10,000</option>
                <option value="12000">৳12,000</option>
                <option value="14000">৳14,000</option>
                <option value="16000">৳16,000</option>
                <option value="18000">৳18,000</option>
                <option value="20000">৳20,000</option>
                <option value="22000">৳22,000</option>
                <option value="24000">৳24,000</option>
            </select>
        </div>
        <div class="pro-filter-group">
            <label class="pro-filter-label">Type</label>
            <select class="pro-filter-input" id="filterType">
                <option value="">All Types</option>
                <option value="owner_direct">Owner Direct</option>
                <option value="roommate_needed">Roommate Needed</option>
            </select>
        </div>
        <div class="pro-filter-group">
            <label class="pro-filter-label">Sort By</label>
            <select class="pro-filter-input" id="filterSort">
                <option value="date_desc">Newest First</option>
                <option value="rent_asc">Rent: Low → High</option>
                <option value="rent_desc">Rent: High → Low</option>
                <option value="distance_asc">Nearest to UIU</option>
            </select>
        </div>
        <div class="pro-filter-actions">
            <button class="btn btn-primary btn-sm" onclick="window.applyFilters()">Search</button>
            <button class="btn btn-ghost btn-sm" onclick="window.clearFilters()">Clear</button>
            <div class="view-toggle">
                <button class="view-toggle-btn active" data-view="list" id="viewList" title="List view">☰</button>
                <button class="view-toggle-btn" data-view="map" id="viewMap" title="Map view">🗺</button>
            </div>
        </div>
    </div>

    <div class="pro-filter-divider"></div>

    <div class="pro-filter-amenities-wrap">
        <span class="amenity-label">Must have:</span>
        <div class="amenity-pill-row">
            <?php foreach ($amenitiesList as $a): ?>
            <button class="amenity-pill" data-slug="<?= $a['slug'] ?>" onclick="togglePill(this)">
                <?= $a['icon'] ?> <?= htmlspecialchars($a['label']) ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="listings-count-bar">
    <span id="listingCountText">Loading listings...</span>
    <span style="font-size:0.78rem;color:var(--text-tertiary);">Click amenity pills to filter</span>
</div>

<div id="listingsGrid" class="listings-grid"></div>

<div id="mapView" style="display:none;">
    <div class="map-container" id="leafletMap"></div>
</div>

<script>
function togglePill(btn) {
    if (btn.classList.contains('selected')) {
        btn.classList.remove('selected');
    } else {
        btn.classList.add('selected');
    }
    clearTimeout(window._pillDebounce);
    window._pillDebounce = setTimeout(function() {
        if (window.loadListings) {
            window.loadListings();
        }
    }, 200);
}

document.addEventListener('DOMContentLoaded', function() {
    var origRender = window.renderListings;

    var origLoad = window.loadListings;
    if (origLoad) {
        var _origLoad = origLoad;
    }
});

var _listingRenderObserver = new MutationObserver(function() {
    var grid = document.getElementById('listingsGrid');
    if (!grid) return;
    var cards = grid.querySelectorAll('.listing-card:not(.skeleton)');
    var countEl = document.getElementById('listingCountText');
    var heroCount = document.getElementById('listingCountDisplay');
    var n = cards.length;
    if (countEl) {
        if (n === 0) {
            countEl.textContent = 'No listings match your filters';
        } else {
            countEl.textContent = n + ' listing' + (n === 1 ? '' : 's') + ' found';
        }
    }
    if (heroCount) {
        heroCount.textContent = n;
    }
});

document.addEventListener('DOMContentLoaded', function() {
    var grid = document.getElementById('listingsGrid');
    if (grid) {
        _listingRenderObserver.observe(grid, { childList: true });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
