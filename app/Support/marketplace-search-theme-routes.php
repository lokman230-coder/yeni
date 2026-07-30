<?php
// v9.5.0 Marketplace expansion + admin smart search + theme asset stabilization
function ao_schema_ensure_v950() {
    static $done=false; if($done) return; $done=true;
    try { db()->exec("ALTER TABLE marketplace_listings MODIFY listing_type varchar(60) DEFAULT 'domain'"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS admin_search_index (id int(11) NOT NULL AUTO_INCREMENT, title varchar(160) NOT NULL, route varchar(190) NOT NULL, keywords text DEFAULT NULL, category varchar(100) DEFAULT NULL, is_active tinyint(1) DEFAULT 1, PRIMARY KEY(id), UNIQUE KEY uniq_route_title(route,title)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS marketplace_categories (id int(11) NOT NULL AUTO_INCREMENT, slug varchar(90) NOT NULL, name varchar(160) NOT NULL, listing_type varchar(60) DEFAULT 'service', is_active tinyint(1) DEFAULT 1, sort_order int(11) DEFAULT 0, PRIMARY KEY(id), UNIQUE KEY slug(slug)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE marketplace_feature_packages ADD UNIQUE KEY uniq_feature_days(days)"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("DELETE p1 FROM marketplace_feature_packages p1 JOIN marketplace_feature_packages p2 ON p1.days=p2.days AND p1.id>p2.id"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $cats=[['domain','Domain','domain',1],['web-design','Web Tasarım','web_design',2],['seo','SEO Paketleri','seo',3],['logo-design','Logo Tasarımı','logo_design',4],['digital-content','Dijital İçerikler','digital_content',5],['mobile-app','Mobil Uygulama','mobile_app',6],['hosting-service','Hosting Hizmeti','hosting',7],['software','Yazılım / Script','software',8]];
    foreach($cats as $c){ try{ db()->prepare("INSERT INTO marketplace_categories(slug,name,listing_type,sort_order,is_active) VALUES(?,?,?,?,1) ON DUPLICATE KEY UPDATE name=VALUES(name),listing_type=VALUES(listing_type),sort_order=VALUES(sort_order),is_active=1")->execute($c); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    foreach([['Öne Çıkarma 7 Gün',7,99],['Öne Çıkarma 15 Gün',15,179],['Öne Çıkarma 30 Gün',30,299],['Öne Çıkarma 60 Gün',60,499]] as $p){ try{ db()->prepare("INSERT INTO marketplace_feature_packages(name,days,price,currency,badge,is_active) VALUES(?,?,?,?,?,1) ON DUPLICATE KEY UPDATE name=VALUES(name),price=VALUES(price),currency=VALUES(currency),badge=VALUES(badge),is_active=1")->execute([$p[0],$p[1],$p[2],'TRY','Öne Çıkan']); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    $items=[
      ['Kredi Kartı Ayarları','admin/accounting/payment-fees','kredi kartı, kart komisyonu, sanal pos, iyzico, paytr, stripe, ödeme api, taksit, komisyon','Muhasebe'],
      ['Sanal POS Yönetimi','admin/accounting/payment-fees','sanal pos, ödeme, kredi kartı, paytr, iyzico, shopier, param, sipay','Muhasebe'],
      ['API Entegrasyonları','admin/api-integrations','api, entegrasyon, servis bağlantıları, webhook','API & Entegrasyonlar'],
      ['Registrarlar','admin/domain-center/registrars','domainnameapi, registrar, epp, domain kayıt, transfer, yenileme','Domain'],
      ['İletiMerkezi SMS','admin/notifications','sms, iletimerkezi, whatsapp, mail, bildirim, bakiye, özel mesaj','Bildirim'],
      ['Duyurular','admin/announcements','duyuru duyurular anons bildirim üst bar kayan yazı site mesajı','Bildirim'],
      ['Theme Center','admin/theme-center/themes','tema, görünüm, site teması, admin teması, müşteri paneli teması, önizleme','Görünüm'],
      ['Marketplace','admin/marketplace','marketplace, domain satışı, web tasarım, seo, logo, dijital içerik, öne çıkarma','Marketplace'],
      ['Ürünler','admin/product-center/products','ürün, paket, hosting, vps, hizmet, sil, düzenle','Ürün'],
      ['QA Scan Center','admin/qa-scan-center','qa scan sistem taraması sistem tarama rapor site analiz görsel test kalite kontrol pdf çalışmayan demo health','Sistem'],
      ['Sunucu API','admin/hosting-server/servers','whm, cpanel, directadmin, plesk, sunucu, hosting api','Hosting'],
      ['Build Center','admin/build-center','android sdk gradle jdk apk aab build merkezi mobilebuilder','Sistem'],
      ['APK AAB Build Kuyruğu','admin/build-center/queue','apk aab kuyruk gradle build log','Sistem']
    ];
    foreach($items as $it){ try{ db()->prepare("INSERT INTO admin_search_index(title,route,keywords,category,is_active) VALUES(?,?,?,?,1) ON DUPLICATE KEY UPDATE keywords=VALUES(keywords),category=VALUES(category),is_active=1")->execute($it); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
    try{ save_setting('ahost_version','25.0.0-rc25'); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
ao_schema_ensure_v950();


function ao_admin_search_seed_final(){
    try { ao_database_upgrade_check(true); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("DELETE FROM admin_search_index WHERE title LIKE '%?%' OR category LIKE '%?%' OR keywords LIKE '%?%'"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $rows = [
      ['Footer Ayarları','admin/settings?tab=frontend','Site','footer alt alan sosyal ikon ödeme iletişim telif copyright düzenle'],
      ['Site Menü Yönetimi','admin/menu-manager?type=site','Site','menü menu header ana menü link dropdown alt menü sırala'],
      ['Footer Menü Yönetimi','admin/menu-manager?type=footer','Site','footer menü alt link hızlı link kurumsal yardım hosting'],
      ['Mobil Menü Yönetimi','admin/menu-manager?type=mobile','Site','mobil mobile bottom menu alt menü telefon'],
      ['Genel Ayarlar','admin/settings?tab=general','Ayarlar','genel ayar site adı firma logo base url dil para birimi'],
      ['Yapay Zeka Ayarları','admin/ai-center','AI','ai al center yapay zeka openai gemini groq openrouter claude deepseek ollama api key model'],
      ['Domain Registrar Ayarları','admin/domain-center/registrars','Domain','registrar domainnameapi isimtescil nictr api epp nameserver'],
      ['Domain Transferleri','admin/domain-center/transfers','Domain','transfer epp kod domain transfer yeni transfer'],
      ['Vergiler ve KDV','admin/accounting/taxes','Muhasebe','kdv vergi oran fatura vergiler düzenle'],
      ['Destek Talepleri','admin/support/tickets','Destek','ticket destek talep cevap otomatik yanıt müşteri'],
      ['Setup Wizard','admin/setup-wizard','Kurulum','setup wizard kurulum sihirbaz ayarlar başlangıç'],
      ['Lisanslama Merkezi','admin/license-center','Lisans','lisans zip yükle codecanyon purchase code android paket domain'],
      ['QA Scan Center','admin/qa-scan-center','Kalite','qa scan sistem taraması sistem tarama rapor site analiz görsel test kalite kontrol pdf çalışmayan demo health'],
      ['Migration Bridge','admin/migration-bridge','Migration','whmcs wisecp blesta migration import aktarım köprü'],
      ['Provider Center','admin/provider-center','Provider','hosting provider whm cpanel plesk directadmin vps sunucu'],
      ['Bilgi Bankası','admin/knowledge-base','İçerik','yardım bilgi bankası akademi makale kategori düzenle'],
      ['Duyurular','admin/announcements','Bildirim','duyuru duyurular anons bildirim site üst bar kayan yazı haber ekle'],
      ['Yardım Kılavuzu','admin/help-center','Yardım','yardım klavuz kılavuz rehber footer menü ayar nasıl yapılır'],
    ];

    $rows = array_merge($rows, [
      ['Slider Yönetimi','admin/site-slider','Site & Görünüm','slider slayt hero ana sayfa menü altı banner vitrin resim video arka plan görsel yükle'],
      ['Hero Kartları','admin/site-heroes','Site & Görünüm','hero kartları sayfa başlığı hero alanı yazı boyutu arka plan renk genişlik içerik düzenle'],
      ['Site Tema Blokları','admin/builder-pro?target=site&template=home','Site & Görünüm','tema blokları blok düzenleme slider hero ana sayfa header footer ürün kartları domain arama fiyat tablosu sss aktif site teması'],
      ['Site Builder Sayfaları','admin/site-builder','Builder','site builder sayfa düzenleme landing page normal sayfa blok widget sürükle bırak editör'],
      ['Site Builder Live Editor','admin/site-builder/live-editor','Builder','site builder canlı editör sayfa düzenleme sürükle bırak'],
      ['Admin Panel Builder','admin/builder-pro?target=admin&template=dashboard','Builder','admin builder dashboard panel düzenleme blok'],
      ['Müşteri Panel Builder','admin/builder-pro?target=customer&template=dashboard','Builder','müşteri panel customer builder dashboard düzenleme'],
      ['Menü Yönetimi','admin/menu-manager','Menü','menü menu yönetim header üst menü alt menü dropdown link sırala düzenle'],
      ['Site Menü','admin/menu-manager?type=site','Menü','site menü ana menü header domain hosting tasarım marketplace referans'],
      ['Footer Menü','admin/menu-manager?type=footer','Menü','footer menü alt menü kurumsal yardım hosting link'],
      ['Mobil Menü','admin/menu-manager?type=mobile','Menü','mobil menü mobile bottom alt navigation'],
      ['Üst Bar Menü','admin/menu-manager?type=topbar','Menü','üst bar topbar menü destek bilgi bankası hakkımızda telefon kur ülke müşteri girişi'],
      ['Tema Merkezi','admin/theme-center/themes','Tema','tema theme prism default görünüm aktif pasif önizleme'],
      ['Prism Tema Ayarları','admin/settings/themes','Tema','prism tema renk header footer full width logo menü görünüm'],
      ['Header / Footer Ayarları','admin/settings?tab=frontend','Site','header footer logo telefon sosyal ödeme bülten iletişim full width'],
      ['Site Özellikleri','admin/settings/site-features','Site & Görünüm','site özellikleri core feature header üst bar menü footer sağ ikonlar slider iletişim seo referans ürün kart filtre bilgi bankası domain sorgulama duyuru ayar'],
      ['Site Araçları','admin/site-tools/design','Site & Görünüm','site araçları arac araclar tools whois dns ssl seo analiz domain değerlendirme hız gzip meta link ip blacklist cpanel lisans kelime robots sitemap kart arka plan arkaplan görünüm düzenle'],
      ['Kur Merkezi','admin/currency-center','Ayarlar','kur para birimi usd try tl döviz'],
      ['Çeviri Merkezi','admin/translation-center','Ayarlar','dil çeviri language türkçe ingilizce bayrak'],
      ['Cache Temizle','admin/cache-center','Sistem','cache önbellek temizle css js tema görünmüyor değişiklik gelmedi'],
      ['Yedekleme','admin/backup-center','Sistem','backup yedek veritabanı dışa aktar sql'],
      ['Update Center','admin/update-center','Sistem','migration güncelleme sql veritabanı upgrade'],
      ['Database Upgrade Wizard','admin/database-upgrade','Sistem','database upgrade veritabanı tablo kolon düzeltme'],
      ['Ürün Grupları','admin/product-center/groups','Ürünler','ürün grupları kategori hosting radyo mail cpanel domain grup'],
      ['Ürün Merkezi','admin/product-center/products','Ürünler','ürün hizmet paket hosting fiyat düzenle'],
      ['Paket Oluştur','admin/product-center/products?action=add','Ürünler','paket oluştur hosting radyo web script android uygulama ürün ekle hizmet ekle fiyat'],
      ['Radyo Hosting Paketleri','admin/product-center/products?action=add','Ürünler','radyo hosting autodj yayın paneli radyo paketi oluştur'],
      ['Web Script Paketleri','admin/product-center/products?action=add','Ürünler','web script php yazılım kaynak kod paket oluştur'],
      ['Android Uygulama Paketleri','admin/product-center/products?action=add','Ürünler','android uygulama apk aab mobil paket oluştur'],
      ['Domain Ücretlendirmesi','admin/domain-center/pricing','Domain','domain fiyat tld yenileme transfer ücret'],
      ['Registrar Ayarları','admin/domain-center/registrars','Domain','domainnameapi registrar api key reseller id nameserver'],
      ['Sağ Butonlar','admin/support/widget-settings','Site & Görünüm','sağ butonlar destek widget ikon bar whatsapp telefon canlı destek ai bilgi bankası düzenle'],
    ]);

    try{
      $q=db()->prepare('INSERT INTO admin_search_index(title,route,category,keywords,is_active) VALUES(?,?,?,?,1) ON DUPLICATE KEY UPDATE keywords=VALUES(keywords), category=VALUES(category), is_active=1');
      foreach($rows as $r) $q->execute($r);
    }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}

function ao_admin_search_results($q) {
    ao_schema_ensure_v950(); ao_admin_search_seed_final();
    $raw=trim((string)$q); if($raw==='') return [];
    $norm=function($s){
        $s=mb_strtolower((string)$s,'UTF-8');
        $s=str_replace(['ı','ğ','ü','ş','ö','ç'], ['i','g','u','s','o','c'], $s);
        $s=preg_replace('/[^a-z0-9]+/u',' ', $s);
        return trim(preg_replace('/\s+/',' ', $s));
    };
    $queryNorm=$norm($raw);
    $terms=array_values(array_filter(explode(' ', $queryNorm), function($t){ return mb_strlen($t,'UTF-8') >= 3; }));
    if(!$terms && $queryNorm!=='') $terms=[$queryNorm];
    $intentGroups=[
        ['urun','urunler','paket','paketler','hizmet','servis','product'],
        ['ekle','eklemek','eklemekistiyorum','olustur','olusturmak','yeni','add','create'],
        ['duzenle','duzenlemek','ayar','yonet','yonetim','edit'],
        ['slider','slayt','hero','banner','vitrin'],
        ['domain','alan','registrar','nameserver','whois','dns'],
        ['musteri','client','kullanici','uye'],
        ['fatura','odeme','tahsilat','finans','muhasebe'],
        ['hosting','sunucu','server','cpanel','whm','plesk','directadmin'],
        ['menu','menuler','header','footer','topbar','mobil'],
        ['tema','theme','prism','gorunum','renk'],
        ['arac','araclar','tools','sitearaclari','whois','ssl','dns','seo','analiz','degerlendirme'],
    ];
    $expandTerms=function($terms) use ($intentGroups){
        $expanded=$terms;
        foreach($terms as $term){
            foreach($intentGroups as $group){
                if(in_array($term,$group,true)){
                    $expanded=array_merge($expanded,$group);
                }
            }
        }
        return array_values(array_unique($expanded));
    };
    $expandedTerms=$expandTerms($terms);
    $fallbackRows=[
        ['title'=>'Ürün Merkezi','route'=>'admin/product-center/products','category'=>'Ürünler','keywords'=>'ürün ürünler hizmet paket düzenle fiyat stok hosting domain radyo web script android uygulama'],
        ['title'=>'Paket / Ürün Oluştur','route'=>'admin/product-center/products?action=add','category'=>'Ürünler','keywords'=>'ürün ekle ürün eklemek istiyorum yeni ürün paket oluştur hizmet ekle hosting paketi radyo hosting web script android uygulama'],
        ['title'=>'Ürün Grupları','route'=>'admin/product-center/groups','category'=>'Ürünler','keywords'=>'ürün grubu kategori grup hosting domain radyo web script android'],
        ['title'=>'Slider Yönetimi','route'=>'admin/site-slider','category'=>'Site & Görünüm','keywords'=>'slider slayt hero banner vitrin ana sayfa görsel video düzenle ekle oluştur'],
        ['title'=>'Hero Kartları','route'=>'admin/site-heroes','category'=>'Site & Görünüm','keywords'=>'hero kartları sayfa başlığı hero alanı yazı boyutu arka plan renk genişlik içerik düzenle'],
        ['title'=>'Site Tema Blokları','route'=>'admin/builder-pro?target=site&template=home','category'=>'Site & Görünüm','keywords'=>'tema blokları blok düzenle slider hero ana sayfa header footer ürün kartları domain arama site builder pro tasarım'],
        ['title'=>'Site Builder Sayfaları','route'=>'admin/site-builder','category'=>'Builder','keywords'=>'site builder sayfa düzenleme landing page normal sayfa özel içerik'],
        ['title'=>'Menü Yönetimi','route'=>'admin/menu-manager','category'=>'Menü','keywords'=>'menü menu header footer topbar mobil alt menü link dropdown düzenle ekle'],
        ['title'=>'Sağ Butonlar','route'=>'admin/support/widget-settings','category'=>'Site & Görünüm','keywords'=>'sağ butonlar destek widget ikon bar whatsapp telefon canlı destek ai bilgi bankası düzenle'],
        ['title'=>'Site Araçları','route'=>'admin/site-tools/design','category'=>'Site & Görünüm','keywords'=>'site araçları arac araclar tools whois dns ssl seo analiz domain değerlendirme hız gzip meta link ip blacklist cpanel lisans kelime robots sitemap kart arka plan arkaplan görünüm düzenle'],
        ['title'=>'Domain Center','route'=>'admin/domain-center','category'=>'Domain','keywords'=>'domain alan adı registrar nameserver whois dns transfer yenileme kayıt'],
        ['title'=>'Registrar Ayarları','route'=>'admin/domain-center/registrars','category'=>'Domain','keywords'=>'domainnameapi registrar api key nameserver epp whois'],
        ['title'=>'Müşteri Ekle','route'=>'admin/customers/add','category'=>'Müşteriler','keywords'=>'müşteri ekle yeni müşteri kullanıcı üye client oluştur'],
        ['title'=>'Fatura / Finans','route'=>'admin/accounting/invoices','category'=>'Finans','keywords'=>'fatura ödeme tahsilat finans muhasebe invoice ödeme al'],
        ['title'=>'Hosting Hesapları','route'=>'admin/hosting-server/accounts','category'=>'Hosting','keywords'=>'hosting hesapları şifre değiştir cpanel whm sunucu panel müşteri hosting'],
        ['title'=>'Tema Merkezi','route'=>'admin/theme-center/themes','category'=>'Tema','keywords'=>'tema theme prism görünüm renk tasarım aktif pasif önizleme'],
    ];

    try{
        $rows=db()->query("SELECT * FROM admin_search_index WHERE is_active=1")->fetchAll() ?: [];
        $seen=[];
        foreach($rows as $r) $seen[($r['route'] ?? '').'|'.($r['title'] ?? '')]=true;
        foreach($fallbackRows as $r){
            $key=$r['route'].'|'.$r['title'];
            if(empty($seen[$key])) $rows[]=$r;
        }
        $scored=[];
        foreach($rows as $r){
            $hay=$norm(($r['title'] ?? '').' '.($r['category'] ?? '').' '.($r['keywords'] ?? '').' '.($r['route'] ?? ''));
            $titleNorm=$norm($r['title'] ?? '');
            $routeNorm=$norm($r['route'] ?? '');
            $score=0;
            if($queryNorm!=='' && str_contains($hay,$queryNorm)) $score+=80;
            foreach($expandedTerms as $t){
                if(str_contains($hay,$t)) $score+=18;
                if(str_contains($titleNorm,$t)) $score+=26;
                if(str_contains($routeNorm,$t)) $score+=10;
                foreach(explode(' ',$hay) as $word){
                    if(strlen($t) >= 4 && strlen($word) >= 4 && levenshtein($t,$word) <= 1){ $score+=6; break; }
                }
            }
            if(preg_match('/\b(ekle|eklemek|olustur|yeni|add|create)\b/',$queryNorm) && preg_match('/\b(ekle|olustur|yeni|add|create)\b/',$hay)) $score+=35;
            if(preg_match('/\b(urun|paket|hizmet|servis|product)\b/',$queryNorm) && preg_match('/\b(urun|paket|hizmet|product)\b/',$hay)) $score+=45;
            if(preg_match('/\b(slider|slayt|hero|banner)\b/',$queryNorm) && preg_match('/\b(slider|slayt|hero|banner)\b/',$hay)) $score+=55;
            if($score>0){ $r['_score']=$score; $scored[]=$r; }
        }
        usort($scored,function($a,$b){
            $d=($b['_score'] ?? 0)<=>($a['_score'] ?? 0);
            return $d ?: strcasecmp($a['title'] ?? '', $b['title'] ?? '');
        });
        return array_slice($scored,0,30);
    }catch(Throwable $e){
        return [];
    }
}

function ao_theme_css_href($area='site') {
    // RC11: Tema CSS dosyaları header ve sayfa sistemini ezmemesi için aktif yükten çıkarıldı.
    // Tema seçimi body class + CSS değişkenleriyle çalışır; görsel sistem tek CSS katmanından yönetilir.
    return '';
}
if (!function_exists('ao_theme_body_class')) {
function ao_theme_body_class($area='site') {
    $t = ao_active_theme($area);
    $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower((string)($t['slug'] ?? 'default')));
    if ($slug === 'prism') $slug = 'ahost-prism';
    return 'theme-' . $slug;
}
}
function ao_theme_preview_html($theme) {
    $style='--ao-primary:'.e($theme['primary_color'] ?? '#2563eb').';--ao-secondary:'.e($theme['secondary_color'] ?? '#0f172a').';--ao-font:'.e($theme['font_family'] ?? 'Inter, Arial, sans-serif').';';
    return '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tema Önizleme - '.e($theme['name'] ?? '').'</title><link rel="stylesheet" href="'.e(url('public/assets/css/site/page.css')).'"><style>body{margin:0;font-family:var(--ao-font)}.preview-ribbon{position:fixed;right:18px;top:18px;background:#111827;color:#fff;border-radius:999px;padding:10px 14px;font-weight:900;z-index:99}.preview-card{padding:38px;border-radius:24px;background:#fff;color:#0f172a;max-width:960px;margin:32px auto;box-shadow:0 18px 60px #0f172a22}.hero-demo{min-height:420px;display:flex;align-items:center;justify-content:center;text-align:center;background:linear-gradient(135deg,var(--ao-primary),var(--ao-secondary));color:#fff}.hero-demo h1{font-size:52px;margin:0 0 12px}.hero-demo a{display:inline-block;background:#fff;color:var(--ao-primary);padding:13px 18px;border-radius:14px;text-decoration:none;font-weight:900}</style></head><body class="'.ao_theme_body_class($theme['area'] ?? 'site').'" style="'.$style.'"><div class="preview-ribbon">Önizleme: '.e($theme['name'] ?? '').'</div><section class="hero-demo"><div><h1>Ahost One</h1><p>Bu tema siteye uygulanmadan önce canlı önizleniyor.</p><a href="#">Domain Sorgula</a></div></section><div class="preview-card"><h2>Renk ve Font Testi</h2><p>Primary ve secondary renkler, butonlar, hero ve CTA alanlarında uygulanır.</p></div></body></html>';
}

function ao_iletimerkezi_channel() {
    try { $q=db()->prepare("SELECT * FROM notification_channels WHERE provider='iletimerkezi' AND channel_type='sms' LIMIT 1"); $q->execute(); return $q->fetch() ?: null; } catch(Throwable $e) { return null; }
}
function ao_iletimerkezi_cfg($channel=null) {
    $channel = $channel ?: ao_iletimerkezi_channel();
    $cfg = $channel ? json_decode($channel['config_json'] ?: '{}', true) : [];
    return is_array($cfg) ? $cfg : [];
}
function ao_iletimerkezi_xml($cfg, $type, $recipient='', $message='') {
    $key=e($cfg['api_key'] ?? ''); $hash=e($cfg['api_hash'] ?? ''); $sender=e($cfg['sender_id'] ?? $cfg['sender'] ?? 'AHOSTONE');
    if ($type==='balance') return "<request><authentication><key>{$key}</key><hash>{$hash}</hash></authentication></request>";
    $recipient=e($recipient); $message=htmlspecialchars($message, ENT_XML1|ENT_COMPAT, 'UTF-8');
    return "<request><authentication><key>{$key}</key><hash>{$hash}</hash></authentication><order><sender>{$sender}</sender><sendDateTime></sendDateTime><iys>".e($cfg['iys'] ?? '0')."</iys><iysList>".e($cfg['iys_list'] ?? 'BIREYSEL')."</iysList><message><text><![CDATA[{$message}]]></text><receipents><number>{$recipient}</number></receipents></message></order></request>";
}
function ao_iletimerkezi_request($type, $xml) {
    $url = 'https://api.iletimerkezi.com/v1/'.($type==='balance'?'get-balance':'send-sms');
    if (!function_exists('curl_init')) return ['ok'=>false,'status'=>'curl_missing','body'=>'PHP cURL aktif değil.'];
    $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_POST=>1,CURLOPT_POSTFIELDS=>$xml,CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>40,CURLOPT_HTTPHEADER=>['Content-Type: text/xml'],CURLOPT_SSL_VERIFYHOST=>1,CURLOPT_SSL_VERIFYPEER=>0]);
    $body=curl_exec($ch); $err=curl_error($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    $ok=($err==='' && $code>=200 && $code<300 && stripos((string)$body,'<status>')!==false && !preg_match('/<status>\s*(?:false|error|0)\s*<\/status>/i',(string)$body));
    return ['ok'=>$ok,'status'=>$code ?: 'error','body'=>$body ?: $err];
}
function ao_iletimerkezi_send($recipient, $message, $event='manual_sms') {
    $ch=ao_iletimerkezi_channel(); $cfg=ao_iletimerkezi_cfg($ch); $test=(int)($ch['test_mode'] ?? 1)===1 || ($ch['status'] ?? 'inactive')!=='active';
    $provider='iletimerkezi';
    if ($test) { $body='TEST MODE: '.$message; $status='test'; $response='Test modunda gerçek SMS gönderilmedi.'; }
    else { $res=ao_iletimerkezi_request('send', ao_iletimerkezi_xml($cfg,'send',$recipient,$message)); $status=$res['ok']?'sent':'error'; $response=$res['body']; }
    try{ db()->prepare("INSERT INTO notification_logs(channel_type,provider,recipient,event_key,subject,message,status,response_body,payload_json,sent_at) VALUES('sms',?,?,?,?,?,?,?,?,NOW())")->execute([$provider,$recipient,$event,'İletiMerkezi SMS',$message,$status,$response,json_encode(['provider'=>$provider,'test'=>$test],JSON_UNESCAPED_UNICODE)]); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return ['ok'=>$status==='sent' || $status==='test','status'=>$status,'message'=>$response];
}
function ao_iletimerkezi_balance() {
    $ch=ao_iletimerkezi_channel(); $cfg=ao_iletimerkezi_cfg($ch); $res=ao_iletimerkezi_request('balance', ao_iletimerkezi_xml($cfg,'balance'));
    $text=$res['body'];
    if (preg_match('/<balance>\s*([^<]+)/i',(string)$text,$m)) $text=trim($m[1]);
    try{ db()->prepare("INSERT INTO sms_balance_checks(provider,balance_text,raw_response,status) VALUES('iletimerkezi',?,?,?)")->execute([mb_substr((string)$text,0,180),(string)$res['body'],$res['ok']?'success':'error']); }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return ['ok'=>$res['ok'],'balance'=>$text,'raw'=>$res['body']];
}
function ao_template_render($event, $vars=[]) {
    try{ $q=db()->prepare('SELECT * FROM notification_templates WHERE event_key=? AND is_active=1 LIMIT 1'); $q->execute([$event]); $t=$q->fetch(); $body=$t['sms_body'] ?? ''; }catch(Throwable $e){ $body=''; }
    foreach($vars as $k=>$v) $body=str_replace('{'.$k.'}', (string)$v, $body);
    return $body ?: ($vars['message'] ?? 'Ahost One bildirimi');
}


if ($route === 'admin/theme-center/preview') {
    require_admin(); ao_schema_ensure_v188();
    $id=(int)($_GET['id'] ?? 0); $slug=trim($_GET['slug'] ?? ''); $area=trim($_GET['area'] ?? 'site') ?: 'site';
    try { if($id){ $q=db()->prepare('SELECT * FROM themes WHERE id=? LIMIT 1'); $q->execute([$id]); } else { $q=db()->prepare('SELECT * FROM themes WHERE slug=? AND area=? LIMIT 1'); $q->execute([$slug,$area]); } $theme=$q->fetch(); } catch(Throwable $e){ $theme=null; }
    if(!$theme) { http_response_code(404); echo 'Tema bulunamadı.'; exit; }
    $_SESSION['theme_preview_id']=(int)$theme['id'];
    $target = ($theme['area'] ?? 'site') === 'admin' ? 'admin/dashboard' : (($theme['area'] ?? 'site') === 'client' ? 'client' : '');
    redirect_to($target.'?theme_preview='.(int)$theme['id']);
}

if ($route === 'admin/theme-center/preview-exit') { require_admin(); unset($_SESSION['theme_preview_id']); redirect_to('admin/theme-center/themes'); }
if ($route === 'admin/theme-center/apply-preview') { require_admin(); ao_schema_ensure_v188(); $id=(int)($_GET['id'] ?? ($_SESSION['theme_preview_id'] ?? 0)); try{ $q=db()->prepare('SELECT * FROM themes WHERE id=? LIMIT 1'); $q->execute([$id]); $t=$q->fetch(); if(!$t) throw new Exception('Tema bulunamadı.'); db()->prepare('UPDATE themes SET is_active=0 WHERE area=?')->execute([$t['area']]); db()->prepare('UPDATE themes SET is_active=1 WHERE id=?')->execute([$id]); unset($_SESSION['theme_preview_id']); flash('success','Önizlenen tema uygulandı.'); }catch(Throwable $e){ flash('error','Tema uygulanamadı: '.$e->getMessage()); } redirect_to('admin/theme-center/themes'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='client/theme-save') { require_customer(); verify_csrf(); ao_schema_ensure_v188(); $c=current_customer(); try{ $site=(int)($_POST['site_theme_id']??0); $client=(int)($_POST['client_theme_id']??0); db()->prepare('INSERT INTO client_preferences(client_id,site_theme_id,client_theme_id) VALUES(?,?,?) ON DUPLICATE KEY UPDATE site_theme_id=VALUES(site_theme_id),client_theme_id=VALUES(client_theme_id)')->execute([(int)$c['id'],$site?:null,$client?:null]); flash('success','Tema tercihiniz kaydedildi.'); }catch(Throwable $e){ flash('error','Tema kaydedilemedi: '.$e->getMessage()); } redirect_to('client/theme'); }
if ($_SERVER['REQUEST_METHOD']==='POST' && $route==='client/builder-save') { require_customer(); verify_csrf(); ao_schema_ensure_v188(); $c=current_customer(); try{ $layout=$_POST['builder_layout_json'] ?? '{}'; json_decode($layout,true); if(json_last_error()!==JSON_ERROR_NONE) throw new Exception('Geçersiz layout JSON.'); db()->prepare('INSERT INTO client_preferences(client_id,builder_layout_json) VALUES(?,?) ON DUPLICATE KEY UPDATE builder_layout_json=VALUES(builder_layout_json)')->execute([(int)$c['id'],$layout]); flash('success','Panel düzeniniz kaydedildi.'); }catch(Throwable $e){ flash('error','Panel düzeni kaydedilemedi: '.$e->getMessage()); } redirect_to('client/builder'); }

if ($route === 'admin/notifications/iletimerkezi-balance') {
    require_admin(); $res=ao_iletimerkezi_balance(); flash($res['ok']?'success':'error','İletiMerkezi bakiye sonucu: '.mb_substr((string)$res['balance'],0,240)); redirect_to('admin/notifications');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'admin/notifications/send-custom-sms') {
    require_admin(); verify_csrf(); $to=trim($_POST['recipient']??''); $msg=trim($_POST['message']??''); if(!$to||!$msg){ flash('error','Alıcı ve mesaj zorunlu.'); } else { $res=ao_iletimerkezi_send($to,$msg,'custom_sms'); flash($res['ok']?'success':'error','Özel SMS sonucu: '.$res['status']); } redirect_to('admin/notifications');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'admin/notifications/send-custom-email') {
    require_admin(); verify_csrf();
    $to=trim((string)($_POST['recipient']??'')); $subject=trim((string)($_POST['subject']??'')); $msg=trim((string)($_POST['message']??''));
    if(!$to || !filter_var($to,FILTER_VALIDATE_EMAIL) || !$subject || !$msg){ flash('error','Gecerli alici, konu ve mesaj zorunlu.'); }
    else { $res=ao_send_email_notification($to,$subject,$msg,'custom_email'); flash($res['ok']?'success':'error','Ozel mail sonucu: '.$res['message']); }
    redirect_to('admin/notifications');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'admin/notifications/send-bulk-sms') {
    require_admin(); verify_csrf();
    $mode=trim((string)($_POST['target_mode'] ?? 'selected'));
    $msg=trim((string)($_POST['message'] ?? ''));
    $normalize=function($phone){
        $p=preg_replace('/\D+/','',(string)$phone);
        if($p==='') return '';
        if(strlen($p)===10) $p='90'.$p;
        if(strlen($p)===11 && str_starts_with($p,'0')) $p='9'.$p;
        return strlen($p)>=10 ? $p : '';
    };
    $targets=[];
    try {
        if($msg==='') throw new Exception('Mesaj zorunlu.');
        if($mode==='manual'){
            foreach(preg_split('/[\s,;]+/', (string)($_POST['manual_recipients'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $phone){ $p=$normalize($phone); if($p) $targets[$p]=['phone'=>$p,'vars'=>[]]; }
        } elseif($mode==='all_active'){
            $rows=db()->query("SELECT * FROM customers WHERE deleted_at IS NULL AND status='active' AND COALESCE(phone,'')<>'' ORDER BY id ASC LIMIT 500")->fetchAll();
            foreach($rows as $c){ $p=$normalize($c['phone'] ?? ''); if($p) $targets[$p]=['phone'=>$p,'vars'=>['ad'=>$c['first_name']??'','soyad'=>$c['last_name']??'','email'=>$c['email']??'']]; }
        } else {
            $ids=array_values(array_filter(array_map('intval', (array)($_POST['customer_ids'] ?? []))));
            if($ids){
                $in=implode(',', array_fill(0,count($ids),'?'));
                $q=db()->prepare("SELECT * FROM customers WHERE id IN ($in) AND deleted_at IS NULL AND COALESCE(phone,'')<>''");
                $q->execute($ids); $rows=$q->fetchAll();
                foreach($rows as $c){ $p=$normalize($c['phone'] ?? ''); if($p) $targets[$p]=['phone'=>$p,'vars'=>['ad'=>$c['first_name']??'','soyad'=>$c['last_name']??'','email'=>$c['email']??'']]; }
            }
        }
        if(!$targets) throw new Exception('Gönderilecek geçerli telefon bulunamadı.');
        $ok=0; $fail=0;
        foreach(array_slice($targets,0,500) as $target){
            $text=$msg; foreach($target['vars'] as $k=>$v) $text=str_replace('{'.$k.'}', (string)$v, $text);
            $res=ao_iletimerkezi_send($target['phone'],$text,'bulk_sms');
            if(!empty($res['ok'])) $ok++; else $fail++;
        }
        flash($fail?'warning':'success','Toplu SMS tamamlandı. Başarılı: '.$ok.' / Hatalı: '.$fail);
    } catch(Throwable $e) { flash('error','Toplu SMS gönderilemedi: '.$e->getMessage()); }
    redirect_to('admin/notifications');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'admin/notifications/send-bulk-email') {
    require_admin(); verify_csrf();
    $mode=trim((string)($_POST['target_mode'] ?? 'selected'));
    $subject=trim((string)($_POST['subject'] ?? ''));
    $msg=trim((string)($_POST['message'] ?? ''));
    $targets=[];
    try {
        if($subject==='' || $msg==='') throw new Exception('Konu ve mesaj zorunlu.');
        if($mode==='manual'){
            foreach(preg_split('/[\s,;]+/', (string)($_POST['manual_recipients'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $email){ $email=trim($email); if(filter_var($email,FILTER_VALIDATE_EMAIL)) $targets[strtolower($email)]=['email'=>$email,'vars'=>[]]; }
        } elseif($mode==='all_active'){
            $rows=db()->query("SELECT * FROM customers WHERE deleted_at IS NULL AND status='active' AND COALESCE(email,'')<>'' ORDER BY id ASC LIMIT 500")->fetchAll();
            foreach($rows as $c){ $email=trim((string)($c['email'] ?? '')); if(filter_var($email,FILTER_VALIDATE_EMAIL)) $targets[strtolower($email)]=['email'=>$email,'vars'=>['ad'=>$c['first_name']??'','soyad'=>$c['last_name']??'','email'=>$email]]; }
        } else {
            $ids=array_values(array_filter(array_map('intval', (array)($_POST['customer_ids'] ?? []))));
            if($ids){
                $in=implode(',', array_fill(0,count($ids),'?'));
                $q=db()->prepare("SELECT * FROM customers WHERE id IN ($in) AND deleted_at IS NULL AND COALESCE(email,'')<>''");
                $q->execute($ids); $rows=$q->fetchAll();
                foreach($rows as $c){ $email=trim((string)($c['email'] ?? '')); if(filter_var($email,FILTER_VALIDATE_EMAIL)) $targets[strtolower($email)]=['email'=>$email,'vars'=>['ad'=>$c['first_name']??'','soyad'=>$c['last_name']??'','email'=>$email]]; }
            }
        }
        if(!$targets) throw new Exception('Gonderilecek gecerli e-posta bulunamadi.');
        $ok=0; $fail=0;
        foreach(array_slice($targets,0,500) as $target){
            $body=$msg; $sub=$subject;
            foreach($target['vars'] as $k=>$v){ $body=str_replace('{'.$k.'}', (string)$v, $body); $sub=str_replace('{'.$k.'}', (string)$v, $sub); }
            $res=ao_send_email_notification($target['email'],$sub,$body,'bulk_email');
            if(!empty($res['ok'])) $ok++; else $fail++;
        }
        flash($fail?'warning':'success','Toplu mail tamamlandi. Basarili: '.$ok.' / Hatali: '.$fail);
    } catch(Throwable $e) { flash('error','Toplu mail gonderilemedi: '.$e->getMessage()); }
    redirect_to('admin/notifications');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $route === 'client/domains/epp-request') {
    require_customer(); $domainId=(int)($_POST['domain_id']??0); $customer=current_customer();
    try { $q=db()->prepare('SELECT * FROM domains WHERE id=? AND customer_id=? LIMIT 1'); $q->execute([$domainId,$customer['id']]); $d=$q->fetch(); if(!$d) throw new Exception('Domain bulunamadı.');
      $epp=$d['epp_code'] ?? ''; if($epp==='') { $res=ao_domain_generate_epp($d); if(empty($res['ok'])) throw new Exception($res['message'] ?? 'Registrar EPP kodu döndürmedi.'); $epp=$res['epp'] ?? ''; }
      if($epp==='') throw new Exception('Registrar EPP kodu döndürmedi.');
      $_SESSION['last_epp_popup']=['domain'=>$d['domain_name'],'epp'=>$epp];
      $phone=$customer['phone'] ?? ''; if($phone) ao_iletimerkezi_send($phone, ao_template_render('domain_epp_code',['customer_name'=>trim(($customer['first_name']??'').' '.($customer['last_name']??'')),'domain'=>$d['domain_name'],'epp_code'=>$epp]), 'domain_epp_code');
      flash('success',$phone ? 'EPP kodu hazırlandı ve müşteri telefonuna SMS bildirimi gönderildi.' : 'EPP kodu hazırlandı.');
    } catch(Throwable $e){ flash('error','EPP alınamadı: '.$e->getMessage()); }
    redirect_to('client/domains/view?id='.$domainId.ao_tab_hash('epp'));
}

