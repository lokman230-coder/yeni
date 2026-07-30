(function(){
  function initMenuManager() {
    // Menu manager page fullwidth layout
    const contentDiv = document.querySelector('.ao-content');
    if(contentDiv && document.getElementById('menuList')) {
      contentDiv.classList.add('menu-manager-page');
    }
    
    // Debug: check if menuList exists
    console.log('[AO-Menu] initMenuManager called, menuList element:', document.getElementById('menuList')?.id, 'hidden element:', document.getElementById('items_json')?.id);
    
    const list=document.getElementById('menuList'), hidden=document.getElementById('items_json');
    if(!list||!hidden) {
      console.error('[AO-Menu] Missing elements - list:', list?.id, 'hidden:', hidden?.id);
      return;
    }
    console.log('[AO-Menu] Elements found!');
    const OPTIONS = window.AO_MENU_LINK_OPTIONS || {types:[], sources:{}};
    let flat=[], dragIndex=null;
    function esc(s){return String(s||'').replace(/[&<>\"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;'}[c]||c));}
    function flatten(items,depth=0){(items||[]).forEach(it=>{const detected=detectType(it.url||'');flat.push({label:it.label||'',url:it.url||'',depth:Math.min(depth,2),link_type:detected.type,source_url:detected.source_url,icon:it.icon||'',target:it.target||'',visibility:it.visibility||'all',language:it.language||''}); if(it.children) flatten(it.children, depth+1);});}
    function detectType(url){
    url=String(url||'');
    if(url==='') return {type:'home',source_url:''};
    if(url==='urunler' || url==='products') return {type:'product_list',source_url:url};
    if(url.indexOf('urun-grubu/')===0 || url.indexOf('product-group/')===0) return {type:'product_group',source_url:url};
    if(url.indexOf('urun/')===0 || url.indexOf('product/')===0) return {type:'product',source_url:url};
    if(url.indexOf('bilgi-bankasi')===0) return {type:'knowledge',source_url:url};
    if(url.indexOf('domain')===0) return {type:'domain',source_url:url};
    if(url.indexOf('marketplace')===0) return {type:'marketplace',source_url:url};
    if(url.indexOf('sitebuilder/preview')===0) return {type:'page',source_url:url};
    return {type:'custom',source_url:''};
  }
  function typeLabel(t){const found=(OPTIONS.types||[]).find(x=>x.value===t); return found?found.label:t;}
  function sourceOptions(type){return (OPTIONS.sources&&OPTIONS.sources[type])?OPTIONS.sources[type]:[];}
  function getSourceByUrl(type,url){return sourceOptions(type).find(x=>x.url===url)||null;}
  flatten(window.AO_MENU_INITIAL||[]); if(!flat.length) flat=[{label:'Ana Sayfa',url:'',depth:0,link_type:'home',source_url:''}];
  function toTree(){const roots=[], stack=[]; flat.forEach(row=>{const node={label:String(row.label||'').trim(),url:String(row.url||'').trim(),children:[]}; ['icon','target','visibility','language'].forEach(k=>{ if(row[k]) node[k]=String(row[k]).trim(); }); if(!node.label)return; const d=Math.max(0,Math.min(2,row.depth||0)); if(d===0||!stack[d-1]){roots.push(node); stack[0]=node;} else {stack[d-1].children.push(node); stack[d]=node;} stack.length=d+1;}); return roots;}
  function sync(){hidden.value=JSON.stringify(toTree());}
  function render(){
    console.log('[AO-Menu] render() called, flat items:', flat.length, 'list element:', list?.id);
    list.innerHTML='';
    flat.forEach((row,i)=>{
      const li=document.createElement('li'); li.className='v222-menu-row v238-menu-row depth-'+(row.depth||0); li.draggable=true;
      const typeOptions=(OPTIONS.types||[]).map(t=>'<option value="'+esc(t.value)+'" '+(row.link_type===t.value?'selected':'')+'>'+esc(t.label)+'</option>').join('');
      const sources=sourceOptions(row.link_type);
      const sourceHtml=sources.length?'<select class="src"><option value="">Seçiniz</option>'+sources.map(s=>'<option value="'+esc(s.url)+'" data-label="'+esc(s.label)+'" '+((row.source_url===s.url||row.url===s.url)?'selected':'')+'>'+esc(s.label)+'</option>').join('')+'</select>':'<select class="src" disabled><option>Bu tip için liste yok</option></select>';
      const readonly = ['product','product_group','product_list','page','domain','marketplace','knowledge','home'].includes(row.link_type||'') ? ' readonly' : '';
      li.innerHTML='<span class="handle" title="Sürükle">☰</span>'+
        '<input class="lbl" placeholder="Menü adı" value="'+esc(row.label)+'">'+
        '<select class="typ" title="Bağlantı Tipi">'+typeOptions+'</select>'+sourceHtml+
        '<input class="url" placeholder="URL / route" value="'+esc(row.url)+'"'+readonly+'>'+
        '<div class="v222-menu-actions"><button type="button" data-act="out">Üst</button><button type="button" data-act="in">Alt</button><button type="button" class="danger" data-act="del">Sil</button></div>';
      const lbl=li.querySelector('.lbl'); if(lbl) lbl.addEventListener('input',e=>{flat[i].label=e.target.value;sync();});
      const url=li.querySelector('.url'); if(url) url.addEventListener('input',e=>{flat[i].url=e.target.value;sync();});
      const ico=li.querySelector('.ico'); if(ico) ico.addEventListener('input',e=>{flat[i].icon=e.target.value;sync();});
      const tgt=li.querySelector('.tgt'); if(tgt) tgt.addEventListener('change',e=>{flat[i].target=e.target.value;sync();});
      const vis=li.querySelector('.vis'); if(vis) vis.addEventListener('change',e=>{flat[i].visibility=e.target.value;sync();});
      const lng=li.querySelector('.lng'); if(lng) lng.addEventListener('input',e=>{flat[i].language=e.target.value;sync();});
      const typ=li.querySelector('.typ'); if(typ) typ.addEventListener('change',e=>{flat[i].link_type=e.target.value;flat[i].source_url='';const opts=sourceOptions(flat[i].link_type); if(opts.length){flat[i].url=opts[0].url; flat[i].source_url=opts[0].url; if(!flat[i].label||flat[i].label==='Yeni Menü') flat[i].label=opts[0].label;} else if(flat[i].link_type==='home'){flat[i].url=''; if(!flat[i].label||flat[i].label==='Yeni Menü') flat[i].label='Ana Sayfa';} else if(flat[i].link_type==='custom'||flat[i].link_type==='external'){flat[i].url=flat[i].url||'#';} render();});
      const src=li.querySelector('.src'); if(src) src.addEventListener('change',e=>{const val=e.target.value; if(!val)return; const opt=e.target.selectedOptions[0]; flat[i].source_url=val; flat[i].url=val; const label=opt?opt.getAttribute('data-label'):''; if(label) flat[i].label=label; render();});
      const act_in=li.querySelector('[data-act="in"]'); if(act_in) act_in.addEventListener('click',()=>{if(i>0){flat[i].depth=Math.min(2,(flat[i].depth||0)+1); render();}});
      const act_out=li.querySelector('[data-act="out"]'); if(act_out) act_out.addEventListener('click',()=>{flat[i].depth=Math.max(0,(flat[i].depth||0)-1); render();});
      const act_del=li.querySelector('[data-act="del"]'); if(act_del) act_del.addEventListener('click',()=>{flat.splice(i,1); render();});
      li.addEventListener('dragstart',()=>{dragIndex=i; li.classList.add('dragging')});
      li.addEventListener('dragend',()=>{li.classList.remove('dragging'); dragIndex=null; sync();});
      li.addEventListener('dragover',e=>{e.preventDefault(); const rect=li.getBoundingClientRect(); const before=e.clientY<rect.top+rect.height/2; const target=i+(before?0:1); if(dragIndex===null||target===dragIndex||target===dragIndex+1)return; const item=flat.splice(dragIndex,1)[0]; let insert=target; if(target>dragIndex) insert--; flat.splice(insert,0,item); dragIndex=insert; render();});
      list.appendChild(li);
    });
    sync();
  }
  window.addMenuItem=function(){flat.push({label:'Yeni Menü',url:'#',depth:0,link_type:'custom',source_url:'',icon:'',target:'',visibility:'all',language:''}); render();};
  window.addPreset=function(){
    const add=(label,url,type)=>flat.push({label,url,depth:0,link_type:type||detectType(url).type,source_url:url,icon:'',target:'',visibility:'all',language:''});
    add('Ürünler','urunler','product_list'); add('Domain Sorgula','domain','domain'); add('Marketplace','marketplace','marketplace'); add('Bilgi Bankası','bilgi-bankasi','knowledge');
    render();
  };
  render(); const form=document.getElementById('menuForm'); if(form)form.addEventListener('submit',sync);
  }
  
  // Init on DOM ready
  if(document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMenuManager);
  } else {
    initMenuManager();
  }
})();

/* Ahost One v24.1.4 - Admin topbar dropdown fix
   Fixes Hızlı Erişim, + Yeni and admin user dropdowns using data-v222-dropdown. */
(function(){
  function closeAll(except){
    document.querySelectorAll('[data-v222-dropdown].open').forEach(function(dd){
      if(dd !== except){
        dd.classList.remove('open');
        var b = dd.querySelector('button[aria-expanded]');
        if(b) b.setAttribute('aria-expanded','false');
      }
    });
  }
  function initTopbarDropdowns(){
    var dropdowns = document.querySelectorAll('[data-v222-dropdown]');
    if(!dropdowns.length) return;
    dropdowns.forEach(function(dd){
      var btn = dd.querySelector('button');
      if(!btn || btn.dataset.v2414Bound === '1') return;
      btn.dataset.v2414Bound = '1';
      if(!btn.hasAttribute('aria-haspopup')) btn.setAttribute('aria-haspopup','true');
      if(!btn.hasAttribute('aria-expanded')) btn.setAttribute('aria-expanded','false');
      btn.addEventListener('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        var willOpen = !dd.classList.contains('open');
        closeAll(dd);
        dd.classList.toggle('open', willOpen);
        btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      });
    });
  }
  document.addEventListener('click', function(e){
    var open = e.target.closest('[data-v222-dropdown].open');
    if(!open) closeAll(null);
  });
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeAll(null);
  });
  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', initTopbarDropdowns);
  } else {
    initTopbarDropdowns();
  }
  window.AhostOneTopbarDropdowns = { init:initTopbarDropdowns, closeAll:closeAll };
})();

