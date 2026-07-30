<?php
// v23.0.0 Provider, Currency, Translation, Knowledge Base and Support helpers
function ao_v23_slug($s){ $s=trim(mb_strtolower((string)$s,'UTF-8')); $tr=['ş'=>'s','ı'=>'i','ğ'=>'g','ü'=>'u','ö'=>'o','ç'=>'c']; $s=strtr($s,$tr); $s=preg_replace('/[^a-z0-9]+/','-',$s); return trim($s,'-') ?: 'item'; }
function ao_v23_ensure_schema(){ static $done=false; if($done) return; $done=true;  try{db()->exec("CREATE TABLE IF NOT EXISTS provider_accounts(id INT AUTO_INCREMENT PRIMARY KEY, provider_slug VARCHAR(80) UNIQUE, provider_name VARCHAR(160), api_status VARCHAR(40) DEFAULT 'not_configured', balance_label VARCHAR(80) NULL, balance_amount DECIMAL(14,2) DEFAULT 0, balance_currency VARCHAR(10) DEFAULT 'TRY', api_help_url VARCHAR(255) NULL, docs TEXT NULL, is_active TINYINT(1) DEFAULT 1, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
  try{db()->exec("CREATE TABLE IF NOT EXISTS currency_rates(id INT AUTO_INCREMENT PRIMARY KEY, currency_code VARCHAR(10) UNIQUE, base_code VARCHAR(10) DEFAULT 'TRY', tcmb_rate DECIMAL(16,6) DEFAULT 0, margin_percent DECIMAL(8,2) DEFAULT 0, final_rate DECIMAL(16,6) DEFAULT 0, source VARCHAR(80) DEFAULT 'TCMB', updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
  try{db()->exec("CREATE TABLE IF NOT EXISTS translation_memory(id INT AUTO_INCREMENT PRIMARY KEY, source_hash CHAR(40) UNIQUE, source_text TEXT, source_lang VARCHAR(10) DEFAULT 'tr', target_lang VARCHAR(10) DEFAULT 'en', translated_text TEXT, context VARCHAR(80) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
  try{db()->exec("CREATE TABLE IF NOT EXISTS translation_languages(code VARCHAR(10) PRIMARY KEY, name VARCHAR(60) NOT NULL, is_active TINYINT(1) DEFAULT 1, sort_order INT DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    db()->exec("INSERT IGNORE INTO translation_languages(code,name,is_active,sort_order) VALUES('en','İngilizce',1,1),('de','Almanca',0,2),('ar','Arapça',0,3)");
  }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
  try{db()->exec("CREATE TABLE IF NOT EXISTS knowledge_articles(id INT AUTO_INCREMENT PRIMARY KEY, audience VARCHAR(20) DEFAULT 'customer', category VARCHAR(120), title VARCHAR(255), slug VARCHAR(255) UNIQUE, excerpt TEXT NULL, content LONGTEXT NULL, seo_title VARCHAR(255) NULL, meta_description TEXT NULL, tags TEXT NULL, status VARCHAR(30) DEFAULT 'draft', lang VARCHAR(10) DEFAULT 'tr', cover_media_id INT NULL, is_seed TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
  try{db()->exec("CREATE TABLE IF NOT EXISTS support_chat_leads(id INT AUTO_INCREMENT PRIMARY KEY, department VARCHAR(120), name VARCHAR(190), email VARCHAR(190), phone VARCHAR(80), subject VARCHAR(255), message TEXT, page_url VARCHAR(255), status VARCHAR(40) DEFAULT 'new', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
  try{db()->exec("CREATE TABLE IF NOT EXISTS provider_product_imports(id INT AUTO_INCREMENT PRIMARY KEY, provider_slug VARCHAR(80), product_code VARCHAR(120), name VARCHAR(255), description TEXT, specs_json LONGTEXT, source_price DECIMAL(14,2) DEFAULT 0, source_currency VARCHAR(10) DEFAULT 'USD', sale_price_try DECIMAL(14,2) DEFAULT 0, import_status VARCHAR(40) DEFAULT 'preview', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
  foreach([['domainnameapi','Domain Name API','Domain registrar API bilgilerini Domain Center > Registrarlar içinde oluştur.'],['resellerclub','ResellerClub','API kullanıcı adı ve key ile domain yedek sağlayıcı olarak yapılandır.'],['sectigo','Sectigo','SSL partner hesabından API bilgilerini al.'],['contabo','Contabo','Contabo Customer Control Panel içinde API client oluştur.'],['hetzner','Hetzner Cloud','Hetzner Cloud Console > Security > API Tokens bölümünden token oluştur.'],['vultr','Vultr','Vultr API token ile cloud ürünlerini senkronize et.'],['digitalocean','DigitalOcean','API Token ile droplet planlarını çek.'],['ovh','OVHcloud','API application key/secret ve consumer key ile bağlan.']] as $p){try{db()->prepare("INSERT IGNORE INTO provider_accounts(provider_slug,provider_name,docs) VALUES(?,?,?)")->execute($p);}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }}
  foreach([['USD',45.00,5.0],['EUR',49.00,5.0],['GBP',58.00,5.0]] as $r){try{db()->prepare("INSERT IGNORE INTO currency_rates(currency_code,tcmb_rate,margin_percent,final_rate) VALUES(?,?,?,? )")->execute([$r[0],$r[1],$r[2],$r[1]+($r[1]*$r[2]/100)]);}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }}
}
function ao_translation_active_languages(){
    ao_v23_ensure_schema();
    try{ return db()->query("SELECT * FROM translation_languages ORDER BY sort_order,code")->fetchAll() ?: []; }catch(Throwable $e){ return []; }
}
function ao_translation_scan_missing(){
    ao_v23_ensure_schema();
    $targets=[]; foreach(ao_translation_active_languages() as $l){ if((int)$l['is_active']===1) $targets[]=$l['code']; }
    if(!$targets) return ['found'=>0,'languages'=>0];
    // Çeviri kaynağı: site içeriğinin Türkçe metinleri (blog yazıları ve marketplace ilanları).
    $sources=[];
    try{ foreach(db()->query("SELECT title, excerpt FROM blog_posts") as $r){ if(trim((string)$r['title'])!=='') $sources[]=[$r['title'],'blog_title']; if(trim((string)($r['excerpt']??''))!=='') $sources[]=[$r['excerpt'],'blog_excerpt']; } }catch(Throwable $e){}
    try{ foreach(db()->query("SELECT title, description FROM marketplace_listings") as $r){ if(trim((string)$r['title'])!=='') $sources[]=[$r['title'],'marketplace_title']; if(trim((string)($r['description']??''))!=='') $sources[]=[$r['description'],'marketplace_description']; } }catch(Throwable $e){}
    $found=0;
    foreach($sources as [$text,$context]){
        $text=trim((string)$text); if($text==='') continue;
        foreach($targets as $lang){
            $hash=sha1($text.'|'.$lang);
            try{
                $exists=db()->prepare("SELECT 1 FROM translation_memory WHERE source_hash=? LIMIT 1");
                $exists->execute([$hash]);
                if($exists->fetchColumn()) continue;
                db()->prepare("INSERT INTO translation_memory(source_hash,source_text,source_lang,target_lang,translated_text,context) VALUES(?,?,?,?,?,?)")
                    ->execute([$hash,$text,'tr',$lang,'',$context]);
                $found++;
            }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        }
    }
    return ['found'=>$found,'languages'=>count($targets)];
}
function ao_v23_seed_knowledge(){ ao_v23_ensure_schema();
 $articles=[
  ['customer','Başlangıç','Domain nedir ve nasıl seçilir?'],
  ['customer','Başlangıç','Hosting nedir ve hangi paket seçilmeli?'],
  ['customer','Başlangıç','VPS nedir, kimler kullanmalı?'],
  ['customer','Başlangıç','Dedicated sunucu nedir?'],
  ['customer','Başlangıç','Reseller hosting nedir?'],
  ['customer','Başlangıç','SSL sertifikası nedir?'],
  ['customer','Domain','Domain nasıl alınır?'],
  ['customer','Domain','Domain transferi nasıl yapılır?'],
  ['customer','Domain','EPP kodu nedir ve nereden alınır?'],
  ['customer','Domain','Domain kilidi nedir, nasıl açılır?'],
  ['customer','Domain','Whois bilgileri nasıl güncellenir?'],
  ['customer','Domain','Nameserver nasıl değiştirilir?'],
  ['customer','Domain','Domain süresi bitince ne olur?'],
  ['customer','Domain','Premium domain nedir?'],
  ['customer','Domain','Domain değerleme nasıl yapılır?'],
  ['customer','Domain','Domain satışında güvenli transfer nasıl yapılır?'],
  ['customer','DNS','DNS nedir?'],
  ['customer','DNS','A kaydı nasıl eklenir?'],
  ['customer','DNS','CNAME kaydı nedir?'],
  ['customer','DNS','MX kaydı nasıl ayarlanır?'],
  ['customer','DNS','SPF kaydı nedir?'],
  ['customer','DNS','DKIM nedir ve nasıl kurulur?'],
  ['customer','DNS','DMARC kaydı nasıl oluşturulur?'],
  ['customer','DNS','DNS yayılım süresi ne kadardır?'],
  ['customer','DNS','Cloudflare ile DNS yönetimi'],
  ['customer','Hosting','cPanel giriş nasıl yapılır?'],
  ['customer','Hosting','DirectAdmin giriş nasıl yapılır?'],
  ['customer','Hosting','Plesk panel giriş nasıl yapılır?'],
  ['customer','Hosting','FTP hesabı nasıl oluşturulur?'],
  ['customer','Hosting','PHP sürümü nasıl değiştirilir?'],
  ['customer','Hosting','Cron Job nasıl oluşturulur?'],
  ['customer','Hosting','Alt alan adı nasıl oluşturulur?'],
  ['customer','Hosting','Hosting yedeği nasıl alınır?'],
  ['customer','Hosting','Hosting taşıma işlemi nasıl yapılır?'],
  ['customer','Hosting','Web sitesi neden yavaş açılır?'],
  ['customer','Hosting','cPanel üzerinden SSH erişimi nasıl hazırlanır?'],
  ['customer','Hosting','cPanel dosya yöneticisi ile site dosyası yükleme'],
  ['customer','Hosting','Alt alan adı ve ek domain nasıl oluşturulur?'],
  ['customer','Hosting','phpMyAdmin ile veritabanı oluşturma ve kullanıcı bağlama'],
  ['customer','Hosting','Hosting hesabında disk ve trafik kullanımı nasıl takip edilir?'],
  ['customer','E-Posta','Kurumsal e-posta hesabı nasıl oluşturulur?'],
  ['customer','E-Posta','Android telefonda kurumsal mail kurulumu'],
  ['customer','E-Posta','iPhone ve iPad mail kurulumu'],
  ['customer','E-Posta','Outlook IMAP/SMTP mail kurulumu'],
  ['customer','E-Posta','Thunderbird mail kurulumu'],
  ['customer','E-Posta','Apple Mail kurulumu'],
  ['customer','E-Posta','SMTP IMAP POP3 farkları nelerdir?'],
  ['customer','E-Posta','Mail gönderemiyorum nasıl çözerim?'],
  ['customer','E-Posta','Mail spam klasörüne düşüyor ne yapmalıyım?'],
  ['customer','SSL','SSL nasıl kurulur?'],
  ['customer','SSL','Let’s Encrypt SSL kurulumu'],
  ['customer','SSL','Wildcard SSL nedir?'],
  ['customer','SSL','Mixed Content hatası nasıl çözülür?'],
  ['customer','SSL','SSL yenileme nasıl yapılır?'],
  ['customer','VPS','VPS ilk kurulum adımları'],
  ['customer','VPS','PuTTY ile SSH bağlantısı nasıl yapılır?'],
  ['customer','VPS','Termius ile SSH bağlantısı'],
  ['customer','VPS','MobaXterm ile SSH ve SFTP kullanımı'],
  ['customer','VPS','SSH key nasıl oluşturulur?'],
  ['customer','VPS','Linux temel komutları'],
  ['customer','VPS','VPS güvenliği için ilk yapılacaklar'],
  ['customer','VPS','CSF Firewall kurulumu'],
  ['customer','VPS','Fail2Ban kurulumu'],
  ['customer','VPS','cPanel kurulumu öncesi hazırlık'],
  ['customer','VPS','DirectAdmin kurulumu öncesi hazırlık'],
  ['customer','VPS','Plesk kurulumu öncesi hazırlık'],
  ['customer','VPS','CyberPanel kurulumu'],
  ['customer','Dedicated','Dedicated sunucu ilk kurulum'],
  ['customer','Dedicated','RAID nedir ve neden önemlidir?'],
  ['customer','Dedicated','Proxmox kurulumu'],
  ['customer','Dedicated','Dedicated sunucuyu VPSlere bölme'],
  ['customer','Dedicated','KVM VPS oluşturma'],
  ['customer','Dedicated','LXC Container oluşturma'],
  ['customer','Dedicated','Snapshot ve yedekleme mantığı'],
  ['customer','Reseller','Reseller hesabı nasıl kullanılır?'],
  ['customer','Reseller','WHM ile hosting paketi oluşturma'],
  ['customer','Reseller','Reseller müşterisi nasıl eklenir?'],
  ['customer','Reseller','Suspend ve Unsuspend nedir?'],
  ['customer','Reseller','Markalı nameserver oluşturma'],
  ['customer','Programlar ve Araçlar','WinSCP ile dosya aktarımı'],
  ['customer','Programlar ve Araçlar','FileZilla ile FTP bağlantısı'],
  ['customer','Programlar ve Araçlar','phpMyAdmin ile veritabanı yönetimi'],
  ['customer','Programlar ve Araçlar','MySQL yedeği alma ve içe aktarma'],
  ['customer','Programlar ve Araçlar','VS Code ile uzak sunucu dosyası düzenleme'],
  ['customer','Programlar ve Araçlar','Git kurulumu ve temel kullanım'],
  ['customer','Programlar ve Araçlar','Docker ve Docker Compose başlangıç'],
  ['customer','WordPress','WordPress kurulumu'],
  ['customer','WordPress','WordPress beyaz sayfa hatası nasıl çözülür?'],
  ['customer','WordPress','WordPress admin paneline giremiyorum ne yapmalıyım?'],
  ['customer','WordPress','WordPress eklenti çakışması nasıl bulunur?'],
  ['customer','WordPress','WordPress PHP bellek limiti nasıl artırılır?'],
  ['customer','WordPress','WordPress site taşıma'],
  ['customer','WordPress','WooCommerce kurulumu'],
  ['customer','WordPress','WordPress hızlandırma'],
  ['customer','WordPress','WordPress güvenliği'],
  ['customer','WordPress','WordPress yedek alma'],
  ['customer','Site Builder','Site Builder ile site oluşturma'],
  ['customer','Site Builder','Site Builder menü ve sayfa yönetimi'],
  ['customer','Site Builder','Site Builder SEO ayarları'],
  ['customer','Mobile Builder','Mobile Builder ile Android uygulama hazırlama'],
  ['customer','Mobile Builder','APK ve AAB nedir?'],
  ['customer','Mobile Builder','Google Play Console’a uygulama yükleme'],
  ['customer','Marketplace','Kaynak kod satın alırken nelere dikkat edilmeli?'],
  ['customer','Marketplace','Domain satışında escrow güvenli transfer'],
  ['customer','Marketplace','Hazır script ve tema satın alma rehberi'],
  ['customer','Domain','Domain transfer kilidi nedir ve nasıl kaldırılır?'],
  ['customer','Domain','Nameserver değişikliği sonrası site neden hemen açılmaz?'],
  ['customer','Domain','DNS A, CNAME, MX ve TXT kayıtları ne işe yarar?'],
  ['customer','Site Araçları','WHOIS sonucu nasıl okunur?'],
  ['customer','Site Araçları','SSL sorgulama sonucu nasıl yorumlanır?'],
  ['customer','Site Araçları','Site hız testi sonuçları ne anlama gelir?'],
  ['customer','Web Tasarım','Web tasarım projesi başlatmadan önce hangi bilgiler hazırlanmalı?'],
  ['customer','Mobil Uygulama','Android uygulama yayına alma sürecinde gerekenler'],
  ['admin','Ahost One Kullanımı','Ahost One ilk kurulum sihirbazı'],
  ['admin','Ahost One Kullanımı','Ayarlar Merkezi kullanımı'],
  ['admin','Ahost One Kullanımı','Ürün Merkezi ürün ve fiyatlandırma'],
  ['admin','Ahost One Kullanımı','Domain Center registrar yönetimi'],
  ['admin','Ahost One Kullanımı','Provider Center API bağlantıları ve bakiye kontrolü'],
  ['admin','Ahost One Kullanımı','Migration Bridge seçmeli import'],
  ['admin','Ahost One Kullanımı','Backup Center bulut yedekleme'],
  ['admin','Ahost One Kullanımı','Build Center APK/AAB repository temizliği'],
  ['admin','Ahost One Kullanımı','Lisans Merkezi suspend ve bildirim yönetimi'],
  ['admin','Ahost One Kullanımı','AI Center API anahtarı ve kullanım alanları'],
  ['admin','Ahost One Kullanımı','Otomasyon Merkezi kural yönetimi'],
  ['admin','Ahost One Kullanımı','Bilgi Bankası makale ve medya yönetimi'],
  ['admin','Ahost One Kullanımı','Module Center ZIP indirme ve güncelleme']
 ];
 foreach($articles as $a){ $slug=ao_v23_slug($a[2]); $content='<h2>'.e($a[2]).'</h2><p>Bu makale Ahost One Bilgi Bankası & Akademi Pro tarafından SEO uyumlu taslak olarak oluşturulmuştur. İçerik kullanıcı odaklıdır; dış görsel bağlantısı kullanılmaz, görseller Medya Kütüphanesi üzerinden WebP/SVG olarak saklanır.</p><h3>Adım Adım</h3><ol><li>İşleme başlamadan önce gerekli hesap, panel veya bağlantı bilgilerini hazırlayın.</li><li>İlgili panele ya da programa giriş yapın.</li><li>Makaledeki adımları sırasıyla uygulayın.</li><li>İşlem sonunda site, mail, domain, sunucu veya uygulama tarafında sonucu test edin.</li></ol><h3>SEO Notu</h3><p>Bu rehber çoklu dil sistemi ile çevrilebilir, meta başlık/açıklama ve ilgili makalelerle güçlendirilebilir.</p><h3>Sık Sorulan Sorular</h3><p>Bu alana müşterinin en çok sorduğu sorular ve kısa cevaplar eklenir.</p>'; try{db()->prepare("INSERT IGNORE INTO knowledge_articles(audience,category,title,slug,excerpt,content,seo_title,meta_description,tags,status,is_seed) VALUES(?,?,?,?,?,?,?,?,?,?,1)")->execute([$a[0],$a[1],$a[2],$slug,$a[2].' rehberi.',$content,$a[2],$a[2].' hakkında adım adım SEO uyumlu Ahost One bilgi bankası rehberi.',$a[1].', rehber, hosting, ahost one','published']);}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
}
function ao_v23_price_try($amount,$currency='USD'){ ao_v23_ensure_schema(); $currency=strtoupper($currency); if($currency==='TRY') return (float)$amount; try{$q=db()->prepare('SELECT final_rate FROM currency_rates WHERE currency_code=? LIMIT 1');$q->execute([$currency]);$rate=(float)$q->fetchColumn(); if($rate<=0)$rate=(float)ao_currency_rate($currency,'TRY'); return round(((float)$amount)*$rate,2);}catch(Throwable $e){return (float)$amount;} }

