<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$isMobile = ($kind ?? 'site') === 'mobile';
?>
<section class="aho-bldr-demo-hero">
    <div class="aho-container">
        <span class="aho-bldr-demo-hero__badge">✨ Elementor + Visual Composer + AI</span>
        <h1 class="aho-bldr-demo-hero__title">
            <?= $isMobile ? 'Mobil Uygulama' : 'Site' ?>
            <span class="aho-bldr-demo-hero__gradient"><?= $isMobile ? 'Dakikalar İçinde' : 'Sürükle-Bırak' ?></span>
        </h1>
        <p style="font-size:var(--aho-text-lg);color:var(--aho-color-ink-600);max-width:640px;margin:0 auto var(--aho-space-6)">
            <?php if ($isMobile): ?>
                Radyo, e-ticaret, kurumsal veya restoran uygulamanızı kod yazmadan tasarlayın; APK ve AAB olarak indirin.
            <?php else: ?>
                Sürükle-bırak kolaylığıyla, yapay zeka desteğiyle, sektörünüze özel şablonlarla profesyonel siteler oluşturun.
            <?php endif; ?>
        </p>

        <div style="display:flex;gap:var(--aho-space-3);justify-content:center;flex-wrap:wrap">
            <a href="/kayit" class="aho-btn aho-btn--accent aho-btn--xl">Ücretsiz Denemeye Başla</a>
            <a href="#sectors" class="aho-btn aho-btn--outline aho-btn--xl">Şablonları Gör</a>
        </div>
    </div>
</section>

<section id="sectors" class="aho-pages-body">
    <div class="aho-container">
        <div style="text-align:center;margin-bottom:var(--aho-space-8)">
            <h2 style="font-size:var(--aho-text-3xl);margin-bottom:var(--aho-space-2)">Sektörünüzü Seçin</h2>
            <p style="color:var(--aho-color-ink-500)">Her sektöre özel bloklar ve şablonlar otomatik yüklenir.</p>
        </div>

        <div class="aho-bldr-sector-grid">
            <?php foreach ($sectors as $slug => $s): ?>
                <a href="<?= $isMobile ? '/mobile-builder' : '/site-builder' ?>#new-<?= e($slug) ?>" class="aho-bldr-sector">
                    <div class="aho-bldr-sector__icon"><?= $s['icon'] ?></div>
                    <div class="aho-bldr-sector__label"><?= e($s['label']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="aho-pages-body" style="padding-block:var(--aho-space-8) var(--aho-space-16)">
    <div class="aho-container" style="max-width:900px;text-align:center">
        <h2 style="margin-bottom:var(--aho-space-4)">Devam etmek için giriş yapın</h2>
        <p style="color:var(--aho-color-ink-500);margin-bottom:var(--aho-space-4)">
            Ücretsiz kayıt olun, ilk projenizi oluşturmaya başlayın.
        </p>
        <div style="display:flex;gap:var(--aho-space-2);justify-content:center;flex-wrap:wrap">
            <a href="/kayit" class="aho-btn aho-btn--primary aho-btn--lg">Kayıt Ol</a>
            <a href="/giris" class="aho-btn aho-btn--outline aho-btn--lg">Giriş Yap</a>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
