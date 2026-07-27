<?php
/**
 * Editör tam ekran layout — özel şablon, layout extend etmez.
 * @var \App\Core\View $view
 */
$activeSkin = \App\Services\Theme\ThemeManager::active();
$stylesheets = \App\Services\Theme\ThemeManager::stylesheetsFor(\App\Services\Theme\ThemeManager::CONTEXT_SITE);
$isMobile = $project['kind'] === 'mobile';
?><!doctype html>
<html lang="tr" data-theme="light" data-theme-skin="<?= e($activeSkin) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title>Editor · <?= e($project['name']) ?></title>
    <link rel="icon" href="<?= asset('img/logo-icon.png') ?>" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <?php foreach ($stylesheets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body>
<div class="aho-bldr-mobile-warning">
    ⚠ Builder editörü mobil cihazlarda desteklenmez. Lütfen masaüstünden açın.
</div>

<div class="aho-bldr-editor">
    <!-- Topbar -->
    <div class="aho-bldr-topbar">
        <div class="aho-bldr-topbar__left">
            <a href="/panel" class="aho-btn aho-btn--ghost aho-btn--sm">← Panel</a>
            <div class="aho-bldr-topbar__name">🎨 <?= e($project['name']) ?></div>
            <span class="aho-bldr-topbar__save-status is-saved" data-bldr-save-status>✓ Kaydedildi</span>
        </div>
        <div class="aho-bldr-topbar__center">
            <div class="aho-bldr-devices">
                <button data-bldr-device="desktop" class="is-active">🖥 Masaüstü</button>
                <button data-bldr-device="tablet">📱 Tablet</button>
                <button data-bldr-device="mobile">📱 Mobil</button>
            </div>
        </div>
        <div class="aho-bldr-topbar__right">
            <button class="aho-btn aho-btn--outline aho-btn--sm" data-bldr-preview>👁 Önizleme</button>
            <button class="aho-btn aho-btn--primary aho-btn--sm" data-bldr-export>⬇ ZIP İndir</button>
        </div>
    </div>

    <!-- Sol: Blok Kütüphanesi -->
    <aside class="aho-bldr-blocks">
        <input type="text" class="aho-bldr-blocks__search" placeholder="🔍 Blok ara..." data-bldr-block-search>
        <?php foreach ($block_groups as $catKey => $blocks): ?>
            <div class="aho-bldr-blocks__group">
                <div class="aho-bldr-blocks__group-title"><?= e($category_labels[$catKey] ?? $catKey) ?></div>
                <div class="aho-bldr-blocks__grid">
                    <?php foreach ($blocks as $b): ?>
                        <div class="aho-bldr-block-item" data-bldr-add-block="<?= e($b['type']) ?>" title="<?= e($b['label']) ?>">
                            <div class="aho-bldr-block-item__icon"><?= $b['icon'] ?></div>
                            <div class="aho-bldr-block-item__label"><?= e($b['label']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </aside>

    <!-- Orta: Canvas -->
    <main class="aho-bldr-canvas" data-bldr-canvas data-device="desktop" data-kind="<?= e($project['kind']) ?>">
        <div class="aho-bldr-canvas__frame">
            <div class="aho-bldr-block-list" data-bldr-block-list></div>
        </div>
    </main>

    <!-- Sağ: Property Panel -->
    <aside class="aho-bldr-props" data-bldr-props>
        <div class="aho-bldr-props__title">Özellikler</div>
        <div class="aho-bldr-props__empty">Bir bloğa tıklayın</div>
    </aside>
</div>

<!-- AI Asistan Paneli (sabit sağ alt köşe) -->
<div class="aho-bldr-ai-fab" id="ahoBldrAiFab" title="AI ile düzenle">
    🤖 AI
</div>
<div class="aho-bldr-ai-panel" id="ahoBldrAiPanel">
    <div class="aho-bldr-ai-panel__header">
        <span>🤖 Builder AI Asistan</span>
        <button type="button" onclick="document.getElementById('ahoBldrAiPanel').classList.remove('open')" style="background:transparent;color:inherit;border:0;cursor:pointer;font-size:20px">✕</button>
    </div>
    <div class="aho-bldr-ai-panel__body" id="ahoBldrAiLog">
        <div class="aho-bldr-ai-msg aho-bldr-ai-msg--bot">
            👋 Merhaba! Sana nasıl yardım edebilirim?<br><br>
            <strong>Örnekler:</strong><br>
            • "hero blok ekle"<br>
            • "pastel renk paletine geç"<br>
            • "blokları listele"<br>
            • "başlığı 'Hoş Geldiniz' olarak değiştir"<br>
            • "3. bloğu sil (onay)"
        </div>
    </div>
    <div class="aho-bldr-ai-panel__input">
        <input type="text" id="ahoBldrAiInput" placeholder="AI'a ne yapmasını istersin..." autocomplete="off">
        <button type="button" id="ahoBldrAiSend">Gönder</button>
    </div>
</div>

<style>
.aho-bldr-ai-fab {
    position: fixed; bottom: 24px; right: 24px; z-index: 9998;
    width: 60px; height: 60px; border-radius: 50%;
    background: linear-gradient(135deg, #8b5cf6, #ec4899);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-weight: 700; cursor: pointer;
    box-shadow: 0 8px 24px rgba(139, 92, 246, 0.4);
    transition: transform .2s;
    font-size: 13px;
}
.aho-bldr-ai-fab:hover { transform: scale(1.1); }
.aho-bldr-ai-panel {
    position: fixed; bottom: 100px; right: 24px; z-index: 9999;
    width: 380px; height: 500px;
    background: #fff; border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    display: none; flex-direction: column; overflow: hidden;
    border: 1px solid #e5e7eb;
}
.aho-bldr-ai-panel.open { display: flex; }
.aho-bldr-ai-panel__header {
    padding: 14px 16px;
    background: linear-gradient(135deg, #8b5cf6, #ec4899);
    color: #fff; font-weight: 600;
    display: flex; align-items: center; justify-content: space-between;
}
.aho-bldr-ai-panel__body {
    flex: 1; padding: 12px; overflow-y: auto; background: #f9fafb;
    font-size: 13px; line-height: 1.5;
}
.aho-bldr-ai-msg {
    padding: 10px 12px; border-radius: 10px; margin-bottom: 8px;
    max-width: 85%; white-space: pre-wrap;
}
.aho-bldr-ai-msg--bot  { background: #fff; border: 1px solid #e5e7eb; }
.aho-bldr-ai-msg--user { background: #8b5cf6; color: #fff; margin-left: auto; }
.aho-bldr-ai-panel__input {
    display: flex; padding: 10px; background: #fff; border-top: 1px solid #e5e7eb; gap: 6px;
}
.aho-bldr-ai-panel__input input {
    flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;
}
.aho-bldr-ai-panel__input button {
    padding: 8px 16px; background: #8b5cf6; color: #fff; border: 0; border-radius: 8px; cursor: pointer;
    font-weight: 600;
}
.aho-bldr-ai-panel__input button:disabled { opacity: .5; cursor: wait; }
</style>

<script src="<?= asset('js/theme.js') ?>" defer></script>
<script src="<?= asset('js/builder.js') ?>" defer></script>
<script>
    // Sayfa yüklenince editörü başlat
    document.addEventListener('DOMContentLoaded', () => {
        AhostOne.modules.Builder.init({
            projectId: <?= (int)$project['id'] ?>,
            pageId:    <?= (int)$active_page['id'] ?>,
            kind:      <?= json_encode($project['kind']) ?>,
            sector:    <?= json_encode($project['sector']) ?>,
            tree:      <?= json_encode(json_decode($active_page['tree_json'] ?? '{}') ?: ['version' => 1, 'blocks' => []], JSON_UNESCAPED_UNICODE) ?>,
        });

        // ---- Builder AI ----
        const projectId = <?= (int)$project['id'] ?>;
        const fab   = document.getElementById('ahoBldrAiFab');
        const panel = document.getElementById('ahoBldrAiPanel');
        const log   = document.getElementById('ahoBldrAiLog');
        const input = document.getElementById('ahoBldrAiInput');
        const send  = document.getElementById('ahoBldrAiSend');
        const csrf  = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        fab.addEventListener('click', () => panel.classList.toggle('open'));

        const append = (text, who) => {
            const d = document.createElement('div');
            d.className = 'aho-bldr-ai-msg aho-bldr-ai-msg--' + who;
            d.innerHTML = text.replace(/\n/g, '<br>');
            log.appendChild(d);
            log.scrollTop = log.scrollHeight;
        };

        const submit = async () => {
            const msg = input.value.trim();
            if (!msg) return;
            append(msg, 'user');
            input.value = '';
            send.disabled = true;
            try {
                const r = await fetch('/ai/builder', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ _csrf: csrf, project_id: projectId, message: msg }),
                });
                const j = await r.json();
                append(j.reply || 'Yanıt alınamadı.', 'bot');
                if (j.tool_ok) {
                    // Değişiklik olduysa sayfayı 2 sn sonra yenile
                    setTimeout(() => location.reload(), 1500);
                }
            } catch (e) {
                append('❌ Hata: ' + e.message, 'bot');
            } finally {
                send.disabled = false;
                input.focus();
            }
        };

        send.addEventListener('click', submit);
        input.addEventListener('keydown', (e) => { if (e.key === 'Enter') submit(); });
    });
</script>
</body>
</html>
