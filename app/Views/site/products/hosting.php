<?php
$service = [
 'kicker'=>'Hosting Center',
 'title'=>'Web hosting, WordPress hosting ve reseller paketleriyle sitenizi güvenle yayına alın.',
 'summary'=>'Hızlı, güvenli ve ölçeklenebilir hosting paketleriyle web siteniz için doğru başlangıcı yapın.',
 'panel'=>[
   'title'=>'Hosting paketinde sizi neler bekler?',
   'items'=>['Ücretsiz SSL ve hızlı yayın başlangıcı', 'WordPress ve kurumsal site ihtiyaçlarına uygun seçenekler', 'Yükseltilebilir kaynak ve destek seçenekleri'],
 ],
 'cards'=>[
   ['icon'=>'🖥','title'=>'Web Hosting','text'=>'Başlangıç, profesyonel ve kurumsal siteler için dengeli hosting seçenekleri.','href'=>'urunler','action'=>'İncele'],
   ['icon'=>'🚀','title'=>'WordPress Hosting','text'=>'WordPress siteleri için hızlı kurulum, SSL, yedekleme ve performans odağı.','href'=>'urunler','action'=>'Paketleri Gör'],
   ['icon'=>'🧰','title'=>'Reseller / Özel Paket','text'=>'Ajanslar ve çoklu site yöneten ekipler için genişletilebilir kaynak seçenekleri.','href'=>'teklif','action'=>'Teklif Al'],
 ],
 'features'=>[['99.9%','Uptime hedefi'],['7/24','Destek akışı'],['SSL','Ücretsiz sertifika'],['AI','Paket önerisi']]
];
require __DIR__.'/_service-page.php';
