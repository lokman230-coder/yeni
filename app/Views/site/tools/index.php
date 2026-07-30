<?php
require_once __DIR__ . '/../../../Services/SiteToolsService.php';

$catalog = function_exists('ao_site_tools_catalog') ? ao_site_tools_catalog() : [];
$categories = function_exists('ao_site_tools_categories') ? ao_site_tools_categories() : [];
$state = function_exists('ao_site_tools_usage_state') ? ao_site_tools_usage_state() : [
    'entitled' => false,
    'used' => 0,
    'limit' => 3,
    'remaining' => 3,
    'reason' => 'Misafir',
];
$remainingText = !empty($state['entitled'])
    ? 'Sınırsız'
    : ((int)($state['remaining'] ?? 0) . ' / ' . (int)($state['limit'] ?? 3));
?>
<section class="ao-site-content ao-tools-page" data-site-tools-page>
    <div class="ao-content-shell">
        <?php $managedHero = function_exists('ao_site_hero_render') ? ao_site_hero_render('site-araclari', ['title'=>'SEO, domain ve teknik kontroller tek ekranda.']) : ''; ?>
        <?php if ($managedHero): ?>
            <?= $managedHero ?>
        <?php endif; ?>
        <div class="ao-tools-limit ao-tools-limit-strip">
            <strong><?= !empty($state['entitled']) ? 'Müşteri erişimi aktif' : 'Ücretsiz kullanım' ?></strong>
            <span>Kalan hak: <?= e($remainingText) ?></span>
        </div>

        <section class="ao-tools-toolbar">
            <label class="ao-tools-search">
                <span>Araç ara</span>
                <input type="search" data-tool-search placeholder="whois, seo, gzip, kdv...">
            </label>
            <div class="ao-tools-filters" aria-label="Araç kategorileri">
                <button type="button" class="active" data-tool-filter="all">Tümü</button>
                <?php foreach ($categories as $category): ?>
                    <button type="button" data-tool-filter="<?= e($category) ?>"><?= e($category) ?></button>
                <?php endforeach; ?>
            </div>
            <div class="ao-tools-type-controls" aria-label="Filtre yazı boyutu">
                <button type="button" data-tool-filter-type="down">A-</button>
                <button type="button" data-tool-filter-type="up">A+</button>
            </div>
        </section>

        <div class="ao-tools-card-grid">
            <?php foreach ($catalog as $tool): ?>
                <?php
                $toolTitle = trim((string)admin_setting('site_tool_title_' . $tool['key'], $tool['title']));
                if ($toolTitle === '') $toolTitle = $tool['title'];
                $toolBg = trim((string)admin_setting('site_tool_card_bg_' . $tool['key'], ''));
                $toolColor = trim((string)admin_setting('site_tool_card_color_' . $tool['key'], ''));
                $toolStyle = [];
                if ($toolBg !== '') $toolStyle[] = "--tool-card-bg-image:url('" . e($toolBg) . "')";
                if ($toolColor !== '' && preg_match('/^#[0-9a-f]{3,8}$/i', $toolColor)) $toolStyle[] = "--tool-card-bg-color:" . e($toolColor);
                ?>
                <button type="button"
                    class="ao-tools-card-mini"
                    <?= $toolStyle ? 'style="' . implode(';', $toolStyle) . '"' : '' ?>
                    data-tool-card
                    data-tool="<?= e($tool['key']) ?>"
                    data-title="<?= e($toolTitle) ?>"
                    data-category="<?= e($tool['category']) ?>"
                    data-input="<?= e($tool['input'] ?? 'url') ?>"
                    data-placeholder="<?= e($tool['placeholder'] ?? '') ?>">
                    <span><?= e($tool['abbr'] ?? 'AO') ?></span>
                    <b><?= e($toolTitle) ?></b>
                    <small><?= e($tool['description']) ?></small>
                    <em><?= e($tool['category']) ?></em>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="ao-site-tool-modal" data-tool-modal hidden>
        <div class="ao-site-tool-backdrop" data-site-tool-close></div>
        <div class="ao-site-tool-dialog" role="dialog" aria-modal="true" aria-labelledby="aoSiteToolTitle">
            <button type="button" class="ao-site-tool-close" data-site-tool-close aria-label="Kapat">&times;</button>
            <div class="ao-site-tool-head">
                <span data-tool-category>Site Araçları</span>
                <h2 id="aoSiteToolTitle" data-tool-title>Araç</h2>
                <p data-tool-help>Sorgu bilgisini girin ve sonucu alın.</p>
            </div>
            <form class="ao-site-tool-form" data-tool-form>
                <input type="hidden" name="tool" data-tool-key>
                <label data-tool-input-wrap>
                    <span data-tool-input-label>Sorgu</span>
                    <textarea name="target" rows="3" data-tool-target placeholder="ornekdomain.com"></textarea>
                </label>
                <div class="ao-site-tool-actions">
                    <button type="submit">Sorgula</button>
                    <button type="button" class="soft" data-site-tool-close>Vazgeç</button>
                </div>
            </form>
            <div class="ao-site-tool-result" data-tool-result></div>
        </div>
    </div>
</section>

