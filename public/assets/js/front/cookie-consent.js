(function(){
  if (window.__aoCookieConsentV1) return;
  window.__aoCookieConsentV1 = true;

  var consentName = 'ao_cookie_consent';
  var visitorName = 'ao_visitor_id';

  function baseUrl() {
    var meta = document.querySelector('meta[name="ahost-base-url"]');
    return ((meta && meta.getAttribute('content')) || '').replace(/\/+$/, '');
  }

  function getCookie(name) {
    var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.$?*|{}()[\]\\/+^]/g, '\\$&') + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : '';
  }

  function setCookie(name, value, days) {
    document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; max-age=' + (60 * 60 * 24 * days) + '; SameSite=Lax';
  }

  function uuid() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return 'v-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 12);
  }

  function consent() {
    return getCookie(consentName) || localStorage.getItem(consentName) || '';
  }

  function visitorId() {
    var id = getCookie(visitorName) || localStorage.getItem(visitorName);
    if (!id) {
      id = uuid();
      localStorage.setItem(visitorName, id);
      setCookie(visitorName, id, 365);
    }
    return id;
  }

  function allowed() {
    return consent() === 'accepted';
  }

  function track(type, data) {
    if (!allowed()) return;
    var payload = Object.assign({
      event_type: type,
      visitor_id: visitorId(),
      path: location.pathname,
      route: location.pathname.replace((baseUrl().replace(/^https?:\/\/[^/]+/i, '') || ''), '').replace(/^\/+/, ''),
      title: document.title,
      referrer: document.referrer || ''
    }, data || {});
    try {
      navigator.sendBeacon && navigator.sendBeacon(baseUrl() + '/cookie/track', new Blob([JSON.stringify(payload)], {type:'application/json'}));
      if (!navigator.sendBeacon) {
        fetch(baseUrl() + '/cookie/track', {method:'POST', body:JSON.stringify(payload), headers:{'Content-Type':'application/json'}, keepalive:true});
      }
    } catch(e) {}
  }

  function textOf(el) {
    return (el.getAttribute('aria-label') || el.getAttribute('title') || el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 160);
  }

  function showBanner() {
    var banner = document.querySelector('[data-cookie-consent]');
    if (!banner || consent()) return;
    banner.hidden = false;
    var accept = banner.querySelector('[data-cookie-accept]');
    var reject = banner.querySelector('[data-cookie-reject]');
    if (accept) accept.addEventListener('click', function(){
      localStorage.setItem(consentName, 'accepted');
      setCookie(consentName, 'accepted', 365);
      banner.hidden = true;
      track('consent_accepted', {label:'Çerez kabul'});
      setTimeout(function(){ track('page_view'); }, 80);
    });
    if (reject) reject.addEventListener('click', function(){
      localStorage.setItem(consentName, 'essential');
      setCookie(consentName, 'essential', 180);
      banner.hidden = true;
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    showBanner();
    if (allowed()) setTimeout(function(){ track('page_view'); }, 250);
  });

  document.addEventListener('click', function(event){
    var target = event.target.closest('a,button,[role="button"],.site-btn,.ao-btn');
    if (!target || target.closest('[data-cookie-consent]')) return;
    var href = target.getAttribute('href') || '';
    track('click', {
      label: textOf(target),
      target: href || target.getAttribute('data-builder-block') || target.className || target.tagName,
      meta: {
        product: (target.closest('[data-product-card],.ao-product-card,.platform-card') || {}).textContent ? textOf(target.closest('[data-product-card],.ao-product-card,.platform-card')) : '',
        section: (target.closest('[data-builder-block]') || {}).getAttribute ? target.closest('[data-builder-block]').getAttribute('data-builder-block') : ''
      }
    });
  }, true);

  window.aoCookieTrack = track;
})();
