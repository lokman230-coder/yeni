/**
 * Admin Module JS
 */
(function (App) {
    'use strict';

    const Admin = {
        init() {
            // Sidebar overlay tıklamasında kapat
            document.addEventListener('click', (e) => {
                const sidebar = document.getElementById('ahoAdminSidebar');
                if (!sidebar || !sidebar.classList.contains('is-open')) return;
                if (window.innerWidth >= 992) return;
                if (e.target.closest('.aho-admin-sidebar') || e.target.closest('[onclick*="ahoAdminSidebar"]')) return;
                sidebar.classList.remove('is-open');
            });
        },
    };

    App.modules.Admin = Admin;
    document.addEventListener('DOMContentLoaded', () => Admin.init());
})(window.AhostOne = window.AhostOne || { modules: {}, config: {}, utils: {} });
