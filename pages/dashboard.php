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

/* ── AI Chat Widget ── */
.ai-search-btn {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: var(--accent-gradient);
    border: none;
    border-radius: var(--radius-sm);
    padding: 6px 12px;
    color: #fff;
    font-size: 0.8rem;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(56,189,248,0.3);
    transition: all 0.2s cubic-bezier(0.34,1.56,0.64,1);
    z-index: 2;
}
.ai-search-btn:hover {
    transform: translateY(-50%) scale(1.05);
    box-shadow: 0 6px 16px rgba(56,189,248,0.45);
}
.ai-search-btn svg { width: 14px; height: 14px; }

#aiChatWidget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 380px;
    max-height: 600px;
    background: rgba(4, 9, 16, 0.85);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: var(--radius-lg);
    box-shadow: 0 16px 40px rgba(0,0,0,0.4), 0 0 0 1px rgba(56,189,248,0.15);
    display: flex;
    flex-direction: column;
    z-index: 9999;
    transform: translateY(20px) scale(0.95);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
}
#aiChatWidget.open {
    transform: translateY(0) scale(1);
    opacity: 1;
    visibility: visible;
}
.ai-chat-header {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(to right, rgba(56,189,248,0.1), transparent);
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}
.ai-chat-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 1.05rem;
    color: #fff;
}
.ai-chat-title svg { color: var(--accent); width: 18px; height: 18px; }
.ai-close-btn {
    background: none; border: none; color: rgba(255,255,255,0.6);
    cursor: pointer; padding: 4px; border-radius: 4px;
    display: flex; align-items: center; justify-content: center;
}
.ai-close-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }

/* AI Fullscreen Overlay */
#aiBlurOverlay {
    position: fixed;
    inset: 0;
    background: rgba(4, 9, 16, 0.65);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 9998;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
}
#aiBlurOverlay.active {
    opacity: 1;
    visibility: visible;
}

.ai-chat-body {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 16px;
    min-height: 300px;
}
.ai-msg {
    max-width: 85%;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 0.9rem;
    line-height: 1.4;
}
.ai-msg.bot {
    align-self: flex-start;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.08);
    border-bottom-left-radius: 4px;
    color: rgba(255,255,255,0.9);
}
.ai-msg.user {
    align-self: flex-end;
    background: var(--accent-gradient);
    color: #fff;
    border-bottom-right-radius: 4px;
    box-shadow: 0 4px 12px rgba(56,189,248,0.2);
}

/* New Amenities Selection inside AI Chat */
.ai-setup-box {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.ai-suggestions-title {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(255,255,255,0.5);
    font-weight: 700;
}
.ai-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.ai-chip {
    background: rgba(56,189,248,0.1);
    border: 1px solid rgba(56,189,248,0.25);
    color: var(--sidebar-neon);
    padding: 6px 12px;
    border-radius: var(--radius-full);
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s;
}
.ai-chip:hover {
    background: var(--accent);
    color: #fff;
}

.ai-amenities-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.ai-amenity-pill {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.7);
    padding: 6px 12px;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}
.ai-amenity-pill:hover {
    background: rgba(255,255,255,0.1);
}
.ai-amenity-pill.selected {
    background: rgba(56,189,248,0.15);
    border-color: var(--accent);
    color: var(--accent);
    box-shadow: inset 0 0 0 1px var(--accent);
}
.ai-amenity-pill svg { width: 12px; height: 12px; }

.ai-chat-footer {
    padding: 16px 20px;
    border-top: 1px solid rgba(255,255,255,0.08);
    display: flex;
    gap: 10px;
}
.ai-chat-input {
    flex: 1;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: var(--radius-full);
    padding: 10px 16px;
    color: #fff;
    font-family: inherit;
    font-size: 0.9rem;
    transition: border-color 0.2s;
}
.ai-chat-input:focus {
    outline: none;
    border-color: var(--accent);
}
.ai-send-btn {
    background: var(--accent);
    border: none;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;
}
.ai-send-btn:hover { background: var(--accent-hover); transform: scale(1.05); }

