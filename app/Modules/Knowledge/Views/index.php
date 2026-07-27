<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero"><div class="aho-container"><h1>Bilgi Bankası</h1><p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2)">Sorularınıza hızlı yanıtlar.</p></div></section>
<section class="aho-pages-body"><div class="aho-container">
    <form style="max-width:600px;margin:0 auto var(--aho-space-8);display:flex;gap:var(--aho-space-2)">
        <input type="text" class="aho-form-input" placeholder="🔍 Nasıl yapabilirim..." style="flex:1">
        <button class="aho-btn aho-btn--primary">Ara</button>
    </form>
    <div class="aho-feature-grid">
        <?php foreach ([
            ['🌐', 'Domain', 'Domain kayıt, transfer, DNS yönetimi'],
            ['🖥️', 'Hosting', 'cPanel, e-posta, veritabanı işlemleri'],
            ['💰', 'Faturalar', 'Ödeme, iade, fatura düzenleme'],
            ['🔒', 'Güvenlik', 'SSL, şifre, iki adımlı doğrulama'],
            ['🎨', 'Site Builder', 'Şablon, blok, yayınlama'],
            ['📱', 'Mobile Builder', 'APK oluşturma, push bildirim'],
        ] as $cat): ?>
        <a href="#" class="aho-card aho-card--hover" style="text-decoration:none;color:inherit">
            <div class="aho-feature-card__icon"><?= $cat[0] ?></div>
            <h3><?= $cat[1] ?></h3>
            <p style="color:var(--aho-color-ink-500);font-size:var(--aho-text-sm)"><?= $cat[2] ?></p>
        </a>
        <?php endforeach; ?>
    </div>
</div></section>
<?php $view->endSection(); ?>
