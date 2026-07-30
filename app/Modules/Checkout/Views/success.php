<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero">
    <div class="aho-container"><h1>Siparişiniz Alındı 🎉</h1></div>
</section>
<section class="aho-pages-body">
    <div class="aho-container">
        <div class="aho-card" style="max-width:640px;margin:0 auto;text-align:center;padding:var(--aho-space-12) var(--aho-space-8)">
            <div style="font-size:64px;margin-bottom:var(--aho-space-4)">✅</div>
            <h2 style="margin-bottom:var(--aho-space-3)">Teşekkürler!</h2>
            <p style="color:var(--aho-color-ink-600);margin-bottom:var(--aho-space-2)">
                Sipariş numaranız: <strong><?= e($order['order_number']) ?></strong>
            </p>

            <?php if ($havale): ?>
                <div class="aho-alert aho-alert--info" style="text-align:left;margin-top:var(--aho-space-4)">
                    <strong>Banka Bilgileri (Havale/EFT):</strong><br>
                    Ödeme talimatları e-posta adresinize gönderildi. Havale/EFT açıklamasına <strong><?= e($order['order_number']) ?></strong> yazmayı unutmayın.
                </div>
            <?php else: ?>
                <p style="color:var(--aho-color-ink-600)">Ödemeniz onaylandığında hizmetiniz otomatik olarak aktifleştirilecektir.</p>
            <?php endif; ?>

            <div style="margin-top:var(--aho-space-6);display:flex;gap:var(--aho-space-2);justify-content:center;flex-wrap:wrap">
                <a href="/panel" class="aho-btn aho-btn--primary">Panele Git</a>
                <a href="/" class="aho-btn aho-btn--outline">Ana Sayfa</a>
            </div>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
