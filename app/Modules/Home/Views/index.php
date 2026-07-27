<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
?>

<!-- Hero -->
<section class="aho-home-hero">
    <div class="aho-container">
        <div class="aho-home-hero__inner">
            <a href="/ai/site-olustur" class="aho-home-hero__badge" style="text-decoration:none;cursor:pointer">
                ✨ Yeni: <strong>"Diş hekimi sitem yap"</strong> — AI ile saniyede site oluştur →
            </a>
            <h1 class="aho-home-hero__title">
                Hosting'ten Mobil Uygulamaya <br>
                <span class="aho-home-hero__gradient">Tek Platform</span>
            </h1>
            <p class="aho-home-hero__subtitle">
                Sıfırdan modern altyapıyla inşa edilmiş; hosting, domain, site builder, mobile builder,
                marketplace ve AI asistanı bir arada sunan Türkiye odaklı platform.
            </p>

            <form class="aho-home-search" action="/domain" method="get" role="search" aria-label="Domain sorgu">
                <input type="text" name="q" class="aho-home-search__input"
                       placeholder="ornekdomain.com — Hemen sorgula"
                       autocomplete="off" required>
                <button type="submit" class="aho-btn aho-btn--accent aho-btn--lg">Sorgula</button>
            </form>

            <div class="aho-home-hero__tlds">
                <span>.com <b>85₺</b></span>
                <span>.net <b>95₺</b></span>
                <span>.com.tr <b>75₺</b></span>
                <span>.org <b>110₺</b></span>
                <span>.io <b>580₺</b></span>
                <span>.dev <b>210₺</b></span>
            </div>
        </div>
    </div>
</section>

<!-- Ürün Blokları -->
<section class="aho-home-section">
    <div class="aho-container">
        <div class="aho-home-section__head">
            <h2>Hizmetlerimiz</h2>
            <p>Web varlığınız için ihtiyacınız olan her şey tek çatı altında.</p>
        </div>

        <div class="aho-feature-grid">
            <a href="/hosting" class="aho-card aho-card--hover aho-home-card">
                <div class="aho-feature-card__icon">🌐</div>
                <h3>Web Hosting</h3>
                <p>NVMe SSD, LiteSpeed, ücretsiz SSL, cPanel ve DirectAdmin panelleri ile hızlı hosting.</p>
                <span class="aho-home-card__cta">İncele →</span>
            </a>

            <a href="/sunucular" class="aho-card aho-card--hover aho-home-card">
                <div class="aho-feature-card__icon">🖥️</div>
                <h3>VPS &amp; Sunucu</h3>
                <p>Yüksek performans, tam yönetim, esnek kaynak. Kurumsal projeleriniz için ideal.</p>
                <span class="aho-home-card__cta">İncele →</span>
            </a>

            <a href="/domain" class="aho-card aho-card--hover aho-home-card">
                <div class="aho-feature-card__icon">🔗</div>
                <h3>Domain</h3>
                <p>Anında sorgulama, WHOIS, DNS ve SSL yönetimi. 300+ uzantı desteği.</p>
                <span class="aho-home-card__cta">İncele →</span>
            </a>

            <a href="/site-builder" class="aho-card aho-card--hover aho-home-card">
                <div class="aho-feature-card__icon">🎨</div>
                <h3>Site Builder <span class="aho-badge aho-badge--accent">AI</span></h3>
                <p>Elementor + Visual Composer + AI gücünü tek yerde birleştiren sürükle-bırak editör.</p>
                <span class="aho-home-card__cta">Demo Dene →</span>
            </a>

            <a href="/mobile-builder" class="aho-card aho-card--hover aho-home-card">
                <div class="aho-feature-card__icon">📱</div>
                <h3>Mobile Builder</h3>
                <p>Radyo, e-ticaret, kurumsal uygulamalar için APK ve AAB çıktısı.</p>
                <span class="aho-home-card__cta">Demo Dene →</span>
            </a>

            <a href="/marketplace" class="aho-card aho-card--hover aho-home-card">
                <div class="aho-feature-card__icon">🛍️</div>
                <h3>Marketplace</h3>
                <p>Domain, tasarım, script, hizmet alım-satımı. Güvenli komisyon sistemiyle.</p>
                <span class="aho-home-card__cta">Keşfet →</span>
            </a>
        </div>
    </div>
</section>

<!-- Site Araçları -->
<section class="aho-home-section aho-home-section--muted">
    <div class="aho-container">
        <div class="aho-home-section__head">
            <h2>Ücretsiz Site Araçları</h2>
            <p>Alan adı, DNS, SSL, SEO ve daha fazlası — 15+ profesyonel araç tek yerde.</p>
        </div>

        <div class="aho-tool-grid">
            <div class="aho-tool">🔍 WHOIS</div>
            <div class="aho-tool">🌐 DNS Kontrol</div>
            <div class="aho-tool">🔒 SSL Kontrol</div>
            <div class="aho-tool">🚀 Site Hız Testi</div>
            <div class="aho-tool">📊 SEO Analiz</div>
            <div class="aho-tool">🛡️ Güvenlik Başlıkları</div>
            <div class="aho-tool">💎 Domain Değerleme</div>
            <div class="aho-tool">📍 IP Lookup</div>
        </div>

        <div style="text-align:center;margin-top:var(--aho-space-8)">
            <a href="/site-araclari" class="aho-btn aho-btn--outline aho-btn--lg">Tüm Araçları Gör →</a>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="aho-home-cta">
    <div class="aho-container">
        <div class="aho-home-cta__box">
            <h2>Hemen Başlayın</h2>
            <p>Web projeniz için hosting, domain ve site builder ihtiyaçlarınızın tümü tek platformda.</p>
            <div class="aho-home-cta__buttons">
                <a href="/hosting" class="aho-btn aho-btn--accent aho-btn--xl">Hosting Al</a>
                <a href="/site-builder" class="aho-btn aho-btn--outline aho-btn--xl" style="color:#fff;border-color:rgba(255,255,255,.3)">Site Builder Demo</a>
            </div>
        </div>
    </div>
</section>

<?php $view->endSection(); ?>
