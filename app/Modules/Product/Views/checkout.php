<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero"><div class="aho-container"><h1>Ödeme</h1></div></section>
<section class="aho-pages-body"><div class="aho-container">
    <div class="aho-card"><div class="aho-empty-state" style="padding:var(--aho-space-12)">
        <div class="aho-empty-state__icon" style="font-size:64px">💳</div>
        <div class="aho-empty-state__title" style="font-size:var(--aho-text-xl)">Ödeme Sayfası</div>
        <div class="aho-empty-state__text">PayTR + havale/EFT + bakiye ödeme akışı Faz 3'te aktifleşecektir.</div>
        <div style="margin-top:var(--aho-space-6)"><a href="/sepet" class="aho-btn aho-btn--outline">Sepete Dön</a></div>
    </div></div>
</div></section>
<?php $view->endSection(); ?>
