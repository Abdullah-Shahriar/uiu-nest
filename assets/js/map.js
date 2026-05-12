/**
 * UIU Nest — Leaflet Map (centered on UIU)
 */
(function() {
    'use strict';

    window.leafletMap = null;
    window.mapInitialized = false;
    let markersLayer = null;

    window.initMap = function() {
        if (window.mapInitialized) return;
        const container = document.getElementById('leafletMap');
        if (!container) return;

        window.leafletMap = L.map('leafletMap', {
            center: [window.UIU_LAT, window.UIU_LNG],
            zoom: 14,
            zoomControl: true,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(window.leafletMap);

        // UIU campus marker
        const uiuIcon = L.divIcon({
            className: 'uiu-marker',
            html: '<div style="background:linear-gradient(135deg,#4361ee,#7c3aed);color:#fff;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;box-shadow:0 2px 12px rgba(67,97,238,0.4);border:3px solid #fff;">🎓</div>',
            iconSize: [36, 36],
            iconAnchor: [18, 18],
        });
        L.marker([window.UIU_LAT, window.UIU_LNG], { icon: uiuIcon })
            .addTo(window.leafletMap)
            .bindPopup('<div class="map-popup"><h4>🎓 United International University</h4><p>Permanent Campus, Madani Avenue</p></div>');

        markersLayer = L.layerGroup().addTo(window.leafletMap);
        window.mapInitialized = true;
    };

    window.updateMapMarkers = function(listings) {
        if (!markersLayer || !window.leafletMap) return;
        markersLayer.clearLayers();

        listings.forEach(l => {
            if (!l.location_lat || !l.location_lng) return;

            const color = l.listing_type === 'roommate_needed' ? '#10b981' : '#4361ee';
            const icon = L.divIcon({
                className: 'listing-marker',
                html: `<div style="background:${color};color:#fff;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;box-shadow:0 2px 8px rgba(0,0,0,0.3);border:2px solid #fff;cursor:pointer;">🏠</div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14],
            });

            const dist = l.distance_km != null ? `<p>📍 ${l.distance_km} km from UIU</p>` : '';
            const popup = `
                <div class="map-popup">
                    <h4>${escapeHtml(l.title)}</h4>
                    <p class="rent-badge">৳${Number(l.rent_amount).toLocaleString()}</p>
                    <p>🏢 ${escapeHtml(l.property_name)}</p>
                    ${dist}
                    <a href="${APP_URL}/pages/listing-detail.php?id=${l.id}" style="display:inline-block;margin-top:6px;padding:4px 12px;background:var(--accent,#4361ee);color:#fff;border-radius:6px;font-size:12px;text-decoration:none;">View Details →</a>
                </div>`;

            L.marker([l.location_lat, l.location_lng], { icon })
                .addTo(markersLayer)
                .bindPopup(popup);
        });
    };
})();
