(function (App) {
    'use strict';

    const Admin = {
        init() {
            this.bindDropdowns();
            this.bindSidebarGroups();
            this.closeSidebarOnMobileNav();
        },

        bindDropdowns() {
            document.querySelectorAll('[data-aho-dropdown-trigger]').forEach(trigger => {
                const wrap = trigger.closest('.aho-topbar__dropdown');
                const menu = wrap ? wrap.querySelector('[data-aho-dropdown-menu]') : null;
                if (!wrap || !menu) return;

                trigger.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    document.querySelectorAll('.aho-topbar__dropdown.is-open').forEach(openWrap => {
                        if (openWrap !== wrap) openWrap.classList.remove('is-open');
                    });
                    wrap.classList.toggle('is-open');
                });
            });

            document.addEventListener('click', () => {
                document.querySelectorAll('.aho-topbar__dropdown.is-open').forEach(wrap => wrap.classList.remove('is-open'));
            });
        },

        bindSidebarGroups() {
            document.querySelectorAll('[data-admin-menu-group]').forEach(group => {
                const key = 'aho_admin_menu_' + group.getAttribute('data-admin-menu-group');
                const saved = localStorage.getItem(key);
                if (saved === 'open') group.open = true;
                if (saved === 'closed' && !group.querySelector('.is-active')) group.open = false;
                group.addEventListener('toggle', () => {
                    localStorage.setItem(key, group.open ? 'open' : 'closed');
                });
            });
        },

        closeSidebarOnMobileNav() {
            const sidebar = document.getElementById('ahoAdminSidebar');
            if (!sidebar) return;
            sidebar.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 992) sidebar.classList.remove('is-open');
                });
            });
        },
    };

    App.modules.Admin = Admin;
    document.addEventListener('DOMContentLoaded', () => Admin.init());
})(window.AhostOne = window.AhostOne || { modules: {}, config: {}, utils: {} });
