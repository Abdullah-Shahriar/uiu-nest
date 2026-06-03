/**
 * UIU Nest — Lightbox (Task 3)
 * Vanilla JS full-screen image slider.
 *
 * Usage:
 *   Lightbox.open(images, startIndex)
 *   where images = [{src: '...', alt: '...'}, ...]
 *
 * Auto-init: any element with data-lightbox="group-name" and data-src="url"
 * will be grouped and clickable.
 */
(function () {
    'use strict';

    /* ── State ─────────────────────────────────────────── */
    let images = [];
    let current = 0;
    let overlay = null;
    let imgEl   = null;
    let capEl   = null;
    let counterEl = null;

    /* ── Build DOM (once) ─────────────────────────────── */
    function buildDOM() {
        if (overlay) return;

        overlay = document.createElement('div');
        overlay.id = 'uiuLightbox';
        overlay.style.cssText = [
            'position:fixed;inset:0;z-index:99999;',
            'background:rgba(0,0,0,0.96);',
            'display:none;align-items:center;justify-content:center;',
            'user-select:none;'
        ].join('');

        overlay.innerHTML = `
<button id="lbClose" aria-label="Close" style="
    position:absolute;top:18px;right:22px;
    background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);
    border-radius:50%;width:44px;height:44px;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    color:#fff;font-size:20px;transition:background 0.18s;z-index:2;
    backdrop-filter:blur(6px);">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
       stroke-linecap="round" width="20" height="20">
    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
  </svg>
</button>

<button id="lbPrev" aria-label="Previous" style="
    position:absolute;left:18px;top:50%;transform:translateY(-50%);
    background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);
    border-radius:50%;width:48px;height:48px;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    color:#fff;font-size:22px;transition:background 0.18s;z-index:2;
    backdrop-filter:blur(6px);">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
       stroke-linecap="round" stroke-linejoin="round" width="22" height="22">
    <polyline points="15 18 9 12 15 6"/>
  </svg>
</button>

<button id="lbNext" aria-label="Next" style="
    position:absolute;right:18px;top:50%;transform:translateY(-50%);
    background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);
    border-radius:50%;width:48px;height:48px;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    color:#fff;font-size:22px;transition:background 0.18s;z-index:2;
    backdrop-filter:blur(6px);">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
       stroke-linecap="round" stroke-linejoin="round" width="22" height="22">
    <polyline points="9 18 15 12 9 6"/>
  </svg>
</button>

<div id="lbImgWrap" style="
    display:flex;align-items:center;justify-content:center;
    width:100%;height:100%;padding:60px 80px;box-sizing:border-box;">
  <img id="lbImg" src="" alt="" style="
      max-width:100%;max-height:100%;object-fit:contain;
      border-radius:8px;transition:opacity 0.22s;"/>
</div>

<div id="lbFooter" style="
    position:absolute;bottom:0;left:0;right:0;
    padding:16px 24px;
    background:linear-gradient(transparent,rgba(0,0,0,0.7));
    display:flex;align-items:center;justify-content:space-between;color:#fff;">
  <span id="lbCaption" style="font-size:0.88rem;opacity:0.85;"></span>
  <span id="lbCounter" style="font-size:0.78rem;opacity:0.65;background:rgba(255,255,255,0.12);
    padding:3px 10px;border-radius:20px;"></span>
</div>`;

        document.body.appendChild(overlay);

        imgEl     = overlay.querySelector('#lbImg');
        capEl     = overlay.querySelector('#lbCaption');
        counterEl = overlay.querySelector('#lbCounter');

        overlay.querySelector('#lbClose').addEventListener('click', close);
        overlay.querySelector('#lbPrev').addEventListener('click', prev);
        overlay.querySelector('#lbNext').addEventListener('click', next);

        /* Close on backdrop click */
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay || e.target.id === 'lbImgWrap') close();
        });

        /* Keyboard */
        document.addEventListener('keydown', function (e) {
            if (!isOpen()) return;
            if (e.key === 'Escape') close();
            if (e.key === 'ArrowLeft') prev();
            if (e.key === 'ArrowRight') next();
        });

        /* Touch / swipe */
        let touchX = null;
        overlay.addEventListener('touchstart', function (e) { touchX = e.touches[0].clientX; }, { passive: true });
        overlay.addEventListener('touchend', function (e) {
            if (touchX === null) return;
            const diff = touchX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 40) diff > 0 ? next() : prev();
            touchX = null;
        });
    }

    /* ── Render current image ─────────────────────────── */
    function render() {
        const img = images[current];
        imgEl.style.opacity = '0';
        setTimeout(function () {
            imgEl.src = img.src;
            imgEl.alt = img.alt || '';
            imgEl.style.opacity = '1';
        }, 120);
        capEl.textContent     = img.alt || '';
        counterEl.textContent = (current + 1) + ' / ' + images.length;

        /* Show/hide nav arrows */
        overlay.querySelector('#lbPrev').style.display = images.length > 1 ? 'flex' : 'none';
        overlay.querySelector('#lbNext').style.display = images.length > 1 ? 'flex' : 'none';
    }

    /* ── Public API ───────────────────────────────────── */
    function open(imgArray, startIndex) {
        buildDOM();
        images  = imgArray || [];
        current = startIndex || 0;
        if (!images.length) return;
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        render();
    }

    function close() {
        if (!overlay) return;
        overlay.style.display = 'none';
        document.body.style.overflow = '';
        imgEl.src = '';
    }

    function prev() {
        current = (current - 1 + images.length) % images.length;
        render();
    }

    function next() {
        current = (current + 1) % images.length;
        render();
    }

    function isOpen() {
        return overlay && overlay.style.display !== 'none';
    }

    /* ── Auto-init via data-lightbox attributes ───────── */
    document.addEventListener('DOMContentLoaded', function () {
        const groups = {};
        document.querySelectorAll('[data-lightbox]').forEach(function (el) {
            const group = el.getAttribute('data-lightbox');
            if (!groups[group]) groups[group] = [];
            groups[group].push({ el: el, src: el.getAttribute('data-src') || el.src || el.href, alt: el.getAttribute('data-alt') || el.alt || '' });
        });

        Object.entries(groups).forEach(function ([group, items]) {
            items.forEach(function (item, idx) {
                item.el.style.cursor = 'zoom-in';
                item.el.addEventListener('click', function (e) {
                    e.preventDefault();
                    open(items.map(function (i) { return { src: i.src, alt: i.alt }; }), idx);
                });
            });
        });
    });

    window.Lightbox = { open: open, close: close, prev: prev, next: next };
})();
