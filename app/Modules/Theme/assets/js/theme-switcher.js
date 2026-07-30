/**
 * Theme Switcher — açma/kapama ve preview.
 */
(function (App) {
    'use strict';

    const Switcher = {
        init() {
            const root = document.getElementById('ahoThemeSwitch');
            if (!root) return;

            const toggle = root.querySelector('.aho-theme-switch__toggle');
            const close  = root.querySelector('.aho-theme-switch__close');

            toggle.addEventListener('click', (e) => {
                e.stopPropagation();
                root.classList.toggle('is-open');
            });
            close.addEventListener('click', () => root.classList.remove('is-open'));

            // Dış tık
            document.addEventListener('click', (e) => {
                if (!root.contains(e.target)) root.classList.remove('is-open');
            });

            // ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') root.classList.remove('is-open');
            });
        },
    };

    App.modules.ThemeSwitcher = Switcher;
    document.addEventListener('DOMContentLoaded', () => Switcher.init());
})(window.AhostOne = window.AhostOne || { modules: {}, config: {}, utils: {} });
