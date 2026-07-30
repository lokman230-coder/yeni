document.addEventListener('click', function (event) {
  var mobileToggle = event.target.closest('.ao-admin-mobile-toggle,.ao-mobile-toggle');
  if (mobileToggle) {
    event.preventDefault();
    document.body.classList.toggle('sidebar-open');
    return;
  }

  if (document.body.classList.contains('sidebar-open') && !event.target.closest('.ao-sidebar,.ao-admin-sidebar')) {
    document.body.classList.remove('sidebar-open');
  }

  if (event.target.matches('.ao-sidebar a') && !event.target.closest('.nav-group > a')) {
    document.body.classList.remove('sidebar-open');
  }

  var tabButton = event.target.closest('[data-ao-tabs] .ao-real-tabs button[data-tab]');
  if (tabButton) {
    var shell = tabButton.closest('[data-ao-tabs]');
    shell.querySelectorAll('.ao-real-tabs button').forEach(function (button) { button.classList.remove('active'); });
    shell.querySelectorAll('.ao-tab-panel').forEach(function (panel) { panel.classList.remove('active'); });
    tabButton.classList.add('active');
    var panel = shell.querySelector('#tab-' + tabButton.dataset.tab);
    if (panel) panel.classList.add('active');
  }
});

(function () {
  function closeOtherRegistrars(current) {
    document.querySelectorAll('[data-registrar-item].open').forEach(function (item) {
      if (item !== current) {
        item.classList.remove('open');
        var body = item.querySelector('[data-registrar-body]');
        var button = item.querySelector('[data-registrar-toggle]');
        if (body) body.hidden = true;
        if (button) button.setAttribute('aria-expanded', 'false');
      }
    });
  }

  function updateAuthFields(form) {
    if (!form) return;
    var mode = (form.querySelector('[data-auth-mode]') || {}).value || 'userpass';
    form.querySelectorAll('[data-auth-field]').forEach(function (element) {
      var modes = (element.getAttribute('data-auth-field') || '').split(/\s+/);
      element.classList.toggle('is-hidden', modes.indexOf(mode) === -1);
    });
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-registrar-toggle]');
    if (!button) return;
    event.preventDefault();
    var item = button.closest('[data-registrar-item]');
    var body = item && item.querySelector('[data-registrar-body]');
    if (!item || !body) return;
    var willOpen = !item.classList.contains('open');
    closeOtherRegistrars(item);
    item.classList.toggle('open', willOpen);
    body.hidden = !willOpen;
    button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    if (willOpen) localStorage.setItem('ao_open_registrar_id', item.getAttribute('data-registrar-id') || '');
  });

  document.addEventListener('change', function (event) {
    if (event.target.matches('[data-auth-mode]')) updateAuthFields(event.target.closest('[data-auth-form]'));
  });

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-auth-form]').forEach(updateAuthFields);
    var saved = localStorage.getItem('ao_open_registrar_id');
    if (!saved) return;
    var item = document.querySelector('[data-registrar-item][data-registrar-id="' + CSS.escape(saved) + '"]');
    if (!item) return;
    item.classList.add('open');
    var body = item.querySelector('[data-registrar-body]');
    var button = item.querySelector('[data-registrar-toggle]');
    if (body) body.hidden = false;
    if (button) button.setAttribute('aria-expanded', 'true');
  });
})();

document.addEventListener('keydown', function (event) {
  if (event.key === 'Escape') document.body.classList.remove('sidebar-open');
});

if (typeof window !== 'undefined') window.AHOST_VERSION = '9.6.0';

