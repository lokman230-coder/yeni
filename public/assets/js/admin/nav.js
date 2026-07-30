(function(){
  function qs(sel, root){ return Array.prototype.slice.call((root||document).querySelectorAll(sel)); }
  function groups(){ return qs('.ao-sidebar .nav-group'); }
  function groupKey(g){
    var a=g.querySelector(':scope > a');
    return (a && (a.getAttribute('href')||a.textContent||'').trim()) || String(groups().indexOf(g));
  }
  function save(){
    var open=groups().filter(function(g){return g.classList.contains('open');}).map(groupKey);
    try{ localStorage.setItem('ao_admin_open_nav_v780', JSON.stringify(open)); }catch(e){}
  }
  function restore(){
    var open=[];
    try{ open=JSON.parse(localStorage.getItem('ao_admin_open_nav_v780')||'[]'); }catch(e){}
    groups().forEach(function(g){ if(open.indexOf(groupKey(g))!==-1) g.classList.add('open'); });
  }
  function closeAll(except){ groups().forEach(function(g){ if(g!==except) g.classList.remove('open'); }); }
  function boot(){
    restore();
    groups().forEach(function(g){
      var head=g.querySelector(':scope > a');
      if(!head) return;
      head.setAttribute('aria-expanded', g.classList.contains('open')?'true':'false');
      head.addEventListener('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        var willOpen=!g.classList.contains('open');
        closeAll(g);
        g.classList.toggle('open', willOpen);
        head.setAttribute('aria-expanded', willOpen?'true':'false');
        save();
      });
    });
    document.addEventListener('click', function(e){
      var link=e.target.closest('.ao-sidebar .nav-group div a');
      if(link){
        var g=link.closest('.nav-group');
        if(g) g.classList.add('open');
        save();
        if(window.innerWidth<980) document.body.classList.remove('sidebar-open');
      }
    });
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