/* property cards inside AI chat */
.ai-result-cards {
    display: flex; flex-direction: column; gap: 10px; margin-top: 10px;
}
.ai-mini-card {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: var(--radius-sm);
    padding: 10px;
    display: flex; gap: 12px;
    cursor: pointer; transition: background 0.2s;
}
.ai-mini-card:hover { background: rgba(255,255,255,0.09); border-color: rgba(56,189,248,0.3); }
.ai-mini-card img { width: 60px; height: 60px; border-radius: 6px; object-fit: cover; }
.ai-mini-info h4 { font-size: 0.9rem; margin-bottom: 4px; color: #fff; }
.ai-mini-info p { font-size: 0.75rem; color: rgba(255,255,255,0.6); }
.ai-mini-info .price { font-weight: 700; color: var(--accent); margin-top: 4px; font-size: 0.85rem; }
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
                   style="width:100%;box-sizing:border-box;padding:10px 130px 10px 42px;background:var(--bg-tertiary);border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:0.9rem;color:var(--text-primary);font-family:inherit;transition:border-color 0.18s;outline:none;"
                   placeholder="Search by property name, title or location..."
                   autocomplete="off"
                   onfocus="this.style.borderColor='var(--accent)'"
                   onblur="this.style.borderColor='var(--border)'">
            <button class="ai-search-btn" onclick="toggleAIChat()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                AI Search
            </button>
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
</div>

<div class="listings-count-bar">
    <span id="listingCountText">Loading listings...</span>
    <span>Use the AI Assistant to find your perfect match!</span>
</div>

<div id="listingsGrid" class="listings-grid"></div>

<div id="mapView" style="display:none;">
    <div class="map-container" id="leafletMap"></div>
</div>

<!-- Fullscreen blur overlay when AI is active -->
<div id="aiBlurOverlay" onclick="toggleAIChat()"></div>

<div id="aiChatWidget">
    <div class="ai-chat-header">
        <div class="ai-chat-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            Nest AI Concierge
        </div>
        <button class="ai-close-btn" onclick="toggleAIChat()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <div class="ai-chat-body" id="aiChatBody">
        <div class="ai-msg bot">Hi! I can help you find exactly what you're looking for. You can type a prompt, or tap the amenities below!</div>
        
        <div class="ai-setup-box" id="aiSetupBox">
            <div class="ai-suggestions-title">Quick Suggestions</div>
            <div class="ai-chips">
                <div class="ai-chip" onclick="sendAiMessage('Single room under 6000')">"Single room under 6000"</div>
                <div class="ai-chip" onclick="sendAiMessage('Furnished house near UIU')">"Furnished house near UIU"</div>
            </div>
            
            <div class="ai-suggestions-title" style="margin-top:8px;">Must have amenities</div>
            <div class="ai-amenities-grid">
                <?php
                $amenityIcons = [
                    'wifi'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0114.08 0"/><path d="M1.42 9a16 16 0 0121.16 0"/><path d="M8.53 16.11a6 6 0 016.95 0"/><circle cx="12" cy="20" r="1" fill="currentColor"/></svg>',
                    'ac'            => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.59 4.59A2 2 0 1111 8H2m10.59 11.41A2 2 0 1013 16H2m15.73-8.27A2.5 2.5 0 1119.5 12H2"/></svg>',
                    'attached_bath' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16a1 1 0 011 1v3a4 4 0 01-4 4H7a4 4 0 01-4-4v-3a1 1 0 011-1z"/><path d="M6 12V5a2 2 0 012-2h3v2.25"/></svg>',
                    'shared_bath'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16a1 1 0 011 1v3a4 4 0 01-4 4H7a4 4 0 01-4-4v-3a1 1 0 011-1z"/></svg>',
                    'furnished'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 9V6a2 2 0 00-2-2H6a2 2 0 00-2 2v3"/><path d="M2 11v5a2 2 0 002 2h16a2 2 0 002-2v-5a2 2 0 00-4 0v2H6v-2a2 2 0 00-4 0z"/></svg>',
                    'balcony'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>',
                    'parking'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 17V7h4a3 3 0 010 6H9"/></svg>',
                    'laundry'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="2"/><circle cx="12" cy="12" r="4"/></svg>',
                    'security'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
                    'cctv'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>',
                    'study_room'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>',
                    'rooftop'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>',
                    'generator'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
                    'lift'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="12"/><polyline points="9 11 12 8 15 11"/></svg>',
                ];
                ?>
                <?php foreach ($amenitiesList as $a): ?>
                <button class="ai-amenity-pill" data-name="<?= htmlspecialchars($a['label']) ?>" onclick="this.classList.toggle('selected')">
                    <?= $amenityIcons[$a['slug']] ?? '' ?>
                    <?= htmlspecialchars($a['label']) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="ai-chat-footer">
        <input type="text" id="aiChatInput" class="ai-chat-input" placeholder="Type your request here..." onkeypress="if(event.key==='Enter') sendAiMessage()">
        <button class="ai-send-btn" onclick="sendAiMessage()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-left:-2px;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </button>
    </div>
</div>

<script>
function toggleAIChat() {
    try {
        console.log("toggleAIChat triggered");
        const chatWidget = document.getElementById('aiChatWidget');
        const overlay = document.getElementById('aiBlurOverlay');
        const globalSearchInput = document.getElementById('globalSearch');
        
        if (!chatWidget) throw new Error("aiChatWidget not found");
        if (!overlay) throw new Error("aiBlurOverlay not found");
        if (!globalSearchInput) throw new Error("globalSearchInput not found");

        // Toggle UI
        chatWidget.classList.toggle('open');
        overlay.classList.toggle('active');
        
        // If opening, check if they already typed something in the main search bar
        if (chatWidget.classList.contains('open')) {
            const query = globalSearchInput.value.trim();
            if (query) {
                globalSearchInput.value = ''; // clear it
                sendAiMessage(query); // instantly start AI search
            } else {
                // Focus the AI input if empty
                const aiInput = document.getElementById('aiChatInput');
                if (aiInput) setTimeout(() => aiInput.focus(), 300);
            }
        }
    } catch (e) {
        alert("UI Error: " + e.message);
        console.error(e);
    }
}

async function sendAiMessage(presetMsg = null) {
    const input = document.getElementById('aiChatInput');
    let msg = presetMsg || input.value.trim();
    
    // Gather selected amenities
    const selectedPills = document.querySelectorAll('.ai-amenity-pill.selected');
    let selectedNames = [];
    selectedPills.forEach(p => {
        selectedNames.push(p.getAttribute('data-name'));
    });

    if (!msg && selectedNames.length === 0) return;

    if (selectedNames.length > 0) {
        msg += (msg ? ". " : "") + "Required: " + selectedNames.join(", ");
    }

    input.value = '';
    const body = document.getElementById('aiChatBody');
    
    // Hide setup box if it exists
    const setupBox = document.getElementById('aiSetupBox');
    if (setupBox) setupBox.style.display = 'none';

    // Add user message
    const userDiv = document.createElement('div');
    userDiv.className = 'ai-msg user';
    userDiv.textContent = msg;
    body.appendChild(userDiv);
    body.scrollTop = body.scrollHeight;

    // Add loading indicator
    const loadDiv = document.createElement('div');
    loadDiv.className = 'ai-msg bot';
    loadDiv.innerHTML = '<span style="opacity:0.5;">Thinking...</span>';
    body.appendChild(loadDiv);
    body.scrollTop = body.scrollHeight;

    try {
        // Call the AI endpoint
        const res = await fetch(window.APP_URL + '/api/ai_search.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ message: msg })
        });
        
        const aiData = await res.json();
        loadDiv.remove();

        if (aiData.error) throw new Error(aiData.error);

        // Append the AI's friendly text response
        const botDiv = document.createElement('div');
        botDiv.className = 'ai-msg bot';
        botDiv.textContent = aiData.user_friendly_message || "Here is what I found based on your request:";
        body.appendChild(botDiv);

        // Fetch the actual listings based on AI's extracted parameters
        const params = new URLSearchParams();
        if (aiData.max_price) params.set('rent_max', aiData.max_price);
        if (aiData.search_keyword) params.set('q', aiData.search_keyword);
        if (aiData.sort_by) params.set('sort', aiData.sort_by);
        if (aiData.amenities_required && aiData.amenities_required.length > 0) {
            params.set('amenities', aiData.amenities_required.join(','));
        }

        const listingsRes = await fetch(window.APP_URL + '/api/listings.php?' + params.toString());
        const listingsData = await listingsRes.json();
        const listings = listingsData.listings || [];

        // Render the results as mini cards in the chat
        if (listings.length > 0) {
            const cardsContainer = document.createElement('div');
            cardsContainer.className = 'ai-result-cards';
            
            // Limit to top 3 suggestions in chat to prevent huge scrolling
            const topListings = listings.slice(0, 3);
            
            topListings.forEach(l => {
                const imgPath = l.cover_photo || l.image_path || 'assets/images/placeholder.jpg';
                const card = document.createElement('div');
                card.className = 'ai-mini-card';
                card.onclick = () => window.location.href = window.APP_URL + '/pages/listing-detail.php?id=' + l.id;
                
                card.innerHTML = `
                    <img src="${window.APP_URL}/${imgPath}" alt="Property" onerror="this.src='${window.APP_URL}/assets/images/placeholder.jpg'">
                    <div class="ai-mini-info">
                        <h4>${l.title}</h4>
                        <p>${l.property_name}</p>
                        <div class="price">৳${l.rent_amount} / month</div>
                    </div>
                `;
                cardsContainer.appendChild(card);
            });

            if (listings.length > 3) {
                const moreBtn = document.createElement('button');
                moreBtn.className = 'ai-chip';
                moreBtn.style.marginTop = '8px';
                moreBtn.textContent = `View all ${listings.length} matches`;
                moreBtn.onclick = () => {
                    // Update main dashboard view
                    if (aiData.max_price) document.getElementById('filterRentMax').value = aiData.max_price;
                    if (aiData.search_keyword) document.getElementById('globalSearch').value = aiData.search_keyword;
                    
                    // We don't have checkboxes anymore, but we can still fetch and render!
                    currentListings = listings;
                    if (typeof renderListings === 'function') renderListings(listings);
                    
                    document.getElementById('listingCountDisplay').textContent = listings.length;
                    toggleAIChat(); // close chat to see results
                };
                cardsContainer.appendChild(moreBtn);
            }
            
            body.appendChild(cardsContainer);
        } else {
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'ai-msg bot';
            emptyDiv.style.opacity = '0.7';
            emptyDiv.style.fontStyle = 'italic';
            emptyDiv.textContent = "I couldn't find any properties matching those exact requirements right now.";
            body.appendChild(emptyDiv);
        }

        body.scrollTop = body.scrollHeight;

    } catch (e) {
        loadDiv.textContent = 'Sorry, an error occurred while searching. ' + e.message;
    }
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
