<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero">
    <div class="aho-container">
        <span class="aho-home-hero__badge" style="display:inline-block">✨ Elementor + Visual Composer + AI</span>
        <h1 style="margin-top:var(--aho-space-4)">Site Builder</h1>
        <p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2);font-size:var(--aho-text-lg)">
            Sürükle-bırak kolaylığıyla, yapay zeka desteğiyle dakikalar içinde profesyonel siteler.
        </p>
        <div style="margin-top:var(--aho-space-6);display:flex;gap:var(--aho-space-3);justify-content:center;flex-wrap:wrap">
            <a href="/sepet" class="aho-btn aho-btn--accent aho-btn--xl">AI ile Tasarlamak İçin Paket Al</a>
            <a href="#" class="aho-btn aho-btn--outline aho-btn--xl">Demo Dene</a>
        </div>
    </div>
</section>

<section class="aho-pages-body">
    <div class="aho-container">
        <div class="aho-feature-grid">
            <?php foreach ([
                ['🎨', 'Canlı Önizleme', 'Değişikliklerinizi anında görün, sayfayı yenilemenize gerek yok.'],
                ['📱', 'Cihaz Görünümü', 'Masaüstü, tablet, mobil için ayrı stiller ayarlayın.'],
                ['🤖', 'AI Yardımcı', '"Bana bir hosting sitesi yap" deyin, AI tüm sayfayı üretsin.'],
                ['🧩', '100+ Blok', 'Kahraman, SSS, fiyatlandırma, referans, blog, harita, form...'],
                ['🎯', 'Sektör Şablonları', 'Hosting, ajans, e-ticaret, restoran, klinik, radyo, portfolyo...'],
                ['📦', 'ZIP / Kaynak Kod', 'Sitenizi kendi sunucunuza taşıyın.'],
            ] as $f): ?>
                <div class="aho-card">
                    <div class="aho-feature-card__icon"><?= $f[0] ?></div>
                    <h3><?= $f[1] ?></h3>
                    <p style="color:var(--aho-color-ink-500);font-size:var(--aho-text-sm)"><?= $f[2] ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php $view->endSection(); ?>
