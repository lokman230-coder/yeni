/**
 * Ahost One Builder Pro
 * AhostBuilder: satır, kolon, blok, sürükle-bırak, canlı inspector ve AI taslak köprüsü.
 */
(function () {
  const $ = (selector, parent = document) => parent.querySelector(selector);
  const $$ = (selector, parent = document) => Array.from(parent.querySelectorAll(selector));
  const clamp = (number, min, max) => Math.max(min, Math.min(max, number));
  const state = { rows: [], selected: null, history: [], future: [], dragWidget: null, dragRow: null, device: 'desktop' };
  const AB4 = window.AhostBuilder4 || null;

  const blockLabels = {
    section: 'Bölüm', container: 'Container', columns_2: '2 Kolon', columns_3: '3 Kolon',
    hero: 'Hero', slider: 'Slider', domain: 'Domain', support_widget: 'Destek',
    product: 'Ürün', pricing: 'Fiyat', kpi: 'KPI', renewal: 'Yenileme',
    invoice: 'Fatura', ticket: 'Ticket', chart: 'Grafik', form: 'Form',
    media: 'Medya', testimonial: 'Yorum', faq: 'SSS', text: 'Metin',
    button: 'Buton', spacer: 'Boşluk', html: 'HTML', header: 'Header',
    footer: 'Footer', hosting: 'Hosting', vps: 'VPS', ssl: 'SSL',
    blog: 'Blog', campaign: 'Kampanya', tabs: 'Sekmeler',
    ai_builder: 'AI Builder', seo: 'SEO', banner: 'Banner'
  };

  const blockHints = {
    section: 'Arka plan, padding ve sayfa bölümü', container: 'İçerik genişliği ve hizalama',
    columns_2: 'Yan yana iki kolon düzeni', columns_3: 'Yan yana üç kolon düzeni',
    hero: 'Başlık, açıklama ve CTA', slider: 'Ana sayfa slider alanı',
    domain: 'Domain sorgula, WHOIS, DNS, SSL', support_widget: 'Sağ alt destek ikonları',
    product: 'Ürün veya paket kartı', pricing: 'Plan ve fiyat karşılaştırması',
    kpi: 'Dashboard istatistik kartı', renewal: 'Yenileme ve kalan süre',
    invoice: 'Fatura bilgisi', ticket: 'Destek talebi kartı',
    chart: 'Grafik ve rapor alanı', form: 'Teklif veya iletişim formu',
    media: 'Görsel, video, galeri', testimonial: 'Müşteri yorumu',
    faq: 'Sık sorulan sorular', text: 'Metin ve tanıtım alanı',
    button: 'Tek aksiyon butonu', spacer: 'Boşluk ve ayırıcı',
    html: 'Özel HTML alanı', header: 'Logo, menü ve CTA',
    footer: 'Footer menü ve SEO alanları', hosting: 'Hosting paket kartı',
    vps: 'VPS/cloud paket kartı', ssl: 'SSL ürün kartı',
    blog: 'Blog ve duyurular', campaign: 'Kampanya bannerı',
    tabs: 'Sekmeli panel navigasyonu', ai_builder: 'AI öneri alanı',
    seo: 'SEO ve meta düzeni', banner: 'Duyuru bandı'
  };

  const blockGroups = [
    { title: 'AhostBuilder Grid', items: ['section', 'container', 'columns_2', 'columns_3'] },
    { title: 'En Çok Kullanılan', items: ['hero', 'domain', 'product', 'pricing', 'support_widget', 'slider'] },
    { title: 'Site Teması', items: ['header', 'footer', 'hosting', 'vps', 'ssl', 'campaign', 'blog', 'faq', 'testimonial'] },
    { title: 'Panel / Operasyon', items: ['kpi', 'chart', 'renewal', 'invoice', 'ticket', 'tabs', 'ai_builder'] },
    { title: 'İçerik', items: ['text', 'button', 'form', 'media', 'banner', 'seo', 'spacer', 'html'] }
  ];

  const defaults = {
    section: { title: 'Yeni Bölüm', text: 'Bu bölüm içine bloklar yerleştirilebilir.', button: '', props: { padding: '28px' } },
    container: { title: 'Container', text: 'İçeriği kontrollü genişlikte tutar.', button: '', props: { padding: '24px', maxWidth: '1200px' } },
    columns_2: { title: '2 Kolon Düzen', text: 'Yan yana iki alan oluşturur.', button: '' },
    columns_3: { title: '3 Kolon Düzen', text: 'Yan yana üç alan oluşturur.', button: '' },
    hero: { title: 'Premium Hero', text: 'Domain, hosting, marketplace ve AI tek panelde.', button: 'Hemen Başla' },
    slider: { title: 'Slider', text: 'Menü altında görünen slider alanı.', button: 'Slider Aktif' },
    product: { title: 'Ürün / Paket', text: 'Hosting, VPS, web tasarım veya lisans ürünü.', price: '₺149/ay', button: 'Satın Al' },
    domain: { title: 'Domain Search Center', text: 'Registrar fiyatı ve komisyon ile canlı fiyat gösterimi.', button: 'Sorgula' },
    support_widget: { title: 'Sağ Alt Destek', text: 'WhatsApp, canlı destek, AI destek, telefon ve ticket widgetı.', button: 'Destek Aç' },
    kpi: { title: 'Dashboard KPI', text: 'MRR, ARR, müşteri, domain ve ticket SLA.', price: '₺75.756' },
    renewal: { title: 'Yenileme Kartı', text: 'Hosting/domain ödeme tarihi ve kalan gün.', button: 'Yenile' },
    invoice: { title: 'Fatura Kartı', text: 'Son faturalar, ödeme durumu ve tahsilat.', button: 'Detay' },
    ticket: { title: 'Ticket Kartı', text: 'Açık destek kayıtları ve SLA.', button: 'Ticket Aç' },
    chart: { title: 'Grafik', text: 'Gelir, sipariş, kaynak kullanımı veya SLA grafiği.' },
    form: { title: 'Form', text: 'Teklif, destek, başvuru veya iletişim formu.', button: 'Gönder' },
    media: { title: 'Medya / Slider', text: 'Görsel, video, galeri veya slider.' },
    testimonial: { title: 'Müşteri Yorumu', text: 'Ahost One operasyonu tek panele topladı.' },
    faq: { title: 'SSS', text: 'Sık sorulan soru ve cevap bloğu.' },
    text: { title: 'Metin Bloğu', text: 'Tanıtım, açıklama veya içerik alanı.' },
    button: { title: 'Buton', text: '', button: 'Buton Metni' },
    spacer: { title: 'Boşluk', text: '', props: { minHeight: '80px' } },
    html: { title: 'Özel HTML', text: '<strong>Özel alan</strong>' },
    header: { title: 'Ahost One', text: 'Domain, Hosting, VPS, SSL, Blog, Kampanyalar', button: 'Müşteri Paneli' },
    footer: { title: 'Ahost One Footer', text: 'Menüler, SEO metinleri, sosyal bağlantılar ve destek kanalları.' },
    hosting: { title: 'Hosting Paketleri', text: 'Paylaşımlı, bayi ve WordPress hosting paketleri.', price: 'TRY 149/ay', button: 'İncele' },
    vps: { title: 'VPS Cloud', text: 'NVMe disk, yedekleme ve ölçeklenebilir kaynak.', price: 'TRY 399/ay', button: 'Kur' },
    ssl: { title: 'SSL Sertifikaları', text: 'DV/OV/EV sertifika ve otomatik kurulum.', price: 'TRY 99/yıl', button: 'Seç' },
    blog: { title: 'Blog ve Duyurular', text: 'SEO uyumlu blog, duyuru ve bilgi bankası kartları.', button: 'Oku' },
    campaign: { title: 'Kampanya Banner', text: 'İlk siparişe özel indirim ve paket fırsatları.', button: 'Kampanyayı Aç' },
    tabs: { title: 'Üst Sekmeli Panel', text: 'Özet, Hizmetler, Domainler, Faturalar, Destek' },
    ai_builder: { title: 'AI Builder', text: 'Prompt ile sayfa, başlık, kampanya ve SEO metni önerileri.', button: 'AI Öner' },
    seo: { title: 'SEO / Meta', text: 'Sayfa başlığı, meta açıklaması, canonical ve sosyal paylaşım metinleri.' },
    banner: { title: 'Duyuru Banner', text: 'Bakım, indirim, yeni ürün veya kampanya duyurusu.', button: 'Detay' }
  };

  if (AB4?.blocks) {
    Object.keys(AB4.blocks).forEach(type => {
      const block = AB4.blocks[type] || {};
      if (!blockLabels[type]) blockLabels[type] = block.label || type;
      if (!blockHints[type]) blockHints[type] = block.hint || '';
      if (!defaults[type]) defaults[type] = JSON.parse(JSON.stringify(block.defaults || { title: block.label || type, text: block.hint || '' }));
    });
    if (Array.isArray(AB4.groups)) {
      AB4.groups.forEach(group => {
        const known = blockGroups.some(item => item.title === group.title);
        if (!known) blockGroups.push({ title: group.title, items: (group.items || []).filter(type => defaults[type]) });
      });
    }
  }

  function uid() { return 'bp_' + Math.random().toString(36).slice(2, 10); }
  function esc(value) { return String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char])); }
  function snap() { state.history.push(JSON.stringify(state.rows)); if (state.history.length > 80) state.history.shift(); state.future = []; }
  function persist() { const field = $('#bp_json'); if (field) field.value = JSON.stringify(state.rows); }
  function widgetText(widget) { return widget.text ?? widget.tet ?? ''; }

  function normalize() {
    state.rows = (state.rows || []).map(row => ({
      id: row.id || uid(),
      cols: (row.cols || []).map(col => ({
        id: col.id || uid(),
        span: clamp(parseInt(col.span || 10, 10), 1, 10),
        widgets: (col.widgets || []).map(widget => ({
          id: widget.id || uid(),
          type: widget.type || 'text',
          title: widget.title || '',
          text: widget.text ?? widget.tet ?? '',
          price: widget.price || '',
          button: widget.button || '',
          props: widget.props || {}
        }))
      }))
    }));
  }

  function init() {
    try { state.rows = JSON.parse($('#bp_json')?.value || '[]') || []; } catch (error) { state.rows = []; }
    normalize();
    if (!state.rows.length) addRow(2, false);
    bindStatic();
    render();
  }

  function bindStatic() {
    $$('.bp-block-item').forEach(item => {
      item.draggable = true;
      item.addEventListener('dragstart', event => event.dataTransfer.setData('bp-new-type', item.dataset.type));
      item.addEventListener('click', () => addWidgetToFirst(item.dataset.type));
    });
    $('#bpAddRow')?.addEventListener('click', () => addRow(parseInt($('#bpCols')?.value || '1', 10), true));
    $('#bpUndo')?.addEventListener('click', undo);
    $('#bpRedo')?.addEventListener('click', redo);
    $('#bpSave')?.addEventListener('click', () => { persist(); $('#bpForm')?.submit(); });
    $('#bpPreviewJson')?.addEventListener('click', showJson);
    $('#bpImportDefault')?.addEventListener('click', importTemplate);
    $('#bpImportTarget')?.addEventListener('click', importTargetTemplate);
    $('#bpSearch')?.addEventListener('input', event => {
      const query = event.target.value.toLowerCase();
      $$('.bp-block-item').forEach(item => { item.style.display = item.innerText.toLowerCase().includes(query) ? 'flex' : 'none'; });
    });
    $$('.bp-device button').forEach(button => button.addEventListener('click', () => {
      state.device = button.dataset.device;
      document.body.classList.remove('bp-preview-desktop', 'bp-preview-tablet', 'bp-preview-mobile');
      document.body.classList.add('bp-preview-' + state.device);
    }));
    document.addEventListener('click', event => { if (event.target.matches('[data-bp-picker-close]')) closeBlockPicker(); });
  }

  function addRow(cols = 1, save = true) {
    if (save) snap();
    cols = clamp(cols, 1, 10);
    const baseSpan = Math.floor(10 / cols) || 1;
    const row = { id: uid(), cols: [] };
    for (let index = 0; index < cols; index++) {
      row.cols.push({ id: uid(), span: index === cols - 1 ? 10 - baseSpan * (cols - 1) : baseSpan, widgets: [] });
    }
    state.rows.push(row);
    render();
  }

  function addWidgetToFirst(type) {
    if (!state.rows.length) addRow(1, false);
    const row = state.rows[0];
    if (!row.cols.length) row.cols.push({ id: uid(), span: 10, widgets: [] });
    addWidget(row.id, row.cols[0].id, type);
  }

  function newWidget(type) {
    const base = JSON.parse(JSON.stringify(defaults[type] || defaults.text));
    return Object.assign({ id: uid(), type, props: {} }, base, { props: Object.assign({}, base.props || {}) });
  }

  function addWidget(rowId, colId, type) {
    snap();
    const col = findCol(rowId, colId);
    if (!col) return;
    if (type === 'columns_2' || type === 'columns_3') {
      addRow(type === 'columns_2' ? 2 : 3, false);
      return;
    }
    col.widgets.push(newWidget(type));
    state.selected = col.widgets[col.widgets.length - 1].id;
    render();
  }

  function findRow(id) { return state.rows.find(row => row.id === id); }
  function findCol(rowId, colId) { return findRow(rowId)?.cols.find(col => col.id === colId); }
  function findWidget(widgetId) {
    for (const row of state.rows) for (const col of row.cols) {
      const widget = col.widgets.find(item => item.id === widgetId);
      if (widget) return { row, col, w: widget };
    }
    return null;
  }

  function render() {
    const canvas = $('#bpCanvas');
    if (!canvas) return;
    canvas.innerHTML = '';
    normalize();
    if (!state.rows.length) {
      canvas.innerHTML = '<div class="bp-empty">Henüz satır yok. Satır ekleyerek başlayın.</div>';
      return;
    }
    state.rows.forEach((row, index) => canvas.appendChild(rowEl(row, index)));
    persist();
    renderInspector();
    bindCanvas();
  }

  function rowEl(row, index) {
    const element = document.createElement('div');
    element.className = 'bp-row';
    element.dataset.rowId = row.id;
    element.innerHTML = `<div class="bp-row-head"><span class="bp-row-drag" draggable="true" title="Satırı sürükle">⋮⋮</span><span class="bp-row-title">Satır ${index + 1} · ${row.cols.length} kolon</span><span class="bp-row-actions"><button type="button" class="bp-mini" data-row-up="${row.id}">↑</button><button type="button" class="bp-mini" data-row-down="${row.id}">↓</button><button type="button" class="bp-mini" data-row-add-col="${row.id}">+ Kolon</button><button type="button" class="bp-mini danger" data-row-del="${row.id}">Sil</button></span></div><div class="bp-grid"></div>`;
    const grid = $('.bp-grid', element);
    row.cols.forEach(col => grid.appendChild(colEl(row, col)));
    return element;
  }

  function colEl(row, col) {
    const element = document.createElement('div');
    element.className = 'bp-col';
    element.dataset.colId = `${row.id}:${col.id}`;
    element.style.setProperty('--span', col.span);
    element.innerHTML = `<div class="bp-col-tools"><button type="button" class="bp-mini bp-add-block" data-add="${row.id}:${col.id}">+ Blok</button><button type="button" class="bp-mini bp-col-tune" data-less="${row.id}:${col.id}">Daralt</button><button type="button" class="bp-mini bp-col-tune" data-more="${row.id}:${col.id}">Genişlet</button><button type="button" class="bp-mini danger bp-col-tune" data-col-del="${row.id}:${col.id}">Sil</button></div><div class="bp-col-drop-hint" data-add="${row.id}:${col.id}">+ Blok ekle</div><div class="bp-col-resize-handle" data-resize="${row.id}:${col.id}" title="Kolonu sürükle"></div>`;
    col.widgets.forEach(widget => element.appendChild(widgetEl(widget)));
    return element;
  }

  function widgetEl(widget) {
    const element = document.createElement('div');
    element.className = 'bp-widget ' + (state.selected === widget.id ? 'selected ' : '') + (widget.props?.hidden ? 'is-hidden ' : '');
    element.dataset.wid = widget.id;
    element.draggable = true;
    if (widget.props?.minHeight) element.style.minHeight = widget.props.minHeight;
    if (widget.props?.background) element.style.background = widget.props.background;
    if (widget.props?.radius) element.style.borderRadius = widget.props.radius;
    if (widget.props?.padding) element.style.padding = widget.props.padding;
    if (widget.props?.textColor) element.style.color = widget.props.textColor;
    if (widget.props?.maxWidth) element.style.maxWidth = widget.props.maxWidth;
    element.style.setProperty('--bp-btn-bg', widget.props?.buttonBg || '');
    element.style.setProperty('--bp-btn-color', widget.props?.buttonColor || '');
    element.innerHTML = `<div class="bp-widget-top"><span class="bp-widget-type">${esc(blockLabels[widget.type] || widget.type)}</span><span class="bp-widget-badges">${widget.props?.hideDesktop ? 'D ' : ''}${widget.props?.hideTablet ? 'T ' : ''}${widget.props?.hideMobile ? 'M ' : ''}</span></div><div class="bp-widget-content" contenteditable="true" data-inline-edit="${widget.id}">${renderWidget(widget)}</div><div class="bp-widget-actions"><button type="button" class="bp-mini" data-select="${widget.id}">Ayarlar</button><button type="button" class="bp-mini" data-copy="${widget.id}">Kopyala</button><button type="button" class="bp-mini danger" data-del="${widget.id}">Sil</button></div><div class="bp-widget-resize-handle" title="Yüksekliği değiştir">⋮⋮</div>`;
    return element;
  }

  function renderWidget(widget) {
    const media = widget.props?.imageUrl ? `<div class="bp-widget-media" style="background-image:url('${esc(widget.props.imageUrl)}')"></div>` : '';
    const text = widgetText(widget);
    if (widget.type === 'slider') return `${media}<h3>${esc(widget.title || 'Slider')}</h3><p>${esc(text || 'Menü altında görünen slider alanı')}</p><div class="bp-chip-row"><span class="cta">Slider Aktif</span><span class="cta green">Menü Altı</span></div>`;
    if (widget.type === 'button') return `<span class="cta">${esc(widget.button || widget.title || 'Buton')}</span>`;
    if (widget.type === 'spacer') return '<div class="bp-spacer-preview">Boşluk / Ayırıcı</div>';
    if (widget.type === 'html') return `<div>${text || ''}</div>`;
    if (['product', 'pricing', 'kpi', 'hosting', 'vps', 'ssl', 'campaign'].includes(widget.type)) return `${media}<h3>${esc(widget.title)}</h3><p>${esc(text)}</p><div class="price">${esc(widget.price || '')}</div>${widget.button ? `<span class="cta">${esc(widget.button)}</span>` : ''}`;
    if (widget.type === 'hero') return `${media}<h2>${esc(widget.title)}</h2><p>${esc(text)}</p><span class="cta">${esc(widget.button || 'Başla')}</span>`;
    if (widget.type === 'domain') return `${media}<h3>${esc(widget.title)}</h3><p>${esc(text)}</p><div class="bp-fake-input">ornekdomain.com <span>${esc(widget.button || 'Sorgula')}</span></div>`;
    if (widget.type === 'support_widget') return `<h3>${esc(widget.title)}</h3><p>${esc(text)}</p><div class="bp-chip-row"><span class="cta">Destek</span><span class="cta green">WhatsApp</span><span class="cta dark">Ticket</span></div>`;
    return `${media}<h3>${esc(widget.title || 'Blok')}</h3><p>${esc(text)}</p>${widget.button ? `<span class="cta">${esc(widget.button)}</span>` : ''}`;
  }

  function bindCanvas() {
    $$('[data-add]').forEach(button => button.addEventListener('click', event => { event.stopPropagation(); const [rowId, colId] = button.dataset.add.split(':'); openBlockPicker(rowId, colId); }));
    $$('[data-less]').forEach(button => button.addEventListener('click', () => resizeCol(button.dataset.less, -1)));
    $$('[data-more]').forEach(button => button.addEventListener('click', () => resizeCol(button.dataset.more, 1)));
    $$('[data-col-del]').forEach(button => button.addEventListener('click', () => deleteCol(button.dataset.colDel)));
    $$('[data-row-add-col]').forEach(button => button.addEventListener('click', () => addCol(button.dataset.rowAddCol)));
    $$('[data-row-del]').forEach(button => button.addEventListener('click', () => { snap(); state.rows = state.rows.filter(row => row.id !== button.dataset.rowDel); render(); }));
    $$('[data-row-up]').forEach(button => button.addEventListener('click', () => moveRow(button.dataset.rowUp, -1)));
    $$('[data-row-down]').forEach(button => button.addEventListener('click', () => moveRow(button.dataset.rowDown, 1)));
    $$('[data-select]').forEach(button => button.addEventListener('click', () => { state.selected = button.dataset.select; render(); }));
    $$('[data-del]').forEach(button => button.addEventListener('click', () => deleteWidget(button.dataset.del)));
    $$('[data-copy]').forEach(button => button.addEventListener('click', () => copyWidget(button.dataset.copy)));
    $$('.bp-widget').forEach(element => { element.addEventListener('dragstart', dragWidgetStart); element.addEventListener('dragover', event => event.preventDefault()); element.addEventListener('drop', dropOnWidget); });
    $$('.bp-col').forEach(element => { element.addEventListener('dragover', event => { event.preventDefault(); element.classList.add('bp-drag-over'); }); element.addEventListener('dragleave', () => element.classList.remove('bp-drag-over')); element.addEventListener('drop', dropOnCol); });
    $$('.bp-row-drag').forEach(handle => handle.addEventListener('dragstart', event => { state.dragRow = event.target.closest('.bp-row')?.dataset.rowId; event.dataTransfer.setData('bp-row', state.dragRow); }));
    $$('.bp-row').forEach(row => { row.addEventListener('dragover', event => event.preventDefault()); row.addEventListener('drop', dropOnRow); });
    $$('.bp-col-resize-handle').forEach(handle => handle.addEventListener('mousedown', startColResize));
    $$('.bp-widget-resize-handle').forEach(handle => handle.addEventListener('mousedown', startHeightResize));
    $$('[data-inline-edit]').forEach(element => { element.addEventListener('blur', () => applyInline(element)); element.addEventListener('keydown', event => { if (event.key === 'Enter' && event.ctrlKey) { event.preventDefault(); element.blur(); } }); });
  }

  function moveRow(id, direction) { const index = state.rows.findIndex(row => row.id === id); const target = index + direction; if (index < 0 || target < 0 || target >= state.rows.length) return; snap(); [state.rows[index], state.rows[target]] = [state.rows[target], state.rows[index]]; render(); }
  function addCol(rowId) { const row = findRow(rowId); if (!row || row.cols.length >= 10) return; snap(); row.cols.push({ id: uid(), span: 1, widgets: [] }); normalizeSpans(row); render(); }
  function deleteCol(key) { const [rowId, colId] = key.split(':'); const row = findRow(rowId); if (!row || row.cols.length <= 1) return; snap(); const col = row.cols.find(item => item.id === colId); row.cols = row.cols.filter(item => item.id !== colId); if (col?.widgets.length) row.cols[0].widgets.push(...col.widgets); normalizeSpans(row); render(); }
  function normalizeSpans(row) { const base = Math.floor(10 / row.cols.length) || 1; row.cols.forEach((col, index) => { col.span = index === row.cols.length - 1 ? 10 - base * (row.cols.length - 1) : base; }); }
  function resizeCol(key, direction) { const [rowId, colId] = key.split(':'); const col = findCol(rowId, colId); if (!col) return; snap(); col.span = clamp((parseInt(col.span, 10) || 1) + direction, 1, 10); render(); }
  function deleteWidget(id) { const hit = findWidget(id); if (!hit) return; snap(); hit.col.widgets = hit.col.widgets.filter(widget => widget.id !== id); if (state.selected === id) state.selected = null; render(); }
  function copyWidget(id) { const hit = findWidget(id); if (!hit) return; snap(); const next = JSON.parse(JSON.stringify(hit.w)); next.id = uid(); next.title = (next.title || 'Blok') + ' Kopya'; hit.col.widgets.push(next); state.selected = next.id; render(); }

  function openBlockPicker(rowId, colId) {
    closeBlockPicker();
    const overlay = document.createElement('div');
    overlay.className = 'bp-block-picker-backdrop';
    overlay.innerHTML = `<div class="bp-block-picker" role="dialog" aria-modal="true"><div class="bp-picker-head"><div><span>Blok Ekle</span><h3>Kolona ne eklemek istiyorsun?</h3></div><button type="button" data-bp-picker-close>x</button></div><input class="bp-picker-search" placeholder="Ara: domain, ürün, hero, fiyat, destek..."><div class="bp-picker-body">${blockGroups.map(group => `<section><h4>${esc(group.title)}</h4><div class="bp-picker-grid">${group.items.filter(key => defaults[key]).map(key => `<button type="button" data-pick-block="${esc(key)}"><b>${esc(blockLabels[key] || key)}</b><small>${esc(blockHints[key] || 'Blok ekle')}</small></button>`).join('')}</div></section>`).join('')}</div></div>`;
    document.body.appendChild(overlay);
    const search = $('.bp-picker-search', overlay);
    search?.focus();
    search?.addEventListener('input', () => { const query = search.value.toLowerCase(); $$('[data-pick-block]', overlay).forEach(button => { button.style.display = button.innerText.toLowerCase().includes(query) ? 'grid' : 'none'; }); });
    $$('[data-pick-block]', overlay).forEach(button => button.addEventListener('click', () => { addWidget(rowId, colId, button.dataset.pickBlock); closeBlockPicker(); }));
    overlay.addEventListener('click', event => { if (event.target === overlay) closeBlockPicker(); });
  }

  function closeBlockPicker() { $$('.bp-block-picker-backdrop').forEach(element => element.remove()); }
  function dragWidgetStart(event) { state.dragWidget = event.currentTarget.dataset.wid; event.dataTransfer.setData('bp-widget', state.dragWidget); }
  function dropOnWidget(event) { event.preventDefault(); const source = state.dragWidget || event.dataTransfer.getData('bp-widget'); const target = event.currentTarget.dataset.wid; if (source && target && source !== target) { snap(); const from = findWidget(source); const to = findWidget(target); if (from && to) { const fromIndex = from.col.widgets.indexOf(from.w); const toIndex = to.col.widgets.indexOf(to.w); from.col.widgets.splice(fromIndex, 1); to.col.widgets.splice(toIndex, 0, from.w); render(); } } }
  function dropOnCol(event) { event.preventDefault(); event.currentTarget.classList.remove('bp-drag-over'); const newType = event.dataTransfer.getData('bp-new-type'); const [rowId, colId] = event.currentTarget.dataset.colId.split(':'); if (newType) { addWidget(rowId, colId, newType); return; } const source = state.dragWidget || event.dataTransfer.getData('bp-widget'); if (source) { snap(); const hit = findWidget(source); const col = findCol(rowId, colId); if (hit && col) { hit.col.widgets = hit.col.widgets.filter(widget => widget.id !== source); col.widgets.push(hit.w); render(); } } }
  function dropOnRow(event) { const source = event.dataTransfer.getData('bp-row'); const target = event.currentTarget.dataset.rowId; if (source && target && source !== target) { event.preventDefault(); snap(); const sourceIndex = state.rows.findIndex(row => row.id === source); const targetIndex = state.rows.findIndex(row => row.id === target); const [row] = state.rows.splice(sourceIndex, 1); state.rows.splice(targetIndex, 0, row); render(); } }

  function startColResize(event) { event.preventDefault(); const key = event.currentTarget.dataset.resize; const [rowId, colId] = key.split(':'); const col = findCol(rowId, colId); if (!col) return; const startX = event.clientX; const startSpan = +col.span || 1; let moved = false; const move = pointer => { const delta = Math.round((pointer.clientX - startX) / 55); const next = clamp(startSpan + delta, 1, 10); if (next !== col.span) { if (!moved) snap(); moved = true; col.span = next; render(); } }; const up = () => { document.removeEventListener('mousemove', move); document.removeEventListener('mouseup', up); }; document.addEventListener('mousemove', move); document.addEventListener('mouseup', up); }
  function startHeightResize(event) { event.preventDefault(); event.stopPropagation(); const widgetId = event.currentTarget.closest('.bp-widget')?.dataset.wid; const hit = findWidget(widgetId); if (!hit) return; const startY = event.clientY; const startHeight = event.currentTarget.closest('.bp-widget').offsetHeight; let moved = false; const move = pointer => { const height = clamp(startHeight + (pointer.clientY - startY), 50, 900); hit.w.props = hit.w.props || {}; hit.w.props.minHeight = height + 'px'; moved = true; render(); }; const up = () => { if (moved) snap(); document.removeEventListener('mousemove', move); document.removeEventListener('mouseup', up); }; document.addEventListener('mousemove', move); document.addEventListener('mouseup', up); }
  function applyInline(element) { const hit = findWidget(element.dataset.inlineEdit); if (!hit) return; const title = element.querySelector('h2,h3'); const paragraph = element.querySelector('p'); const cta = element.querySelector('.cta'); snap(); if (title) hit.w.title = title.textContent.trim(); if (paragraph) hit.w.text = paragraph.textContent.trim(); if (cta && hit.w.type !== 'support_widget') hit.w.button = cta.textContent.trim(); render(); }

  function renderInspector() {
    const panel = $('#bpInspector');
    if (!panel) return;
    const hit = state.selected ? findWidget(state.selected) : null;
    if (!hit) {
      panel.innerHTML = '<p>Bir blok seçin. Seçince içerik, stil, görünürlük ve boyut ayarları burada açılır.</p>';
      return;
    }
    const widget = hit.w;
    const props = widget.props || {};
    panel.innerHTML = `<div class="bp-inspector-tabs"><button class="active">İçerik</button><button>Stil</button><button>Görünürlük</button></div><div class="bp-inspector-field"><label>Blok Tipi</label><select id="iType">${Object.keys(defaults).map(key => `<option value="${key}" ${widget.type === key ? 'selected' : ''}>${esc(blockLabels[key] || key)}</option>`).join('')}</select></div><div class="bp-inspector-field"><label>Başlık</label><input id="iTitle" value="${esc(widget.title)}"></div><div class="bp-inspector-field"><label>Metin / HTML</label><textarea id="iText">${esc(widgetText(widget))}</textarea></div><div class="bp-inspector-field"><label>Fiyat / Ek Değer</label><input id="iPrice" value="${esc(widget.price || '')}"></div><div class="bp-inspector-field"><label>Buton Yazısı</label><input id="iButton" value="${esc(widget.button || '')}"></div><div class="bp-inspector-field"><label>Görsel URL</label><input id="iImage" value="${esc(props.imageUrl || '')}" placeholder="public/uploads/gorsel.webp"></div><div class="bp-inspector-grid"><div class="bp-inspector-field"><label>Arka Plan</label><input id="iBg" value="${esc(props.background || '')}"></div><div class="bp-inspector-field"><label>Yazı Rengi</label><input id="iTextColor" value="${esc(props.textColor || '')}"></div><div class="bp-inspector-field"><label>Buton Rengi</label><input id="iButtonBg" value="${esc(props.buttonBg || '')}"></div><div class="bp-inspector-field"><label>Buton Yazı</label><input id="iButtonColor" value="${esc(props.buttonColor || '')}"></div><div class="bp-inspector-field"><label>Radius</label><input id="iRadius" value="${esc(props.radius || '')}"></div><div class="bp-inspector-field"><label>Padding</label><input id="iPadding" value="${esc(props.padding || '')}"></div><div class="bp-inspector-field"><label>Max Genişlik</label><input id="iMaxWidth" value="${esc(props.maxWidth || '')}"></div><div class="bp-inspector-field"><label>Min Yükseklik</label><input id="iHeight" value="${esc(props.minHeight || '')}"></div></div><label class="bp-check"><input id="iHidden" type="checkbox" ${props.hidden ? 'checked' : ''}> Bloku gizle</label><label class="bp-check"><input id="iDesk" type="checkbox" ${props.hideDesktop ? 'checked' : ''}> Desktop gizle</label><label class="bp-check"><input id="iTab" type="checkbox" ${props.hideTablet ? 'checked' : ''}> Tablet gizle</label><label class="bp-check"><input id="iMob" type="checkbox" ${props.hideMobile ? 'checked' : ''}> Mobil gizle</label><button type="button" class="bp-btn" id="iApply">Uygula</button><button type="button" class="bp-btn soft" id="iDuplicate">Kopyala</button><button type="button" class="bp-btn danger" id="iDelete">Kaldır</button>`;
    panel.insertAdjacentHTML('afterbegin', `<div class="bp-ai-box"><strong>AhostBuilder AI</strong><p>Seçili bloğu güvenli taslakla yenile.</p><textarea id="bpAiPrompt" placeholder="Örn: bu hero alanını koyu mavi premium SaaS görünümü yap"></textarea><div class="bp-ai-actions"><button type="button" class="bp-btn soft" id="bpAiRun">Öner</button><button type="button" class="bp-btn" id="bpAiApply" disabled>Uygula</button></div><small id="bpAiStatus"></small></div>`);
    $('#bpAiRun')?.addEventListener('click', () => runAhostBuilderAi(widget));
    $('#bpAiApply')?.addEventListener('click', () => applyAhostBuilderAi(widget));
    $('#iApply').addEventListener('click', () => {
      snap();
      widget.type = $('#iType').value;
      widget.title = $('#iTitle').value;
      widget.text = $('#iText').value;
      widget.price = $('#iPrice').value;
      widget.button = $('#iButton').value;
      widget.props = widget.props || {};
      widget.props.imageUrl = $('#iImage').value;
      widget.props.background = $('#iBg').value;
      widget.props.textColor = $('#iTextColor').value;
      widget.props.buttonBg = $('#iButtonBg').value;
      widget.props.buttonColor = $('#iButtonColor').value;
      widget.props.radius = $('#iRadius').value;
      widget.props.padding = $('#iPadding').value;
      widget.props.maxWidth = $('#iMaxWidth').value;
      widget.props.minHeight = $('#iHeight').value;
      widget.props.hidden = $('#iHidden').checked;
      widget.props.hideDesktop = $('#iDesk').checked;
      widget.props.hideTablet = $('#iTab').checked;
      widget.props.hideMobile = $('#iMob').checked;
      render();
    });
    $('#iDuplicate').addEventListener('click', () => copyWidget(widget.id));
    $('#iDelete').addEventListener('click', () => deleteWidget(widget.id));
  }

  async function runAhostBuilderAi(widget) {
    const prompt = $('#bpAiPrompt')?.value.trim() || '';
    const status = $('#bpAiStatus');
    const apply = $('#bpAiApply');
    if (!prompt) { if (status) status.textContent = 'Komut yazın.'; return; }
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="csrf_token"]')?.value || '';
    const fd = new FormData();
    fd.append('csrf_token', csrf);
    fd.append('prompt', prompt);
    fd.append('target', window.BUILDER_PRO_TARGET || $('#bp_target')?.value || 'site');
    fd.append('template', window.BUILDER_PRO_TEMPLATE || '');
    fd.append('selected_type', widget?.type || '');
    if (status) status.textContent = 'AhostBuilder AI düşünüyor...';
    if (apply) apply.disabled = true;
    try {
      const res = await fetch((window.AHOST_BASE_URL || '') + '/admin/ahostbuilder/assistant/run', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
      const json = await res.json();
      if (!json.ok) throw new Error(json.message || 'Öneri üretilemedi.');
      state.aiProposal = json.proposal || null;
      if (status) status.textContent = json.message || 'Öneri hazır.';
      if (apply) apply.disabled = !state.aiProposal;
    } catch (error) {
      state.aiProposal = null;
      if (status) status.textContent = 'AI önerisi alınamadı: ' + error.message;
    }
  }

  function applyAhostBuilderAi(widget) {
    if (!widget || !state.aiProposal) return;
    snap();
    const proposal = state.aiProposal;
    widget.type = proposal.type || widget.type;
    widget.title = proposal.title || widget.title;
    widget.text = proposal.text || widget.text || '';
    widget.button = proposal.button || widget.button || '';
    widget.props = { ...(widget.props || {}), ...(proposal.props || {}) };
    state.aiProposal = null;
    render();
  }

  function undo() { if (!state.history.length) return; state.future.push(JSON.stringify(state.rows)); state.rows = JSON.parse(state.history.pop()); state.selected = null; render(); }
  function redo() { if (!state.future.length) return; state.history.push(JSON.stringify(state.rows)); state.rows = JSON.parse(state.future.pop()); state.selected = null; render(); }
  function showJson() { const dialog = document.createElement('dialog'); dialog.className = 'bp-json-dialog'; dialog.innerHTML = `<form method="dialog"><h3>Builder JSON</h3><textarea>${esc(JSON.stringify(state.rows, null, 2))}</textarea><button class="bp-btn">Kapat</button></form>`; document.body.appendChild(dialog); dialog.showModal(); dialog.addEventListener('close', () => dialog.remove()); }
  function importTemplate() { if (!confirm('Mevcut düzenin üzerine hazır AhostBuilder şablonu yüklensin mi?')) return; snap(); state.rows = [{ id: uid(), cols: [{ id: uid(), span: 6, widgets: [newWidget('hero')] }, { id: uid(), span: 4, widgets: [newWidget('domain'), newWidget('support_widget')] }] }, { id: uid(), cols: [{ id: uid(), span: 3, widgets: [newWidget('kpi')] }, { id: uid(), span: 3, widgets: [newWidget('product')] }, { id: uid(), span: 2, widgets: [newWidget('invoice')] }, { id: uid(), span: 2, widgets: [newWidget('ticket')] }] }, { id: uid(), cols: [{ id: uid(), span: 5, widgets: [newWidget('pricing')] }, { id: uid(), span: 5, widgets: [newWidget('faq')] }] }]; render(); }
  function importTargetTemplate() {
    if (!confirm('Mevcut düzenin üzerine bu hedefe uygun şablon yüklensin mi?')) return;
    snap();
    const target = window.BUILDER_PRO_TARGET || $('#bp_target')?.value || 'site';
    if (target === 'admin') {
      state.rows = [{ id: uid(), cols: [{ id: uid(), span: 2, widgets: [newWidget('kpi')] }, { id: uid(), span: 2, widgets: [newWidget('kpi')] }, { id: uid(), span: 3, widgets: [newWidget('chart')] }, { id: uid(), span: 3, widgets: [newWidget('ai_builder')] }] }, { id: uid(), cols: [{ id: uid(), span: 3, widgets: [newWidget('domain')] }, { id: uid(), span: 3, widgets: [newWidget('hosting')] }, { id: uid(), span: 2, widgets: [newWidget('invoice')] }, { id: uid(), span: 2, widgets: [newWidget('ticket')] }] }];
    } else if (target === 'customer') {
      state.rows = [{ id: uid(), cols: [{ id: uid(), span: 10, widgets: [newWidget('tabs')] }] }, { id: uid(), cols: [{ id: uid(), span: 4, widgets: [newWidget('renewal')] }, { id: uid(), span: 3, widgets: [newWidget('product')] }, { id: uid(), span: 3, widgets: [newWidget('invoice')] }] }, { id: uid(), cols: [{ id: uid(), span: 5, widgets: [newWidget('domain')] }, { id: uid(), span: 5, widgets: [newWidget('ticket')] }] }];
    } else {
      state.rows = [{ id: uid(), cols: [{ id: uid(), span: 10, widgets: [newWidget('header')] }] }, { id: uid(), cols: [{ id: uid(), span: 5, widgets: [newWidget('hero')] }, { id: uid(), span: 3, widgets: [newWidget('domain')] }, { id: uid(), span: 2, widgets: [newWidget('support_widget')] }] }, { id: uid(), cols: [{ id: uid(), span: 3, widgets: [newWidget('hosting')] }, { id: uid(), span: 3, widgets: [newWidget('vps')] }, { id: uid(), span: 2, widgets: [newWidget('ssl')] }, { id: uid(), span: 2, widgets: [newWidget('campaign')] }] }, { id: uid(), cols: [{ id: uid(), span: 5, widgets: [newWidget('blog')] }, { id: uid(), span: 5, widgets: [newWidget('footer')] }] }];
    }
    render();
  }

  document.addEventListener('DOMContentLoaded', init);
})();
