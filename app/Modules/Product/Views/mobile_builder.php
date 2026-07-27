<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero"><div class="aho-container">
    <h1>Mobile Builder</h1>
    <p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2);font-size:var(--aho-text-lg)">
        Radyo, e-ticaret, kurumsal ve daha fazlası için Android APK / AAB çıktısı.
    </p>
    <div style="margin-top:var(--aho-space-6);display:flex;gap:var(--aho-space-3);justify-content:center;flex-wrap:wrap">
        <a href="/sepet" class="aho-btn aho-btn--accent aho-btn--xl">Mobile Builder Paketi Al</a>
        <a href="#" class="aho-btn aho-btn--outline aho-btn--xl">Şablonları Gör</a>
    </div>
</div></section>

<section class="aho-pages-body"><div class="aho-container">
    <div class="aho-feature-grid">
        <?php foreach ([
            ['🎵', 'Radyo', 'Player, DJ programı, istek hattı, sosyal medya'],
            ['🛒', 'E-Ticaret', 'Ürünler, sepet, ödeme, sipariş takibi'],
            ['🏢', 'Kurumsal', 'Hizmetler, ekip, referanslar, iletişim'],
            ['🍽️', 'Restoran', 'Menü, rezervasyon, sipariş'],
            ['📰', 'Haber', 'Kategori, yazar, push bildirim'],
            ['🎓', 'Eğitim', 'Kurs, ders, video, sertifika'],
            ['💪', 'Spor Salonu', 'Ders programı, üyelik, ödeme'],
            ['📅', 'Randevu', 'Takvim, personel, ödeme'],
        ] as $t): ?>
            <div class="aho-card aho-card--hover">
                <div class="aho-feature-card__icon"><?= $t[0] ?></div>
                <h3><?= $t[1] ?></h3>
                <p style="color:var(--aho-color-ink-500);font-size:var(--aho-text-sm)"><?= $t[2] ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div></section>
<?php $view->endSection(); ?>