<script>
(function(){
  var page = document.querySelector('[data-site-tools-page]');
  if (!page) return;
  var modal = page.querySelector('[data-tool-modal]');
  if (modal && modal.parentElement !== document.body) document.body.appendChild(modal);
  if (!modal) return;
  var form = modal.querySelector('[data-tool-form]');
  var target = modal.querySelector('[data-tool-target]');
  var result = modal.querySelector('[data-tool-result]');
  var title = modal.querySelector('[data-tool-title]');
  var categoryLabel = modal.querySelector('[data-tool-category]');
  var help = modal.querySelector('[data-tool-help]');
  var toolKey = modal.querySelector('[data-tool-key]');
  var inputWrap = modal.querySelector('[data-tool-input-wrap]');
  var inputLabel = modal.querySelector('[data-tool-input-label]');
  var activeCard = null;
  var filterFontSize = Number(localStorage.getItem('aoToolsFilterFontSize') || 12);

  function readToolJson(response) {
    return response.text().then(function(raw){
      raw = String(raw || '').replace(/^[\uFEFF\u200B\s]+/, '');
      try { return JSON.parse(raw); }
      catch (err) {
        throw new Error('JSON beklenirken farklı cevap alındı: ' + raw.slice(0, 160));
      }
    });
  }

  function baseUrl() {
    var meta = document.querySelector('meta[name="ahost-base-url"]');
    return ((window.AHOST_BASE_URL || '') || (meta ? meta.getAttribute('content') : '') || '').replace(/\/$/, '');
  }

  function setFilterFontSize(size) {
    filterFontSize = Math.max(10, Math.min(14, Number(size) || 12));
    page.style.setProperty('--tool-filter-font-size', filterFontSize + 'px');
    try { localStorage.setItem('aoToolsFilterFontSize', String(filterFontSize)); } catch (e) {}
  }

  function openTool(card) {
    activeCard = card;
    title.textContent = card.dataset.title || 'Araç';
    categoryLabel.textContent = card.dataset.category || 'Site Araçları';
    help.textContent = card.querySelector('small') ? card.querySelector('small').textContent : 'Sorgu bilgisini girin.';
    toolKey.value = card.dataset.tool || '';
    target.value = '';
    target.placeholder = card.dataset.placeholder || '';
    var inputType = card.dataset.input || 'url';
    inputLabel.textContent = inputType === 'none' ? 'Bu araç ek bilgi istemez' : 'Sorgu';
    inputWrap.style.display = inputType === 'none' ? 'none' : 'grid';
    result.innerHTML = '';
    modal.hidden = false;
    document.body.classList.add('ao-site-tool-modal-open');
    setTimeout(function(){ if (inputType !== 'none') target.focus(); }, 40);
  }

  function closeTool() {
    modal.hidden = true;
    result.innerHTML = '';
    var dialog = modal.querySelector('.ao-site-tool-dialog');
    if (dialog) dialog.classList.remove('is-report-wide');
    document.body.classList.remove('ao-site-tool-modal-open');
  }

  function applyFilter() {
    var q = (page.querySelector('[data-tool-search]').value || '').toLowerCase();
    var active = page.querySelector('[data-tool-filter].active');
    var category = active ? (active.dataset.toolFilter || 'all') : 'all';
    page.querySelectorAll('[data-tool-card]').forEach(function(card){
      var text = (card.dataset.title + ' ' + card.dataset.category + ' ' + card.textContent).toLowerCase();
      var okText = !q || text.indexOf(q) > -1;
      var okCat = category === 'all' || card.dataset.category === category;
      card.style.display = (okText && okCat) ? '' : 'none';
    });
  }

  setFilterFontSize(filterFontSize);

  page.addEventListener('click', function(e){
    var card = e.target.closest('[data-tool-card]');
    if (card) { openTool(card); return; }
    var typeBtn = e.target.closest('[data-tool-filter-type]');
    if (typeBtn) {
      setFilterFontSize(filterFontSize + (typeBtn.dataset.toolFilterType === 'up' ? 1 : -1));
      return;
    }
    var filter = e.target.closest('[data-tool-filter]');
    if (filter) {
      page.querySelectorAll('[data-tool-filter]').forEach(function(button){ button.classList.remove('active'); });
      filter.classList.add('active');
      applyFilter();
    }
  });

  modal.addEventListener('click', function(e){
    var close = e.target.closest('[data-site-tool-close]');
    if (close) { closeTool(); }
  });

  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && !modal.hidden) closeTool();
  });

  page.querySelector('[data-tool-search]').addEventListener('input', applyFilter);
  target.addEventListener('keydown', function(e){
    if (e.key !== 'Enter' || e.shiftKey || e.ctrlKey || e.altKey || e.metaKey || modal.hidden) return;
    e.preventDefault();
    form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit', {cancelable:true, bubbles:true}));
  });

  form.addEventListener('submit', async function(e){
    e.preventDefault();
    if (!activeCard) return;
    result.innerHTML = '<div class="ao-tool-result-card ao-tool-loading"><strong>Sorgulanıyor...</strong></div>';
    var payload = new URLSearchParams();
    payload.set('tool', activeCard.dataset.tool || '');
    payload.set('target', target.value || '');
    try {
      var response = await fetch(baseUrl() + '/api/site-tool-run', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},
        body: payload.toString()
      });
      var data = await readToolJson(response);
      result.innerHTML = data.html || '<div class="ao-modal-error">Sonuç alınamadı.</div>';
      var dialog = modal.querySelector('.ao-site-tool-dialog');
      if (dialog) {
        dialog.classList.toggle('is-report-wide', !!result.querySelector('.ao-whois-premium,.ao-tool-whois-report,.ao-dns-premium,.ao-ssl-premium,.ao-domain-appraisal,.ao-site-analysis-report,.ao-seo-analysis-report'));
      }
    } catch (err) {
      result.innerHTML = '<div class="ao-modal-error">Araç sonucu okunamadı. Lütfen birkaç saniye sonra tekrar deneyin.</div>';
      if (window.console) console.warn('[site-tool-run]', err);
    }
  });
})();
</script>

