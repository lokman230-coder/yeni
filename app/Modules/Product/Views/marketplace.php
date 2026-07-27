<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero"><div class="aho-container">
    <h1>Marketplace</h1>
    <p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2);font-size:var(--aho-text-lg)">
        Domain, tasarım, script, mobil uygulama alım-satımı — güvenli komisyon sistemiyle.
    </p>
</div></section>

<section class="aho-pages-body"><div class="aho-container">
    <div class="aho-feature-grid">
        <?php foreach ([
            ['🌐', 'Premium Domain', '15.000₺', 'Sahibinden'],
            ['🎨', 'Logo Tasarım', '750₺', 'Profesyonel'],
            ['💻', 'Web Tasarım', '4.500₺', 'Anahtar Teslim'],
            ['📱', 'Mobil Uygulama', '9.900₺', 'Radyo Şablonu'],
            ['⚙️', 'PHP Script', '1.200₺', 'Kaynak Kod'],
            ['🔍', 'SEO Hizmeti', '2.500₺', 'Aylık'],
        ] as $it): ?>
            <div class="aho-card aho-card--hover">
                <div class="aho-feature-card__icon"><?= $it[0] ?></div>
                <h3><?= $it[1] ?></h3>
                <p style="color:var(--aho-color-ink-500);font-size:var(--aho-text-sm);margin-bottom:var(--aho-space-3)"><?= $it[3] ?></p>
                <div style="font-size:var(--aho-text-2xl);font-weight:700;color:var(--aho-color-primary-700)"><?= $it[2] ?></div>
                <div style="margin-top:var(--aho-space-4)"><a href="#" class="aho-btn aho-btn--outline aho-btn--sm">İncele</a></div>
            </div>
        <?php endforeach; ?>
    </div>
</div></section>
<?php $view->endSection(); ?>
