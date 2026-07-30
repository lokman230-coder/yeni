// Ahost One RC12 — single UI behavior layer
(function(){
  function closest(el, sel){ return el && (el.matches && el.matches(sel) ? el : (el.closest ? el.closest(sel) : null)); }
  document.addEventListener('click', function(e){
    var toggle = closest(e.target, '[data-ao-menu-toggle]');
    if(toggle){
      var menu = document.querySelector('[data-ao-menu]');
      if(menu){ menu.classList.toggle('is-open'); toggle.setAttribute('aria-expanded', menu.classList.contains('is-open') ? 'true' : 'false'); }
      e.preventDefault();
      return;
    }
    if(!closest(e.target, '[data-ao-menu]') && !closest(e.target, '[data-ao-menu-toggle]')){
      document.querySelectorAll('[data-ao-menu].is-open').forEach(function(menu){ menu.classList.remove('is-open'); });
      document.querySelectorAll('[data-ao-menu-toggle][aria-expanded="true"]').forEach(function(btn){ btn.setAttribute('aria-expanded','false'); });
    }
  });
  document.addEventListener('click', function(e){
    var dd = closest(e.target, '[data-dropdown], .ao-dropdown, .dropdown');
    document.querySelectorAll('[data-dropdown].is-open, .ao-dropdown.is-open, .dropdown.is-open').forEach(function(x){ if(x !== dd) x.classList.remove('is-open'); });
    var btn = closest(e.target, '[data-dropdown] > button, .ao-dropdown > button, .dropdown > button');
    if(btn){ btn.parentElement.classList.toggle('is-open'); e.preventDefault(); }
  });
  function activateTab(btn){
    var target = btn.getAttribute('data-tab-target') || btn.getAttribute('data-ao-tab') || btn.getAttribute('href') || btn.dataset.target;
    if(!target) return;
    target = target.replace(/^#/, '');
    var root = btn.closest('[data-ao-tabs], .ao-tabs, .tabs, .tab-nav, .nav-tabs') || document;
    var scope = root.parentElement || document;
    root.querySelectorAll('[data-tab-target], [data-ao-tab], .tab-link, .tab-btn, .nav-link, button, a').forEach(function(b){ b.classList.remove('active'); });
    btn.classList.add('active');
    scope.querySelectorAll('[data-tab-pane], [data-ao-tab-pane], .tab-pane').forEach(function(p){
      var id = p.getAttribute('data-tab-pane') || p.getAttribute('data-ao-tab-pane') || p.id;
      p.classList.toggle('active', id === target);
    });
  }
  document.addEventListener('click', function(e){
    var btn = closest(e.target, '[data-tab-target], [data-ao-tab], .tab-link[href^="#"], .tab-btn[data-target], .nav-tabs .nav-link[href^="#"]');
    if(btn){ activateTab(btn); e.preventDefault(); }
  });
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('[data-ao-tabs], .ao-tabs, .tabs, .tab-nav, .nav-tabs').forEach(function(root){
      var active = root.querySelector('.active[data-tab-target], .active[data-ao-tab], .tab-link.active, .tab-btn.active, .nav-link.active') || root.querySelector('[data-tab-target], [data-ao-tab], .tab-link[href^="#"], .tab-btn[data-target], .nav-link[href^="#"]');
      if(active) activateTab(active);
    });
  });
})();
