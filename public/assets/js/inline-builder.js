(function(){
  const qs=(s,ctx=document)=>ctx.querySelector(s);
  const qsa=(s,ctx=document)=>Array.from(ctx.querySelectorAll(s));
  const cfg=window.AHOST_INLINE_BUILDER||{};
  const state={selected:null,history:[],future:[],drag:null,text:false,area:false,areaStart:null,panelSide:localStorage.getItem('ao_inline_panel_side')||'left',panelMinimized:localStorage.getItem('ao_inline_panel_minimized')==='1'};
  const BUILDER_CHROME_SELECTOR='.ao-floating-edit,.ao-support-widget-pro,.ao-support-modal,.mobile-bottom-nav,.mobile-support-panel,.ao-inline-toolbar,.ao-inline-panel,.ao-campaign-modal';
  const BUILDER_OVERLAY_SELECTOR='.ao-inline-resize-handle,.ao-inline-move-handle,.ao-inline-area-catcher,.ao-inline-area-hint,.ao-inline-area-box';
  const INTERACTIVE_SELECTOR='a,button,input,select,textarea,.site-btn,.ao-btn,.btn,[role="button"]';
  const ACTION_BUTTON_SELECTOR='a,button,[role="button"],.site-btn,.ao-btn,.btn';
  const MAIN_INTERACTIVE_SELECTOR='main a,main button,main input,main select,main textarea,.site-btn,.ao-btn,.btn,[role="button"]';
  const templates={
    hero:{label:'Hero',html:'<section class="ao-inline-new-block ao-inline-hero" data-builder-block="hero"><span>Hero</span><h2>Yeni nesil hosting deneyimi</h2><p>Domain, hosting, SSL ve destek süreçlerini tek ekrandan yönetin.</p><a href="#" class="ao-inline-cta">Başla</a></section>'},
    domain:{label:'Domain Sorgu',html:'<section class="ao-inline-new-block ao-inline-domain" data-builder-block="domain"><h3>Domain Sorgula</h3><p>Alan adınızı yazın, uygunluk ve fiyatı hızlıca görün.</p><div class="ao-inline-fake-input">ornekdomain.com <b>Sorgula</b></div></section>'},
    product:{label:'Ürün / Paket',html:'<article class="ao-inline-new-block ao-inline-product" data-builder-block="product"><h3>Linux Hosting</h3><p>NVMe disk, ücretsiz SSL ve hızlı destek.</p><strong>₺149/ay</strong><a href="#" class="ao-inline-cta">Sepete Ekle</a></article>'},
    pricing:{label:'Fiyat Tablosu',html:'<article class="ao-inline-new-block ao-inline-pricing" data-builder-block="pricing"><h3>Pro Paket</h3><p>Kurumsal web siteleri için optimize paket.</p><strong>₺399/ay</strong><a href="#" class="ao-inline-cta">Satın Al</a></article>'},
    support_widget:{label:'Destek Widget',html:'<article class="ao-inline-new-block ao-inline-support" data-builder-block="support_widget"><h3>Sağ Alt Destek</h3><p>WhatsApp, canlı destek, AI ve ticket butonları.</p><div><span>Telefon</span><span>WhatsApp</span><span>Ticket</span></div></article>'},
    ticket:{label:'Ticket Aç',html:'<article class="ao-inline-new-block ao-inline-ticket" data-builder-block="ticket"><h3>Ticket Aç</h3><p>Destek talebini hızlıca oluşturun.</p><a href="#" class="ao-inline-cta">Ticket Aç</a></article>'},
    text:{label:'Metin',html:'<section class="ao-inline-new-block ao-inline-text" data-builder-block="text"><h3>Yeni Başlık</h3><p>Bu alanı doğrudan sayfa üzerinde düzenleyebilirsiniz.</p></section>'},
    image:{label:'Görsel Alanı',html:'<section class="ao-inline-new-block ao-inline-image-block" data-builder-block="image" data-ao-image-target><h3>Kapak Görseli</h3><p>Panelden görsel URL ekleyin, arka plan ve ölçüleri düzenleyin.</p></section>'},
    login_button:{label:'Giriş Butonu',html:'<p class="ao-inline-new-block ao-inline-button-wrap" data-builder-block="login_button" data-ao-action-type="link" data-ao-action-url="/client/login" data-ao-dropdown-items="Müşteri Girişi|/client/login\\nAdmin Girişi|/admin/login"><a href="/client/login" class="ao-inline-cta" data-ao-action-button>Giriş Yap</a></p>'},
    button:{label:'Buton',html:'<p class="ao-inline-new-block ao-inline-button-wrap" data-builder-block="button" data-ao-action-type="link" data-ao-action-url="#"><a href="#" class="ao-inline-cta" data-ao-action-button>Yeni Buton</a></p>'},
    spacer:{label:'Boşluk',html:'<div class="ao-inline-new-block ao-inline-spacer" data-builder-block="spacer"></div>'}
  };
  function uid(){return 'inline_'+Math.random().toString(36).slice(2,9)}
  function esc(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]))}
  function toast(msg,type='ok'){
    let t=qs('.ao-inline-toast');
    if(!t){t=document.createElement('div');t.className='ao-inline-toast';document.body.appendChild(t);}
    t.textContent=msg;t.dataset.type=type;t.hidden=false;
    clearTimeout(t._timer);t._timer=setTimeout(()=>{t.hidden=true},2600);
  }
  function injectStyle(){
    if(qs('#ao-inline-builder-elementor-style'))return;
    const s=document.createElement('style');
    s.id='ao-inline-builder-elementor-style';
    s.textContent=`
      body.ao-inline-editing{--ao-inline-blue:#2563eb;--ao-inline-border:#bfdbfe}
      body.ao-inline-editing [data-builder-block]{position:relative!important;outline:1.5px dashed rgba(37,99,235,.36)!important;outline-offset:4px!important;cursor:grab!important;transition:outline .16s ease,box-shadow .16s ease,opacity .16s ease}
      body.ao-inline-editing [data-builder-block].ao-inline-selected{outline:2px solid var(--ao-inline-blue)!important;box-shadow:0 0 0 6px rgba(37,99,235,.10)!important;z-index:3}
      body.ao-inline-editing [data-builder-block].ao-inline-drag-over{outline-color:#22c55e!important;box-shadow:0 0 0 7px rgba(34,197,94,.12)!important}
      body.ao-text-editing [data-builder-block],body.ao-text-editing [data-builder-block] *{cursor:text!important}
      .ao-inline-toolbar{position:fixed!important;left:50%!important;bottom:22px!important;transform:translateX(-50%)!important;z-index:99920!important;display:flex!important;gap:7px!important;align-items:center!important;max-width:min(920px,calc(100vw - 28px))!important;overflow:auto!important;background:rgba(15,23,42,.94)!important;color:#fff!important;border:1px solid rgba(255,255,255,.16)!important;border-radius:999px!important;padding:7px!important;box-shadow:0 18px 54px rgba(2,6,23,.28)!important;backdrop-filter:blur(18px)!important;font-family:Inter,Arial,sans-serif!important}
      .ao-inline-toolbar button,.ao-inline-toolbar a{appearance:none!important;border:0!important;border-radius:999px!important;min-height:36px!important;padding:0 13px!important;background:#fff!important;color:#0f172a!important;text-decoration:none!important;font-weight:520!important;font-size:13px!important;line-height:1!important;letter-spacing:0!important;white-space:nowrap!important;cursor:pointer!important;box-shadow:0 1px 0 rgba(15,23,42,.05)!important}
      .ao-inline-toolbar .primary{background:linear-gradient(135deg,#22c55e,#06b6d4);color:#fff}
      .ao-inline-toolbar .danger{background:#fee2e2;color:#991b1b}
      .ao-inline-toolbar .active{background:#dbeafe;color:#1d4ed8}
      .ao-inline-resize-handle,.ao-inline-move-handle{position:fixed;z-index:99930;width:18px;height:18px;border-radius:6px;background:#2563eb;border:3px solid #fff;box-shadow:0 8px 22px rgba(37,99,235,.35)}
      .ao-inline-resize-handle{cursor:nwse-resize}
      .ao-inline-resize-handle:after{content:"";position:absolute;right:2px;bottom:2px;width:7px;height:7px;border-right:2px solid #fff;border-bottom:2px solid #fff}
      .ao-inline-move-handle{cursor:grab;display:grid;place-items:center;color:#fff;font-size:12px;font-weight:620;line-height:1}
      .ao-inline-move-handle:before{content:"↕"}
      .ao-inline-panel{position:fixed!important;left:18px!important;top:92px!important;z-index:99910!important;width:min(372px,calc(100vw - 32px))!important;max-height:calc(100vh - 130px)!important;overflow:auto!important;background:rgba(255,255,255,.98)!important;border:1px solid #dbe7f6!important;border-radius:24px!important;padding:16px!important;box-shadow:0 22px 62px rgba(15,23,42,.14)!important;color:#0f172a!important;font-family:Inter,Arial,sans-serif!important;backdrop-filter:blur(14px)!important}
      .ao-inline-panel.ao-inline-panel--right{left:auto;right:18px}
      .ao-inline-panel.ao-inline-panel--min{width:auto;max-width:220px;min-width:0;max-height:none;overflow:visible;padding:8px;border-radius:999px}
      .ao-inline-panel.ao-inline-panel--min .ao-inline-panel-body{display:none}
      .ao-inline-panel.ao-inline-panel--min h3{margin:0;font-size:13px}
      .ao-inline-panel-tools{display:flex;gap:7px;flex-wrap:wrap;margin:8px 0 12px}
      .ao-inline-panel-tools button{border:1px solid #dbeafe!important;background:#fff!important;color:#1d4ed8!important;border-radius:999px!important;min-height:34px!important;padding:0 12px!important;font-size:12px!important;font-weight:520!important;letter-spacing:0!important;cursor:pointer!important}
      .ao-inline-panel h3{margin:0 0 8px!important;font-size:16px!important;letter-spacing:0!important;font-weight:580!important}
      .ao-inline-panel p{margin:0 0 12px!important;color:#64748b!important;font-size:13px!important;line-height:1.5!important;font-weight:400!important}
      .ao-inline-panel label{display:grid!important;gap:5px!important;margin:10px 0!important;color:#334155!important;font-size:12px!important;font-weight:500!important;letter-spacing:0!important}
      .ao-inline-panel .ao-inline-field-row{display:grid;grid-template-columns:1fr auto;gap:7px;align-items:end}
      .ao-inline-panel .ao-inline-field-row button{border:1px solid #fecaca!important;background:#fff1f2!important;color:#991b1b!important;border-radius:13px!important;min-height:38px!important;padding:0 10px!important;font-size:12px!important;font-weight:520!important;cursor:pointer!important}
      .ao-inline-panel .ao-inline-field-row button[data-ao-upload-image],.ao-inline-panel .ao-inline-field-row button[data-ao-upload-bg-image]{border-color:#bfdbfe;background:#eef4ff;color:#1d4ed8}
      .ao-inline-panel input,.ao-inline-panel textarea,.ao-inline-panel select{width:100%!important;border:1px solid #dbe7f6!important;border-radius:14px!important;min-height:40px!important;padding:8px 11px!important;background:#f8fbff!important;color:#0f172a!important;font-size:13px!important;font-weight:420!important;letter-spacing:0!important;box-shadow:none!important}
      .ao-inline-panel input[type="color"]{width:52px!important;min-width:52px!important;height:42px!important;min-height:42px!important;padding:3px!important;border-radius:14px!important;background:#fff!important;cursor:pointer!important}
      .ao-inline-panel input[type="color"]::-webkit-color-swatch-wrapper{padding:0!important}
      .ao-inline-panel input[type="color"]::-webkit-color-swatch{border:0!important;border-radius:10px!important}
      .ao-inline-panel textarea{min-height:76px}
      .ao-inline-library{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:10px}
      .ao-inline-library button{border:1px solid #dbeafe;background:#f8fbff;color:#0f172a;border-radius:12px;min-height:42px;padding:8px 10px;font-weight:560;cursor:pointer;text-align:left}
      .ao-inline-library button:hover{background:#eef4ff;color:#1d4ed8}
      .ao-inline-selected-tag{display:inline-flex;border-radius:999px;background:#eef4ff;color:#1d4ed8;padding:6px 9px;font-size:12px;font-weight:560}
      .ao-inline-new-block{box-sizing:border-box;margin:16px auto;padding:22px;border:1px solid #dbe7f6;border-radius:18px;background:#fff;box-shadow:0 14px 34px rgba(15,23,42,.07);max-width:1120px}
      .ao-inline-new-block h2,.ao-inline-new-block h3{margin:0 0 8px;color:#0f172a;font-weight:620;letter-spacing:0;line-height:1.12}
      .ao-inline-new-block p{margin:0 0 12px;color:#64748b;font-weight:420;line-height:1.55}
      .ao-inline-new-block strong{display:block;margin:8px 0;font-size:22px;color:#0f172a}
      .ao-inline-cta{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:9px 14px;border-radius:12px;background:#2563eb;color:#fff!important;text-decoration:none!important;font-weight:560}
      .ao-inline-fake-input{display:flex;justify-content:space-between;gap:10px;border:1px solid #dbeafe;border-radius:14px;background:#f8fbff;padding:12px;color:#64748b}.ao-inline-fake-input b{color:#1d4ed8}
      .ao-inline-support div{display:flex;gap:8px;flex-wrap:wrap}.ao-inline-support span{border-radius:999px;background:#eef4ff;color:#1d4ed8;padding:7px 10px;font-size:12px;font-weight:560}
      .ao-inline-spacer{min-height:72px;background:repeating-linear-gradient(45deg,#f8fbff,#f8fbff 10px,#eef4ff 10px,#eef4ff 20px)}
      .ao-inline-image-block{min-height:240px;background:linear-gradient(135deg,#fff7ed,#f8fbff);background-size:cover;background-position:center;display:grid;align-content:end}
      .ao-inline-action-preview{display:grid;gap:6px;margin-top:6px;border:1px solid #dbeafe;border-radius:12px;background:#f8fbff;padding:8px}
      .ao-inline-action-preview a{color:#1d4ed8;text-decoration:none;font-size:12px;font-weight:560}
      .ao-inline-toast{position:fixed;right:18px;bottom:96px;z-index:99940;max-width:min(360px,calc(100vw - 32px));border-radius:16px;padding:12px 14px;background:#0f172a;color:#fff;font-weight:560;box-shadow:0 20px 60px rgba(2,6,23,.28)}
      .ao-inline-toast[data-type="error"]{background:#991b1b}
      .ao-inline-area-selecting *{cursor:crosshair!important}
      body.ao-inline-area-selecting .ao-support-widget-pro,body.ao-inline-area-selecting .ao-floating-edit,body.ao-inline-area-selecting .mobile-bottom-nav{z-index:99960!important}
      .ao-inline-area-catcher{position:fixed;inset:0;z-index:99905;background:rgba(15,23,42,.035);cursor:crosshair}
      .ao-inline-area-hint{position:fixed;left:50%;top:18px;transform:translateX(-50%);z-index:99936;background:#0f172a;color:#fff;border-radius:999px;padding:10px 14px;font-size:13px;font-weight:560;box-shadow:0 18px 50px rgba(2,6,23,.25);pointer-events:none}
      .ao-inline-area-box{position:fixed;z-index:99935;pointer-events:none;border:2px solid #2563eb;background:rgba(37,99,235,.12);box-shadow:0 0 0 9999px rgba(15,23,42,.08);border-radius:8px}
      @media(max-width:760px){.ao-inline-toolbar{left:12px;right:12px;bottom:86px;transform:none;border-radius:20px}.ao-inline-panel{display:none}.ao-inline-library{grid-template-columns:1fr}}
      @media print{.ao-inline-toolbar,.ao-inline-panel,.ao-inline-toast{display:none!important}}
    `;
    document.head.appendChild(s);
  }
  function addBlockMarkers(){
    const selectors=[
      '[data-builder-block]','header','footer','.ao-public-header','.ao-unified-header','.ahost-site-footer','.site-footer',
      '.ao-site-topbar','.ao-prism-topbar-island','.ao-prism-topbar-inner','.ao-prism-topbar-left','.ao-prism-topbar-right',
      '.ao-public-header__inner','.ao-public-nav','.ao-public-nav-item','.ao-public-submenu','.ao-public-actions',
      '.site-hero','.ao-hero','.e-site-hero','.platform-hero','.platform-visual','.u2-card','.customer-panel-card','.e-card',
      '.ao-product-group-pills','.ao-product-group-select','[data-product-card]','[data-product-field]',
      'header nav','header a','header button','header li','header span',
      '.ao-site-topbar a','.ao-site-topbar button','.ao-site-topbar span','.ao-site-topbar strong',
      'footer nav','footer a','footer button','footer h4','footer p','footer span',
      'main h1','main h2','main h3','main h4','main p','main li','main strong','main small','main span',
      '.ref-card','.ref-cover','.ref-body','.ref-logo','.ref-site-url','.ref-tech','.ref-visit-btn',
      '.ao-product-image','.ao-product-hero-media','.product-card','.price-card','.feature-card','.ao-card','.stat-card','.ao-section','.platform-grid','.hero-actions',
      '.platform-card','.premium-card','.dashboard-card','.client-card','.customer-card','.content-card','.module-card','.info-card',
      '.site-btn','.ao-btn','.btn','main a','main label','main select','main input','main textarea','main button','main > section','main > article'
    ].join(',');
    qsa(selectors).forEach((el,i)=>{
      if(el.closest(BUILDER_CHROME_SELECTOR))return;
      if(el.closest('.ao-inline-panel,.ao-inline-toolbar'))return;
      if(!el.hasAttribute('data-builder-block')){
        const tag=el.tagName.toLowerCase();
        const kind=tag==='header'||el.matches('.ao-public-header,.ao-unified-header')?'header':(tag==='footer'||el.matches('.ahost-site-footer,.site-footer')?'footer':'block');
        el.setAttribute('data-builder-block',kind+'-'+(i+1));
      }
      if(!el.dataset.aoInlineWidth)el.dataset.aoInlineWidth='100';
      el.setAttribute('draggable','true');
      el.setAttribute('contenteditable','false');
    });
  }
  function editingRoot(){
    return document.body;
  }
  function snapshot(){
    const root=editingRoot();
    const clone=root.cloneNode(true);
    qsa('.ao-inline-toolbar,.ao-inline-panel,.ao-inline-toast',clone).forEach(el=>el.remove());
    state.history.push(clone.innerHTML);
    if(state.history.length>50)state.history.shift();
    state.future=[];
  }
  function pathOf(el){
    if(!el||!el.parentElement)return '';
    const parts=[];
    while(el&&el!==document.body){
      const p=el.parentElement;
      parts.unshift(Array.prototype.indexOf.call(p.children,el));
      el=p;
    }
    return parts.join('.');
  }
  function blockName(el){
    if(!el)return 'Blok seçilmedi';
    const key=el.getAttribute('data-builder-block')||'blok';
    const h=qs('h1,h2,h3,h4,strong',el);
    const own=ownTextValue(el);
    return (h&&h.textContent.trim()?h.textContent.trim():(own||key)).slice(0,80);
  }
  function ownTextNode(el){
    if(!el)return null;
    return Array.from(el.childNodes).find(n=>n.nodeType===3 && n.textContent.trim()!=='')||null;
  }
  function ownTextValue(el){
    const node=ownTextNode(el);
    return node ? node.textContent.trim() : '';
  }
  function setOwnText(el,value){
    if(!el)return;
    const next=String(value||'').trim();
    let node=ownTextNode(el);
    if(!next){
      if(node)node.remove();
      return;
    }
    if(!node){
      node=document.createTextNode('');
      el.insertBefore(node,el.firstChild||null);
    }
    node.textContent=next+' ';
  }
  function cssUrlValue(value){
    const raw=String(value||'').trim();
    if(!raw||raw==='none')return '';
    const match=raw.match(/url\((['"]?)(.*?)\1\)/i);
    return match?match[2]:raw;
  }
  function hexValue(value,fallback='#ffffff'){
    const raw=String(value||'').trim();
    const short=raw.match(/^#([0-9a-f]{3})$/i);
    if(short)return '#'+short[1].split('').map(ch=>ch+ch).join('');
    const full=raw.match(/^#([0-9a-f]{6})$/i);
    if(full)return full[0];
    const rgb=raw.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
    if(rgb)return '#'+[rgb[1],rgb[2],rgb[3]].map(v=>Math.max(0,Math.min(255,Number(v)||0)).toString(16).padStart(2,'0')).join('');
    return fallback;
  }
  function assetUrl(value){
    const raw=String(value||'').trim();
    if(!raw)return '';
    if(/^(https?:|data:|blob:|\/)/i.test(raw))return raw;
    const meta=document.querySelector('meta[name="ahost-base-url"]');
    const base=((cfg.baseUrl||(meta&&meta.getAttribute('content'))||'').replace(/\/+$/,''));
    return (base+'/'+raw.replace(/^\/+/,'')).trim();
  }
  function setStyle(el,prop,value,important=false){
    if(!el)return;
    const next=String(value||'').trim();
    if(next)el.style.setProperty(prop,next,important?'important':'');
    else el.style.removeProperty(prop);
  }
  function imageTarget(el){
    if(!el)return null;
    if(el.matches('img,.platform-visual,.ao-product-image,.ao-product-hero-media,[data-ao-image-target]'))return el;
    return qs('[data-ao-image-target],.platform-visual,.ao-product-image,.ao-product-hero-media,img,picture img',el)||el;
  }
  function imageValue(el){
    const target=imageTarget(el);
    if(!target)return '';
    if(target.tagName&&target.tagName.toLowerCase()==='img')return target.getAttribute('src')||'';
    return cssUrlValue(target.style.backgroundImage||getComputedStyle(target).backgroundImage||'');
  }
  function selectedBackgroundValue(el){
    if(!el)return '';
    return cssUrlValue(el.style.backgroundImage||'');
  }
  function setTargetImage(target,value,fit='cover',position='center'){
    if(!target)return;
    const next=String(value||'').trim();
    const resolved=assetUrl(next);
    const isImg=target.tagName&&target.tagName.toLowerCase()==='img';
    if(isImg){
      if(next)target.setAttribute('src',resolved); else target.removeAttribute('src');
      target.style.width='100%';
      target.style.height='100%';
      target.style.maxWidth='100%';
      target.style.objectFit=fit||'cover';
      target.style.objectPosition=position||'center';
      target.style.display='block';
      return;
    }
    setStyle(target,'background-image',next?'url("'+resolved.replace(/"/g,'&quot;')+'")':'',true);
    setStyle(target,'background-size',fit||'cover',true);
    setStyle(target,'background-position',position||'center',true);
    setStyle(target,'background-repeat','no-repeat',true);
  }
  function select(el){
    qsa('[data-builder-block].ao-inline-selected').forEach(x=>x.classList.remove('ao-inline-selected'));
    state.selected=el||null;
    if(el)el.classList.add('ao-inline-selected');
    renderPanel();
    renderResizeHandle();
  }
  function selected(){return state.selected&&document.body.contains(state.selected)?state.selected:null}
  function bindDocumentSelection(){
    if(state.documentSelectionBound)return;
    state.documentSelectionBound=true;
    document.addEventListener('click',ev=>{
      if(!document.body.classList.contains('ao-inline-editing'))return;
      if(ev.target.closest(BUILDER_CHROME_SELECTOR))return;
      if(ev.target.closest(BUILDER_OVERLAY_SELECTOR))return;
      let target=ev.target.closest('[data-builder-block]');
      if(!target){
        addBlockMarkers();
        target=ev.target.closest('[data-builder-block]');
      }
      if(!target || target.closest(BUILDER_CHROME_SELECTOR))return;
      ev.preventDefault();
      ev.stopPropagation();
      if(typeof ev.stopImmediatePropagation==='function')ev.stopImmediatePropagation();
      select(target);
    },true);
  }
  function enable(){
    injectStyle();
    document.body.classList.add('ao-inline-editing');
    addBlockMarkers();
    if(!qs('.ao-inline-toolbar'))renderToolbar();
    if(!qs('.ao-inline-panel'))renderPanel();
    bindDocumentSelection();
    bindBlocks();
    toast('Sayfa üzeri Builder açıldı.');
  }
  function disable(){
    document.body.classList.remove('ao-inline-editing','ao-text-editing');
    qsa('[data-builder-block]').forEach(el=>{el.setAttribute('contenteditable','false');el.classList.remove('ao-inline-selected','ao-inline-drag-over')});
    qsa('.ao-inline-toolbar,.ao-inline-panel,'+BUILDER_OVERLAY_SELECTOR).forEach(el=>el.remove());
    state.selected=null;state.text=false;
  }
  function renderToolbar(){
    const toolbar=document.createElement('div');
    toolbar.className='ao-inline-toolbar';
    toolbar.innerHTML=[
      '<button class="primary" type="button" data-ao-save>Kaydet</button>',
      '<button type="button" data-ao-add>+ Blok</button>',
      '<button type="button" data-ao-area>Alan Seç</button>',
      '<button type="button" data-ao-up>Yukarı</button>',
      '<button type="button" data-ao-down>Aşağı</button>',
      '<button type="button" data-ao-narrow>Daralt</button>',
      '<button type="button" data-ao-wide>Genişlet</button>',
      '<button type="button" data-ao-copy>Kopyala</button>',
      '<button class="danger" type="button" data-ao-delete>Sil</button>',
      '<button type="button" data-ao-text>Yazı</button>',
      '<button type="button" data-ao-undo>Geri Al</button>',
      '<button type="button" data-ao-reset>Kapat</button>',
      '<a href="#" data-ao-admin>Gelişmiş</a>'
    ].join('');
    document.body.appendChild(toolbar);
    toolbar.addEventListener('click',handleToolbar);
  }
  function renderPanel(){
    let panel=qs('.ao-inline-panel');
    if(!panel){panel=document.createElement('div');panel.className='ao-inline-panel';document.body.appendChild(panel);}
    panel.classList.toggle('ao-inline-panel--right',state.panelSide==='right');
    panel.classList.toggle('ao-inline-panel--min',state.panelMinimized);
    const el=selected();
    const width=el?Number(el.dataset.aoInlineWidth||100):100;
    const ownText=el?ownTextValue(el):'';
    const title=el?(qs('h1,h2,h3,h4,strong',el)?.textContent||''):'';
    const text=el?(qs('p',el)?.textContent||''):'';
    const actionBtn=el?qs('[data-ao-action-button],a,button',el):null;
    const directButton=el&&el.matches(ACTION_BUTTON_SELECTOR)?el:actionBtn;
    const buttonText=directButton?(directButton.textContent||'').trim():'';
    const actionType=el?.dataset.aoActionType||'link';
    const actionUrl=el?.dataset.aoActionUrl||directButton?.getAttribute('href')||'#';
    const dropdownItems=el?.dataset.aoDropdownItems||'Müşteri Girişi|/client/login\nAdmin Girişi|/admin/login';
    const styleVal=(prop)=>el?(el.style[prop]||''):'';
    const computedVal=(node,prop)=>node?(node.style[prop]||getComputedStyle(node)[prop]||''):'';
    const pad=styleVal('padding');
    const margin=styleVal('margin');
    const marginLeft=styleVal('marginLeft');
    const exactWidth=styleVal('width')||(el?Math.round(el.getBoundingClientRect().width)+'px':'');
    const exactHeight=styleVal('height')||'';
    const minWidth=styleVal('minWidth');
    const maxHeight=styleVal('maxHeight');
    const align=styleVal('textAlign');
    const justify=styleVal('justifyContent');
    const left=styleVal('left');
    const top=styleVal('top');
    const bg=styleVal('background')||styleVal('backgroundColor');
    const color=styleVal('color');
    const fontSize=styleVal('fontSize');
    const fontFamily=styleVal('fontFamily');
    const fontWeight=styleVal('fontWeight');
    const lineHeight=styleVal('lineHeight');
    const letterSpacing=styleVal('letterSpacing');
    const radius=styleVal('borderRadius');
    const border=styleVal('border');
    const borderColor=styleVal('borderColor')||hexValue(border,'#dbe7f6');
    const shadow=styleVal('boxShadow');
    const minHeight=styleVal('minHeight');
    const maxWidth=styleVal('maxWidth');
    const display=styleVal('display');
    const opacity=styleVal('opacity');
    const customClass=el?(el.dataset.aoCustomClass||''):'';
    const customId=el?(el.id||''):'';
    const coverImage=el?imageValue(el):'';
    const bgImage=el?selectedBackgroundValue(el):'';
    const imageTargetEl=imageTarget(el);
    const titleEl=el?qs('h1,h2,h3,h4,strong',el):null;
    const textEl=el?qs('p',el):null;
    const titleFontSize=computedVal(titleEl,'fontSize');
    const titleLineHeight=computedVal(titleEl,'lineHeight');
    const textFontSize=computedVal(textEl,'fontSize');
    const textLineHeight=computedVal(textEl,'lineHeight');
    const imageFit=imageTargetEl?(imageTargetEl.style.backgroundSize||'cover'):'cover';
    const imagePosition=imageTargetEl?(imageTargetEl.style.backgroundPosition||'center'):'center';
    const btnBg=actionBtn?(actionBtn.style.background||actionBtn.style.backgroundColor||''):'';
    const btnColor=actionBtn?(actionBtn.style.color||''):'';
    const btnRadius=actionBtn?(actionBtn.style.borderRadius||''):'';
    const btnWidth=actionBtn?(actionBtn.style.width||''):'';
    const btnHeight=actionBtn?(actionBtn.style.height||actionBtn.style.minHeight||''):'';
    const btnPadding=actionBtn?(actionBtn.style.padding||''):'';
    const btnFontSize=actionBtn?(actionBtn.style.fontSize||getComputedStyle(actionBtn).fontSize||''):'';
    panel.innerHTML=`
      <h3>Elementor Hızlı Builder</h3>
      <div class="ao-inline-panel-tools"><button type="button" data-ao-min-panel>${state.panelMinimized?'Aç':'Simge Durumu'}</button><button type="button" data-ao-area-panel>Alan Seç</button><button type="button" data-ao-dock-panel="left">Panel Sol</button><button type="button" data-ao-dock-panel="right">Panel Sağ</button></div>
      <div class="ao-inline-panel-body">
      <p>Blok seç, ekle, sil, sürükle, taşı, daralt/genişlet ve yazıları sayfa üzerinde düzenle.</p>
      <span class="ao-inline-selected-tag">${esc(blockName(el))}</span>
      <div class="ao-inline-field-row"><label>Alan Yazısı<input data-ao-i-own-text value="${esc(ownText)}" placeholder="Kategori gibi seçili alan yazısı" ${el?'':'disabled'}></label><button type="button" data-ao-clear-own-text ${el?'':'disabled'}>Sil</button></div>
      <label>Başlık<input data-ao-i-title value="${esc(title)}" ${el?'':'disabled'}></label>
      <label>Açıklama<textarea data-ao-i-text ${el?'':'disabled'}>${esc(text)}</textarea></label>
      <label>Genişlik<select data-ao-i-width ${el?'':'disabled'}>${[100,75,66,50,33,25].map(v=>`<option value="${v}" ${width===v?'selected':''}>${v}%</option>`).join('')}</select></label>
      <label>Net Genişlik<input data-ao-i-exact-width value="${esc(exactWidth)}" placeholder="360px / 50% / auto" ${el?'':'disabled'}></label>
      <label>Net Yükseklik<input data-ao-i-height value="${esc(exactHeight)}" placeholder="240px / auto / boş" ${el?'':'disabled'}></label>
      <label>Minimum Genişlik<input data-ao-i-min-width value="${esc(minWidth)}" placeholder="220px / boş" ${el?'':'disabled'}></label>
      <label>Maksimum Genişlik<input data-ao-i-max-width value="${esc(maxWidth)}" placeholder="1120px / 80% / boş" ${el?'':'disabled'}></label>
      <label>Minimum Yükseklik<input data-ao-i-min-height value="${esc(minHeight)}" placeholder="120px / 40vh / boş" ${el?'':'disabled'}></label>
      <label>Maksimum Yükseklik<input data-ao-i-max-height value="${esc(maxHeight)}" placeholder="640px / boş" ${el?'':'disabled'}></label>
      <label>İç Boşluk<input data-ao-i-padding value="${esc(pad)}" placeholder="24px veya 24px 40px" ${el?'':'disabled'}></label>
      <label>Dış Boşluk<input data-ao-i-margin value="${esc(margin)}" placeholder="0 auto / 24px 0" ${el?'':'disabled'}></label>
      <label>Sol Boşluk<input data-ao-i-margin-left value="${esc(marginLeft)}" placeholder="0px / 24px / auto" ${el?'':'disabled'}></label>
      <label>Hizalama<select data-ao-i-align ${el?'':'disabled'}><option value="" ${align===''?'selected':''}>Tema varsayılanı</option><option value="left" ${align==='left'?'selected':''}>Sol</option><option value="center" ${align==='center'?'selected':''}>Orta</option><option value="right" ${align==='right'?'selected':''}>Sağ</option></select></label>
      <label>İç Yerleşim<select data-ao-i-justify ${el?'':'disabled'}><option value="" ${justify===''?'selected':''}>Tema varsayılanı</option><option value="flex-start" ${justify==='flex-start'?'selected':''}>Sola</option><option value="center" ${justify==='center'?'selected':''}>Ortala</option><option value="flex-end" ${justify==='flex-end'?'selected':''}>Sağa</option><option value="space-between" ${justify==='space-between'?'selected':''}>Aralıklı</option></select></label>
      <div class="ao-inline-panel-tools"><button type="button" data-ao-center-block ${el?'':'disabled'}>Alanı Ortala</button><button type="button" data-ao-center-content ${el?'':'disabled'}>İçeriği Ortala</button><button type="button" data-ao-free-move ${el?'':'disabled'}>Sürükle Taşı</button></div>
      <label>X Kaydır<input data-ao-i-left value="${esc(left)}" placeholder="0px / 24px / -10px" ${el?'':'disabled'}></label>
      <label>Y Kaydır<input data-ao-i-top value="${esc(top)}" placeholder="0px / 24px / -10px" ${el?'':'disabled'}></label>
      <div class="ao-inline-field-row"><label>Arka Plan<input data-ao-i-bg value="${esc(bg)}" placeholder="#fff / linear-gradient(...) / url(...)" ${el?'':'disabled'}></label><input type="color" data-ao-color-for="data-ao-i-bg" value="${esc(hexValue(bg))}" ${el?'':'disabled'}></div>
      <div class="ao-inline-field-row"><label>Kapak / Görsel URL<input data-ao-i-image value="${esc(coverImage)}" placeholder="public/uploads/gorsel.webp veya https://..." ${el?'':'disabled'}></label><button type="button" data-ao-upload-image ${el?'':'disabled'}>Yükle</button><button type="button" data-ao-clear-image ${el?'':'disabled'}>Sil</button></div>
      <div class="ao-inline-field-row"><label>Arka Plan Görseli URL<input data-ao-i-bg-image value="${esc(bgImage)}" placeholder="public/uploads/arkaplan.webp veya https://..." ${el?'':'disabled'}></label><button type="button" data-ao-upload-bg-image ${el?'':'disabled'}>Yükle</button></div>
      <label>Görsel Ölçeği<select data-ao-i-image-fit ${el?'':'disabled'}><option value="cover" ${imageFit==='cover'?'selected':''}>Kapla</option><option value="contain" ${imageFit==='contain'?'selected':''}>Sığdır</option><option value="auto" ${imageFit==='auto'?'selected':''}>Orijinal</option></select></label>
      <label>Görsel Konumu<input data-ao-i-image-position value="${esc(imagePosition)}" placeholder="center / top center / 50% 20%" ${el?'':'disabled'}></label>
      <div class="ao-inline-field-row"><label>Yazı Rengi<input data-ao-i-color value="${esc(color)}" placeholder="#0f172a" ${el?'':'disabled'}></label><input type="color" data-ao-color-for="data-ao-i-color" value="${esc(hexValue(color,'#0f172a'))}" ${el?'':'disabled'}></div>
      <label>Genel Font Boyutu<input data-ao-i-font-size value="${esc(fontSize)}" placeholder="16px / 1rem / boş" ${el?'':'disabled'}></label>
      <label>Yazı Tipi<select data-ao-i-font-family-select ${el?'':'disabled'}>
        <option value="" ${fontFamily===''?'selected':''}>Tema varsayılanı</option>
        <option value="Inter, Arial, sans-serif" ${fontFamily.includes('Inter')?'selected':''}>Inter</option>
        <option value="Arial, sans-serif" ${fontFamily.includes('Arial')&&!fontFamily.includes('Inter')?'selected':''}>Arial</option>
        <option value="Poppins, Arial, sans-serif" ${fontFamily.includes('Poppins')?'selected':''}>Poppins</option>
        <option value="Montserrat, Arial, sans-serif" ${fontFamily.includes('Montserrat')?'selected':''}>Montserrat</option>
        <option value="Roboto, Arial, sans-serif" ${fontFamily.includes('Roboto')?'selected':''}>Roboto</option>
        <option value="Georgia, serif" ${fontFamily.includes('Georgia')?'selected':''}>Georgia</option>
      </select></label>
      <label>Yazı Tipi Kodu<input data-ao-i-font-family value="${esc(fontFamily)}" placeholder="Inter, Arial, sans-serif" ${el?'':'disabled'}></label>
      <label>Başlık Font Boyutu<input data-ao-i-title-font-size value="${esc(titleFontSize)}" placeholder="32px" ${titleEl?'':'disabled'}></label>
      <label>Başlık Satır Yüksekliği<input data-ao-i-title-line-height value="${esc(titleLineHeight)}" placeholder="1.15 / 42px" ${titleEl?'':'disabled'}></label>
      <label>Açıklama Font Boyutu<input data-ao-i-text-font-size value="${esc(textFontSize)}" placeholder="16px" ${textEl?'':'disabled'}></label>
      <label>Açıklama Satır Yüksekliği<input data-ao-i-text-line-height value="${esc(textLineHeight)}" placeholder="1.5 / 24px" ${textEl?'':'disabled'}></label>
      <label>Font Kalınlığı<select data-ao-i-font-weight ${el?'':'disabled'}><option value="" ${fontWeight===''?'selected':''}>Tema varsayılanı</option><option value="400" ${fontWeight==='400'?'selected':''}>İnce 400</option><option value="500" ${fontWeight==='500'?'selected':''}>Zarif 500</option><option value="600" ${fontWeight==='600'?'selected':''}>Orta 600</option><option value="700" ${fontWeight==='700'?'selected':''}>Vurgulu 700</option><option value="800" ${fontWeight==='800'?'selected':''}>Kalın 800</option></select></label>
      <label>Satır Yüksekliği<input data-ao-i-line-height value="${esc(lineHeight)}" placeholder="1.4 / 24px" ${el?'':'disabled'}></label>
      <label>Harf Aralığı<input data-ao-i-letter-spacing value="${esc(letterSpacing)}" placeholder="0px / 0.02em" ${el?'':'disabled'}></label>
      <label>Köşe Oval<input data-ao-i-radius value="${esc(radius)}" placeholder="18px / 999px" ${el?'':'disabled'}></label>
      <label>Kenar Çizgisi<input data-ao-i-border value="${esc(border)}" placeholder="1px solid #e2e8f0" ${el?'':'disabled'}></label>
      <div class="ao-inline-field-row"><label>Kenar Rengi<input data-ao-i-border-color value="${esc(borderColor)}" placeholder="#e2e8f0" ${el?'':'disabled'}></label><input type="color" data-ao-color-for="data-ao-i-border-color" value="${esc(hexValue(borderColor,'#dbe7f6'))}" ${el?'':'disabled'}></div>
      <label>Gölge<input data-ao-i-shadow value="${esc(shadow)}" placeholder="0 18px 50px rgba(...)" ${el?'':'disabled'}></label>
      <label>Görünüm<select data-ao-i-display ${el?'':'disabled'}><option value="" ${display===''?'selected':''}>Tema varsayılanı</option><option value="block" ${display==='block'?'selected':''}>Blok</option><option value="grid" ${display==='grid'?'selected':''}>Grid</option><option value="flex" ${display==='flex'?'selected':''}>Flex</option><option value="inline-flex" ${display==='inline-flex'?'selected':''}>Inline Flex</option><option value="none" ${display==='none'?'selected':''}>Gizle</option></select></label>
      <label>Şeffaflık<input data-ao-i-opacity value="${esc(opacity)}" placeholder="1 / 0.85 / boş" ${el?'':'disabled'}></label>
      <label>Özel Class<input data-ao-i-class value="${esc(customClass)}" placeholder="premium-center-block" ${el?'':'disabled'}></label>
      <label>Özel ID<input data-ao-i-id value="${esc(customId)}" placeholder="kategori-alani" ${el?'':'disabled'}></label>
      <label>Buton Davranışı<select data-ao-i-action ${directButton?'':'disabled'}><option value="link" ${actionType==='link'?'selected':''}>Linke git</option><option value="dropdown" ${actionType==='dropdown'?'selected':''}>Dropdown aç</option><option value="modal" ${actionType==='modal'?'selected':''}>Modal aç</option></select></label>
      <label>Buton Yazısı<input data-ao-i-button-text value="${esc(buttonText)}" ${directButton?'':'disabled'}></label>
      <label>Buton URL / Modal ID<input data-ao-i-url value="${esc(actionUrl)}" ${directButton?'':'disabled'}></label>
      <label>Dropdown Öğeleri<textarea data-ao-i-dropdown ${directButton?'':'disabled'} placeholder="Etiket|/url">${esc(dropdownItems)}</textarea></label>
      <div class="ao-inline-field-row"><label>Buton Arka Plan<input data-ao-i-btn-bg value="${esc(btnBg)}" placeholder="#2563eb / linear-gradient(...)" ${directButton?'':'disabled'}></label><input type="color" data-ao-color-for="data-ao-i-btn-bg" value="${esc(hexValue(btnBg,'#2563eb'))}" ${directButton?'':'disabled'}></div>
      <div class="ao-inline-field-row"><label>Buton Yazı Rengi<input data-ao-i-btn-color value="${esc(btnColor)}" placeholder="#fff" ${directButton?'':'disabled'}></label><input type="color" data-ao-color-for="data-ao-i-btn-color" value="${esc(hexValue(btnColor))}" ${directButton?'':'disabled'}></div>
      <label>Buton Köşe Oval<input data-ao-i-btn-radius value="${esc(btnRadius)}" placeholder="14px / 999px" ${directButton?'':'disabled'}></label>
      <label>Buton Genişlik<input data-ao-i-btn-width value="${esc(btnWidth)}" placeholder="auto / 180px / 100%" ${directButton?'':'disabled'}></label>
      <label>Buton Yükseklik<input data-ao-i-btn-height value="${esc(btnHeight)}" placeholder="48px" ${directButton?'':'disabled'}></label>
      <label>Buton İç Boşluk<input data-ao-i-btn-padding value="${esc(btnPadding)}" placeholder="12px 20px" ${directButton?'':'disabled'}></label>
      <label>Buton Font Boyutu<input data-ao-i-btn-font-size value="${esc(btnFontSize)}" placeholder="15px" ${directButton?'':'disabled'}></label>
      <div class="ao-inline-library">${Object.entries(templates).map(([k,t])=>`<button type="button" data-ao-pick="${k}">+ ${esc(t.label)}</button>`).join('')}</div>
      </div>
    `;
    panel.querySelector('[data-ao-i-own-text]')?.addEventListener('input',e=>{setOwnText(selected(),e.target.value);});
    panel.querySelector('[data-ao-clear-own-text]')?.addEventListener('click',()=>{const input=panel.querySelector('[data-ao-i-own-text]'); if(input)input.value=''; setOwnText(selected(),''); renderPanel();});
    panel.querySelector('[data-ao-i-title]')?.addEventListener('input',e=>{const h=selected()&&qs('h1,h2,h3,h4,strong',selected()); if(h)h.textContent=e.target.value;});
    panel.querySelector('[data-ao-i-text]')?.addEventListener('input',e=>{const p=selected()&&qs('p',selected()); if(p)p.textContent=e.target.value;});
    panel.querySelector('[data-ao-i-width]')?.addEventListener('change',e=>setWidth(Number(e.target.value)));
    panel.querySelectorAll('[data-ao-i-padding],[data-ao-i-margin],[data-ao-i-margin-left],[data-ao-i-left],[data-ao-i-top],[data-ao-i-bg],[data-ao-i-image],[data-ao-i-bg-image],[data-ao-i-image-position],[data-ao-i-color],[data-ao-i-font-size],[data-ao-i-font-family],[data-ao-i-title-font-size],[data-ao-i-title-line-height],[data-ao-i-text-font-size],[data-ao-i-text-line-height],[data-ao-i-line-height],[data-ao-i-letter-spacing],[data-ao-i-radius],[data-ao-i-border],[data-ao-i-border-color],[data-ao-i-shadow],[data-ao-i-exact-width],[data-ao-i-height],[data-ao-i-min-width],[data-ao-i-min-height],[data-ao-i-max-width],[data-ao-i-max-height],[data-ao-i-opacity],[data-ao-i-class],[data-ao-i-id],[data-ao-i-btn-bg],[data-ao-i-btn-color],[data-ao-i-btn-radius],[data-ao-i-btn-width],[data-ao-i-btn-height],[data-ao-i-btn-padding],[data-ao-i-btn-font-size]').forEach(input=>input.addEventListener('input',applyStyleSettings));
    panel.querySelector('[data-ao-i-align]')?.addEventListener('change',applyStyleSettings);
    panel.querySelector('[data-ao-i-justify]')?.addEventListener('change',applyStyleSettings);
    panel.querySelector('[data-ao-i-font-weight]')?.addEventListener('change',applyStyleSettings);
    panel.querySelector('[data-ao-i-font-family-select]')?.addEventListener('change',e=>{
      const input=panel.querySelector('[data-ao-i-font-family]');
      if(input)input.value=e.target.value;
      applyStyleSettings();
    });
    panel.querySelector('[data-ao-i-display]')?.addEventListener('change',applyStyleSettings);
    panel.querySelector('[data-ao-i-image-fit]')?.addEventListener('change',applyStyleSettings);
    panel.querySelector('[data-ao-clear-image]')?.addEventListener('click',()=>{
      const input=panel.querySelector('[data-ao-i-image]');
      const target=imageTarget(selected());
      if(input)input.value='';
      if(target)setTargetImage(target,'');
      renderPanel();
    });
    panel.querySelector('[data-ao-upload-image]')?.addEventListener('click',()=>uploadBuilderAsset('image'));
    panel.querySelector('[data-ao-upload-bg-image]')?.addEventListener('click',()=>uploadBuilderAsset('bg'));
    panel.querySelector('[data-ao-i-action]')?.addEventListener('change',applyActionSettings);
    panel.querySelector('[data-ao-i-button-text]')?.addEventListener('input',applyActionSettings);
    panel.querySelector('[data-ao-i-url]')?.addEventListener('input',applyActionSettings);
    panel.querySelector('[data-ao-i-dropdown]')?.addEventListener('input',applyActionSettings);
    panel.querySelectorAll('[data-ao-color-for]').forEach(input=>input.addEventListener('input',e=>{
      const target=panel.querySelector('['+e.currentTarget.dataset.aoColorFor+']');
      if(target){target.value=e.currentTarget.value; applyStyleSettings();}
    }));
    panel.querySelectorAll('[data-ao-i-bg],[data-ao-i-color],[data-ao-i-border-color],[data-ao-i-btn-bg],[data-ao-i-btn-color]').forEach(input=>input.addEventListener('input',e=>{
      const picker=panel.querySelector('[data-ao-color-for="'+e.currentTarget.getAttributeNames().find(n=>n.startsWith('data-ao-i-'))+'"]');
      if(picker && /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(e.currentTarget.value.trim())) picker.value=hexValue(e.currentTarget.value);
    }));
    panel.querySelectorAll('[data-ao-dock-panel]').forEach(b=>b.addEventListener('click',()=>dockPanel(b.dataset.aoDockPanel)));
    panel.querySelector('[data-ao-min-panel]')?.addEventListener('click',togglePanelMinimized);
    panel.querySelector('[data-ao-area-panel]')?.addEventListener('click',e=>beginAreaSelect(e.currentTarget));
    panel.querySelector('[data-ao-center-block]')?.addEventListener('click',centerSelectedBlock);
    panel.querySelector('[data-ao-center-content]')?.addEventListener('click',centerSelectedContent);
    panel.querySelector('[data-ao-free-move]')?.addEventListener('click',beginFreeMove);
    panel.querySelectorAll('[data-ao-pick]').forEach(b=>b.addEventListener('click',()=>addBlock(b.dataset.aoPick)));
  }
  function togglePanelMinimized(){
    state.panelMinimized=!state.panelMinimized;
    try{localStorage.setItem('ao_inline_panel_minimized',state.panelMinimized?'1':'0');}catch(e){}
    renderPanel();
  }
  function dockPanel(side){
    state.panelSide=side==='right'?'right':'left';
    try{localStorage.setItem('ao_inline_panel_side',state.panelSide);}catch(e){}
    const panel=qs('.ao-inline-panel');
    if(panel)panel.classList.toggle('ao-inline-panel--right',state.panelSide==='right');
    toast(state.panelSide==='right'?'Builder paneli sağa alındı.':'Builder paneli sola alındı.');
  }
  function centerSelectedBlock(){
    const el=selected(); if(!el)return toast('Önce alan seçin.','error');
    snapshot();
    el.style.display=el.style.display||'flex';
    el.style.marginLeft='auto';
    el.style.marginRight='auto';
    if(!el.style.maxWidth && el.getBoundingClientRect().width >= window.innerWidth*.86)el.style.maxWidth='1120px';
    el.style.left='';
    el.style.top='';
    toast('Seçili alan sayfaya ortalandı.');
    renderPanel();
  }
  function centerSelectedContent(){
    const el=selected(); if(!el)return toast('Önce alan seçin.','error');
    snapshot();
    el.style.display=el.style.display||'flex';
    el.style.justifyContent='center';
    el.style.textAlign='center';
    toast('Seçili alanın içeriği ortalandı.');
    renderPanel();
  }
  function beginFreeMove(){
    const el=selected(); if(!el)return toast('Önce alan seçin.','error');
    toast('Seçili alanı sürükleyerek taşı.');
    el.style.position=el.style.position||'relative';
    el.setAttribute('draggable','false');
    let start=null;
    const startLeft=parseFloat(el.style.left||'0')||0;
    const startTop=parseFloat(el.style.top||'0')||0;
    const down=(ev)=>{
      if(!el.contains(ev.target) || ev.target.closest('.ao-inline-panel,.ao-inline-toolbar'))return;
      ev.preventDefault();
      snapshot();
      start={x:ev.clientX,y:ev.clientY,left:startLeft,top:startTop};
      document.addEventListener('pointermove',move,true);
      document.addEventListener('pointerup',up,true);
    };
    const move=(ev)=>{
      if(!start)return;
      el.style.left=Math.round(start.left+(ev.clientX-start.x))+'px';
      el.style.top=Math.round(start.top+(ev.clientY-start.y))+'px';
    };
    const up=()=>{
      document.removeEventListener('pointermove',move,true);
      document.removeEventListener('pointerup',up,true);
      el.removeEventListener('pointerdown',down,true);
      el.setAttribute('draggable','true');
      start=null;
      renderPanel();
      toast('Alan taşındı.');
    };
    el.addEventListener('pointerdown',down,true);
  }
  function renderResizeHandle(){
    qsa('.ao-inline-resize-handle,.ao-inline-move-handle').forEach(el=>el.remove());
    const el=selected();
    if(!el||!document.body.classList.contains('ao-inline-editing'))return;
    const r=el.getBoundingClientRect();
    if(r.width<12||r.height<12)return;
    const moveHandle=document.createElement('div');
    moveHandle.className='ao-inline-move-handle';
    Object.assign(moveHandle.style,{left:Math.round(r.left-9)+'px',top:Math.round(r.top-9)+'px'});
    document.body.appendChild(moveHandle);
    moveHandle.addEventListener('pointerdown',ev=>{
      ev.preventDefault();
      ev.stopPropagation();
      const moving=selected();
      if(!moving)return;
      snapshot();
      moveHandle.style.cursor='grabbing';
      moving.style.opacity='.62';
      const move=moveEv=>{
        qsa('.ao-inline-drag-over').forEach(x=>x.classList.remove('ao-inline-drag-over'));
        const target=dropTargetForPoint(moveEv.clientX,moveEv.clientY,moving);
        if(target)target.classList.add('ao-inline-drag-over');
      };
      const up=upEv=>{
        document.removeEventListener('pointermove',move,true);
        document.removeEventListener('pointerup',up,true);
        moving.style.opacity='';
        const target=dropTargetForPoint(upEv.clientX,upEv.clientY,moving);
        qsa('.ao-inline-drag-over').forEach(x=>x.classList.remove('ao-inline-drag-over'));
        if(target&&target.parentElement){
          const tr=target.getBoundingClientRect();
          const after=upEv.clientY>tr.top+(tr.height/2);
          if(after)target.after(moving); else target.before(moving);
          addBlockMarkers();
          bindBlocks();
          select(moving);
          toast('Blok sürükle bırak ile taşındı.');
        }else{
          renderResizeHandle();
        }
      };
      document.addEventListener('pointermove',move,true);
      document.addEventListener('pointerup',up,true);
    });
    const handle=document.createElement('div');
    handle.className='ao-inline-resize-handle';
    Object.assign(handle.style,{left:Math.round(r.right-9)+'px',top:Math.round(r.bottom-9)+'px'});
    document.body.appendChild(handle);
    handle.addEventListener('pointerdown',ev=>{
      ev.preventDefault();
      ev.stopPropagation();
      const target=selected();
      if(!target)return;
      snapshot();
      const start={x:ev.clientX,y:ev.clientY,w:target.getBoundingClientRect().width,h:target.getBoundingClientRect().height};
      target.style.boxSizing='border-box';
      target.style.maxWidth='none';
      target.style.display=target.style.display||'inline-flex';
      const selectBox=qs('select',target);
      if(selectBox){
        selectBox.style.width='100%';
        selectBox.style.minWidth='0';
        selectBox.style.flex='1 1 auto';
      }
      const move=moveEv=>{
        const nextW=Math.max(90,Math.round(start.w+(moveEv.clientX-start.x)));
        const nextH=Math.max(38,Math.round(start.h+(moveEv.clientY-start.y)));
        target.style.width=nextW+'px';
        target.style.minHeight=nextH+'px';
        target.dataset.aoInlineWidth='custom';
        const nr=target.getBoundingClientRect();
        Object.assign(handle.style,{left:Math.round(nr.right-9)+'px',top:Math.round(nr.bottom-9)+'px'});
      };
      const up=()=>{
        document.removeEventListener('pointermove',move,true);
        document.removeEventListener('pointerup',up,true);
        renderPanel();
        renderResizeHandle();
        toast('Alan fareyle yeniden boyutlandırıldı.');
      };
      document.addEventListener('pointermove',move,true);
      document.addEventListener('pointerup',up,true);
    });
  }
  function dropTargetForPoint(x,y,moving){
    const hidden=qsa('.ao-inline-resize-handle,.ao-inline-move-handle');
    hidden.forEach(h=>h.style.pointerEvents='none');
    let raw=document.elementFromPoint(x,y)?.closest?.('[data-builder-block]');
    hidden.forEach(h=>h.style.pointerEvents='');
    if(!raw||raw===moving||moving.contains(raw)||raw.closest('.ao-inline-toolbar,.ao-inline-panel,.ao-floating-edit,.ao-support-widget-pro'))return null;
    let cur=raw;
    while(cur&&cur.parentElement&&cur.parentElement!==moving.parentElement){
      if(cur.parentElement===document.body)break;
      cur=cur.parentElement.closest?.('[data-builder-block]')||cur.parentElement;
    }
    if(cur&&cur!==moving&&!moving.contains(cur)&&cur.matches?.('[data-builder-block]'))return cur;
    return raw;
  }
  function applyStyleSettings(){
    const el=selected(); if(!el)return;
    const panel=qs('.ao-inline-panel'); if(!panel)return;
    el.style.padding=panel.querySelector('[data-ao-i-padding]')?.value||'';
    el.style.margin=panel.querySelector('[data-ao-i-margin]')?.value||'';
    el.style.marginLeft=panel.querySelector('[data-ao-i-margin-left]')?.value||'';
    el.style.left=panel.querySelector('[data-ao-i-left]')?.value||'';
    el.style.top=panel.querySelector('[data-ao-i-top]')?.value||'';
    if(el.style.left||el.style.top)el.style.position=el.style.position||'relative';
    el.style.width=panel.querySelector('[data-ao-i-exact-width]')?.value||el.style.width||'';
    el.style.height=panel.querySelector('[data-ao-i-height]')?.value||'';
    el.style.minWidth=panel.querySelector('[data-ao-i-min-width]')?.value||'';
    el.style.textAlign=panel.querySelector('[data-ao-i-align]')?.value||'';
    el.style.justifyContent=panel.querySelector('[data-ao-i-justify]')?.value||'';
    setStyle(el,'background',panel.querySelector('[data-ao-i-bg]')?.value||'',true);
    const bgImage=(panel.querySelector('[data-ao-i-bg-image]')?.value||'').trim();
    if(bgImage){
      const resolvedBg=assetUrl(bgImage);
      setStyle(el,'background-image','url("'+resolvedBg.replace(/"/g,'&quot;')+'")',true);
      setStyle(el,'background-size',panel.querySelector('[data-ao-i-image-fit]')?.value||'cover',true);
      setStyle(el,'background-position',panel.querySelector('[data-ao-i-image-position]')?.value||'center',true);
      setStyle(el,'background-repeat','no-repeat',true);
    }
    const imgUrl=(panel.querySelector('[data-ao-i-image]')?.value||'').trim();
    const imgTarget=imageTarget(el);
    if(imgTarget&&imgUrl)setTargetImage(imgTarget,imgUrl,panel.querySelector('[data-ao-i-image-fit]')?.value||'cover',panel.querySelector('[data-ao-i-image-position]')?.value||'center');
    el.style.color=panel.querySelector('[data-ao-i-color]')?.value||'';
    el.style.fontSize=panel.querySelector('[data-ao-i-font-size]')?.value||'';
    el.style.fontFamily=panel.querySelector('[data-ao-i-font-family]')?.value||'';
    el.style.fontWeight=panel.querySelector('[data-ao-i-font-weight]')?.value||'';
    el.style.lineHeight=panel.querySelector('[data-ao-i-line-height]')?.value||'';
    el.style.letterSpacing=panel.querySelector('[data-ao-i-letter-spacing]')?.value||'';
    el.style.borderRadius=panel.querySelector('[data-ao-i-radius]')?.value||'';
    el.style.border=panel.querySelector('[data-ao-i-border]')?.value||'';
    el.style.borderColor=panel.querySelector('[data-ao-i-border-color]')?.value||'';
    el.style.boxShadow=panel.querySelector('[data-ao-i-shadow]')?.value||'';
    el.style.minHeight=panel.querySelector('[data-ao-i-min-height]')?.value||'';
    el.style.maxWidth=panel.querySelector('[data-ao-i-max-width]')?.value||'';
    el.style.maxHeight=panel.querySelector('[data-ao-i-max-height]')?.value||'';
    el.style.display=panel.querySelector('[data-ao-i-display]')?.value||'';
    el.style.opacity=panel.querySelector('[data-ao-i-opacity]')?.value||'';
    const titleEl=qs('h1,h2,h3,h4,strong',el);
    if(titleEl){
      titleEl.style.fontSize=panel.querySelector('[data-ao-i-title-font-size]')?.value||'';
      titleEl.style.lineHeight=panel.querySelector('[data-ao-i-title-line-height]')?.value||'';
    }
    const textEl=qs('p',el);
    if(textEl){
      textEl.style.fontSize=panel.querySelector('[data-ao-i-text-font-size]')?.value||'';
      textEl.style.lineHeight=panel.querySelector('[data-ao-i-text-line-height]')?.value||'';
    }
    const nextClass=(panel.querySelector('[data-ao-i-class]')?.value||'').trim();
    if(el.dataset.aoCustomClass)el.classList.remove(...el.dataset.aoCustomClass.split(/\s+/).filter(Boolean));
    el.dataset.aoCustomClass=nextClass;
    if(nextClass)el.classList.add(...nextClass.split(/\s+/).filter(Boolean));
    el.id=(panel.querySelector('[data-ao-i-id]')?.value||'').trim();
    const btn=qs('[data-ao-action-button],a,button',el);
    if(btn){
      btn.style.background=panel.querySelector('[data-ao-i-btn-bg]')?.value||'';
      btn.style.color=panel.querySelector('[data-ao-i-btn-color]')?.value||'';
      btn.style.borderRadius=panel.querySelector('[data-ao-i-btn-radius]')?.value||'';
      btn.style.width=panel.querySelector('[data-ao-i-btn-width]')?.value||'';
      const btnHeight=panel.querySelector('[data-ao-i-btn-height]')?.value||'';
      btn.style.height=btnHeight;
      btn.style.minHeight=btnHeight;
      btn.style.padding=panel.querySelector('[data-ao-i-btn-padding]')?.value||'';
      btn.style.fontSize=panel.querySelector('[data-ao-i-btn-font-size]')?.value||'';
    }
  }
  function applyActionSettings(){
    const el=selected(); if(!el)return;
    const btn=el.matches(ACTION_BUTTON_SELECTOR)?el:qs('[data-ao-action-button],a,button',el); if(!btn)return;
    const panel=qs('.ao-inline-panel');
    const type=panel?.querySelector('[data-ao-i-action]')?.value||'link';
    const url=panel?.querySelector('[data-ao-i-url]')?.value||'#';
    const label=panel?.querySelector('[data-ao-i-button-text]')?.value;
    const items=panel?.querySelector('[data-ao-i-dropdown]')?.value||'';
    el.dataset.aoActionType=type;
    el.dataset.aoActionUrl=url;
    el.dataset.aoDropdownItems=items;
    btn.setAttribute('data-ao-action-button','');
    if(label!==undefined)btn.textContent=label;
    btn.setAttribute('href',type==='link'?url:'#');
    btn.dataset.aoActionType=type;
    btn.dataset.aoActionUrl=url;
    qs('.ao-inline-action-preview',el)?.remove();
    if(type==='dropdown'){
      const box=document.createElement('div');
      box.className='ao-inline-action-preview';
      box.innerHTML=items.split(/\n+/).filter(Boolean).map(line=>{
        const parts=line.split('|');
        return `<a href="${esc(parts[1]||'#')}">${esc(parts[0]||'Link')}</a>`;
      }).join('');
      el.appendChild(box);
    }
    if(type==='modal'){
      btn.setAttribute('data-ao-modal-target',url);
    }else{
      btn.removeAttribute('data-ao-modal-target');
    }
  }
  async function uploadBuilderAsset(kind='image'){
    const panel=qs('.ao-inline-panel');
    if(!panel)return;
    if(!cfg.csrf)return toast('Yükleme için admin oturumu gerekli.','error');
    const picker=document.createElement('input');
    picker.type='file';
    picker.accept='image/*,.svg,.webp,.avif,.bmp,.ico';
    picker.style.position='fixed';
    picker.style.left='-9999px';
    document.body.appendChild(picker);
    picker.addEventListener('change',async()=>{
      const file=picker.files&&picker.files[0];
      picker.remove();
      if(!file)return;
      const fd=new FormData();
      fd.append('csrf_token',cfg.csrf);
      fd.append('asset',file);
      try{
        toast('Görsel yükleniyor...');
        const res=await fetch((cfg.baseUrl||'')+'/admin/builder-pro/upload-asset',{method:'POST',body:fd,headers:{'Accept':'application/json'}});
        const json=await res.json();
        if(!json.ok)throw new Error(json.message||'Yüklenemedi');
        const selector=kind==='bg'?'[data-ao-i-bg-image]':'[data-ao-i-image]';
        const input=panel.querySelector(selector);
        if(input)input.value=json.url;
        applyStyleSettings();
        toast('Görsel yüklendi ve seçili alana uygulandı.');
      }catch(err){
        toast('Görsel yüklenemedi: '+err.message,'error');
      }
    },{once:true});
    picker.click();
  }
  function handleToolbar(e){
    const btn=e.target.closest('button,a'); if(!btn)return;
    if(btn.matches('[data-ao-save]'))return save();
    if(btn.matches('[data-ao-add]'))return renderPanel(),toast('Soldaki panelden blok seç.');
    if(btn.matches('[data-ao-area]'))return beginAreaSelect(btn);
    if(btn.matches('[data-ao-up]'))return move(-1);
    if(btn.matches('[data-ao-down]'))return move(1);
    if(btn.matches('[data-ao-narrow]'))return changeWidth(-25);
    if(btn.matches('[data-ao-wide]'))return changeWidth(25);
    if(btn.matches('[data-ao-copy]'))return copy();
    if(btn.matches('[data-ao-delete]'))return remove();
    if(btn.matches('[data-ao-text]'))return toggleText(btn);
    if(btn.matches('[data-ao-undo]'))return undo();
    if(btn.matches('[data-ao-reset]'))return disable();
    if(btn.matches('[data-ao-admin]')){
      e.preventDefault();
      const target=cfg.target||document.body.getAttribute('data-app')||'site';
      const template=cfg.template||(location.pathname||'/').replace(/^\/+/,'').replace(/[^a-z0-9\/_-]/gi,'').replace(/\//g,'-')||'home';
      const meta=document.querySelector('meta[name="ahost-base-url"]');
      location.href=(cfg.baseUrl||(meta&&meta.getAttribute('content'))||'')+'/admin/builder-pro?target='+encodeURIComponent(target)+'&template='+encodeURIComponent(template);
    }
  }
  function addBlock(type){
    const tpl=templates[type]||templates.text;
    const wrap=document.createElement('div');
    wrap.innerHTML=tpl.html.trim();
    const node=wrap.firstElementChild;
    node.setAttribute('data-builder-block',type+'-'+uid());
    snapshot();
    const el=selected();
    if(el&&(el.matches('header,footer,.ao-public-header,.ao-unified-header,.ahost-site-footer,.site-footer')))el.appendChild(node);
    else if(el&&el.parentElement)el.insertAdjacentElement('afterend',node);
    else editingRoot().appendChild(node);
    addBlockMarkers();bindBlocks();select(node);
    toast(tpl.label+' eklendi.');
  }
  function remove(){
    const el=selected(); if(!el)return toast('Önce blok seçin.','error');
    if(!confirm('Seçili blok silinsin mi?'))return;
    snapshot(); const next=el.nextElementSibling||el.previousElementSibling; el.remove(); select(next?.matches?.('[data-builder-block]')?next:null); toast('Blok silindi.');
  }
  function copy(){
    const el=selected(); if(!el)return toast('Önce blok seçin.','error');
    snapshot(); const clone=el.cloneNode(true); clone.setAttribute('data-builder-block',(el.getAttribute('data-builder-block')||'block')+'-'+uid()); clone.classList.remove('ao-inline-selected'); el.after(clone); bindBlocks(); select(clone); toast('Blok kopyalandı.');
  }
  function move(dir){
    const el=selected(); if(!el)return toast('Önce blok seçin.','error');
    const sib=dir<0?el.previousElementSibling:el.nextElementSibling;
    if(!sib)return;
    snapshot();
    if(dir<0)el.parentElement.insertBefore(el,sib); else el.parentElement.insertBefore(sib,el);
    select(el); toast('Blok taşındı.');
  }
  function setWidth(v){
    const el=selected(); if(!el)return;
    v=Math.max(25,Math.min(100,v)); snapshot();
    el.dataset.aoInlineWidth=String(v);
    el.style.width=v+'%';
    el.style.maxWidth=v===100?'':v+'%';
    el.style.display=v<100?'inline-block':'';
    el.style.verticalAlign='top';
    renderPanel();
  }
  function changeWidth(delta){
    const el=selected(); if(!el)return toast('Önce blok seçin.','error');
    const steps=[25,33,50,66,75,100];
    const cur=Number(el.dataset.aoInlineWidth||100);
    let idx=steps.reduce((best,v,i)=>Math.abs(v-cur)<Math.abs(steps[best]-cur)?i:best,0);
    idx=Math.max(0,Math.min(steps.length-1,idx+(delta>0?1:-1)));
    setWidth(steps[idx]);
  }
  function toggleText(btn){
    state.text=!state.text;
    document.body.classList.toggle('ao-text-editing',state.text);
    btn.classList.toggle('active',state.text);
    qsa('[data-builder-block]').forEach(el=>el.setAttribute('contenteditable',state.text?'true':'false'));
    toast(state.text?'Yazı düzenleme açık.':'Yazı düzenleme kapalı.');
  }
  function bindBlocks(){
    qsa('[data-builder-block]').forEach(el=>{
      if(el.dataset.aoInlineBound==='1')return;
      el.dataset.aoInlineBound='1';
      el.addEventListener('click',ev=>{
        if(!document.body.classList.contains('ao-inline-editing'))return;
        if(ev.target.closest('.ao-inline-toolbar,.ao-inline-panel'))return;
        ev.preventDefault();
        ev.stopPropagation();
        const direct=ev.target.closest('[data-builder-block]');
        if(direct&&direct!==el&&el.contains(direct))return;
        select(el); renderPanel();
      },true);
      el.addEventListener('dragstart',ev=>{if(!document.body.classList.contains('ao-inline-editing'))return; state.drag=el; ev.dataTransfer.setData('text/plain',el.getAttribute('data-builder-block')||'block');});
      el.addEventListener('dragover',ev=>{if(state.drag&&state.drag!==el){ev.preventDefault();el.classList.add('ao-inline-drag-over');}});
      el.addEventListener('dragleave',()=>el.classList.remove('ao-inline-drag-over'));
      el.addEventListener('drop',ev=>{if(!state.drag||state.drag===el)return; ev.preventDefault(); snapshot(); el.classList.remove('ao-inline-drag-over'); el.parentElement.insertBefore(state.drag,el.nextSibling); select(state.drag); state.drag=null; toast('Blok sürüklenip taşındı.');});
      el.addEventListener('dragend',()=>{qsa('.ao-inline-drag-over').forEach(x=>x.classList.remove('ao-inline-drag-over'));state.drag=null;});
    });
  }
  function beginAreaSelect(btn){
    state.area=!state.area;
    btn.classList.toggle('active',state.area);
    document.body.classList.toggle('ao-inline-area-selecting',state.area);
    toast(state.area?'Düzenlenecek alanı çizerek seç.':'Alan seçimi kapatıldı.');
    qsa('.ao-inline-area-catcher,.ao-inline-area-hint,.ao-inline-area-box').forEach(el=>el.remove());
    if(!state.area)return;
    const catcher=document.createElement('div');
    catcher.className='ao-inline-area-catcher';
    const hint=document.createElement('div');
    hint.className='ao-inline-area-hint';
    hint.textContent='Düzenlenecek alanı ekran görüntüsü alır gibi çiz. ESC ile iptal.';
    document.body.appendChild(catcher);
    document.body.appendChild(hint);
    const onDown=(ev)=>{
      if(!state.area)return cleanup();
      if(ev.button!==0)return;
      if(ev.target.closest('.ao-inline-toolbar,.ao-inline-panel,.ao-inline-toast'))return;
      ev.preventDefault();
      state.areaStart={x:ev.clientX,y:ev.clientY};
      const box=document.createElement('div');
      box.className='ao-inline-area-box';
      document.body.appendChild(box);
      const draw=(moveEv)=>{
        const x=Math.min(state.areaStart.x,moveEv.clientX), y=Math.min(state.areaStart.y,moveEv.clientY);
        const w=Math.abs(moveEv.clientX-state.areaStart.x), h=Math.abs(moveEv.clientY-state.areaStart.y);
        Object.assign(box.style,{left:x+'px',top:y+'px',width:w+'px',height:h+'px'});
      };
      const up=(upEv)=>{
        document.removeEventListener('pointermove',draw,true);
        document.removeEventListener('pointerup',up,true);
        const rect={
          left:Math.min(state.areaStart.x,upEv.clientX),
          top:Math.min(state.areaStart.y,upEv.clientY),
          right:Math.max(state.areaStart.x,upEv.clientX),
          bottom:Math.max(state.areaStart.y,upEv.clientY),
          width:Math.abs(upEv.clientX-state.areaStart.x),
          height:Math.abs(upEv.clientY-state.areaStart.y)
        };
        box.remove();
        state.area=false;
        state.areaStart=null;
        document.body.classList.remove('ao-inline-area-selecting');
        btn.classList.remove('active');
        const picked=pickBlockByRect(rect);
        if(picked&&shouldUsePickedBlock(rect,picked)){select(picked);toast('Alan seçildi: '+blockName(picked));}
        else {const custom=createAreaBlock(rect,picked); if(custom){select(custom);toast('Çizilen alan yeni düzenleme bloğu oldu.');} else toast('Bu çizimde düzenlenebilir alan bulunamadı.','error');}
        cleanup();
      };
      document.addEventListener('pointermove',draw,true);
      document.addEventListener('pointerup',up,true);
    };
    const onKey=(ev)=>{
      if(ev.key==='Escape'){
        state.area=false;
        document.body.classList.remove('ao-inline-area-selecting');
        btn.classList.remove('active');
        cleanup();
        toast('Alan seçimi iptal edildi.');
      }
    };
    const cleanup=()=>{
      catcher.removeEventListener('pointerdown',onDown,true);
      document.removeEventListener('keydown',onKey,true);
      qsa('.ao-inline-area-catcher,.ao-inline-area-hint,.ao-inline-area-box').forEach(el=>el.remove());
    };
    catcher.addEventListener('pointerdown',onDown,true);
    document.addEventListener('keydown',onKey,true);
  }
  function shouldUsePickedBlock(sel,el){
    if(!sel||!el)return false;
    if(el.matches(INTERACTIVE_SELECTOR))return true;
    const r=el.getBoundingClientRect();
    const left=Math.max(sel.left,r.left), top=Math.max(sel.top,r.top), right=Math.min(sel.right,r.right), bottom=Math.min(sel.bottom,r.bottom);
    const overlap=Math.max(0,right-left)*Math.max(0,bottom-top);
    const selArea=Math.max(1,sel.width*sel.height);
    const elArea=Math.max(1,r.width*r.height);
    return (overlap/selArea)>.72 && (overlap/elArea)>.62;
  }
  function createAreaBlock(sel,picked){
    if(!sel||sel.width<16||sel.height<16)return null;
    snapshot();
    const cx=sel.left+(sel.width/2), cy=sel.top+(sel.height/2);
    let parent=(document.elementFromPoint(cx,cy)?.closest?.('[data-builder-block]'))||picked||qs('main')||document.body;
    if(parent.closest?.(BUILDER_CHROME_SELECTOR))parent=qs('main')||document.body;
    const pr=parent.getBoundingClientRect();
    const area=document.createElement('section');
    area.className='ao-inline-custom-area';
    area.setAttribute('data-builder-block','area-'+uid());
    area.setAttribute('data-ao-image-target','');
    area.innerHTML='<h3>Yeni Alan</h3><p>Bu çizilen alanı panelden düzenleyin.</p>';
    Object.assign(area.style,{
      position:'absolute',
      left:Math.max(0,Math.round(sel.left-pr.left+(parent.scrollLeft||0)))+'px',
      top:Math.max(0,Math.round(sel.top-pr.top+(parent.scrollTop||0)))+'px',
      width:Math.round(sel.width)+'px',
      minHeight:Math.round(sel.height)+'px',
      padding:'16px',
      borderRadius:'18px',
      border:'1px solid rgba(191,219,254,.95)',
      background:'rgba(255,255,255,.92)',
      boxShadow:'0 18px 50px rgba(15,23,42,.12)',
      zIndex:'4',
      boxSizing:'border-box'
    });
    const cs=getComputedStyle(parent);
    if(cs.position==='static')parent.style.position='relative';
    parent.appendChild(area);
    addBlockMarkers();
    bindBlocks();
    return area;
  }
  function pickBlockByRect(sel){
    if(!sel||sel.width<8||sel.height<8)return null;
    const area=sel.width*sel.height;
    let best=null;
    const candidates=new Set([
      ...qsa('[data-builder-block]'),
      ...qsa(MAIN_INTERACTIVE_SELECTOR)
    ]);
    candidates.forEach(el=>{
      if(el.closest(BUILDER_CHROME_SELECTOR))return;
      const cs=getComputedStyle(el);
      if(cs.display==='none'||cs.visibility==='hidden')return;
      const r=el.getBoundingClientRect();
      if(r.width<4||r.height<4)return;
      const left=Math.max(sel.left,r.left), top=Math.max(sel.top,r.top), right=Math.min(sel.right,r.right), bottom=Math.min(sel.bottom,r.bottom);
      const w=Math.max(0,right-left), h=Math.max(0,bottom-top), overlap=w*h;
      if(!overlap)return;
      const ratioToSelection=overlap/area;
      const ratioToElement=overlap/(r.width*r.height);
      if(ratioToSelection<.18&&ratioToElement<.18)return;
      const isInteractive=el.matches(INTERACTIVE_SELECTOR);
      const centerX=sel.left+(sel.width/2), centerY=sel.top+(sel.height/2);
      const containsCenter=centerX>=r.left&&centerX<=r.right&&centerY>=r.top&&centerY<=r.bottom;
      const sizePenalty=Math.abs((r.width*r.height)-area)/Math.max(area,r.width*r.height);
      const score=(ratioToSelection*2.2)+ratioToElement+(isInteractive?2.2:0)+(containsCenter?0.45:0)-(sizePenalty*.35);
      if(!best||score>best.score)best={el,score};
    });
    if(best?.el&&!best.el.hasAttribute('data-builder-block')){
      best.el.setAttribute('data-builder-block',(best.el.tagName||'control').toLowerCase()+'-'+uid());
      best.el.dataset.aoInlineWidth=best.el.dataset.aoInlineWidth||'100';
      best.el.setAttribute('draggable','true');
      best.el.setAttribute('contenteditable','false');
      best.el.dataset.aoInlineBound='';
      bindBlocks();
    }
    return best?.el||null;
  }
  function serialize(){
    const widgets=qsa('[data-builder-block]').filter(el=>!el.closest(BUILDER_CHROME_SELECTOR)).map((el,i)=>{
      const rawType=(el.getAttribute('data-builder-block')||'text').split('-')[0];
      const type=/^block$/i.test(rawType)?'text':(rawType||'text');
      return {
        id:el.getAttribute('data-builder-block')||('inline_'+i),
        type:type,
        title:(qs('h1,h2,h3,h4,strong',el)?.textContent||blockName(el)).trim(),
        text:(qs('p',el)?.textContent||'').trim(),
        button:(qs('a,button',el)?.textContent||'').trim(),
        props:{width:el.dataset.aoInlineWidth||'100',html:el.outerHTML}
        ,action:{type:el.dataset.aoActionType||'',url:el.dataset.aoActionUrl||'',dropdown:el.dataset.aoDropdownItems||''}
      };
    });
    return [{id:'inline_front_row',cols:[{id:'inline_front_col',span:10,widgets:widgets}]}];
  }
  function collectBoundContentUpdates(){
    const updates={products:[]};
    qsa('[data-product-card][data-product-id]').forEach(card=>{
      if(card.closest(BUILDER_CHROME_SELECTOR))return;
      const id=parseInt(card.dataset.productId||'0',10)||0;
      if(!id)return;
      const field=(name)=>((qs('[data-product-field="'+name+'"]',card)||{}).textContent||'').replace(/\s+/g,' ').trim();
      const select=qs('[data-product-cycle-select]',card);
      updates.products.push({
        id:id,
        name:field('name'),
        short_description:field('short_description'),
        price_label:field('price'),
        cycle:select ? (select.value||'') : ''
      });
    });
    return updates;
  }
  async function save(){
    const layout=serialize();
    const boundUpdates=collectBoundContentUpdates();
    const key='ao_inline_draft_'+location.pathname;
    try{localStorage.setItem(key,JSON.stringify(layout));}catch(e){}
    if(!cfg.csrf){toast('Taslak bu tarayıcıya kaydedildi. CSRF yok, DB kaydı atlandı.','error');return;}
    const fd=new FormData();
    fd.append('csrf_token',cfg.csrf);
    fd.append('target',cfg.target||'site');
    fd.append('template_key',cfg.template||'home');
    fd.append('layout_json',JSON.stringify(layout));
    fd.append('bound_updates_json',JSON.stringify(boundUpdates));
    try{
      const res=await fetch((cfg.baseUrl||'')+'/admin/builder-pro/inline-save',{method:'POST',body:fd,headers:{'Accept':'application/json'}});
      const json=await res.json();
      if(!json.ok)throw new Error(json.message||'Kaydedilemedi');
      toast('Builder düzeni kaydedildi ve Builder Pro ile eşitlendi.');
    }catch(err){
      toast('Yerel taslak kaydedildi, DB kaydı başarısız: '+err.message,'error');
    }
  }
  function undo(){
    if(!state.history.length)return toast('Geri alınacak işlem yok.','error');
    const root=editingRoot();
    state.future.push(root.innerHTML);
    root.innerHTML=state.history.pop();
    state.selected=null;
    addBlockMarkers();
    bindBlocks();
    renderPanel();
    toast('Son işlem geri alındı.');
  }
  document.addEventListener('click',function(e){
    const a=e.target.closest('.ao-inline-edit-start,[data-ao-inline-edit]');
    if(a){e.preventDefault();enable();}
  });
})();

