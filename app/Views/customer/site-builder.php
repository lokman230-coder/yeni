<?php
/**
 * Ahost One v25.0.0 RC20 - Müşteri Site Builder
 * Müşterilerin kendi sitelerini düzenleyebileceği canlı builder
 */
require_customer();

// Get customer data
$customer = current_customer();
$customerId = (int)($customer['id'] ?? 0);

// Get customer's sitebuilder projects
$projects = [];
try {
    $q = db()->prepare('SELECT * FROM sitebuilder_projects WHERE customer_id=? ORDER BY id DESC LIMIT 10');
    $q->execute([$customerId]);
    $projects = $q->fetchAll();
} catch (Throwable $e) {}

$sitebuilderAiEnabled = function_exists('admin_setting') ? admin_setting('sitebuilder_ai_edit', '1') !== '0' : true;
$sitebuilderDefaultProvider = function_exists('admin_setting') ? (string)admin_setting('default_ai_provider', 'gemini') : 'gemini';
$sitebuilderAiProviders = [
    'gemini' => 'Gemini',
    'openai' => 'ChatGPT / OpenAI',
    'openrouter' => 'OpenRouter',
    'groq' => 'Groq',
    'local' => 'Yerel Taslak',
];

// Get current project/page if editing
$projectId = (int)($_GET['project_id'] ?? 0);
$pageId = (int)($_GET['page_id'] ?? 0);

$currentProject = null;
$currentPage = null;

if ($projectId) {
    try {
        $q = db()->prepare('SELECT * FROM sitebuilder_projects WHERE id=? AND customer_id=? LIMIT 1');
        $q->execute([$projectId, $customerId]);
        $currentProject = $q->fetch();
    } catch (Throwable $e) {}
}

if ($pageId) {
    try {
        $q = db()->prepare('SELECT p.* FROM sitebuilder_pages p INNER JOIN sitebuilder_projects sp ON sp.id=p.project_id WHERE p.id=? AND sp.customer_id=? LIMIT 1');
        $q->execute([$pageId, $customerId]);
        $currentPage = $q->fetch();
    } catch (Throwable $e) {}
}

// If no page selected but we have a project
if (!$currentPage && $currentProject) {
    try {
        $q = db()->prepare('SELECT * FROM sitebuilder_pages WHERE project_id=? ORDER BY id ASC LIMIT 1');
        $q->execute([$projectId]);
        $currentPage = $q->fetch();
    } catch (Throwable $e) {}
}

// Get pages for current project
$pages = [];
if ($currentProject) {
    try {
        $q = db()->prepare('SELECT * FROM sitebuilder_pages WHERE project_id=? ORDER BY id ASC');
        $q->execute([$projectId]);
        $pages = $q->fetchAll();
    } catch (Throwable $e) {}
}

// Check if customer has permission
$canEdit = $currentProject && ((int)$currentProject['customer_id'] === $customerId);
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $currentPage ? e($currentPage['title']) : 'Site Builder' ?> - Ahost One</title>
<?php /* Standalone customer builder shell: only core tokens/reset and builder area CSS. */ ?>
    <link rel="stylesheet" href="<?= assetv('css/core/tokens.css') ?>">
    <link rel="stylesheet" href="<?= assetv('css/core/reset.css') ?>">
    <link rel="stylesheet" href="<?= assetv('css/core/typography.css') ?>">
    <link rel="stylesheet" href="<?= assetv('css/areas/builder/builder.css') ?>">