/* Ahost One v24.1.5 - mobile shell hardening */
(function(){
  function ready(fn){ if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',fn); else fn(); }
  ready(function(){
    document.addEventListener('click', function(e){
      if(document.body.classList.contains('sidebar-open') && !e.target.closest('.ao-sidebar') && !e.target.closest('.ao-mobile-toggle')){
        document.body.classList.remove('sidebar-open');
      }
    });
    document.addEventListener('keydown', function(e){ if(e.key==='Escape') document.body.classList.remove('sidebar-open'); });
  });
})();


/* v2465-mobile-pointerdown: robust touch support for Hızlı Erişim, Yeni and Admin dropdowns */
(function(){
  function closeAll(except){document.querySelectorAll('[data-v222-dropdown].open').forEach(function(dd){if(dd!==except){dd.classList.remove('open');var b=dd.querySelector('button');if(b)b.setAttribute('aria-expanded','false');}})}
  function bind(){document.querySelectorAll('[data-v222-dropdown]').forEach(function(dd){var btn=dd.querySelector('button'); if(!btn||btn.dataset.v2465Touch==='1')return; btn.dataset.v2465Touch='1'; btn.setAttribute('type','button'); btn.setAttribute('aria-haspopup','true'); btn.setAttribute('aria-expanded','false'); ['pointerdown','touchstart'].forEach(function(ev){btn.addEventListener(ev,function(e){e.preventDefault();e.stopPropagation();var open=!dd.classList.contains('open');closeAll(dd);dd.classList.toggle('open',open);btn.setAttribute('aria-expanded',open?'true':'false');},{passive:false});});});}
  document.addEventListener('DOMContentLoaded',bind); bind();
})();
