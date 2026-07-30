<?php
$service = [
 'kicker'=>'VPS & Sunucu Merkezi',
 'title'=>'Performans odaklı VPS, dedicated ve yönetilen sunucu çözümleri.',
 'summary'=>'Projenizin ihtiyacına göre CPU, RAM, disk, lokasyon ve yönetim desteğiyle ölçeklenebilir sunucu seçeneklerini keşfedin.',
 'panel'=>[
   'title'=>'Sunucu seçiminde öne çıkanlar',
   'items'=>['Kaynağı net ve yükseltilebilir paketler', 'Lokasyon ve panel seçenekleri', 'Kurulum, güvenlik ve yönetim desteği'],
 ],
 'cards'=>[
   ['icon'=>'☁️','title'=>'VPS / VDS','text'=>'CPU, RAM, disk ve trafik ihtiyacınıza göre ölçeklenebilir sanal sunucular.','href'=>'urunler','action'=>'İncele'],
   ['icon'=>'🏢','title'=>'Dedicated Sunucu','text'=>'Yüksek performans isteyen projeler için özel sunucu seçenekleri.','href'=>'teklif','action'=>'Teklif Al'],
   ['icon'=>'🛡','title'=>'Yönetilen Sunucu','text'=>'Bakım, güvenlik, yedekleme ve panel yönetimi için uzman destek.','href'=>'dijital-hizmetler','action'=>'Detay Al'],
 ],
 'features'=>[['CPU/RAM','Net kaynak'],['Lokasyon','Çoklu bölge'],['Panel','cPanel/Plesk'],['Destek','Yönetim hizmeti']]
];
require __DIR__.'/_service-page.php';
