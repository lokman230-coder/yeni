<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero"><div class="aho-container">
    <h1>Destek Merkezi</h1>
    <p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2)">Size en hızlı şekilde yardımcı olmak için buradayız.</p>
</div></section>

<section class="aho-pages-body"><div class="aho-container">
    <div class="aho-feature-grid">
        <a href="/bilgi-bankasi" class="aho-card aho-card--hover" style="text-decoration:none;color:inherit">
            <div class="aho-feature-card__icon">📚</div>
            <h3>Bilgi Bankası</h3>
            <p style="color:var(--aho-color-ink-500);font-size:var(--aho-text-sm)">Kendi kendinize çözüm bulun.</p>
        </a>
        <a href="/iletisim" class="aho-card aho-card--hover" style="text-decoration:none;color:inherit">
            <div class="aho-feature-card__icon">✉️</div>
            <h3>İletişim Formu</h3>
            <p style="color:var(--aho-color-ink-500);font-size:var(--aho-text-sm)">Yazın, en kısa sürede dönüş yapalım.</p>
        </a>
        <a href="/panel" class="aho-card aho-card--hover" style="text-decoration:none;color:inherit">
            <div class="aho-feature-card__icon">🎫</div>
            <h3>Destek Talebi</h3>
            <p style="color:var(--aho-color-ink-500);font-size:var(--aho-text-sm)">Panelden yeni ticket açın.</p>
        </a>
    </div>
</div></section>
<?php $view->endSection(); ?>
