/**
 * UIU Nest — Core App JS (theme, sidebar, modals, toasts, utils)
 */
(function() {
    'use strict';

    // ─── Theme System ───
    const ThemeManager = {
        init() {
            const saved = localStorage.getItem('uiu-theme');
            const system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            this.set(saved || system, false);

            document.getElementById('themeToggle')?.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-theme');
                this.set(current === 'dark' ? 'light' : 'dark', true);
            });

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem('uiu-theme')) {
                    this.set(e.matches ? 'dark' : 'light', false);
                }
            });
        },
        set(theme, save) {
            document.documentElement.setAttribute('data-theme', theme);
            if (save) localStorage.setItem('uiu-theme', theme);
        }
    };

    // ─── Sidebar ───
    const Sidebar = {
        init() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const hamburger = document.getElementById('hamburgerBtn');
            const closeBtn = document.getElementById('sidebarClose');

            if (!sidebar) return;

            hamburger?.addEventListener('click', () => this.toggle(true));
            closeBtn?.addEventListener('click', () => this.toggle(false));
            overlay?.addEventListener('click', () => this.toggle(false));
        },
        toggle(open) {
            document.getElementById('sidebar')?.classList.toggle('open', open);
            document.getElementById('sidebarOverlay')?.classList.toggle('active', open);
            document.body.style.overflow = open ? 'hidden' : '';
        }
    };

    // ─── User Dropdown ───
    const UserDropdown = {
        init() {
            const el = document.getElementById('topbarUser');
            if (!el) return;
            el.addEventListener('click', (e) => {
                e.stopPropagation();
                el.classList.toggle('open');
            });
            document.addEventListener('click', () => el.classList.remove('open'));
        }
    };

    // ─── Toast System ───
    window.Toast = {
        show(message, type = 'info', duration = 4000) {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `<span>${icons[type] || ''}</span><span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('removing');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
    };

    // ─── Modal System ───
    window.Modal = {
        open(id) {
            const overlay = document.getElementById(id);
            if (overlay) { overlay.classList.add('active'); document.body.style.overflow = 'hidden'; }
        },
        close(id) {
            const overlay = document.getElementById(id);
            if (overlay) { overlay.classList.remove('active'); document.body.style.overflow = ''; }
        }
    };

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(m => {
                m.classList.remove('active');
            });
            document.body.style.overflow = '';
        }
    });

    // ─── Fetch API Wrapper ───
    window.fetchAPI = async function(url, options = {}) {
        const csrf = document.getElementById('csrfToken')?.value || '';
        const defaults = {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrf,
            },
        };
        if (!(options.body instanceof FormData)) {
            defaults.headers['Content-Type'] = 'application/json';
        }
        const config = { ...defaults, ...options };
        if (options.headers) {
            config.headers = { ...defaults.headers, ...options.headers };
        }
        try {
            const resp = await fetch(url, config);
            const data = await resp.json();
            if (!resp.ok) {
                throw new Error(data.error || data.message || 'Request failed');
            }
            return data;
        } catch (err) {
            Toast.show(err.message, 'error');
            throw err;
        }
    };

    // ─── Init ───
    document.addEventListener('DOMContentLoaded', () => {
        ThemeManager.init();
        Sidebar.init();
        UserDropdown.init();
    });
})();
