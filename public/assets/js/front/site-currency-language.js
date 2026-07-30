(function(){
  if (document.body && document.body.classList.contains('theme-ahost-prism')) return;

  function readCookie(name){
    var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.$?*|{}()[\]\\/+^]/g, '\\$&') + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : '';
  }

  function writeCookie(name, value){
    document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; max-age=' + (60 * 60 * 24 * 365);
  }

  var SYMBOLS = {TRY:'₺', TL:'₺', USD:'$', EUR:'€', GBP:'£'};

  function currencyDisplayMode(){
    var el = document.querySelector('[data-currency-switcher]');
    return (el && el.getAttribute('data-currency-display-mode')) || 'symbol';
  }

  function currencyLabel(currency){
    currency = (currency || 'TRY').toUpperCase();
    var symbol = SYMBOLS[currency] || currency;
    var mode = currencyDisplayMode();
    if(mode === 'code') return currency;
    if(mode === 'both') return symbol + ' ' + currency;
    return symbol;
  }

  function formatMoney(value, currency){
    currency = (currency || 'TRY').toUpperCase();
    var symbol = SYMBOLS[currency] || currency;
    var mode = currencyDisplayMode();
    var locale = currency === 'TRY' ? 'tr-TR' : 'en-US';
    var amount = Number(value || 0).toLocaleString(locale, {minimumFractionDigits:2, maximumFractionDigits:2});
    if(mode === 'code') return amount + ' ' + currency;
    if(mode === 'both') return amount + ' ' + symbol + ' ' + currency;
    return currency === 'TRY' ? amount + ' ' + symbol : symbol + amount;
  }

  function priceBaseTry(el){
    var raw = el.getAttribute('data-price-base') || el.getAttribute('data-base-price') || '';
    var n = parseFloat(String(raw).replace(',', '.'));
    return isNaN(n) ? null : n;
  }

  function getRatesMap(){
    var el = document.querySelector('[data-rates]');
    if(el){
      try {
        var map = JSON.parse(el.getAttribute('data-rates') || '{}');
        if(map && typeof map === 'object'){
          if(!map.TRY) map.TRY = 1;
          return map;
        }
      } catch(e){}
    }
    var simple = document.querySelector('[data-currency-switcher][data-rate]');
    var rate = simple ? parseFloat(simple.getAttribute('data-rate')) : 1;
    return {TRY:1, USD: rate || 1};
  }

  function closeMenus(except){
    document.querySelectorAll('[data-prism-currency-menu], [data-lang-menu], [data-prism-login-menu]').forEach(function(menu){
      if(menu !== except) menu.setAttribute('hidden', '');
    });
    document.querySelectorAll('[aria-expanded="true"]').forEach(function(btn){
      var ownsCurrent = except && btn.closest('[data-prism-currency], [data-lang-dropdown], [data-prism-login]') && btn.closest('[data-prism-currency], [data-lang-dropdown], [data-prism-login]').contains(except);
      if(!ownsCurrent) btn.setAttribute('aria-expanded', 'false');
    });
  }

  function toggleMenu(button, selector){
    var wrap = button.closest('[data-prism-currency], [data-lang-dropdown], [data-prism-login]');
    var menu = wrap ? wrap.querySelector(selector) : null;
    if(!menu) return;
    var shouldOpen = menu.hasAttribute('hidden');
    closeMenus(shouldOpen ? menu : null);
    menu.toggleAttribute('hidden', !shouldOpen);
    button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
  }

  function openAuthModal(panel){
    var modal = document.querySelector('[data-auth-modal]');
    if(!modal) return;
    modal.hidden = false;
    document.documentElement.classList.add('ao-auth-modal-open');
    setAuthPanel(panel || 'login');
    var first = modal.querySelector('input');
    if(first) setTimeout(function(){ first.focus(); }, 40);
  }

  function closeAuthModal(){
    var modal = document.querySelector('[data-auth-modal]');
    if(!modal) return;
    modal.hidden = true;
    document.documentElement.classList.remove('ao-auth-modal-open');
  }

  function setAuthPanel(panel){
    panel = panel === 'register' ? 'register' : 'login';
    document.querySelectorAll('[data-auth-tab]').forEach(function(btn){
      btn.classList.toggle('is-active', btn.getAttribute('data-auth-tab') === panel);
    });
    document.querySelectorAll('[data-auth-panel]').forEach(function(section){
      section.hidden = section.getAttribute('data-auth-panel') !== panel;
    });
    if(panel === 'login') setLoginMethod('email');
  }

  function setLoginMethod(method){
    method = method === 'sms' || method === 'phone' ? method : 'email';
    document.querySelectorAll('[data-login-method-tab]').forEach(function(btn){
      btn.classList.toggle('is-active', btn.getAttribute('data-login-method-tab') === method);
    });
    document.querySelectorAll('[data-login-method-panel]').forEach(function(panel){
      panel.hidden = panel.getAttribute('data-login-method-panel') !== method;
    });
  }

  function setCurrency(currency, rates){
    currency = (currency || 'TRY').toUpperCase();
    if(currency === 'TL') currency = 'TRY';
    if(!rates[currency]) currency = rates.TRY ? 'TRY' : Object.keys(rates)[0];
    writeCookie('ao_currency', currency);

    document.querySelectorAll('[data-currency-switcher] button[data-currency]').forEach(function(btn){
      var active = btn.getAttribute('data-currency') === currency;
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    });

    document.querySelectorAll('[data-prism-current-currency]').forEach(function(span){
      span.textContent = currencyLabel(currency);
    });

    var rate = parseFloat(rates[currency] || 1) || 1;
    document.querySelectorAll('[data-price-base], [data-base-price]').forEach(function(el){
      var tryAmount = priceBaseTry(el);
      if(tryAmount === null) return;
      var shown = currency === 'TRY' ? tryAmount : (tryAmount / rate);
      el.textContent = formatMoney(shown, currency);
    });
  }

  function setLanguage(button){
    var lang = (button.getAttribute('data-lang') || 'tr').toLowerCase();
    var label = button.getAttribute('data-lang-label') || lang.toUpperCase();
    var flagUrl = button.getAttribute('data-lang-flag-url') || '';
    writeCookie('ao_lang', lang);
    document.documentElement.setAttribute('lang', lang);
    document.querySelectorAll('[data-current-lang-label]').forEach(function(el){
      if(flagUrl){
        el.innerHTML = '';
        var img = document.createElement('img');
        img.src = flagUrl;
        img.alt = lang.toUpperCase();
        img.loading = 'lazy';
        el.appendChild(img);
      } else {
        el.textContent = label;
      }
    });
    document.querySelectorAll('[data-lang-menu] button[data-lang]').forEach(function(btn){
      var active = (btn.getAttribute('data-lang') || '').toLowerCase() === lang;
      btn.setAttribute('aria-pressed', active ? 'true' : 'false');
      btn.classList.toggle('is-active', active);
    });

    var wrap = button.closest('[data-lang-dropdown]');
    var mode = (wrap && wrap.getAttribute('data-lang-mode')) || 'ajax';
    if(mode === 'ajax'){
      var baseUrl = (wrap && wrap.getAttribute('data-base-url')) || window.AHOST_BASE_URL || '';
      if(baseUrl){
        fetch(baseUrl.replace(/\/$/, '') + '/language/set', {
          method: 'POST',
          headers: {'Content-Type':'application/x-www-form-urlencoded', 'Accept':'application/json'},
          body: 'lang=' + encodeURIComponent(lang)
        }).catch(function(){});
      }
    }
  }

  document.addEventListener('DOMContentLoaded', function(){
    var rates = getRatesMap();
    var switcher = document.querySelector('[data-currency-switcher]');
    var current = (switcher && switcher.getAttribute('data-current')) || readCookie('ao_currency') || 'TRY';
    setCurrency(current, rates);

    document.addEventListener('click', function(e){
      var currencyToggle = e.target.closest('[data-prism-currency-toggle]');
      if(currencyToggle){
        e.preventDefault();
        toggleMenu(currencyToggle, '[data-prism-currency-menu]');
        return;
      }

      var langToggle = e.target.closest('[data-lang-button]');
      if(langToggle){
        e.preventDefault();
        toggleMenu(langToggle, '[data-lang-menu]');
        return;
      }

      var loginToggle = e.target.closest('[data-prism-login-toggle]');
      if(loginToggle){
        e.preventDefault();
        if(document.querySelector('[data-auth-modal]') && !document.body.classList.contains('is-customer-authenticated')){
          closeMenus();
          openAuthModal('login');
        } else {
          toggleMenu(loginToggle, '[data-prism-login-menu]');
        }
        return;
      }

      var authOpen = e.target.closest('[data-auth-modal-open]');
      if(authOpen){
        e.preventDefault();
        closeMenus();
        openAuthModal(authOpen.getAttribute('data-auth-modal-open') || 'login');
        return;
      }

      var authTab = e.target.closest('[data-auth-tab]');
      if(authTab){
        e.preventDefault();
        setAuthPanel(authTab.getAttribute('data-auth-tab'));
        return;
      }

      var loginMethodTab = e.target.closest('[data-login-method-tab]');
      if(loginMethodTab){
        e.preventDefault();
        setLoginMethod(loginMethodTab.getAttribute('data-login-method-tab'));
        return;
      }

      if(e.target.closest('[data-auth-modal-close]')){
        e.preventDefault();
        closeAuthModal();
        return;
      }

      var currencyBtn = e.target.closest('[data-currency-switcher] button[data-currency], [data-prism-currency-menu] button[data-currency]');
      if(currencyBtn){
        e.preventDefault();
        setCurrency(currencyBtn.getAttribute('data-currency'), rates);
        closeMenus();
        return;
      }

      var langBtn = e.target.closest('[data-lang-menu] button[data-lang]');
      if(langBtn){
        e.preventDefault();
        setLanguage(langBtn);
        closeMenus();
        return;
      }

      if(!e.target.closest('[data-prism-currency], [data-lang-dropdown], [data-prism-login]')) {
        closeMenus();
      }
    });

    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape') {
        closeMenus();
        closeAuthModal();
      }
    });
  });
})();

