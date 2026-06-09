(function() {
    'use strict';

    var currentView = 'list';
    var currentListings = [];
    var debounceTimer = null;

    // SVG icons (inline, no emoji)
    var SVG = {
        home:     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:52px;height:52px;stroke:rgba(255,255,255,0.18)"><path d="M3 9.5L12 3l9 6.5V21H3V9.5z"/><rect x="9" y="14" width="6" height="7" rx="1"/></svg>',
        building: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;flex-shrink:0"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>',
        bed:      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;flex-shrink:0"><path d="M2 4v16M2 8h18a2 2 0 012 2v6a2 2 0 01-2 2H2"/><path d="M2 13h20"/></svg>',
        pin:      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;flex-shrink:0"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 00-8 8c0 5.25 8 14 8 14s8-8.75 8-14a8 8 0 00-8-8z"/></svg>',
        heart:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>',
        heartFill:'<svg viewBox="0 0 24 24" fill="var(--heart-color)" stroke="none" style="width:15px;height:15px"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>',
        search:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
        noResult: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:48px;height:48px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
    };

    // Amenity icon map (SVG paths instead of emojis)
    var AMENITY_ICONS = {
        wifi:          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><path d="M5 12.55a11 11 0 0114.08 0"/><path d="M1.42 9a16 16 0 0121.16 0"/><path d="M8.53 16.11a6 6 0 016.95 0"/><circle cx="12" cy="20" r="1" fill="currentColor"/></svg>',
        ac:            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><path d="M9.59 4.59A2 2 0 1111 8H2m10.59 11.41A2 2 0 1013 16H2m15.73-8.27A2.5 2.5 0 1119.5 12H2"/></svg>',
        attached_bath: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><path d="M4 12h16a1 1 0 011 1v3a4 4 0 01-4 4H7a4 4 0 01-4-4v-3a1 1 0 011-1z"/><path d="M6 12V5a2 2 0 012-2h3v2.25"/><line x1="4" y1="20" x2="4" y2="22"/><line x1="20" y1="20" x2="20" y2="22"/></svg>',
        furnished:     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><path d="M20 9V6a2 2 0 00-2-2H6a2 2 0 00-2 2v3"/><path d="M2 11v5a2 2 0 002 2h16a2 2 0 002-2v-5a2 2 0 00-4 0v2H6v-2a2 2 0 00-4 0z"/></svg>',
        parking:       '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 17V7h4a3 3 0 010 6H9"/></svg>',
        security:      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        cctv:          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>',
        study_room:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>',
        generator:     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        balcony:       '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>',
        rooftop:       '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>',
        laundry:       '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><rect x="2" y="2" width="20" height="20" rx="2"/><circle cx="12" cy="12" r="4"/></svg>',
        shared_bath:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><path d="M4 12h16a1 1 0 011 1v3a4 4 0 01-4 4H7a4 4 0 01-4-4v-3a1 1 0 011-1z"/></svg>',
    };

    document.addEventListener('DOMContentLoaded', function() {
        bindFilterEvents();
        bindViewToggle();
        bindSearchBar();
        loadListings();
    });

    async function loadListings() {
        var grid = document.getElementById('listingsGrid');
        if (!grid) return;

        grid.innerHTML = [1,2,3,4,5,6].map(function() {
            return '<div class="card listing-card skeleton" style="height:310px"></div>';
        }).join('');

        var params = getFilterParams();
        try {
            var data = await fetchAPI(window.APP_URL + '/api/listings.php?' + params);
            currentListings = data.listings || [];
            renderListings(currentListings);
            if (typeof window.updateMapMarkers === 'function') {
                window.updateMapMarkers(currentListings);
            }
        } catch (e) {
            grid.innerHTML = '<div class="empty-state"><div class="empty-state-icon">' + SVG.noResult + '</div><h3>Error loading listings</h3><p>Please try again later.</p></div>';
        }
    }

    function renderListings(listings) {
        var grid = document.getElementById('listingsGrid');
        if (!grid) return;

        if (listings.length === 0) {
            grid.innerHTML = '<div class="empty-state"><div class="empty-state-icon">' + SVG.noResult + '</div><h3>No listings found</h3><p>Try adjusting your filters or search term.</p></div>';
            return;
        }

        var html = '';
        for (var i = 0; i < listings.length; i++) {
            var l = listings[i];
            var amenities = [];
            try { amenities = JSON.parse(l.amenities_json || '[]'); } catch (ex) { amenities = []; }

            var amenityTags = '';
            var shown = amenities.slice(0, 4);
            for (var j = 0; j < shown.length; j++) {
                var slug = shown[j];
                var icon = AMENITY_ICONS[slug] || '';
                amenityTags += '<span class="amenity-tag">' + icon + getAmenityLabel(slug) + '</span>';
            }

            var heartBtn = '';
            if (window.UIU_USER && window.UIU_USER.logged_in) {
                var savedClass = l.is_saved ? 'saved' : '';
                var heartIco   = l.is_saved ? SVG.heartFill : SVG.heart;
                heartBtn = '<button class="heart-btn ' + savedClass + '" onclick="toggleSave(event,' + l.id + ')" title="Save listing">' + heartIco + '</button>';
            }

            var dist = '';
            if (l.distance_km != null) {
                dist = '<span class="distance-pill">' + SVG.pin + ' ' + l.distance_km + ' km</span>';
            }

            var beds = l.capacity > 1 ? l.capacity + ' beds' : '1 bed';

            // Cover photo from property_images
            var imageSection = '';
            if (l.cover_photo) {
                imageSection = '<img src="' + window.APP_URL + '/' + escapeHtml(l.cover_photo) + '" alt="' + escapeHtml(l.title) + '" loading="lazy">';
            } else if (l.image_path) {
                imageSection = '<img src="' + window.APP_URL + '/' + escapeHtml(l.image_path) + '" alt="' + escapeHtml(l.title) + '" loading="lazy">';
            } else {
                imageSection = '<div class="listing-card-placeholder">' + SVG.home + '</div>';
            }

            var avatarImg = l.creator_avatar ? '<img src="' + window.APP_URL + '/' + escapeHtml(l.creator_avatar) + '" style="width:20px;height:20px;border-radius:50%;object-fit:cover;margin-right:6px;vertical-align:middle;">' : '<div style="width:20px;height:20px;border-radius:50%;background:var(--border);color:var(--text-secondary);display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:600;margin-right:6px;vertical-align:middle;">' + (l.created_by_name || 'U').charAt(0).toUpperCase() + '</div>';

            html += '<div class="card listing-card" data-id="' + l.id + '">'
                + '<div class="listing-card-image">'
                + imageSection
                + '<span class="rent-badge">' + formatRent(l.rent_amount) + '</span>'
                + heartBtn
                + '</div>'
                + '<div class="listing-card-content">'
                + '<a href="' + window.APP_URL + '/pages/listing-detail.php?id=' + l.id + '" class="listing-card-title">' + escapeHtml(l.title) + '</a>'
                + '<div class="listing-card-meta">'
                + '<span>' + SVG.building + ' ' + escapeHtml(l.property_name) + '</span>'
                + '<span>' + SVG.bed + ' ' + beds + '</span>'
                + dist
                + '</div>'
                + '<div class="listing-card-amenities">' + amenityTags + '</div>'
                + '</div>'
                + '<div class="listing-card-footer" style="display:flex;align-items:center;justify-content:space-between;">'
                + '<div style="display:flex;align-items:center;">' + avatarImg + '<small style="font-weight:500;">' + escapeHtml(l.created_by_name || '') + '</small></div>'
                + '<a href="' + window.APP_URL + '/pages/listing-detail.php?id=' + l.id + '" class="btn btn-sm btn-outline">View &rarr;</a>'
                + '</div>'
                + '</div>';
        }

        grid.innerHTML = html;
    }

    function getFilterParams() {
        var params = new URLSearchParams();
        var rentMin  = document.getElementById('filterRentMin');
        var rentMax  = document.getElementById('filterRentMax');
        var filterSort = document.getElementById('filterSort');
        var searchInput = document.getElementById('globalSearch');

        if (rentMin && rentMin.value)    params.set('rent_min', rentMin.value);
        if (rentMax && rentMax.value)    params.set('rent_max', rentMax.value);
        if (filterSort && filterSort.value) params.set('sort', filterSort.value);
        if (searchInput && searchInput.value.trim()) params.set('q', searchInput.value.trim());

        var selectedPills = document.querySelectorAll('.amenity-pill.selected');
        var amenities = [];
        for (var i = 0; i < selectedPills.length; i++) {
            amenities.push(selectedPills[i].dataset.slug);
        }
        if (amenities.length > 0) params.set('amenities', amenities.join(','));

        return params.toString();
    }

    function bindFilterEvents() {
        var selects = document.querySelectorAll('#filterRentMin, #filterRentMax, #filterSort');
        for (var i = 0; i < selects.length; i++) {
            selects[i].addEventListener('change', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(loadListings, 250);
            });
        }
    }

    function bindSearchBar() {
        var searchInput = document.getElementById('globalSearch');
        if (!searchInput) return;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(loadListings, 350);
        });
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { clearTimeout(debounceTimer); loadListings(); }
        });
    }

    function bindViewToggle() {
        var btns = document.querySelectorAll('.view-toggle-btn');
        for (var i = 0; i < btns.length; i++) {
            btns[i].addEventListener('click', function() {
                var view = this.dataset.view;
                currentView = view;
                for (var j = 0; j < btns.length; j++) btns[j].classList.remove('active');
                this.classList.add('active');

                var grid    = document.getElementById('listingsGrid');
                var mapView = document.getElementById('mapView');
                if (grid)    grid.style.display    = (view === 'list') ? '' : 'none';
                if (mapView) mapView.style.display  = (view === 'map')  ? '' : 'none';

                if (view === 'map') {
                    if (typeof window.initMap === 'function' && !window.mapInitialized) window.initMap();
                    if (window.leafletMap) {
                        window.leafletMap.invalidateSize();
                        window.updateMapMarkers(currentListings);
                    }
                }
            });
        }
    }

    window.toggleSave = async function(event, listingId) {
        event.preventDefault();
        event.stopPropagation();
        if (!window.UIU_USER || !window.UIU_USER.logged_in) {
            Toast.show('Please login to save listings', 'info');
            return;
        }
        var btn = event.currentTarget;
        var wasSaved = btn.classList.contains('saved');
        try {
            await fetchAPI(window.APP_URL + '/api/saved.php', {
                method: 'POST',
                body: JSON.stringify({ listing_id: listingId })
            });
            btn.classList.toggle('saved');
            btn.innerHTML = wasSaved ? SVG.heart : SVG.heartFill;
            Toast.show(wasSaved ? 'Removed from saved' : 'Saved!', 'success');
        } catch (e) {}
    };

    function getAmenityLabel(key) {
        var labels = {
            wifi:          'WiFi', ac: 'AC', attached_bath: 'Attached Bath',
            shared_bath:   'Shared Bath', furnished: 'Furnished', balcony: 'Balcony',
            parking:       'Parking', laundry: 'Laundry', security: 'Security',
            cctv:          'CCTV', study_room: 'Study Room', rooftop: 'Rooftop',
            generator:     'Generator', lift: 'Lift',
        };
        return labels[key] || key;
    }

    function formatRent(amount) {
        return '\u09F3' + Number(amount).toLocaleString('en-BD');
    }

    window.escapeHtml = function(str) {
        var div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    };

    window.loadListings      = loadListings;
    window.currentListings   = function() { return currentListings; };
    window.applyFilters      = loadListings;

    window.clearFilters = function() {
        var rentMin    = document.getElementById('filterRentMin');
        var rentMax    = document.getElementById('filterRentMax');
        var filterSort = document.getElementById('filterSort');
        var search     = document.getElementById('globalSearch');

        if (rentMin)    rentMin.value    = '';
        if (rentMax)    rentMax.value    = '';
        if (filterSort) filterSort.value = 'date_desc';
        if (search)     search.value     = '';

        document.querySelectorAll('.amenity-pill.selected').forEach(function(p) {
            p.classList.remove('selected');
        });
        loadListings();
    };
})();
