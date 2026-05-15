<?php
$pageName = 'Dashboard';
$includeMapJS = true;
$includeListingsJS = true;
require_once __DIR__ . '/../includes/header.php';

$amenitiesList = getAmenitiesList();
?>

<style>
/* ── Dashboard Bento Hero ── */
.dash-hero {
    background: var(--accent-gradient);
    border-radius: var(--radius-lg);
    padding: 24px 28px;
    margin-bottom: 22px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    position: relative;
    overflow: hidden;
}
.dash-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,0.04);
}
.dash-hero::after {
    content: '';
    position: absolute;
    bottom: -30px; left: 30%;
    width: 140px; height: 140px;
    border-radius: 50%;
    background: rgba(255,255,255,0.03);
}
.dash-hero-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 4px;
}
.dash-hero-sub { font-size: 0.84rem; opacity: 0.85; }
.dash-hero-count {
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: var(--radius);
    padding: 12px 22px;
    text-align: center;
    backdrop-filter: blur(10px);
    z-index: 1;
}
.dash-hero-count strong { display: block; font-size: 1.8rem; font-weight: 800; line-height: 1; font-family: 'Outfit', sans-serif; }
.dash-hero-count span { font-size: 0.72rem; opacity: 0.82; }

/* ── Search + Filter Panel ── */
.pro-filter-wrap {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 18px 20px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-sm);
}
[data-theme="dark"] .pro-filter-wrap {
    border-color: var(--bento-border);
    box-shadow: 0 0 0 1px var(--bento-border), var(--shadow-sm);
}
.pro-filter-search-row {
    margin-bottom: 14px;
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
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-tertiary);
}
.pro-filter-input {
    background: var(--bg-tertiary);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 8px 10px;
    font-size: 0.84rem;
    color: var(--text-primary);
    font-family: inherit;
    width: 100%;
    transition: border-color 0.18s;
    cursor: pointer;
}
.pro-filter-input:focus { border-color: var(--accent); outline: none; }
.pro-filter-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    flex-wrap: wrap;
}
.pro-filter-divider { height: 1px; background: var(--border); margin: 14px 0; }
.pro-filter-amenities-wrap { display: flex; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
.amenity-label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-tertiary);
    padding-top: 6px;
    white-space: nowrap;
}
.amenity-pill-row { display: flex; flex-wrap: wrap; gap: 6px; flex: 1; }
.amenity-pill {
    padding: 5px 12px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-full);
    background: var(--bg-tertiary);
    color: var(--text-secondary);
    font-size: 0.76rem;
    cursor: pointer;
    transition: all 0.16s;
    font-family: inherit;
    font-weight: 500;
    user-select: none;
}
.amenity-pill:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }
.amenity-pill.selected { background: var(--accent); border-color: var(--accent); color: #fff; }
.listings-count-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
    flex-wrap: wrap;
    gap: 8px;
}
.listings-count-bar span { font-size: 0.80rem; color: var(--text-tertiary); }
.view-toggle { display: flex; border: 1.5px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; }
.view-toggle-btn {
    padding: 7px 14px;
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 0.82rem;
    font-family: inherit;
    color: var(--text-tertiary);
    transition: all 0.16s;
    display: flex; align-items: center; gap: 5px;
}
.view-toggle-btn svg { width: 14px; height: 14px; }
.view-toggle-btn.active { background: var(--accent); color: #fff; }
.view-toggle-btn:not(.active):hover { background: var(--bg-tertiary); }
</style>

<div class="dash-hero">
    <div>
        <div class="dash-hero-title" data-i18n="hero_title">Find Your Perfect Room Near UIU</div>
        <div class="dash-hero-sub" data-i18n="hero_sub">Browse verified properties — filter by rent, amenities &amp; location</div>
    </div>
    <div class="dash-hero-count">
        <strong id="listingCountDisplay">—</strong>
        <span>listings found</span>
    </div>
</div>

<div class="pro-filter-wrap">
    <!-- Global Search -->
    <div class="pro-filter-search-row">
        <div class="global-search-wrap" style="max-width:100%;position:relative;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--text-tertiary);z-index:1;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="globalSearch"
                   style="width:100%;box-sizing:border-box;padding:10px 16px 10px 42px;background:var(--bg-tertiary);border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:0.9rem;color:var(--text-primary);font-family:inherit;transition:border-color 0.18s;outline:none;"
                   placeholder="Search by property name, title or location..."
                   autocomplete="off"
                   onfocus="this.style.borderColor='var(--accent)'"
                   onblur="this.style.borderColor='var(--border)'">
        </div>
    </div>

    <!-- Rent + Sort Filters (NO Type filter) -->
    <div class="pro-filter-top">
        <div class="pro-filter-group">
            <label class="pro-filter-label">Min Rent</label>
            <select class="pro-filter-input" id="filterRentMin">
                <option value="">Any</option>
                <option value="4000">৳4,000</option>
                <option value="5000">৳5,000</option>
                <option value="6000">৳6,000</option>
                <option value="7000">৳7,000</option>
                <option value="8000">৳8,000</option>
                <option value="9000">৳9,000</option>
                <option value="10000">৳10,000</option>
                <option value="12000">৳12,000</option>
            </select>
        </div>
        <div class="pro-filter-group">
            <label class="pro-filter-label">Max Rent</label>
            <select class="pro-filter-input" id="filterRentMax">
                <option value="">Any</option>
                <option value="5000">৳5,000</option>
                <option value="6000">৳6,000</option>
                <option value="7000">৳7,000</option>
                <option value="8000">৳8,000</option>
                <option value="10000">৳10,000</option>
                <option value="12000">৳12,000</option>
                <option value="15000">৳15,000</option>
                <option value="20000">৳20,000</option>
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
            <button class="btn btn-primary btn-sm" onclick="window.applyFilters()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Search
            </button>
            <button class="btn btn-ghost btn-sm" onclick="window.clearFilters()">Clear</button>
            <div class="view-toggle">
                <button class="view-toggle-btn active" data-view="list" id="viewList" title="List view">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    List
                </button>
                <button class="view-toggle-btn" data-view="map" id="viewMap" title="Map view">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                    Map
                </button>
            </div>
        </div>
    </div>

    <div class="pro-filter-divider"></div>

    <div class="pro-filter-amenities-wrap">
        <span class="amenity-label">Must have:</span>
        <div class="amenity-pill-row">
            <?php
            $amenityIcons = [
                'wifi'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;display:inline;vertical-align:middle;"><path d="M5 12.55a11 11 0 0114.08 0"/><path d="M1.42 9a16 16 0 0121.16 0"/><path d="M8.53 16.11a6 6 0 016.95 0"/><circle cx="12" cy="20" r="1" fill="currentColor"/></svg>',
                'ac'            => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;display:inline;vertical-align:middle;"><path d="M9.59 4.59A2 2 0 1111 8H2m10.59 11.41A2 2 0 1013 16H2m15.73-8.27A2.5 2.5 0 1119.5 12H2"/></svg>',
                'attached_bath' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;display:inline;vertical-align:middle;"><path d="M4 12h16a1 1 0 011 1v3a4 4 0 01-4 4H7a4 4 0 01-4-4v-3a1 1 0 011-1z"/><path d="M6 12V5a2 2 0 012-2h3v2.25"/></svg>',
                'shared_bath'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;display:inline;vertical-align:middle;"><path d="M4 12h16a1 1 0 011 1v3a4 4 0 01-4 4H7a4 4 0 01-4-4v-3a1 1 0 011-1z"/></svg>',
                'furnished'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;display:inline;vertical-align:middle;"><path d="M20 9V6a2 2 0 00-2-2H6a2 2 0 00-2 2v3"/><path d="M2 11v5a2 2 0 002 2h16a2 2 0 002-2v-5a2 2 0 00-4 0v2H6v-2a2 2 0 00-4 0z"/></svg>',
                'balcony'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;display:inline;vertical-align:middle;"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>',
                'parking'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;display:inline;vertical-align:middle;"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 17V7h4a3 3 0 010 6H9"/></svg>',
                'laundry'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;display:inline;vertical-align:middle;"><rect x="2" y="2" width="20" height="20" rx="2"/><circle cx="12" cy="12" r="4"/></svg>',
                'security'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;display:inline;vertical-align:middle;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
                'cctv'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;display:inline;vertical-align:middle;"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>',
                'study_room'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;display:inline;vertical-align:middle;"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>',
                'rooftop'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;display:inline;vertical-align:middle;"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>',
                'generator'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;display:inline;vertical-align:middle;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
                'lift'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;display:inline;vertical-align:middle;"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="12"/><polyline points="9 11 12 8 15 11"/></svg>',
            ];
            ?>
            <?php foreach ($amenitiesList as $a): ?>
            <button class="amenity-pill" data-slug="<?= $a['slug'] ?>" onclick="togglePill(this)" style="display:flex;align-items:center;gap:5px;">
                <?= $amenityIcons[$a['slug']] ?? '' ?>
                <?= htmlspecialchars($a['label']) ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="listings-count-bar">
    <span id="listingCountText">Loading listings...</span>
    <span>Click amenity pills to filter by feature</span>
</div>

<div id="listingsGrid" class="listings-grid"></div>

<div id="mapView" style="display:none;">
    <div class="map-container" id="leafletMap"></div>
</div>

<script>
function togglePill(btn) {
    btn.classList.toggle('selected');
    clearTimeout(window._pillDebounce);
    window._pillDebounce = setTimeout(function() {
        if (window.loadListings) window.loadListings();
    }, 200);
}

// Observer to update count badge
var _listingRenderObserver = new MutationObserver(function() {
    var grid = document.getElementById('listingsGrid');
    if (!grid) return;
    var cards = grid.querySelectorAll('.listing-card:not(.skeleton)');
    var n = cards.length;
    var countEl = document.getElementById('listingCountText');
    var heroCount = document.getElementById('listingCountDisplay');
    if (countEl)   countEl.textContent  = n === 0 ? 'No listings match your filters' : n + ' listing' + (n === 1 ? '' : 's') + ' found';
    if (heroCount) heroCount.textContent = n;
});

document.addEventListener('DOMContentLoaded', function() {
    var grid = document.getElementById('listingsGrid');
    if (grid) _listingRenderObserver.observe(grid, { childList: true });
});
</script>



<?php require_once __DIR__ . '/../includes/footer.php'; ?>
