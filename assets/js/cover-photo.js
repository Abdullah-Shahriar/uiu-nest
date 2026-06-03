/**
 * UIU Nest — Cover Photo Position Adjuster (Task 3)
 *
 * Allows property owners to drag the cover photo to reposition it.
 * Saves the CSS object-position to the server via AJAX.
 *
 * Usage (PHP side):
 *   CoverPhoto.init({
 *       imgEl:      document.getElementById('coverPhotoImg'),
 *       propertyId: 5,
 *       editable:   true   // false for non-owners
 *   });
 */
(function () {
    'use strict';

    function init(opts) {
        const img        = opts.imgEl;
        const propId     = opts.propertyId;
        const editable   = opts.editable !== false;
        const apiUrl     = (window.APP_URL || '') + '/api/properties.php';

        if (!img || !editable) return;

        /* ── Inject adjust button ──────────────────────────────── */
        const wrap = img.closest('.cover-photo-wrap') || img.parentElement;
        wrap.style.position = 'relative';
        wrap.style.overflow = 'hidden';

        const toolbar = document.createElement('div');
        toolbar.id    = 'coverPhotoToolbar';
        toolbar.style.cssText = [
            'position:absolute;bottom:12px;right:12px;',
            'display:flex;gap:8px;z-index:10;',
            'opacity:0;transition:opacity 0.2s;'
        ].join('');

        toolbar.innerHTML = `
<button id="cpAdjustBtn" title="Drag to reposition photo" style="
    padding:6px 14px;border-radius:20px;border:1.5px solid rgba(255,255,255,0.35);
    background:rgba(0,0,0,0.55);backdrop-filter:blur(8px);
    color:#fff;font-size:0.78rem;font-weight:600;cursor:grab;
    display:flex;align-items:center;gap:6px;">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
       stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
    <polyline points="5 9 2 12 5 15"/><polyline points="9 5 12 2 15 5"/>
    <polyline points="15 19 12 22 9 19"/><polyline points="19 9 22 12 19 15"/>
    <line x1="2" y1="12" x2="22" y2="12"/><line x1="12" y1="2" x2="12" y2="22"/>
  </svg>
  Adjust
</button>
<button id="cpSaveBtn" title="Save position" style="
    padding:6px 14px;border-radius:20px;border:1.5px solid rgba(56,189,248,0.55);
    background:rgba(14,165,233,0.75);backdrop-filter:blur(8px);
    color:#fff;font-size:0.78rem;font-weight:600;cursor:pointer;
    display:none;align-items:center;gap:6px;">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
       stroke-linecap="round" stroke-linejoin="round" width="13" height="13">
    <polyline points="20 6 9 17 4 12"/>
  </svg>
  Save
</button>`;

        wrap.appendChild(toolbar);

        /* Show toolbar on hover */
        wrap.addEventListener('mouseenter', function () { toolbar.style.opacity = '1'; });
        wrap.addEventListener('mouseleave', function () { if (!adjusting) toolbar.style.opacity = '0'; });

        const adjustBtn = toolbar.querySelector('#cpAdjustBtn');
        const saveBtn   = toolbar.querySelector('#cpSaveBtn');

        /* ── Drag-to-reposition logic ──────────────────────────── */
        let adjusting = false;
        let startX = 0, startY = 0;
        let posX = 50, posY = 50; // default 50% 50%

        /* Parse existing object-position */
        const existingPos = img.style.objectPosition || '50% 50%';
        const parts = existingPos.split(' ');
        posX = parseFloat(parts[0]) || 50;
        posY = parseFloat(parts[1]) || 50;

        adjustBtn.addEventListener('click', function () {
            adjusting = !adjusting;
            if (adjusting) {
                img.style.cursor       = 'grab';
                adjustBtn.style.display = 'none';
                saveBtn.style.display   = 'flex';
                toolbar.style.opacity   = '1';
            }
        });

        img.addEventListener('mousedown', function (e) {
            if (!adjusting) return;
            e.preventDefault();
            img.style.cursor = 'grabbing';
            startX = e.clientX;
            startY = e.clientY;

            function onMove(ev) {
                const rect = img.getBoundingClientRect();
                const dx = ((ev.clientX - startX) / rect.width)  * -100;
                const dy = ((ev.clientY - startY) / rect.height) * -100;
                posX = Math.max(0, Math.min(100, posX + dx));
                posY = Math.max(0, Math.min(100, posY + dy));
                img.style.objectPosition = posX.toFixed(1) + '% ' + posY.toFixed(1) + '%';
                startX = ev.clientX;
                startY = ev.clientY;
            }

            function onUp() {
                img.style.cursor = 'grab';
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
            }

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });

        /* Touch support */
        img.addEventListener('touchstart', function (e) {
            if (!adjusting) return;
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        }, { passive: true });

        img.addEventListener('touchmove', function (e) {
            if (!adjusting) return;
            e.preventDefault();
            const rect = img.getBoundingClientRect();
            const dx = ((e.touches[0].clientX - startX) / rect.width)  * -80;
            const dy = ((e.touches[0].clientY - startY) / rect.height) * -80;
            posX = Math.max(0, Math.min(100, posX + dx));
            posY = Math.max(0, Math.min(100, posY + dy));
            img.style.objectPosition = posX.toFixed(1) + '% ' + posY.toFixed(1) + '%';
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        }, { passive: false });

        /* ── Save to server ──────────────────────────────────────── */
        saveBtn.addEventListener('click', async function () {
            saveBtn.textContent = 'Saving...';
            saveBtn.disabled = true;

            try {
                const r = await fetch(apiUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        id: propId,
                        cover_photo_position: posX.toFixed(1) + '% ' + posY.toFixed(1) + '%'
                    })
                });
                const d = await r.json();
                if (d.success) {
                    window.Toast && Toast.show('Cover photo position saved!', 'success');
                } else {
                    window.Toast && Toast.show(d.error || 'Save failed', 'error');
                }
            } catch (err) {
                window.Toast && Toast.show('Network error', 'error');
            }

            adjusting = false;
            img.style.cursor    = 'default';
            saveBtn.style.display  = 'none';
            adjustBtn.style.display = 'flex';
            saveBtn.disabled    = false;
            saveBtn.innerHTML   = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
       stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg> Save`;
            toolbar.style.opacity   = '0';
        });
    }

    window.CoverPhoto = { init: init };
})();
