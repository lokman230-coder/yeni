<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>
<section class="aho-pages-hero" style="padding-block:var(--aho-space-16)">
    <div class="aho-container">
        <h1>Domain Sorgulama</h1>
        <p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2);font-size:var(--aho-text-lg)">300+ uzantı ile hayalinizdeki domain'i bulun.</p>

        <form class="aho-home-search" action="/domain" method="get" style="margin-top:var(--aho-space-6)">
            <input type="text" name="q" class="aho-home-search__input" placeholder="ornekdomain.com" required>
            <button type="submit" class="aho-btn aho-btn--accent aho-btn--lg">Sorgula</button>
        </form>

        <div class="aho-home-hero__tlds">
            <span>.com <b>85₺</b></span><span>.net <b>95₺</b></span>
            <span>.com.tr <b>75₺</b></span><span>.org <b>110₺</b></span>
            <span>.io <b>580₺</b></span><span>.dev <b>210₺</b></span>
            <span>.tech <b>190₺</b></span><span>.online <b>145₺</b></span>
        </div>
    </div>
</section>

<section class="aho-pages-body">
    <div class="aho-container" style="text-align:center">
        <p style="color:var(--aho-color-ink-500)">
            Gerçek domain sorgulama Faz 4'te <strong>DomainNameAPI</strong> entegrasyonu ile aktifleştirilecektir.
            WHOIS, DNS, SSL kontrolleri, domain değerleme ve ön sipariş modülleri hazırlanmaktadır.
        </p>
    </div>
</section>
<?php $view->endSection(); ?>
