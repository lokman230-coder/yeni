/**
 * Ahost Bilişim — Theme JS
 * Tek global: window.AhostOne
 */
(function () {
    'use strict';

    window.AhostOne = window.AhostOne || {
        modules: {},
        config: {},
        utils: {},
    };

    const App = window.AhostOne;

    // ---- Utilities ----
    App.utils.$ = (sel, root = document) => root.querySelector(sel);
    App.utils.$$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

    App.utils.on = (el, event, selectorOrHandler, handler) => {
        if (typeof selectorOrHandler === 'function') {
            el.addEventListener(event, selectorOrHandler);
            return;
        }
        el.addEventListener(event, (e) => {
            const target = e.target.closest(selectorOrHandler);
            if (target) handler.call(target, e, target);
        });
    };

    App.utils.emit = (name, detail = {}) => {
        document.dispatchEvent(new CustomEvent('aho:' + name, { detail, bubbles: true }));
    };

    App.utils.fetch = async (url, options = {}) => {
        const defaults = {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        };
        // CSRF token varsa ekle
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            defaults.headers['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');
        }
        const merged = {
            ...defaults,
            ...options,
            headers: { ...defaults.headers, ...(options.headers || {}) },
        };
        const res = await fetch(url, merged);
        const contentType = res.headers.get('content-type') || '';
        const data = contentType.includes('application/json') ? await res.json() : await res.text();
        if (!res.ok) throw { status: res.status, data };
        return data;
    };

    // ---- Toast ----
    App.toast = {
        container: null,
        ensure() {
            if (this.container) return;
            const el = document.createElement('div');
            el.className = 'aho-toast-container';
            el.setAttribute('aria-live', 'polite');
            document.body.appendChild(el);
            this.container = el;
        },
        show(message, type = 'info', duration = 5000) {
            this.ensure();
            const t = document.createElement('div');
            t.className = `aho-toast aho-toast--${type}`;
            t.innerHTML = `<span>${message}</span><button class="aho-toast__close" aria-label="Kapat">×</button>`;
            this.container.appendChild(t);
            requestAnimationFrame(() => t.classList.add('is-visible'));
            const remove = () => {
                t.classList.remove('is-visible');
                setTimeout(() => t.remove(), 220);
            };
            t.querySelector('.aho-toast__close').addEventListener('click', remove);
            if (duration > 0) setTimeout(remove, duration);
        },
        success(m, d) { this.show(m, 'success', d); },
        error(m, d) { this.show(m, 'danger', d); },
        info(m, d) { this.show(m, 'info', d); },
        warning(m, d) { this.show(m, 'warning', d); },
    };

    // ---- Theme (dark/light) ----
    App.theme = {
        get() {
            return document.documentElement.getAttribute('data-theme') || 'light';
        },
        set(mode) {
            document.documentElement.setAttribute('data-theme', mode);
            try { localStorage.setItem('aho-theme', mode); } catch (e) {}
        },
        toggle() {
            this.set(this.get() === 'dark' ? 'light' : 'dark');
        },
        init() {
            try {
                const stored = localStorage.getItem('aho-theme');
                if (stored) { this.set(stored); return; }
            } catch (e) {}
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                this.set('dark');
            }
        },
    };

    // Erken tema başlat (FOUC önlemek için ideal olarak inline script'te yapılır)
    App.theme.init();

    // ---- Cookie helper ----
    App.cookie = {
        get(name) {
            const m = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
            return m ? decodeURIComponent(m[2]) : null;
        },
        set(name, value, days = 365) {
            const d = new Date(); d.setTime(d.getTime() + days * 864e5);
            document.cookie = `${name}=${encodeURIComponent(value)};expires=${d.toUTCString()};path=/;SameSite=Lax`;
        },
    };

    // ---- Dropdown ----
    App.dropdown = {
        init() {
            document.addEventListener('click', (e) => {
                const trigger = e.target.closest('[data-aho-dropdown-trigger]');
                if (trigger) {
                    e.preventDefault();
                    const menu = trigger.nextElementSibling;
                    if (menu && menu.hasAttribute('data-aho-dropdown-menu')) {
                        // Diğerlerini kapat
                        document.querySelectorAll('[data-aho-dropdown-menu].is-open').forEach(m => {
                            if (m !== menu) m.classList.remove('is-open');
                        });
                        menu.classList.toggle('is-open');
                    }
                    return;
                }
                // Dış tıklama → kapat
                if (!e.target.closest('[data-aho-dropdown-menu]')) {
                    document.querySelectorAll('[data-aho-dropdown-menu].is-open').forEach(m => m.classList.remove('is-open'));
                }
            });
        },
    };

    // ---- DOM Ready ----
    document.addEventListener('DOMContentLoaded', () => {
        App.dropdown.init();
        App.utils.emit('ready', { app: App });
    });
})();
