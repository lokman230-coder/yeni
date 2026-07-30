<?php
$ctx = $context ?? 'public';
$position = $ctx === 'public' ? 'left' : 'right';
$ctxLabel = match($ctx) {
    'customer' => 'Musteri AI',
    'admin' => 'Admin AI',
    default => 'AI Asistan',
};
$suggestions = match($ctx) {
    'customer' => ['Faturam ne kadar?', 'Hizmetim ne zaman yenilenir?', 'Ticket acmak istiyorum'],
    'admin' => ['Musteri ara', 'Yeni urun ekle', 'Sistem durumu', 'Bu ay gelir raporu'],
    default => ['Hosting oner', 'Domain sorgula', 'Site olusturmak istiyorum', 'Fiyatlar ne?'],
};
$welcome = match($ctx) {
    'customer' => 'Merhaba! Hizmetleriniz, faturalariniz veya destek konularinda yardim edeyim.',
    'admin' => 'Yonetici modu: musteri, urun, siparis, rapor ve sistem konularinda yardim edeyim.',
    default => 'Merhaba! Hosting, domain, site builder veya diger hizmetlerimizde nasil yardimci olabilirim?',
};
?>
<div class="aho-ai-widget aho-ai-widget--<?= e($position) ?>"
     data-aho-ai-widget
     data-aho-ai-context="<?= e($ctx) ?>">
    <button class="aho-ai-widget__toggle" data-aho-ai-toggle aria-label="AI Asistan">🤖</button>

    <div class="aho-ai-panel" role="dialog" aria-label="AI Asistan">
        <div class="aho-ai-panel__header">
            <div class="aho-ai-panel__title">
                <div class="aho-ai-panel__title-icon">🤖</div>
                <div>
                    <?= e($ctxLabel) ?>
                    <div class="aho-ai-panel__ctx"><?= e($ctx) ?> · Ctrl+K</div>
                </div>
            </div>
            <div style="display:flex;gap:6px;align-items:center">
                <button class="aho-ai-panel__close" data-aho-ai-clear aria-label="Konusmayi temizle" title="Konusmayi temizle">Temizle</button>
                <button class="aho-ai-panel__close" data-aho-ai-close aria-label="Kapat">×</button>
            </div>
        </div>

        <div class="aho-ai-panel__messages" data-aho-ai-messages>
            <div class="aho-ai-msg aho-ai-msg--bot" data-aho-ai-welcome><?= e($welcome) ?></div>
        </div>

        <div class="aho-ai-suggestions">
            <?php foreach ($suggestions as $s): ?>
                <button type="button" class="aho-ai-chip" data-aho-ai-suggest><?= e($s) ?></button>
            <?php endforeach; ?>
        </div>

        <form class="aho-ai-input-bar" data-aho-ai-form>
            <input type="text" class="aho-ai-input"
                   placeholder="Mesajinizi yazin..." data-aho-ai-input
                   required maxlength="2000" autocomplete="off">
            <button type="submit" class="aho-ai-send" data-aho-ai-send aria-label="Gonder">➤</button>
        </form>

        <div class="aho-ai-footer">🔒 Ahost Bilisim AI · yanitlar baglam guvenligi ile filtrelenir</div>
    </div>
</div>
