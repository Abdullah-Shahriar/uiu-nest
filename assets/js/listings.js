(function() {
    'use strict';

    var currentView = 'list';
    var currentListings = [];
    var debounceTimer = null;

    document.addEventListener('DOMContentLoaded', function() {
        bindFilterEvents();
        bindViewToggle();
        loadListings();
    });

    async function loadListings() {
        var grid = document.getElementById('listingsGrid');
        if (!grid) return;

        grid.innerHTML = [1,2,3,4,5,6].map(function() {
            return '<div class="card listing-card skeleton" style="height:300px"></div>';
        }).join('');

        var params = getFilterParams();
        try {
            var data = await fetchAPI(APP_URL + '/api/listings.php?' + params);
            currentListings = data.listings || [];
            renderListings(currentListings);
            if (typeof window.updateMapMarkers === 'function') {
                window.updateMapMarkers(currentListings);
            }
        } catch (e) {
            grid.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🔍</div><h3>Error loading listings</h3><p>Please try again later.</p></div>';
        }
    }

    function renderListings(listings) {
        var grid = document.getElementById('listingsGrid');
        if (!grid) return;

        if (listings.length === 0) {
            grid.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🏠</div><h3>No listings found</h3><p>Try adjusting your filters or clearing amenity selections</p></div>';
            return;
        }

        var html = '';
        for (var i = 0; i < listings.length; i++) {
            var l = listings[i];
            var amenities = [];
            try {
                amenities = JSON.parse(l.amenities_json || '[]');
            } catch (ex) {
                amenities = [];
            }

            var amenityTags = '';
            var shown = amenities.slice(0, 4);
            for (var j = 0; j < shown.length; j++) {
                amenityTags += '<span class="amenity-tag">' + getAmenityLabel(shown[j]) + '</span>';
            }

            var typeBadge = '';
            if (l.listing_type === 'roommate_needed') {
                typeBadge = '<span class="badge badge-enrolled">Roommate</span>';
            } else {
                typeBadge = '<span class="badge badge-draft">Direct</span>';
            }

            var heartBtn = '';
            if (window.UIU_USER && window.UIU_USER.logged_in) {
                var savedClass = l.is_saved ? 'saved' : '';
                var heartIcon = l.is_saved ? '❤️' : '🤍';
                heartBtn = '<button class="heart-btn ' + savedClass + '" onclick="toggleSave(event,' + l.id + ')" title="Save">' + heartIcon + '</button>';
            }

            var dist = '';
            if (l.distance_km != null) {
                dist = '<span class="distance-pill">📍 ' + l.distance_km + ' km</span>';
            }

            var beds = l.capacity > 1 ? l.capacity + ' beds' : '1 bed';

            html += '<div class="card listing-card" data-id="' + l.id + '">'
                + '<div class="listing-card-image">'
                + '<div style="width:100%;height:100%;background:var(--accent-gradient);display:flex;align-items:center;justify-content:center;font-size:2.8rem;opacity:0.25;">🏠</div>'
                + '<span class="rent-badge">' + formatRent(l.rent_amount) + '</span>'
                + '<div class="type-badge">' + typeBadge + '</div>'
                + heartBtn
                + '</div>'
                + '<div class="listing-card-content">'
                + '<a href="' + APP_URL + '/pages/listing-detail.php?id=' + l.id + '" class="listing-card-title">' + escapeHtml(l.title) + '</a>'
                + '<div class="listing-card-meta">'
                + '<span>🏢 ' + escapeHtml(l.property_name) + '</span>'
                + '<span>🛏️ ' + beds + '</span>'
                + dist
                + '</div>'
                + '<div class="listing-card-amenities">' + amenityTags + '</div>'
                + '</div>'
                + '<div class="listing-card-footer">'
                + '<small>' + escapeHtml(l.created_by_name || '') + '</small>'
                + '<a href="' + APP_URL + '/pages/listing-detail.php?id=' + l.id + '" class="btn btn-sm btn-outline">View →</a>'
                + '</div>'
                + '</div>';
        }

        grid.innerHTML = html;
    }

    function getFilterParams() {
        var params = new URLSearchParams();

        var rentMin = document.getElementById('filterRentMin');
        var rentMax = document.getElementById('filterRentMax');
        var filterType = document.getElementById('filterType');
        var filterSort = document.getElementById('filterSort');

        if (rentMin && rentMin.value) {
            params.set('rent_min', rentMin.value);
        }
        if (rentMax && rentMax.value) {
            params.set('rent_max', rentMax.value);
        }
        if (filterType && filterType.value) {
            params.set('type', filterType.value);
        }
        if (filterSort && filterSort.value) {
            params.set('sort', filterSort.value);
        }

        var selectedPills = document.querySelectorAll('.amenity-pill.selected');
        var amenities = [];
        for (var i = 0; i < selectedPills.length; i++) {
            amenities.push(selectedPills[i].dataset.slug);
        }
        if (amenities.length > 0) {
            params.set('amenities', amenities.join(','));
        }

        return params.toString();
    }

    function bindFilterEvents() {
        var selects = document.querySelectorAll('#filterRentMin, #filterRentMax, #filterType, #filterSort');
        for (var i = 0; i < selects.length; i++) {
            selects[i].addEventListener('change', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(loadListings, 250);
            });
        }
    }

    function bindViewToggle() {
        var btns = document.querySelectorAll('.view-toggle-btn');
        for (var i = 0; i < btns.length; i++) {
            btns[i].addEventListener('click', function() {
                var view = this.dataset.view;
                currentView = view;
                for (var j = 0; j < btns.length; j++) {
                    btns[j].classList.remove('active');
                }
                this.classList.add('active');

                var grid = document.getElementById('listingsGrid');
                var mapView = document.getElementById('mapView');

                if (grid) {
                    if (view === 'list') {
                        grid.style.display = '';
                    } else {
                        grid.style.display = 'none';
                    }
                }
                if (mapView) {
                    if (view === 'map') {
                        mapView.style.display = '';
                        if (typeof window.initMap === 'function' && !window.mapInitialized) {
                            window.initMap();
                        }
                        if (window.leafletMap) {
                            window.leafletMap.invalidateSize();
                            window.updateMapMarkers(currentListings);
                        }
                    } else {
                        mapView.style.display = 'none';
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
            await fetchAPI(APP_URL + '/api/saved.php', {
                method: 'POST',
                body: JSON.stringify({ listing_id: listingId })
            });
            btn.classList.toggle('saved');
            btn.innerHTML = wasSaved ? '🤍' : '❤️';
            Toast.show(wasSaved ? 'Removed from saved' : 'Saved!', 'success');
        } catch (e) {}
    };

    function getAmenityLabel(key) {
        var labels = {
            wifi:          '📶 WiFi',
            ac:            '❄️ AC',
            attached_bath: '🚿 Attached Bath',
            shared_bath:   '🛁 Shared Bath',
            furnished:     '🪑 Furnished',
            balcony:       '🌇 Balcony',
            parking:       '🅿️ Parking',
            laundry:       '👕 Laundry',
            security:      '🔒 Security',
            cctv:          '📹 CCTV',
            study_room:    '📚 Study Room',
            rooftop:       '🏙️ Rooftop'
        };
        if (labels[key]) {
            return labels[key];
        }
        return key;
    }

    function formatRent(amount) {
        return '৳' + Number(amount).toLocaleString('en-BD');
    }

    window.escapeHtml = function(str) {
        var div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    };

    window.loadListings = loadListings;
    window.currentListings = function() { return currentListings; };

    window.applyFilters = loadListings;

    window.clearFilters = function() {
        var rentMin = document.getElementById('filterRentMin');
        var rentMax = document.getElementById('filterRentMax');
        var filterType = document.getElementById('filterType');
        var filterSort = document.getElementById('filterSort');

        if (rentMin) rentMin.value = '';
        if (rentMax) rentMax.value = '';
        if (filterType) filterType.value = '';
        if (filterSort) filterSort.value = 'date_desc';

        document.querySelectorAll('.amenity-pill.selected').forEach(function(p) {
            p.classList.remove('selected');
        });

        loadListings();
    };
})();
