/**
 * UIU Nest v2 — Core App JS
 * Clock, Sidebar, Theme, Language, Toast, Modal, Fetch
 */
(function () {
    'use strict';

    // ─── SVG Icon helpers ─────────────────────────────────
    const Icons = {
        success: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--success)"><polyline points="20 6 9 17 4 12"/></svg>`,
        error:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--danger)"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
        info:    `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--info)"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
        warning: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--warning)"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
    };

    // ─── Theme ────────────────────────────────────────────
    const ThemeManager = {
        init() {
            const saved = localStorage.getItem('uiu-theme') || 'dark';
            this.set(saved, false);
            document.getElementById('themeToggle')?.addEventListener('click', () => {
                const cur = document.documentElement.getAttribute('data-theme');
                this.set(cur === 'dark' ? 'light' : 'dark', true);
            });
        },
        set(theme, save) {
            document.documentElement.setAttribute('data-theme', theme);
            if (save) localStorage.setItem('uiu-theme', theme);
        }
    };

    // ─── Live Clock ───────────────────────────────────────
    const Clock = {
        init() {
            const timeEl = document.getElementById('clockTime');
            const dateEl = document.getElementById('clockDate');
            if (!timeEl || !dateEl) return;
            const tick = () => {
                const now = new Date();
                timeEl.textContent = now.toLocaleTimeString('en-GB', { hour12: false });
                dateEl.textContent = now.toLocaleDateString('en-GB', {
                    weekday: 'short', day: '2-digit', month: 'short', year: 'numeric'
                });
            };
            tick();
            setInterval(tick, 1000);
        }
    };

    // ─── Language Translator ──────────────────────────────
    const TRANSLATIONS = {
        en: {
            nav_browse:         'Browse Listings',
            nav_profile:        'My Profile',
            nav_complaint:      'Submit Complaint',
            nav_applications:   'Applications',
            nav_all_apps:       'All Applications',
            nav_saved:          'Saved Listings',
            nav_manage_listings:'Manage Listings',
            nav_properties:     'My Properties',
            nav_former:         'Former Residents',
            nav_admin:          'Admin Panel',
            nav_sec_student:    'Student',
            nav_sec_manager:    'House Manager',
            nav_sec_owner:      'Owner',
            nav_sec_admin:      'Administration',
            btn_login:          'Login',
            btn_signup:         'Sign Up',
            btn_cancel:         'Cancel',
            btn_submit:         'Submit',
            complaint_title:    'Submit a Complaint',
            complaint_note:     'Your complaint goes directly to the Admin. Check "Submit Anonymously" to hide your identity.',
            complaint_category: 'Category',
            complaint_subject:  'Subject',
            complaint_desc:     'Description',
            complaint_anon_label: 'Submit Anonymously',
            complaint_anon_hint:  'Your name will not be visible to the Admin.',
            page_dashboard:     'Dashboard',
            page_my_profile:    'My Profile',
            page_applications:  'Applications',
            page_admin:         'Admin Panel',
            page_properties:    'My Properties',
        },
        bn: {
            nav_browse:         'তালিকা দেখুন',
            nav_profile:        'আমার প্রোফাইল',
            nav_complaint:      'অভিযোগ দিন',
            nav_applications:   'আবেদনসমূহ',
            nav_all_apps:       'সব আবেদন',
            nav_saved:          'সংরক্ষিত তালিকা',
            nav_manage_listings:'তালিকা পরিচালনা',
            nav_properties:     'আমার সম্পত্তি',
            nav_former:         'পূর্ববর্তী বাসিন্দা',
            nav_admin:          'অ্যাডমিন প্যানেল',
            nav_sec_student:    'ছাত্র',
            nav_sec_manager:    'হাউস ম্যানেজার',
            nav_sec_owner:      'মালিক',
            nav_sec_admin:      'প্রশাসন',
            btn_login:          'লগইন',
            btn_signup:         'নিবন্ধন',
            btn_cancel:         'বাতিল',
            btn_submit:         'জমা দিন',
            complaint_title:    'অভিযোগ জমা দিন',
            complaint_note:     'আপনার অভিযোগ সরাসরি অ্যাডমিনের কাছে যাবে।',
            complaint_category: 'বিভাগ',
            complaint_subject:  'বিষয়',
            complaint_desc:     'বিবরণ',
            complaint_anon_label: 'বেনামে জমা দিন',
            complaint_anon_hint:  'আপনার নাম অ্যাডমিনকে দেখানো হবে না।',
            page_dashboard:     'ড্যাশবোর্ড',
            page_my_profile:    'আমার প্রোফাইল',
            page_applications:  'আবেদনসমূহ',
            page_admin:         'অ্যাডমিন প্যানেল',
            page_properties:    'আমার সম্পত্তি',
        }
    };

    window.UIULang = {
        current: localStorage.getItem('uiu-lang') || 'en',
        init() {
            this.apply(this.current);
            this._syncButtons(this.current);
        },
        toggle() {
            this.set(this.current === 'en' ? 'bn' : 'en');
        },
        set(lang) {
            this.current = lang;
            localStorage.setItem('uiu-lang', lang);
            document.documentElement.setAttribute('data-lang', lang);
            this._syncButtons(lang);
            this.apply(lang);

            // Sync with Google Translate
            const gtValue = lang === 'bn' ? '/en/bn' : '/en/en';
            const gtCookieMatch = document.cookie.match(/(^|;) ?googtrans=([^;]*)(;|$)/);
            if (!gtCookieMatch || gtCookieMatch[2] !== gtValue) {
                document.cookie = `googtrans=${gtValue}; path=/`;
                document.cookie = `googtrans=${gtValue}; path=/; domain=${location.hostname}`;
                window.location.reload();
            }
        },
        _syncButtons(lang) {
            // Sidebar pills
            document.getElementById('langEn')?.classList.toggle('active', lang === 'en');
            document.getElementById('langBn')?.classList.toggle('active', lang === 'bn');
            // Topbar button
            const topBtn   = document.getElementById('langTopbarBtn');
            const topLabel = document.getElementById('langTopbarLabel');
            if (topLabel) topLabel.textContent = lang === 'en' ? 'EN' : 'বাং';
            if (topBtn)   topBtn.classList.toggle('bn-active', lang === 'bn');
        },
        apply(lang) {
            const dict = TRANSLATIONS[lang] || TRANSLATIONS.en;
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (dict[key] !== undefined) el.textContent = dict[key];
            });
        }
    };

    // ─── Sidebar ──────────────────────────────────────────
    const Sidebar = {
        init() {
            const sidebar   = document.getElementById('sidebar');
            const overlay   = document.getElementById('sidebarOverlay');
            const hamburger = document.getElementById('hamburgerBtn');
            const closeBtn  = document.getElementById('sidebarClose');
            if (!sidebar) return;
            hamburger?.addEventListener('click', () => this.toggle(true));
            closeBtn?.addEventListener('click',  () => this.toggle(false));
            overlay?.addEventListener('click',   () => this.toggle(false));
        },
        toggle(open) {
            document.getElementById('sidebar')?.classList.toggle('open', open);
            document.getElementById('sidebarOverlay')?.classList.toggle('active', open);
            document.body.style.overflow = open ? 'hidden' : '';
        }
    };

    // ─── Toast ────────────────────────────────────────────
    window.Toast = {
        show(message, type = 'info', duration = 4000) {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `<span style="flex-shrink:0;display:flex;">${Icons[type] || ''}</span><span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('removing');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
    };

    // ─── Modal ────────────────────────────────────────────
    window.Modal = {
        open(id) {
            const el = document.getElementById(id);
            if (el) { el.classList.add('active'); document.body.style.overflow = 'hidden'; }
        },
        close(id) {
            const el = document.getElementById(id);
            if (el) { el.classList.remove('active'); document.body.style.overflow = ''; }
        }
    };

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
            document.body.style.overflow = '';
        }
    });

    // ─── Fetch wrapper ────────────────────────────────────
    window.fetchAPI = async function (url, options = {}) {
        const csrf = document.getElementById('csrfToken')?.value || '';
        const defaults = {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrf,
            }
        };
        if (!(options.body instanceof FormData)) {
            defaults.headers['Content-Type'] = 'application/json';
        }
        const config = { ...defaults, ...options };
        if (options.headers) config.headers = { ...defaults.headers, ...options.headers };
        try {
            const resp = await fetch(url, config);
            const data = await resp.json();
            if (!resp.ok) throw new Error(data.error || data.message || 'Request failed');
            return data;
        } catch (err) {
            Toast.show(err.message, 'error');
            throw err;
        }
    };

    // ─── Init ─────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        ThemeManager.init();
        Clock.init();
        UIULang.init();
        Sidebar.init();
    });
})();
