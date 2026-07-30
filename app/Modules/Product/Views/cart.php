<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero"><div class="aho-container"><h1>Sepetiniz</h1></div></section>
<section class="aho-pages-body"><div class="aho-container">
    <div class="aho-card">
        <div class="aho-empty-state" style="padding:var(--aho-space-12)">
            <div class="aho-empty-state__icon" style="font-size:64px">🛒</div>
            <div class="aho-empty-state__title" style="font-size:var(--aho-text-xl)">Sepetiniz Boş</div>
            <div class="aho-empty-state__text" style="max-width:520px;margin:var(--aho-space-2) auto">
                Sepetinizde ürün bulunmuyor. Hosting, domain veya site builder paketlerimizi inceleyebilirsiniz.
            </div>
            <div style="margin-top:var(--aho-space-6);display:flex;gap:var(--aho-space-2);justify-content:center;flex-wrap:wrap">
                <a href="/hosting" class="aho-btn aho-btn--primary">Hosting'e Göz At</a>
                <a href="/domain" class="aho-btn aho-btn--outline">Domain Sorgula</a>
            </div>
        </div>
    </div>
    <p style="text-align:center;color:var(--aho-color-ink-500);margin-top:var(--aho-space-6);font-size:var(--aho-text-sm)">
        Tam sepet + ödeme akışı Faz 3'te devreye alınacaktır (PayTR entegrasyonu, vergi, kupon, ek paketler).
    </p>
</div></section>
<?php $view->endSection(); ?>
