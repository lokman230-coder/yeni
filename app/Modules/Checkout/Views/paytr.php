<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero">
    <div class="aho-container"><h1>Kredi Kartı ile Ödeme</h1></div>
</section>

<section class="aho-checkout">
    <div class="aho-container" style="max-width:800px">
        <?php if (!$result['success']): ?>
            <div class="aho-card">
                <div class="aho-alert aho-alert--danger"><?= e($result['error'] ?? 'PayTR hatası') ?></div>
                <div class="aho-alert aho-alert--info">
                    <strong>Test/Geliştirme Modu:</strong> PayTR API bilgileri henüz .env dosyasına girilmemişse
                    bu adım çalışmayabilir. Şu değişkenleri doldurun:
                    <code>PAYTR_MERCHANT_ID</code>, <code>PAYTR_MERCHANT_KEY</code>, <code>PAYTR_MERCHANT_SALT</code>.
                </div>
                <a href="/odeme" class="aho-btn aho-btn--outline">← Ödeme Yöntemine Dön</a>
            </div>
        <?php else: ?>
            <p style="text-align:center;color:var(--aho-color-ink-500);margin-bottom:var(--aho-space-4)">
                Sipariş <strong><?= e($order['order_number']) ?></strong> için güvenli ödeme sayfasına yönlendiriliyorsunuz…
            </p>
            <iframe src="<?= e($result['iframe_url']) ?>"
                    style="width:100%;min-height:640px;border:0;border-radius:var(--aho-radius-lg);box-shadow:var(--aho-shadow-lg)"
                    allow="payment"></iframe>
        <?php endif; ?>
    </div>
</section>
<?php $view->endSection(); ?>
