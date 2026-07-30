<?php
function ao_catalog_slug($value){
    $value=mb_strtolower(trim((string)$value),'UTF-8');
    $value=strtr($value,['ç'=>'c','ğ'=>'g','ı'=>'i','ö'=>'o','ş'=>'s','ü'=>'u']);
    return trim(preg_replace('/[^a-z0-9]+/','-',$value),'-');
}
function ao_catalog_ensure_showcase_schema(){
    try{ db()->exec("CREATE TABLE IF NOT EXISTS portfolio_references (
        id INT AUTO_INCREMENT PRIMARY KEY,title VARCHAR(190) NOT NULL,slug VARCHAR(220) NOT NULL UNIQUE,
        reference_type VARCHAR(40) DEFAULT 'website',sector VARCHAR(120) NULL,short_description TEXT NULL,
        description LONGTEXT NULL,image_url VARCHAR(255) NULL,cover_image_url VARCHAR(255) NULL,card_background_url VARCHAR(255) NULL,logo_url VARCHAR(255) NULL,project_url VARCHAR(255) NULL,
        technologies VARCHAR(255) NULL,is_featured TINYINT(1) DEFAULT 0,is_active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        KEY reference_type(reference_type),KEY is_active(is_active),KEY sort_order(sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Throwable $e){}
    foreach([
        "ALTER TABLE portfolio_references ADD COLUMN project_url VARCHAR(255) NULL AFTER logo_url",
        "ALTER TABLE portfolio_references ADD COLUMN cover_image_url VARCHAR(255) NULL AFTER image_url",
        "ALTER TABLE portfolio_references ADD COLUMN card_background_url VARCHAR(255) NULL AFTER cover_image_url",
        "ALTER TABLE portfolio_references ADD COLUMN logo_url VARCHAR(255) NULL AFTER cover_image_url",
    ] as $sql){ try{ db()->exec($sql); }catch(Throwable $e){} }
}
function ao_catalog_normalize_project_url($url){
    $url=trim((string)$url);
    if($url==='' || $url==='#') return '';
    if(!preg_match('~^https?://~i',$url)) $url='https://'.$url;
    return filter_var($url,FILTER_VALIDATE_URL) ? $url : '';
}
function ao_catalog_reference_screenshot_is_ready($bytes){
    if(!is_string($bytes) || strlen($bytes)<4096) return false;
    $info=@getimagesizefromstring($bytes);
    if(!$info || empty($info[0]) || empty($info[1])) return false;
    return (int)$info[0] >= 800 && (int)$info[1] >= 450;
}
function ao_catalog_reference_screenshot($projectUrl,$slug){
    $projectUrl=ao_catalog_normalize_project_url($projectUrl);
    $slug=ao_catalog_slug($slug ?: parse_url($projectUrl,PHP_URL_HOST) ?: 'reference');
    if($projectUrl==='' || $slug==='') return '';
    $dir=dirname(__DIR__).'/public/assets/uploads/references';
    if(!is_dir($dir)) @mkdir($dir,0775,true);
    if(!is_dir($dir) || !is_writable($dir)) return '';
    $bridge=dirname(__DIR__).'/app/Services/PHPScreenshotBridge.php';
    if(is_file($bridge)){
        require_once $bridge;
        if(class_exists('PHPScreenshotBridge')){
            $result=PHPScreenshotBridge::captureTo($projectUrl,$dir,$slug.'-screenshot','Reference',1280,900,$slug,[
                'wait_ms'=>12000,
                'timeout'=>35,
                'full_page'=>false,
            ]);
            if(!empty($result['success']) && !empty($result['real']) && !empty($result['file']) && is_file($result['file'])){
                return 'public/assets/uploads/references/'.basename($result['file']);
            }
        }
    }
    $target=$dir.'/'.$slug.'-screenshot.jpg';
    $providers=[
        'https://image.thum.io/get/width/1280/crop/820/noanimate/'.rawurlencode($projectUrl),
    ];
    $context=stream_context_create(['http'=>['timeout'=>12,'user_agent'=>'AhostOneReferenceBot/1.0']]);
    foreach($providers as $provider){
        $bytes=@file_get_contents($provider,false,$context);
        if(ao_catalog_reference_screenshot_is_ready($bytes)){
            if(@file_put_contents($target,$bytes)!==false){
                return 'public/assets/uploads/references/'.basename($target);
            }
        }
    }
    return '';
}
function ao_catalog_seed_catalog(){
    try{
        ao_v2334_seed_product_groups();
        ao_v2332_ensure_schema();
        ao_v237_ensure_product_pricing_schema();
        if(admin_setting('starter_catalog_v2450_seeded','0')==='1') return;
        $profiles=[
            'hosting'=>['hosting','whm',['Başlangıç Hosting','Kurumsal Hosting','WordPress Hosting','E-Ticaret Hosting','Ajans Hosting'],[149,249,349,549,899]],
            'vps-sunucu'=>['server','manual',['Cloud VPS S','Cloud VPS M','Cloud VPS L','Yönetilen VPS','Dedicated Pro'],[499,799,1299,1999,3499]],
            'ssl'=>['ssl','manual',['DV SSL','Wildcard SSL','Business SSL','E-Ticaret SSL','Enterprise SSL'],[299,799,1299,1899,2999]],
            'sitebuilder'=>['service','manual',['SiteBuilder Başlangıç','SiteBuilder Kurumsal','SiteBuilder E-Ticaret','SiteBuilder Ajans','SiteBuilder Enterprise'],[249,449,749,1199,1999]],
            'mobilebuilder'=>['service','manual',['MobileBuilder PWA','MobileBuilder Android','MobileBuilder Business','MobileBuilder Commerce','MobileBuilder Agency'],[499,999,1499,2499,3999]],
            'web-tasarim'=>['service','manual',['Tek Sayfa Web','Kurumsal Web','E-Ticaret Sitesi','Özel Web Portalı','Enterprise Web Projesi'],[4990,8990,14990,24990,44990]],
            'mobil-uygulama'=>['service','manual',['Android Başlangıç','Kurumsal Android','E-Ticaret Uygulaması','Randevu Uygulaması','Özel Mobil Platform'],[9990,14990,24990,34990,59990]],
            'seo'=>['service','manual',['SEO Başlangıç','Yerel SEO','Kurumsal SEO','E-Ticaret SEO','SEO Growth'],[1499,2499,3999,5999,8999]],
            'dijital-hizmetler'=>['service','manual',['Logo ve Marka Kiti','Sosyal Medya Paketi','İçerik Üretimi','Dijital Reklam Yönetimi','Dijital Dönüşüm Paketi'],[1499,2499,3499,4999,9999]],
            'marketplace'=>['marketplace','marketplace',['Premium Tema','Kurumsal Script','E-Ticaret Arayüzü','Mobil Uygulama Kaynak Kodu','SaaS Başlangıç Kiti'],[999,1999,2999,3999,5999]],
        ];
        $groups=db()->query("SELECT id,name,slug FROM product_groups WHERE is_active=1 AND slug<>'domain' ORDER BY sort_order,id")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach($groups as $group){
            $p=$profiles[$group['slug']]??['service','manual',[$group['name'].' Başlangıç',$group['name'].' Plus',$group['name'].' Pro',$group['name'].' Business',$group['name'].' Enterprise'],[499,899,1499,2499,4999]];
            foreach($p[2] as $i=>$name){
                $slug='baslangic-'.$group['slug'].'-'.($i+1);
                $short=$name.', modern işletmeler için ölçeklenebilir ve yönetilebilir bir çözümdür.';
                $html='<h2>'.$name.'</h2><p>'.$short.'</p><ul><li>Premium SaaS deneyimi</li><li>Merkezi yönetim ve raporlama</li><li>İhtiyaca göre geliştirilebilir yapı</li></ul>';
                db()->prepare("INSERT INTO products(group_id,name,slug,type,module_name,short_description,description,price,currency,billing_cycle,is_active,visibility,seo_title,meta_description,sort_order)
                    VALUES(?,?,?,?,?,?,?,?,?,'monthly',1,'visible',?,?,?) ON DUPLICATE KEY UPDATE group_id=VALUES(group_id),name=VALUES(name),short_description=VALUES(short_description),description=VALUES(description),is_active=1,visibility='visible'")
                    ->execute([(int)$group['id'],$name,$slug,$p[0],$p[1],$short,$html,(float)$p[3][$i],'TRY',$name,$short,($i+1)*10]);
                $q=db()->prepare('SELECT id FROM products WHERE slug=?'); $q->execute([$slug]); $productId=(int)$q->fetchColumn();
                if($productId) db()->prepare("INSERT INTO product_pricing(product_id,cycle,price,setup_fee,currency,is_active) VALUES(?,'monthly',?,0,'TRY',1) ON DUPLICATE KEY UPDATE price=VALUES(price),currency='TRY',is_active=1")->execute([$productId,(float)$p[3][$i]]);
            }
        }
        save_setting('starter_catalog_v2450_seeded','1');
    }catch(Throwable $e){}
}
function ao_catalog_seed_references(){
    ao_catalog_ensure_showcase_schema();
    try{
        if(admin_setting('portfolio_v2464_seeded','0')==='1') return;
        $web=[
            ['Nova Kurumsal','Teknoloji','B2B hizmetlerini sade bir satış akışıyla sunan kurumsal web deneyimi.','Next.js, UI/UX, SEO'],
            ['Mira Restaurant','Yeme İçme','Menü, rezervasyon ve şube içeriklerini bir araya getiren mobil uyumlu site.','PHP, Responsive UI, CMS'],
            ['Arven Mimarlık','Mimarlık','Projeleri büyük görseller ve güçlü tipografiyle öne çıkaran portföy sitesi.','Portfolio, WebP, SEO'],
            ['Lina Beauty','Güzellik','Hizmet, uzman ve randevu akışlarını merkezileştiren premium marka sitesi.','Booking, CMS, Analytics'],
            ['Atlas Lojistik','Lojistik','Teklif toplama ve operasyon kabiliyetlerini anlatan çok dilli kurumsal site.','Multilanguage, Forms, SEO'],
            ['Vega Eğitim','Eğitim','Programlar, eğitmenler ve başvuru süreçleri için dönüşüm odaklı eğitim portalı.','LMS UI, Forms, CRM'],
            ['Orion Yapı','İnşaat','Projeleri ve yatırım fırsatlarını sergileyen gayrimenkul vitrini.','Property UI, Maps, CMS'],
            ['Pera Klinik','Sağlık','Hekim profilleri ve online ön görüşme akışına sahip klinik sitesi.','Healthcare UI, Booking, SEO'],
            ['Kuzey Enerji','Enerji','Ürünleri ve teknik dokümanları düzenli sunan endüstriyel B2B platformu.','Catalog, Documents, Leads'],
            ['Moneta Finans','Finans','Hizmetleri ve başvuru adımlarını netleştiren güvenlik odaklı finans sitesi.','Secure UI, Forms, Analytics'],
        ];
        $android=[
            ['RotaGo Android','Seyahat','Rota planlama, favoriler ve anlık bildirimlerle seyahat asistanı.','Kotlin, Maps, Push'],
            ['SiparişJet Android','E-Ticaret','Ürün keşfi, hızlı sepet ve sipariş takibi sunan mobil ticaret uygulaması.','Kotlin, REST API, Payment'],
            ['FitLife Android','Sağlık & Spor','Antrenman planı, ilerleme takibi ve üyelik yönetimini birleştiren uygulama.','Kotlin, Charts, Notifications'],
            ['RadyoMix Android','Medya','Canlı yayın, program akışı ve favori istasyon deneyimi.','ExoPlayer, Media Session, Push'],
            ['UstaBul Android','Hizmet Pazarı','Konuma göre hizmet sağlayıcı keşfi, teklif ve mesajlaşma akışı.','Kotlin, Maps, Chat'],
            ['EduClass Android','Eğitim','Ders içerikleri, sınavlar ve öğrenci bildirimleri için eğitim uygulaması.','Kotlin, Video, Quiz'],
            ['KlinikCep Android','Sağlık','Randevu, doktor profili ve güvenli hasta bilgilendirme deneyimi.','Kotlin, Booking, Secure API'],
            ['Haber360 Android','Haber','Kategori, favori, çevrimdışı okuma ve anlık haber bildirimleri.','Kotlin, Offline Cache, Push'],
            ['EtkinlikPro Android','Etkinlik','Etkinlik keşfi, QR bilet ve takvim entegrasyonlu mobil platform.','Kotlin, QR, Calendar'],
            ['SahaTakip Android','Operasyon','Ekip, görev, konum ve saha raporlarını tek panelde birleştiren uygulama.','Kotlin, GPS, REST API'],
        ];
        $web=array_slice($web,0,5); $android=array_slice($android,0,5);
        $q=db()->prepare('INSERT INTO portfolio_references(title,slug,reference_type,sector,short_description,description,image_url,cover_image_url,logo_url,technologies,is_featured,is_active,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?,?,1,?) ON DUPLICATE KEY UPDATE title=VALUES(title),sector=VALUES(sector),short_description=VALUES(short_description),image_url=VALUES(image_url),cover_image_url=VALUES(cover_image_url),logo_url=VALUES(logo_url),technologies=VALUES(technologies)');
        foreach($web as $i=>$r){ $img='public/assets/img/reference-web-'.($i+1).'.svg'; $q->execute([$r[0],ao_catalog_slug($r[0]),'website',$r[1],$r[2],'<p>'.$r[2].'</p>',$img,$img,'public/assets/img/reference-logo-'.($i+1).'.svg',$r[3],$i<3?1:0,($i+1)*10]); }
        foreach($android as $i=>$r){ $img='public/assets/img/reference-android-'.($i+1).'.svg'; $q->execute([$r[0],ao_catalog_slug($r[0]),'android',$r[1],$r[2],'<p>'.$r[2].'</p>',$img,$img,'public/assets/img/reference-app-logo-'.($i+1).'.svg',$r[3],$i<3?1:0,110+($i+1)*10]); }
        save_setting('portfolio_v2464_seeded','1');
    }catch(Throwable $e){}
}
ao_catalog_seed_catalog();
ao_catalog_seed_references();

if($_SERVER['REQUEST_METHOD']==='POST' && $route==='admin/references/save'){
    require_admin(); verify_csrf(); ao_catalog_ensure_showcase_schema();
    $id=(int)($_POST['id']??0); $title=trim($_POST['title']??''); $slug=ao_catalog_slug($_POST['slug']??$title);
    try{
        if(!$title||!$slug) throw new Exception('Başlık zorunlu.');
        $projectUrl=ao_catalog_normalize_project_url($_POST['project_url']??'');
        $imageUrl=trim($_POST['image_url']??'');
        $coverUrl=trim($_POST['cover_image_url']??'');
        $cardBackgroundUrl=trim($_POST['card_background_url']??'');
        $screenshotWanted=$projectUrl!=='' && (isset($_POST['refresh_screenshot']) || $coverUrl==='');
        $screenshotSaved=false;
        if($screenshotWanted){
            $captured=ao_catalog_reference_screenshot($projectUrl,$slug);
            if($captured!==''){ $coverUrl=$captured; $screenshotSaved=true; }
        }
        if($coverUrl==='') $coverUrl=$imageUrl;
        if($imageUrl==='' && $coverUrl!=='') $imageUrl=$coverUrl;
        $data=[$title,$slug,in_array($_POST['reference_type']??'website',['website','android'],true)?$_POST['reference_type']:'website',trim($_POST['sector']??''),trim($_POST['short_description']??''),ao_v2400_sanitize_product_html($_POST['description']??''),$imageUrl,$coverUrl,$cardBackgroundUrl,trim($_POST['logo_url']??''),$projectUrl,trim($_POST['technologies']??''),isset($_POST['is_featured'])?1:0,isset($_POST['is_active'])?1:0,(int)($_POST['sort_order']??0)];
        if($id) db()->prepare('UPDATE portfolio_references SET title=?,slug=?,reference_type=?,sector=?,short_description=?,description=?,image_url=?,cover_image_url=?,card_background_url=?,logo_url=?,project_url=?,technologies=?,is_featured=?,is_active=?,sort_order=? WHERE id=?')->execute([...$data,$id]);
        else db()->prepare('INSERT INTO portfolio_references(title,slug,reference_type,sector,short_description,description,image_url,cover_image_url,card_background_url,logo_url,project_url,technologies,is_featured,is_active,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($data);
        flash('success','Referans kaydedildi.'.($screenshotWanted && !$screenshotSaved ? ' Ekran görüntüsü otomatik alınamadı; kapak görseli alanına manuel URL ekleyebilirsiniz.' : ''));
    }catch(Throwable $e){ flash('error','Referans kaydedilemedi: '.$e->getMessage()); }
    redirect_to('admin/references');
}
if($route==='admin/references/delete'){
    require_admin(); verify_csrf();
    try{ db()->prepare('DELETE FROM portfolio_references WHERE id=?')->execute([(int)($_GET['id']??0)]); flash('success','Referans silindi.'); }catch(Throwable $e){ flash('error','Referans silinemedi.'); }
    redirect_to('admin/references');
}
