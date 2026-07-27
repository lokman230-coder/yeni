/**
 * Cookie Consent Module
 */
(function (App) {
    'use strict';

    const Cookie = {
        cookieName: 'aho_cookie_consent',
        init() {
            const banner = document.getElementById('ahoCookieBanner');
            if (!banner) return;

            banner.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-aho-cookie]');
                if (!btn) return;
                const choice = btn.getAttribute('data-aho-cookie');
                this.setChoice(choice === 'accept' ? 'accepted' : 'rejected');
                banner.remove();
            });
        },
        setChoice(value) {
            App.cookie.set(this.cookieName, value, 365);
            App.utils.emit('cookie:consent', { value });
            if (value === 'accepted') this.enableAnalytics();
        },
        enableAnalytics() {
            // Faz 5'te CookieAnalytics tracker burada bağlanacak
            console.info('[Ahost] Analytics enabled');
        },
    };

    App.modules.Cookie = Cookie;
    document.addEventListener('DOMContentLoaded', () => Cookie.init());
})(window.AhostOne = window.AhostOne || { modules: {}, config: {}, utils: {}, cookie: {} });
