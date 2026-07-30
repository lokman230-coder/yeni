<?php
$service = [
  'kicker' => 'AI SiteBuilder',
  'title' => 'Yapay zeka destekli SiteBuilder ile dakikalar içinde profesyonel web sitesi oluşturun.',
  'summary' => 'Hazır şablonlar, sürükle-bırak editör, AI içerik/tasarım önerileri, önizleme ve yayınlama akışı tek merkezde.',
  'primary' => ['Site Tasarlat', 'urun-grubu/web-tasarim'],
  'secondary' => ['Site Oluştur', 'sitebuilder/create-demo'],
  'panel' => [
    'title' => 'Web siteniz için neler hazır?',
    'items' => ['Sektörünüze uygun sayfa yapısı', 'Mobil uyumlu ve hızlı arayüz', 'Yayın öncesi önizleme ve düzenleme'],
  ],
  'cards' => [
    ['icon' => 'AI', 'title' => 'AI Tasarım', 'text' => 'Sektör, firma adı ve hedefe göre sayfa yapısı, renk paleti, metin ve CTA üretir.', 'href' => 'sitebuilder/create-demo', 'action' => 'Oluştur'],
    ['icon' => 'BL', 'title' => 'Blok Editör', 'text' => 'Hero, hizmet, fiyat, referans, form, SSS ve iletişim bloklarını kolayca düzenleyin.', 'href' => 'sitebuilder/preview-public', 'action' => 'Önizle'],
    ['icon' => 'EX', 'title' => 'Yayınlama / Çıktı', 'text' => 'ZIP export, kaynak kod ve müşteri proje yönetimi paket iznine göre çalışır.', 'href' => 'sitebuilder/preview-public', 'action' => 'Paket Seç'],
  ],
  'features' => [['AI', 'Site tasarımı'], ['SEO', 'Meta önerileri'], ['Mobil', 'Responsive'], ['Export', 'ZIP akışı']],
  'final_cta' => [
    'title' => 'Hazır şablondan öteye geçmek ister misiniz?',
    'text' => 'Kurumsal site, e-ticaret veya özel web tasarım paketlerinden birini seçerek ekibimize sitenizi tasarlatabilirsiniz.',
    'primary' => ['Site Tasarlat', 'urun-grubu/web-tasarim'],
    'secondary' => ['SiteBuilder Paketleri', 'urun-grubu/sitebuilder'],
  ],
];
require __DIR__ . '/_service-page.php';
