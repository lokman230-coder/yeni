<?php
$service = [
  'kicker' => 'AI MobileBuilder',
  'title' => 'Kod bilmeden mobil uygulama tasarlayın, APK/AAB build sürecini tek merkezden yönetin.',
  'summary' => 'MobileBuilder; uygulama ekranları, ikon, splash, tema, önizleme, build kuyruğu ve AI destekli uygulama tasarımını birleştirir.',
  'primary' => ['Uygulama Tasarlat', 'urun-grubu/mobil-uygulama'],
  'secondary' => ['Uygulama Oluştur', 'mobilebuilder/create-demo'],
  'panel' => [
    'title' => 'Uygulamanız için neler hazır?',
    'items' => ['Radyo, mağaza, randevu ve kurumsal ekran akışları', 'APK/AAB ve kaynak kod paket seçenekleri', 'Bildirim, splash ve marka ayarları'],
  ],
  'cards' => [
    ['icon' => 'AI', 'title' => 'AI Uygulama Tasarımı', 'text' => 'Uygulama fikrine göre ekran akışı, alt menü, onboarding, metin ve renk önerileri üretir.', 'href' => 'mobilebuilder/create-demo', 'action' => 'Oluştur'],
    ['icon' => 'ŞB', 'title' => 'Şablonlar', 'text' => 'Restoran, randevu, eğitim, e-ticaret, radyo ve kurumsal uygulama şablonları.', 'href' => 'mobilebuilder/preview-public', 'action' => 'Önizle'],
    ['icon' => 'PK', 'title' => 'Çıktı Paketleri', 'text' => 'APK, AAB, PWA ve kaynak kod çıktıları uygun paket satın alındıktan sonra aktif olur.', 'href' => 'mobilebuilder/preview-public', 'action' => 'Paket Seç'],
  ],
  'features' => [['APK/AAB', 'Build kuyruğu'], ['AI', 'Ekran akışı'], ['PWA', 'Web app'], ['Log', 'Hata analizi']],
  'final_cta' => [
    'title' => 'Uygulamanızı ekibe tasarlatın',
    'text' => 'Radyo, randevu, e-ticaret veya kurumsal uygulama paketlerinden birini seçerek tasarım ve teslim sürecini başlatabilirsiniz.',
    'primary' => ['Uygulama Tasarlat', 'urun-grubu/mobil-uygulama'],
    'secondary' => ['MobileBuilder Paketleri', 'urun-grubu/mobilebuilder'],
  ],
];
require __DIR__ . '/_service-page.php';
