<?php
/**
 * AI Widget partial — layout tarafından render edilir.
 * $context değişkeni: 'public' | 'customer' | 'admin'
 */
$ctx = $context ?? 'public';
$position = $ctx === 'public' ? 'left' : 'right';
$ctxLabel = match($ctx) {
    'customer' => 'Müşteri AI',
    'admin'    => 'Admin AI',
    default    => 'AI Asistan',
};
$suggestions = match($ctx) {
    'customer' => ['Faturam ne kadar?', 'Hizmetim ne zaman yenilenir?', 'Ticket açmak istiyorum'],
    'admin'    => ['Müşteri ara', 'Yeni ürün ekle', 'Sistem durumu', 'Bu ay gelir raporu'],
    default    => ['Hosting öner', 'Domain sorgula', 'Site oluşturmak istiyorum', 'Fiyatlar ne?'],
};
$welcome = match($ctx) {
    'customer' => 'Merhaba! Hizmetleriniz, faturalarınız veya destek konularında yardım edeyim.',
    'admin'    => 'Yönetici modu: müşteri, ürün, sipariş, rapor ve sistem konularında yardım edeyim.',
    default    => 'Merhaba! Hosting, domain, site builder veya diğer hizmetlerimizde nasıl yardımcı olabilirim?',
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
            <button class="aho-ai-panel__close" data-aho-ai-close aria-label="Kapat">✕</button>
        </div>

        <div class="aho-ai-panel__messages" data-aho-ai-messages>
            <div class="aho-ai-msg aho-ai-msg--bot"><?= e($welcome) ?></div>
        </div>

        <div class="aho-ai-suggestions">
            <?php foreach ($suggestions as $s): ?>
                <button type="button" class="aho-ai-chip" data-aho-ai-suggest><?= e($s) ?></button>
            <?php endforeach; ?>
        </div>

        <form class="aho-ai-input-bar" data-aho-ai-form>
            <input type="text" class="aho-ai-input"
                   placeholder="Mesajınızı yazın..." data-aho-ai-input
                   required maxlength="2000" autocomplete="off">
            <button type="submit" class="aho-ai-send" data-aho-ai-send aria-label="Gönder">➤</button>
        </form>

        <div class="aho-ai-footer">🔒 Ahost Bilişim AI · yanıtlar bağlam güvenliği ile filtrelenir</div>
    </div>
</div>
