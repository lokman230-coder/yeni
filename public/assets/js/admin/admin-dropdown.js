
// v24.7.0 Admin topbar dropdown stability fix.
// Fixes: dropdown opens then closes instantly on mobile/touch.
(function(){
  function dropdowns(){ return Array.prototype.slice.call(document.querySelectorAll('[data-v222-dropdown]')); }
  function closeAll(except){
    dropdowns().forEach(function(dd){
      if(dd!==except){
        dd.classList.remove('is-open');
        var btn=dd.querySelector(':scope > button');
        if(btn) btn.setAttribute('aria-expanded','false');
      }
    });
  }
  function toggle(dd, force){
    if(!dd) return;
    var btn=dd.querySelector(':scope > button');
    var willOpen = typeof force === 'boolean' ? force : !dd.classList.contains('is-open');
    closeAll(dd);
    dd.classList.toggle('is-open', willOpen);
    if(btn) btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
  }
  function boot(){
    dropdowns().forEach(function(dd){
      var btn=dd.querySelector(':scope > button');
      var menu=dd.querySelector('.ao-v222-mega,.ao-v222-menu');
      if(btn){
        btn.setAttribute('aria-haspopup','true');
        btn.setAttribute('aria-expanded', dd.classList.contains('is-open') ? 'true':'false');
        btn.addEventListener('click', function(e){
          e.preventDefault();
          e.stopPropagation();
          toggle(dd);
        });
        btn.addEventListener('pointerdown', function(e){ e.stopPropagation(); });
      }
      if(menu){
        menu.addEventListener('click', function(e){ e.stopPropagation(); });
        menu.addEventListener('pointerdown', function(e){ e.stopPropagation(); });
      }
      dd.addEventListener('mouseenter', function(){
        if(window.matchMedia('(hover:hover)').matches) toggle(dd, true);
      });
      dd.addEventListener('mouseleave', function(){
        if(window.matchMedia('(hover:hover)').matches) toggle(dd, false);
      });
    });
    document.addEventListener('click', function(e){
      if(!e.target.closest('[data-v222-dropdown]')) closeAll();
    });
    document.addEventListener('keydown', function(e){
      if(e.key==='Escape') closeAll();
    });
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