(function () {
  function ready(fn) {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn, { once: true });
    else fn();
  }

  function charCount(value) {
    return Array.from((value || '').trim()).length;
  }

  function wordCount(value) {
    var clean = (value || '').replace(/<[^>]*>/g, ' ').trim();
    return clean ? clean.split(/\s+/).filter(Boolean).length : 0;
  }

  function fieldLabel(element) {
    var label = element.closest('label');
    if (label) {
      var clone = label.cloneNode(true);
      clone.querySelectorAll('input,textarea,select,small,.ao-seo-advisor-hint').forEach(function (node) { node.remove(); });
      return clone.textContent.trim().replace(/\s+/g, ' ');
    }
    return (element.getAttribute('placeholder') || element.name || 'Alan').trim();
  }

  function ensureHint(element) {
    if (element.dataset.aoSeoAdvisorAttached === '1') {
      return element.nextElementSibling && element.nextElementSibling.classList.contains('ao-seo-advisor-hint') ? element.nextElementSibling : null;
    }
    element.dataset.aoSeoAdvisorAttached = '1';
    var hint = document.createElement('span');
    hint.className = 'ao-seo-advisor-hint';
    hint.setAttribute('aria-live', 'polite');
    hint.innerHTML = '<span></span><b class="ao-seo-advisor-meter"><i></i></b>';
    element.insertAdjacentElement('afterend', hint);
    return hint;
  }

  function evaluate(element, rule) {
    var value = element.value || '';
    var chars = charCount(value);
    var words = wordCount(value);
    var state = 'warn';
    var text = '';

    if (rule.kind === 'title') {
      if (chars === 0) { state = 'warn'; text = 'SEO başlığı boş. Özel başlık girersen arama sonucu daha kontrollü olur.'; }
      else if (chars < rule.min) { state = 'warn'; text = 'Başlık kısa: ' + chars + ' karakter. İdeal ' + rule.min + '-' + rule.max + ' karakter.'; }
      else if (chars > rule.max) { state = 'bad'; text = 'Başlık uzun: ' + chars + ' karakter. Google genelde ' + rule.max + ' karakterden sonrasını keser.'; }
      else { state = 'good'; text = 'Başlık iyi görünüyor: ' + chars + ' karakter.'; }
    } else if (rule.kind === 'description') {
      if (chars === 0) { state = 'warn'; text = 'Meta açıklama boş. 120-160 karakterlik açıklama tıklanma oranını artırır.'; }
      else if (chars < rule.min) { state = 'warn'; text = 'Açıklama kısa: ' + chars + ' karakter, ' + words + ' kelime. İdeal ' + rule.min + '-' + rule.max + ' karakter.'; }
      else if (chars > rule.max) { state = 'bad'; text = 'Açıklama uzun: ' + chars + ' karakter. ' + rule.max + ' karakter üstü arama sonucunda kesilebilir.'; }
      else { state = 'good'; text = 'Açıklama SEO için dengeli: ' + chars + ' karakter, ' + words + ' kelime.'; }
    } else if (rule.kind === 'summary') {
      if (chars === 0) { state = 'warn'; text = 'Kısa açıklama boş. Kart ve SEO özeti için net bir fayda cümlesi ekleyin.'; }
      else if (chars < rule.min) { state = 'warn'; text = 'Kısa açıklama biraz zayıf: ' + chars + ' karakter. İdeal ' + rule.min + '-' + rule.max + ' karakter.'; }
      else if (chars > rule.max) { state = 'bad'; text = 'Kısa açıklama uzun: ' + chars + ' karakter. Kartlarda taşma ve SEO kesilmesi olabilir.'; }
      else { state = 'good'; text = 'Kısa açıklama iyi: ' + chars + ' karakter.'; }
    } else if (rule.kind === 'keywords') {
      var items = value.split(',').map(function (item) { return item.trim(); }).filter(Boolean);
      if (items.length === 0) { state = 'warn'; text = 'Anahtar kelime boş. 3-8 odak kelime yeterlidir.'; }
      else if (items.length > rule.max) { state = 'bad'; text = 'Çok fazla anahtar kelime var: ' + items.length + '. 3-8 odak kelime daha sağlıklı.'; }
      else { state = 'good'; text = 'Anahtar kelime sayısı uygun: ' + items.length + '.'; }
      chars = Math.min(items.length, rule.max);
    } else if (rule.kind === 'slug') {
      var ok = /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(value.trim());
      if (value.trim() === '') { state = 'warn'; text = 'Slug boşsa sistem başlıktan üretebilir.'; }
      else if (!ok) { state = 'bad'; text = 'Slug küçük harf, rakam ve tire ile yazılmalı.'; }
      else if (chars > rule.max) { state = 'warn'; text = 'Slug uzun: ' + chars + ' karakter. Kısa URL daha okunur olur.'; }
      else { state = 'good'; text = 'Slug uygun görünüyor.'; }
    }

    var hint = ensureHint(element);
    if (!hint) return;
    hint.className = 'ao-seo-advisor-hint is-' + state;
    hint.querySelector('span').textContent = fieldLabel(element) + ': ' + text;
    var max = rule.max || 160;
    var percent = Math.max(4, Math.min(100, Math.round((chars / max) * 100)));
    hint.querySelector('i').style.width = percent + '%';
  }

  function attach() {
    var rules = [
      { kind: 'title', min: 35, max: 60, selector: 'input[name="meta_title"],input[name="seo_title"],input[name="settings[seo_title]"],input[name="settings[seo_og_title]"]' },
      { kind: 'description', min: 120, max: 160, selector: 'textarea[name="meta_description"],input[name="meta_description"],textarea[name="settings[seo_description]"],input[name="settings[seo_description]"]' },
      { kind: 'summary', min: 80, max: 220, selector: 'textarea[name="excerpt"],textarea[name="short_description"],textarea[name="summary"]' },
      { kind: 'keywords', min: 3, max: 8, selector: 'input[name="meta_keywords"],input[name="settings[seo_keywords]"]' },
      { kind: 'slug', min: 3, max: 75, selector: 'input[name="slug"]' }
    ];
    rules.forEach(function (rule) {
      document.querySelectorAll(rule.selector).forEach(function (element) {
        if (element.closest('[data-ao-seo-advisor="off"]')) return;
        evaluate(element, rule);
        element.addEventListener('input', function () { evaluate(element, rule); });
        element.addEventListener('change', function () { evaluate(element, rule); });
      });
    });
  }

  ready(attach);
})();