</head>
<body class="customer-builder-mode">
    <?php if ($currentPage && $canEdit): ?>
    <!-- CUSTOMER LIVE BUILDER MODE -->
    <div class="ao-builder-bar">
        <div class="ao-builder-bar-left">
            <a href="<?= url('client/site-builder') ?>" class="ao-btn soft">← Projelerim</a>
            <span class="ao-builder-divider">|</span>
            <span><strong><?= e($currentProject['name'] ?? '') ?></strong></span>
            <span class="ao-builder-sep">→</span>
            <span><?= e($currentPage['title']) ?></span>
        </div>
        <div class="ao-builder-bar-right">
            <a class="ao-btn soft" target="_blank" href="<?= url('sitebuilder/preview?id='.$pageId) ?>">👁 Önizle</a>
            <button class="ao-btn primary" id="savePage">💾 Kaydet</button>
        </div>
    </div>
    
    <form method="post" action="<?= url('client/site-builder/page-save') ?>" id="customerBuilderForm" class="cp-hidden-form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $pageId ?>">
        <input type="hidden" id="customer_builder_json" name="builder_json" value='<?= e($currentPage['builder_json'] ?? '') ?>'>
    </form>
    
    <div class="lb-wrapper">
        <!-- SIDEBAR -->
        <div class="lb-sidebar">
            <div class="lb-sidebar-section">
                <h3>Öğe Ekle</h3>
                <div class="lb-widget-grid">
                    <div class="lb-widget" data-widget-type="heading" draggable="true">
                        <span class="lb-widget-icon">H</span>
                        <span class="lb-widget-label">Başlık</span>
                    </div>
                    <div class="lb-widget" data-widget-type="text" draggable="true">
                        <span class="lb-widget-icon">¶</span>
                        <span class="lb-widget-label">Metin</span>
                    </div>
                    <div class="lb-widget" data-widget-type="button" draggable="true">
                        <span class="lb-widget-icon">▶</span>
                        <span class="lb-widget-label">Buton</span>
                    </div>
                    <div class="lb-widget" data-widget-type="image" draggable="true">
                        <span class="lb-widget-icon">🖼</span>
                        <span class="lb-widget-label">Görsel</span>
                    </div>
                    <div class="lb-widget" data-widget-type="radio_player" draggable="true">
                        <span class="lb-widget-icon">📻</span>
                        <span class="lb-widget-label">Radyo Player</span>
                    </div>
                    <div class="lb-widget" data-widget-type="now_playing" draggable="true">
                        <span class="lb-widget-icon">🎧</span>
                        <span class="lb-widget-label">Şu An Çalan</span>
                    </div>
                </div>
            </div>
            
            <div class="lb-sidebar-section">
                <h3>Bloklar</h3>
                <div class="lb-widget-grid">
                    <div class="lb-widget" data-widget-type="feature" draggable="true">
                        <span class="lb-widget-icon">⭐</span>
                        <span class="lb-widget-label">Özellik</span>
                    </div>
                    <div class="lb-widget" data-widget-type="price" draggable="true">
                        <span class="lb-widget-icon">₺</span>
                        <span class="lb-widget-label">Fiyat</span>
                    </div>
                    <div class="lb-widget" data-widget-type="divider" draggable="true">
                        <span class="lb-widget-icon">―</span>
                        <span class="lb-widget-label">Ayraç</span>
                    </div>
                    <div class="lb-widget" data-widget-type="spacer" draggable="true">
                        <span class="lb-widget-icon">↕</span>
                        <span class="lb-widget-label">Boşluk</span>
                    </div>
                    <div class="lb-widget" data-widget-type="song_request" draggable="true">
                        <span class="lb-widget-icon">🎵</span>
                        <span class="lb-widget-label">Şarkı İsteği</span>
                    </div>
                    <div class="lb-widget" data-widget-type="form" draggable="true">
                        <span class="lb-widget-icon">✉</span>
                        <span class="lb-widget-label">Form</span>
                    </div>
                    <div class="lb-widget" data-widget-type="map" draggable="true">
                        <span class="lb-widget-icon">📍</span>
                        <span class="lb-widget-label">Harita</span>
                    </div>
                    <div class="lb-widget" data-widget-type="notification" draggable="true">
                        <span class="lb-widget-icon">🔔</span>
                        <span class="lb-widget-label">Duyuru</span>
                    </div>
                    <div class="lb-widget" data-widget-type="cart" draggable="true">
                        <span class="lb-widget-icon">🛒</span>
                        <span class="lb-widget-label">Sepet</span>
                    </div>
                </div>
            </div>
            
            <div class="lb-sidebar-section">
                <h3>Sayfalarım</h3>
                <div class="cp-builder-page-list">
                    <?php foreach ($pages as $pg): ?>
                    <a href="<?= url('client/site-builder?project_id='.$projectId.'&page_id='.$pg['id']) ?>" 
                       class="ao-btn soft cp-builder-page-link <?= $pg['id'] == $pageId ? 'primary' : '' ?>">
                        ✏️ <?= e($pg['title']) ?>
                        <?php if ($pg['status'] === 'published'): ?>
                        <span class="cp-builder-page-status live">✓</span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- CANVAS -->
        <div class="lb-canvas-wrap">
            <div class="lb-toolbar">
                <button type="button" class="lb-toolbar-btn" id="lbUndo" title="Geri Al (Ctrl+Z)">↩ Geri</button>
                <button type="button" class="lb-toolbar-btn" id="lbRedo" title="Yinele (Ctrl+Shift+Z)">↪ İleri</button>
                <div class="lb-toolbar-sep"></div>
                <button type="button" class="lb-toolbar-btn primary" id="lbSaveCustomer">💾 Kaydet</button>
                <span class="cp-builder-save-note">Tüm değişiklikler otomatik kaydedilir</span>
            </div>
            
            <div class="lb-canvas" id="lbCanvas"></div>
        </div>
        
        <!-- INSPECTOR -->
        <div class="lb-inspector" id="lbInspector">
            <div class="cp-builder-empty-state">
                <p class="cp-builder-empty-emoji">🎨</p>
                <p class="cp-builder-empty-text">Düzenlemek istediğiniz öğeye tıklayın</p>
            </div>
        </div>
    </div>
    
    <script src="<?= assetv('js/admin/ahost-builder4-core.js') ?>"></script>
    <script src="<?= assetv('js/admin/live-builder.js') ?>"></script>
    <script>
    function syncCustomerBuilderJson() {
        if (window.LiveBuilder) window.LiveBuilder.saveState();
        var target = document.getElementById('customer_builder_json');
        var source = document.getElementById('live_builder_json') || target;
        if (target && source) target.value = source.value || '[]';
    }
    document.getElementById('savePage').addEventListener('click', function() {
        syncCustomerBuilderJson();
        document.getElementById('customerBuilderForm').submit();
    });
    
    document.getElementById('lbSaveCustomer').addEventListener('click', function() {
        syncCustomerBuilderJson();
        document.getElementById('customerBuilderForm').submit();
    });
    </script>
    
    <?php else: ?>
    <!-- PROJECT LIST -->
    <div class="cp-builder-project-shell">
        <div class="ao-card">
            <div class="cp-builder-project-head">
                <div>
                    <h2 class="cp-builder-title">Site Builder</h2>
                    <p class="cp-builder-subtitle">Kendi web sitenizi oluşturun ve düzenleyin</p>
                </div>
                <div class="cp-builder-project-actions">
                    <form method="post" action="<?= url('client/site-builder/project-create') ?>" class="cp-builder-project-form">
                        <?= csrf_field() ?>
                        <input name="name" value="Web Sitem" class="cp-builder-project-input">
                        <button class="ao-btn primary" type="submit">➕ Yeni Proje Oluştur</button>
                    </form>
                    <a href="<?= url('client/dashboard') ?>" class="ao-btn soft">← Panele Dön</a>
                </div>
            </div>

            <?php if ($sitebuilderAiEnabled): ?>
            <div class="ao-card ao-builder-ai-assist" id="ai-yardimi">
                <div class="ao-ai-head">
                    <div>
                        <span class="ao-ai-badge">AI Yardımı</span>
                        <h3>Web siteniz için AI sohbeti başlatın</h3>
                        <p>İstediğiniz siteyi yazın, hangi AI sağlayıcısından yardım almak istediğinizi seçin; sayfa yapısı ve ilk içerik taslak olarak hazırlansın.</p>
                    </div>
                    <div class="ao-ai-provider-row" aria-label="AI sağlayıcıları">
                        <?php foreach ($sitebuilderAiProviders as $providerLabel): ?>
                            <span><?= e($providerLabel) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <form class="ao-ai-chat-form" method="post" action="<?= url('client/site-builder/project-create') ?>">
                    <?= csrf_field() ?>
                    <div class="ao-ai-chat-toolbar two">
                        <label class="ao-ai-chat-field">Proje Adı
                            <input name="name" value="AI Web Sitem">
                        </label>
                        <label class="ao-ai-chat-field">AI Sağlayıcı
                            <select name="ai_provider">
                                <?php foreach ($sitebuilderAiProviders as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= $sitebuilderDefaultProvider === $key || ($sitebuilderDefaultProvider === 'chatgpt' && $key === 'openai') ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <label class="ao-ai-chat-field">AI’ye ne yaptırmak istiyorsunuz?
                        <textarea name="ai_prompt" rows="5" required placeholder="Örn: Radyo sitesi, canlı yayın, istek hattı, sosyal medya bağlantıları, paketler ve iletişim bölümü olsun. İlk ekran modern, hızlı ve mobil uyumlu görünsün."></textarea>
                    </label>
                    <div class="ao-ai-chat-send">
                        <button class="ao-btn primary" type="submit">AI Sohbeti Başlat</button>
                        <small>API yanıt vermezse güvenli taslak otomatik oluşturulur.</small>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <?php if (empty($projects)): ?>
            <div class="cp-builder-empty-project">
                <p class="cp-builder-empty-emoji">🚀</p>
                <h3 class="cp-builder-empty-title">Henüz projeniz yok</h3>
                <p class="cp-builder-empty-copy">Yeni proje oluşturarak ilk sayfanızı düzenlemeye başlayabilirsiniz.</p>
                <form method="post" action="<?= url('client/site-builder/project-create') ?>" class="cp-builder-empty-form">
                    <?= csrf_field() ?>
                    <input name="name" value="Web Sitem" class="cp-builder-project-input">
                    <button class="ao-btn primary" type="submit">Site Oluştur</button>
                </form>
            </div>
            <?php else: ?>
            <div class="cp-builder-project-grid">
                <?php foreach ($projects as $proj): ?>
                <div class="ao-card cp-builder-project-card">
                    <h4 class="cp-builder-project-name"><?= e($proj['name']) ?></h4>
                    <p class="cp-builder-project-meta">
                        <?= ucfirst($proj['type'] ?? 'site') ?> • <?= ucfirst($proj['status'] ?? 'active') ?>
                    </p>
                    <div class="cp-builder-project-actions-row">
                        <form method="post" action="<?= url('client/site-builder/page-create') ?>" class="cp-builder-page-create-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="project_id" value="<?= (int)$proj['id'] ?>">
                            <input name="title" value="Yeni Sayfa" class="cp-builder-page-input">
                            <button class="ao-btn primary cp-builder-page-button" type="submit">+ Sayfa</button>
                        </form>
                        <a class="ao-btn soft cp-builder-package-link" data-builder-package-alert data-builder-package-kind="site" href="<?= url('cart/add?product=sitebuilder-output-package') ?>">ZIP / Kaynak Kod</a>
                    </div>
                    <div class="cp-builder-project-pages">
                        <?php
                        // Get pages for this project
                        $projPages = [];
                        try {
                            $qp = db()->prepare('SELECT id, title, status FROM sitebuilder_pages WHERE project_id=? ORDER BY id ASC LIMIT 5');
                            $qp->execute([$proj['id']]);
                            $projPages = $qp->fetchAll();
                        } catch (Throwable $e) {}
                        ?>
                        <?php foreach ($projPages as $pg): ?>
                        <a href="<?= url('client/site-builder?project_id='.$proj['id'].'&page_id='.$pg['id']) ?>" class="ao-btn soft cp-builder-project-page-link">
                            <span>✏️ <?= e($pg['title']) ?></span>
                            <?php if ($pg['status'] === 'published'): ?>
                            <span class="cp-builder-page-status live">✓ Yayında</span>
                            <?php else: ?>
                            <span class="cp-builder-page-status draft">Taslak</span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
