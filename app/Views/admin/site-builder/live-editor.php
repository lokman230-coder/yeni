<?php
/**
 * Ahost One v24.11.6 - Live Builder Editor
 * Sürükle-bırak, boyutlandırma, canlı önizleme
 */

// Ensure schema
ao_schema_ensure_v1400();

// Get page data
$id = (int)($_GET['id'] ?? 0);
if (!$id) { $id = ao_sitebuilder_default_page_id(); }

$q = db()->prepare('SELECT p.*, pr.name project_name FROM sitebuilder_pages p LEFT JOIN sitebuilder_projects pr ON pr.id=p.project_id WHERE p.id=? LIMIT 1');
$q->execute([$id]);
$page = $q->fetch();

if (!$page) {
    echo '<div class="ao-card">Sayfa bulunamadı.</div>';
    return;
}

// Get existing JSON or use default
$builderJson = $page['builder_json'] ?: '';
$pageTitle = e($page['title']);
$projectName = e($page['project_name']);
?>

<div class="ao-card" style="margin-bottom: 0; border-radius: 16px 16px 0 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <span class="ao-kicker"><?= $projectName ?></span>
            <h2 style="margin: 4px 0 0"><?= $pageTitle ?> — Live Builder</h2>
            <p style="margin: 8px 0 0; color: #64748b;">Sürükle-bırak ile sayfa düzenle, öğe ekle/çıkar, stillerini değiştir.</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a class="ao-btn soft" target="_blank" href="<?= url('sitebuilder/preview?id='.$page['id']) ?>">👁 Önizle</a>
            <a class="ao-btn soft" href="<?= url('admin/site-builder/pages') ?>">← Sayfalar</a>
        </div>
    </div>
</div>

<form method="post" action="<?= url('admin/site-builder/page-save') ?>" id="lbForm">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$page['id'] ?>">
    <input type="hidden" id="live_builder_json" name="builder_json" value='<?= e($builderJson) ?>'>
    
    <div class="lb-wrapper">
        <!-- SIDEBAR: Widgets -->
        <div class="lb-sidebar">
            <div class="lb-sidebar-section">
                <h3>Temel Öğeler</h3>
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
                        <span class="lb-widget-icon">▶</span>
                        <span class="lb-widget-label">Radyo Player</span>
                    </div>
                    <div class="lb-widget" data-widget-type="now_playing" draggable="true">
                        <span class="lb-widget-icon">♪</span>
                        <span class="lb-widget-label">Şu An</span>
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
                        <span class="lb-widget-icon">✉</span>
                        <span class="lb-widget-label">İstek</span>
                    </div>
                    <div class="lb-widget" data-widget-type="form" draggable="true">
                        <span class="lb-widget-icon">▤</span>
                        <span class="lb-widget-label">Form</span>
                    </div>
                    <div class="lb-widget" data-widget-type="map" draggable="true">
                        <span class="lb-widget-icon">⌖</span>
                        <span class="lb-widget-label">Harita</span>
                    </div>
                    <div class="lb-widget" data-widget-type="notification" draggable="true">
                        <span class="lb-widget-icon">!</span>
                        <span class="lb-widget-label">Bildirim</span>
                    </div>
                    <div class="lb-widget" data-widget-type="cart" draggable="true">
                        <span class="lb-widget-icon">🛒</span>
                        <span class="lb-widget-label">Sepet</span>
                    </div>
                </div>
            </div>
            
            <div class="lb-sidebar-section">
                <h3>Sayfa Ayarları</h3>
                <div style="padding: 8px 0;">
                    <label style="font-size: 12px; color: #64748b; display: block; margin-bottom: 4px;">Sayfa Başlığı</label>
                    <input type="text" class="lb-input" id="pageTitle" value="<?= $pageTitle ?>" style="margin-bottom: 12px;">
                    
                    <label style="font-size: 12px; color: #64748b; display: block; margin-bottom: 4px;">Max Genişlik</label>
                    <select class="lb-input" id="canvasWidth">
                        <option value="100%">Tam Genişlik</option>
                        <option value="1200px" selected>1200px</option>
                        <option value="960px">960px</option>
                        <option value="720px">720px</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- CANVAS -->
        <div class="lb-canvas-wrap">
            <div class="lb-toolbar">
                <button type="button" class="lb-toolbar-btn" id="lbUndo" title="Geri Al (Ctrl+Z)">↩ Geri</button>
                <button type="button" class="lb-toolbar-btn" id="lbRedo" title="Yinele (Ctrl+Shift+Z)">↪ İleri</button>
                <div class="lb-toolbar-sep"></div>
                <button type="button" class="lb-toolbar-btn" id="lbAddSection">➕ Bölüm Ekle</button>
                <div class="lb-toolbar-sep"></div>
                <button type="button" class="lb-toolbar-btn primary" id="lbSave">💾 Kaydet</button>
                <span style="margin-left: auto; font-size: 12px; color: #94a3b8;">Ctrl+Z: Geri | Ctrl+Shift+Z: İleri | Delete: Sil | Esc: İptal</span>
            </div>
            
            <div class="lb-canvas" id="lbCanvas" style="max-width: var(--canvas-width, 1200px);">
                <!-- Sections will be rendered here by JavaScript -->
            </div>
            
            <div class="lb-add-section-placeholder" id="lbAddSectionPlaceholder">
                <span>➕</span>
                <p>Yeni bölüm eklemek için tıkla veya yukarıdaki butonu kullan</p>
            </div>
        </div>
        
        <!-- INSPECTOR -->
        <div class="lb-inspector" id="lbInspector">
            <div style="padding: 40px 20px; text-align: center; color: #94a3b8;">
                <p style="font-size: 48px; margin: 0 0 12px;">🎨</p>
                <p style="margin: 0;">Bir öğe seçin veya yeni bölüm ekleyin</p>
            </div>
        </div>
    </div>
</form>

<script src="<?= assetv('js/admin/ahost-builder4-core.js') ?>"></script>
<script src="<?= assetv('js/admin/live-builder.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Canvas width control
    document.getElementById('canvasWidth').addEventListener('change', function() {
        document.getElementById('lbCanvas').style.maxWidth = this.value;
    });
    
    // Add section click
    document.getElementById('lbAddSection').addEventListener('click', function() {
        if (window.LiveBuilder) {
            window.LiveBuilder.addSection();
            document.getElementById('lbAddSectionPlaceholder').style.display = 'none';
        }
    });
    
    document.getElementById('lbAddSectionPlaceholder').addEventListener('click', function() {
        if (window.LiveBuilder) {
            window.LiveBuilder.addSection();
            this.style.display = 'none';
        }
    });
    
    // Save button
    document.getElementById('lbSave').addEventListener('click', function() {
        if (window.LiveBuilder) {
            window.LiveBuilder.saveState();
            document.getElementById('lbForm').submit();
        }
    });
    
    // Check if canvas is empty
    setTimeout(function() {
        const canvas = document.getElementById('lbCanvas');
        if (!canvas || canvas.children.length === 0) {
            document.getElementById('lbAddSectionPlaceholder').style.display = 'flex';
        } else {
            document.getElementById('lbAddSectionPlaceholder').style.display = 'none';
        }
    }, 500);
});
</script>
