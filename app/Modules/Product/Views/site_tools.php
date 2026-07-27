<?php
/** @var \App\Core\View $view */
$view->extend('layouts.public');
$view->section('content');
$tools = [
    ['🔍', 'WHOIS', 'Domain sahibi ve kayıt bilgileri'],
    ['🌐', 'DNS Kontrol', 'A, AAAA, MX, TXT, NS, CNAME, CAA'],
    ['🔒', 'SSL Kontrol', 'Issuer, tarih, kalan gün, CN'],
    ['📊', 'SEO Analiz', 'Title, meta, H tag, schema, canonical'],
    ['⚡', 'Site Hız Testi', 'TTFB, LCP, sayfa boyutu, öneriler'],
    ['🛡️', 'Güvenlik Başlıkları', 'CSP, HSTS, X-Frame, Referrer'],
    ['📱', 'Site Analiz', 'SSL, DNS, HTTP status, yanıt süresi'],
    ['💎', 'Domain Değerleme', 'TLD, uzunluk, marka gücü, SEO'],
    ['📍', 'IP Lookup', 'Konum, ISP, ASN'],
    ['📡', 'Ping', 'Sunucu erişilebilirliği ve gecikme'],
    ['📄', 'HTTP Header', 'Response header inceleme'],
    ['🤖', 'Robots.txt', 'Kontrol ve validasyon'],
    ['🗺️', 'Sitemap', 'XML sitemap doğrulama'],
    ['🏷️', 'Meta Tag Analiz', 'Open Graph, Twitter Card'],
    ['🔗', 'Link Analiz', 'İç ve dış linkler'],
];
?>
<section class="aho-pages-hero"><div class="aho-container">
    <h1>Site Araçları</h1>
    <p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-2);font-size:var(--aho-text-lg)">
        15+ profesyonel araç — hepsi ücretsiz, hepsi gerçek veri.
    </p>
</div></section>

<section class="aho-pages-body"><div class="aho-container">
    <div class="aho-feature-grid">
        <?php foreach ($tools as $tool): ?>
            <a href="#" class="aho-card aho-card--hover" style="text-decoration:none;color:inherit">
                <div class="aho-feature-card__icon"><?= $tool[0] ?></div>
                <h3><?= $tool[1] ?></h3>
                <p style="color:var(--aho-color-ink-500);font-size:var(--aho-text-sm)"><?= $tool[2] ?></p>
            </a>
        <?php endforeach; ?>
    </div>
    <p style="text-align:center;color:var(--aho-color-ink-500);margin-top:var(--aho-space-8);font-size:var(--aho-text-sm)">
        Araçların işlevsel entegrasyonu Faz 4'te tamamlanacaktır.
    </p>
</div></section>
<?php $view->endSection(); ?>
