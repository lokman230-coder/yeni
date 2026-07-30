<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero">
    <div class="aho-container">
        <h1><?= e($title ?? 'Ödeme') ?></h1>
        <p style="opacity:.85"><?= e(ucfirst($gateway ?? 'gateway')) ?> ile ödeme başlatılıyor…</p>
    </div>
</section>

<section class="aho-checkout">
    <div class="aho-container">
        <?php if (!($result['success'] ?? false)): ?>
            <div class="aho-alert aho-alert--danger" style="margin-bottom:var(--aho-space-4)">
                <strong>Ödeme başlatılamadı:</strong>
                <?= e($result['error'] ?? 'Bilinmeyen hata') ?>
            </div>
            <div class="aho-card">
                <h3 class="aho-card__title">Yapılandırma eksik</h3>
                <p style="color:var(--aho-color-ink-700);margin-top:var(--aho-space-2)">
                    Bu ödeme sağlayıcısını aktifleştirmek için <code>.env</code> dosyanıza şu değişkenleri girin:
                </p>
                <ul style="margin-top:var(--aho-space-3);padding-left:var(--aho-space-4)">
                    <?php foreach (($env_keys ?? []) as $k): ?>
                        <li><code><?= e($k) ?></code></li>
                    <?php endforeach; ?>
                </ul>
                <div style="margin-top:var(--aho-space-4);display:flex;gap:var(--aho-space-2)">
                    <a href="/odeme" class="aho-btn aho-btn--ghost">← Ödeme Yöntemi Seç</a>
                    <a href="/panel/siparislerim" class="aho-btn aho-btn--primary">Siparişlerim</a>
                </div>
            </div>
        <?php else: ?>
            <div class="aho-card" style="text-align:center">
                <p><?= e($result['message'] ?? 'Ödeme sağlayıcısına yönlendiriliyorsunuz…') ?></p>
                <?php if (!empty($result['redirect_url'])): ?>
                    <script>window.location.href = <?= json_encode($result['redirect_url']) ?>;</script>
                    <a class="aho-btn aho-btn--primary" href="<?= e($result['redirect_url']) ?>">Devam Et</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php $view->endSection(); ?>
