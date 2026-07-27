/**
 * Header Module JS
 * Namespace: AhostOne.modules.Header
 */
(function (App) {
    'use strict';

    const Header = {
        init() {
            const btn = document.querySelector('[data-aho-mobile-toggle]');
            const menu = document.querySelector('[data-aho-mobile-menu]');
            if (!btn || !menu) return;

            btn.addEventListener('click', () => {
                const isOpen = menu.classList.toggle('is-open');
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                document.body.style.overflow = isOpen ? 'hidden' : '';
            });

            // Route değişimlerinde kapat (SPA-like davranış için)
            menu.querySelectorAll('a').forEach(a => {
                a.addEventListener('click', () => {
                    menu.classList.remove('is-open');
                    btn.setAttribute('aria-expanded', 'false');
                    document.body.style.overflow = '';
                });
            });
        },
    };

    App.modules.Header = Header;
    document.addEventListener('DOMContentLoaded', () => Header.init());
})(window.AhostOne = window.AhostOne || { modules: {}, config: {}, utils: {} });
