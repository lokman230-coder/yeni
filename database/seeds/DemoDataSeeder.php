<?php

/**
 * KAPSAMLI DEMO SEED — Hosting'e yüklendikten sonra göze hoş görünecek örnek veriler.
 * Sen kendine göre admin panelden değiştirirsin.
 *
 * İçerik:
 *   • 20+ Portfolio referansı
 *   • 15 TLD config (com, com.tr, io, dev, ...)
 *   • 10 domain fiyatı (registrar maliyet + markup)
 *   • 5 örnek vendor + 20 marketplace ilanı (tema, script, SEO paketi)
 *   • 8 kupon
 *   • 10 ek paket (SSL, backup, CDN, ek disk vs)
 *   • 5 paket opsiyonu (lokasyon, PHP, panel, OS)
 *   • 8 tema/script örneği
 *   • 3 örnek lisans (issued)
 *   • 6 örnek müşteri
 */

use App\Core\Database\Connection;
use App\Support\Slug;

return new class {
    public function run(): void
    {
        $this->seedTldConfigs();
        $this->seedDomainPricing();
        $this->seedPortfolio();
        $this->seedAddons();
        $this->seedPackageOptions();
        $this->seedCoupons();
        $this->seedDemoCustomers();
        $this->seedVendors();
        $this->seedMarketplace();
        $this->seedLicenses();
        echo "  ✓ Demo veriler yüklendi.\n";
    }

    // ─── 1. TLD Configs (satış fiyatı için markup + belge gereksinimi) ───
    private function seedTldConfigs(): void
    {
        $tlds = [
            // TR TLD'leri — belge isteyen
            ['tld'=>'com.tr','label'=>'.com.tr','markup_type'=>'percent','markup_value'=>25,'min_price'=>150,'requires_documents'=>1,'required_documents'=>['tckn','tax_id'],'is_popular'=>1,'sort_order'=>5],
            ['tld'=>'net.tr','label'=>'.net.tr','markup_type'=>'percent','markup_value'=>25,'min_price'=>150,'requires_documents'=>1,'required_documents'=>['tckn','trademark_cert'],'sort_order'=>10],
            ['tld'=>'org.tr','label'=>'.org.tr','markup_type'=>'percent','markup_value'=>25,'min_price'=>150,'requires_documents'=>1,'required_documents'=>['company_reg'],'sort_order'=>15],
            ['tld'=>'gen.tr','label'=>'.gen.tr','markup_type'=>'percent','markup_value'=>25,'min_price'=>100,'sort_order'=>20],
            ['tld'=>'tv.tr','label'=>'.tv.tr','markup_type'=>'percent','markup_value'=>25,'min_price'=>150,'sort_order'=>25],
            ['tld'=>'web.tr','label'=>'.web.tr','markup_type'=>'percent','markup_value'=>30,'min_price'=>50,'sort_order'=>30],
            ['tld'=>'bel.tr','label'=>'.bel.tr','markup_type'=>'percent','markup_value'=>25,'requires_documents'=>1,'required_documents'=>['company_reg'],'sort_order'=>35],

            // Global TLD'ler
            ['tld'=>'com','label'=>'.com','markup_type'=>'percent','markup_value'=>35,'min_price'=>150,'is_popular'=>1,'sort_order'=>1],
            ['tld'=>'net','label'=>'.net','markup_type'=>'percent','markup_value'=>35,'min_price'=>150,'sort_order'=>40],
            ['tld'=>'org','label'=>'.org','markup_type'=>'percent','markup_value'=>35,'min_price'=>150,'sort_order'=>45],
            ['tld'=>'io','label'=>'.io','markup_type'=>'percent','markup_value'=>30,'min_price'=>800,'is_popular'=>1,'sort_order'=>50],
            ['tld'=>'dev','label'=>'.dev','markup_type'=>'percent','markup_value'=>30,'min_price'=>400,'is_popular'=>1,'sort_order'=>55],
            ['tld'=>'app','label'=>'.app','markup_type'=>'percent','markup_value'=>30,'min_price'=>400,'sort_order'=>60],
            ['tld'=>'tech','label'=>'.tech','markup_type'=>'percent','markup_value'=>30,'min_price'=>250,'sort_order'=>65],
            ['tld'=>'shop','label'=>'.shop','markup_type'=>'percent','markup_value'=>30,'min_price'=>200,'sort_order'=>70],
            ['tld'=>'store','label'=>'.store','markup_type'=>'percent','markup_value'=>30,'min_price'=>200,'sort_order'=>75],
            ['tld'=>'online','label'=>'.online','markup_type'=>'percent','markup_value'=>30,'min_price'=>150,'sort_order'=>80],
            ['tld'=>'xyz','label'=>'.xyz','markup_type'=>'percent','markup_value'=>40,'min_price'=>50,'sort_order'=>85],
            ['tld'=>'ai','label'=>'.ai','markup_type'=>'percent','markup_value'=>25,'min_price'=>2500,'is_popular'=>1,'sort_order'=>90],
        ];

        foreach ($tlds as $t) {
            $exists = Connection::selectOne("SELECT id FROM tld_configs WHERE tld = ?", [$t['tld']]);
            if ($exists) continue;
            Connection::insert('tld_configs', [
                'tld'                     => $t['tld'],
                'label'                   => $t['label'],
                'markup_type'             => $t['markup_type'],
                'markup_value'            => $t['markup_value'],
                'min_price'               => $t['min_price'] ?? null,
                'requires_documents'      => $t['requires_documents'] ?? 0,
                'required_documents_json' => isset($t['required_documents']) ? json_encode($t['required_documents']) : null,
                'allow_transfer'          => 1,
                'allow_backorder'         => 1,
                'min_years'               => 1,
                'max_years'               => 10,
                'is_popular'              => $t['is_popular'] ?? 0,
                'is_active'               => 1,
                'sort_order'              => $t['sort_order'] ?? 100,
                'created_at'              => date('Y-m-d H:i:s'),
                'updated_at'              => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // ─── 2. Domain fiyatlandırma (registrar maliyet) ───
    private function seedDomainPricing(): void
    {
        // Registrar id'sini bul (default: manual veya ilk aktif)
        $reg = Connection::selectOne("SELECT id FROM domain_registrars WHERE is_active = 1 ORDER BY id LIMIT 1")
             ?: Connection::selectOne("SELECT id FROM domain_registrars ORDER BY id LIMIT 1");
        $registrarId = $reg['id'] ?? null;

        $prices = [
            ['com.tr', 150, 150, 150],
            ['net.tr', 150, 150, 150],
            ['org.tr', 150, 150, 150],
            ['gen.tr', 100, 100, 100],
            ['tv.tr',  150, 150, 150],
            ['web.tr',  50,  50,  50],
            ['com',    250, 250, 250],
            ['net',    280, 280, 280],
            ['org',    300, 300, 300],
            ['io',    1200,1200,1200],
            ['dev',    500, 500, 500],
            ['app',    500, 500, 500],
            ['tech',   400, 400, 400],
            ['shop',   350, 350, 350],
            ['xyz',     80,  80,  80],
            ['ai',    3500,3500,3500],
        ];

        foreach ($prices as $p) {
            $exists = Connection::selectOne("SELECT id FROM domain_pricing WHERE tld = ? AND period_years = 1", [$p[0]]);
            if ($exists) continue;
            Connection::insert('domain_pricing', [
                'registrar_id'    => $registrarId,
                'tld'             => $p[0],
                'period_years'    => 1,
                'register_price'  => $p[1],
                'transfer_price'  => $p[2],
                'renew_price'     => $p[3],
                'currency'        => 'TRY',
                'is_active'       => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // ─── 3. Portfolio Projeler (referanslar) ───
    private function seedPortfolio(): void
    {
        $projects = [
            ['title'=>'AntikVadi.com — E-ticaret','sector'=>'ecommerce','category'=>'ecommerce','client'=>'Antik Vadi Ltd.','desc'=>'Antika satışı için özel geliştirilmiş e-ticaret sitesi. WooCommerce + özel tema.','tech'=>['WordPress','WooCommerce','PHP','MySQL'],'quote'=>'Sitemiz açıldıktan 2 hafta içinde ilk siparişimizi aldık. Ahost ekibine teşekkürler!','duration'=>21,'featured'=>1],
            ['title'=>'DentaKlinik.tr — Sağlık','sector'=>'dental','category'=>'corporate','client'=>'Dr. Aylin Erkan','desc'=>'Diş kliniği için modern, mobil uyumlu kurumsal site + randevu sistemi.','tech'=>['Laravel','Vue.js','TailwindCSS'],'quote'=>'Randevu sistemi mükemmel çalışıyor.','duration'=>14,'featured'=>1],
            ['title'=>'MutfakTv — Yayın Sitesi','sector'=>'media','category'=>'web','client'=>'Mutfak TV','desc'=>'Yemek tarifleri ve video yayınları için içerik platformu.','tech'=>['Next.js','MongoDB','AWS S3'],'quote'=>'Site hızı ve kullanıcı deneyimi harika.','duration'=>30],
            ['title'=>'FitLife Mobil Uygulama','sector'=>'fitness','category'=>'mobile','client'=>'FitLife A.Ş.','desc'=>'iOS + Android fitness takip uygulaması. React Native ile geliştirildi.','tech'=>['React Native','Firebase','Node.js'],'quote'=>'App Store\'da 4.8 yıldız aldık!','duration'=>60,'featured'=>1],
            ['title'=>'IstanbulAvukat.com — Hukuk','sector'=>'law','category'=>'landing','client'=>'İstanbul Hukuk Bürosu','desc'=>'Avukatlık bürosu için premium landing page + SEO optimizasyonu.','tech'=>['HTML5','CSS3','JavaScript'],'quote'=>'Google\'da 1. sayfada yer alıyoruz.','duration'=>7],
            ['title'=>'RadyoAntalya — Streaming','sector'=>'radio','category'=>'web','client'=>'Radyo Antalya','desc'=>'Canlı radyo yayını için özel altyapı + istek hattı.','tech'=>['PHP','Icecast','WebSocket'],'quote'=>'Kesintisiz yayın için mükemmel çözüm.','duration'=>10],
            ['title'=>'AkilliEv Marketplace','sector'=>'ecommerce','category'=>'marketplace','client'=>'Akıllı Ev A.Ş.','desc'=>'Akıllı ev ürünleri için multi-vendor marketplace.','tech'=>['Laravel','Vue.js','Redis','MySQL'],'quote'=>'6 ay içinde 200+ satıcı kayıt oldu.','duration'=>90,'featured'=>1],
            ['title'=>'GayrimenkulTR — Emlak','sector'=>'real-estate','category'=>'saas','client'=>'GTR Emlak','desc'=>'Emlak ilanları SaaS platformu. Ofislere abonelik modeli.','tech'=>['Symfony','Doctrine','Stripe'],'quote'=>'Ayda 15K aktif ilan yönetiyoruz.','duration'=>75],
            ['title'=>'KaraKedi Kafe','sector'=>'restaurant','category'=>'corporate','client'=>'KaraKedi Kafe','desc'=>'Kafe için menü + rezervasyon + online sipariş sistemi.','tech'=>['WordPress','WPBakery','Payment Gateway'],'quote'=>'Online sipariş cirosuna büyük katkı sağladı.','duration'=>12],
            ['title'=>'EğitimHub — LMS','sector'=>'education','category'=>'saas','client'=>'Eğitim Hub','desc'=>'Online kurs platformu. Video streaming + quiz + sertifika.','tech'=>['Laravel','Mux','Zoom API'],'quote'=>'10K öğrencimiz aktif kullanıyor.','duration'=>120,'featured'=>1],
            ['title'=>'YeniKarşıkoy Bel.','sector'=>'government','category'=>'corporate','client'=>'YK Belediyesi','desc'=>'Belediye kurumsal sitesi + e-belediye entegrasyonu.','tech'=>['ASP.NET Core','SQL Server'],'quote'=>'Vatandaş memnuniyeti arttı.','duration'=>45],
            ['title'=>'HızlıTaşınma Mobil','sector'=>'logistics','category'=>'mobile','client'=>'Hızlı Taşınma','desc'=>'Nakliye şirketleri için sürücü + müşteri mobil uygulaması.','tech'=>['Flutter','GoogleMaps API'],'quote'=>'Şoförlerimiz 3 günde alıştı.','duration'=>50],
            ['title'=>'BursaSpor Kulübü','sector'=>'sports','category'=>'web','client'=>'BursaSpor A.Ş.','desc'=>'Spor kulübü web sitesi + üyelik + bilet satışı.','tech'=>['Drupal','iyzico','MySQL'],'quote'=>'Bilet satışı %200 arttı.','duration'=>30],
            ['title'=>'KrepSepeti — Mobil','sector'=>'food-delivery','category'=>'mobile','client'=>'Krep Sepeti','desc'=>'Yemek sipariş uygulaması + kurye takip.','tech'=>['React Native','Node.js','Socket.io'],'quote'=>'Sipariş süreleri %40 azaldı.','duration'=>75],
            ['title'=>'MarangoDunya.com','sector'=>'crafts','category'=>'ecommerce','client'=>'Marango Ltd.','desc'=>'Ahşap ürünleri e-ticaret sitesi + ürün özelleştirici.','tech'=>['OpenCart','jQuery','PHP'],'quote'=>'Özel sipariş sistemi çok işlevsel.','duration'=>28],
            ['title'=>'MelisaSanat.com','sector'=>'art','category'=>'portfolio','client'=>'Melisa Yıldız','desc'=>'Sanatçı portfolio sitesi + online sanat satışı.','tech'=>['HTML5','GSAP','Stripe'],'quote'=>'Uluslararası müşteriler de alım yapıyor.','duration'=>18],
            ['title'=>'TeknikServis360','sector'=>'services','category'=>'saas','client'=>'TeknikServis 360','desc'=>'Servis takip yazılımı. iş emri + fatura + envanter.','tech'=>['Django','React','PostgreSQL'],'quote'=>'İş süreçlerimiz artık dijital.','duration'=>90],
            ['title'=>'OtoparkYönetim','sector'=>'automotive','category'=>'saas','client'=>'ParkPlus','desc'=>'Otopark yönetim yazılımı + plaka tanıma entegrasyonu.','tech'=>['Node.js','MongoDB','OpenCV'],'quote'=>'Manuel iş yok, her şey otomatik.','duration'=>110],
            ['title'=>'BebekMobiliyaTR','sector'=>'baby','category'=>'ecommerce','client'=>'Bebek Mobilya','desc'=>'Bebek odası mobilyaları e-ticaret.','tech'=>['Shopify','Liquid'],'quote'=>'Kolay yönetim için harika.','duration'=>14],
            ['title'=>'PetOtel Rezervasyon','sector'=>'pets','category'=>'web','client'=>'Pet Otel','desc'=>'Evcil hayvan oteli rezervasyon sistemi.','tech'=>['Laravel','Vue.js','Stripe'],'quote'=>'Tatil dönemlerinde çok işimize yaradı.','duration'=>25],
        ];

        foreach ($projects as $i => $p) {
            $slug = Slug::make($p['title']);
            if (Connection::selectOne("SELECT id FROM portfolio_projects WHERE slug = ?", [$slug])) continue;

            Connection::insert('portfolio_projects', [
                'title'          => $p['title'],
                'slug'           => $slug,
                'client_name'    => $p['client'],
                'category'       => $p['category'],
                'sector'         => $p['sector'],
                'description'    => $p['desc'],
                'technologies'   => json_encode($p['tech'], JSON_UNESCAPED_UNICODE),
                'customer_quote' => $p['quote'],
                'duration_days'  => $p['duration'],
                'is_featured'    => $p['featured'] ?? 0,
                'is_published'   => 1,
                'sort_order'     => $i * 10,
                'published_at'   => date('Y-m-d H:i:s', time() - ($i * 86400 * 15)),
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // ─── 4. Ek Paketler ───
    private function seedAddons(): void
    {
        $addons = [
            ['Ek 10 GB Disk Alanı', 'Hostinginize 10 GB ek NVMe disk alanı ekler.', 29.00, 'monthly', 'disk'],
            ['Ek 50 GB Disk Alanı', '50 GB ek NVMe SSD disk.', 79.00, 'monthly', 'disk'],
            ['Aylık +100 GB Trafik', 'Aylık ek 100 GB bant genişliği.', 15.00, 'monthly', 'bandwidth'],
            ['Wildcard SSL Sertifikası', 'Tüm subdomain\'ler için SSL (*.site.com).', 299.00, 'annually', 'ssl'],
            ['EV SSL Sertifikası', 'Yeşil çubuklu güvenlik göstergesi, banka seviyesi.', 999.00, 'annually', 'ssl'],
            ['Günlük Yedekleme', 'Günlük otomatik yedek + 30 gün saklama.', 39.00, 'monthly', 'backup'],
            ['Cloudflare Pro CDN', 'Cloudflare Pro plan + WAF + optimizasyon.', 149.00, 'monthly', 'cdn'],
            ['Site Taşıma Servisi', 'Diğer hostingden Ahost\'a taşıma (uzman ekip).', 199.00, 'onetime', 'migration'],
            ['Ek Dedicated IP', 'Özel IP adresi (SSL için gerekli olabilir).', 79.00, 'monthly', 'ip'],
            ['Malware Tarama + Temizleme', 'Haftalık malware taraması, tespit halinde temizleme.', 129.00, 'monthly', 'security'],
        ];

        foreach ($addons as $i => $a) {
            $slug = Slug::make($a[0]);
            if (Connection::selectOne("SELECT id FROM product_addons WHERE slug = ?", [$slug])) continue;
            Connection::insert('product_addons', [
                'product_id'  => null,   // genel — tüm ürünlere eklenebilir
                'name'        => $a[0],
                'slug'        => $slug,
                'description' => $a[1],
                'price'       => $a[2],
                'currency'    => 'TRY',
                'period'      => $a[3],
                'addon_type'  => $a[4],
                'is_active'   => 1,
                'sort_order'  => $i * 10,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // ─── 5. Paket Opsiyonları (Lokasyon, PHP, Panel, OS, Lisans süresi) ───
    private function seedPackageOptions(): void
    {
        $options = [
            [
                'name'=>'Sunucu Lokasyonu', 'input_type'=>'radio', 'is_required'=>1,
                'description'=>'Hosting sunucunuzun coğrafi konumu — ziyaretçi hızı için önemli.',
                'values'=>[
                    ['İstanbul (Türkiye)','istanbul',0,'TRY','monthly',1],
                    ['Ankara (Türkiye)','ankara',0,'TRY','monthly',0],
                    ['Frankfurt (Almanya)','frankfurt',50,'TRY','monthly',0],
                    ['Amsterdam (Hollanda)','amsterdam',50,'TRY','monthly',0],
                    ['Los Angeles (ABD)','la',75,'TRY','monthly',0],
                ],
            ],
            [
                'name'=>'Kontrol Paneli', 'input_type'=>'radio', 'is_required'=>1,
                'description'=>'Hosting hesabınızı yöneteceğiniz panel.',
                'values'=>[
                    ['cPanel','cpanel',0,'TRY','monthly',1],
                    ['DirectAdmin','directadmin',-10,'TRY','monthly',0],
                    ['Plesk','plesk',15,'TRY','monthly',0],
                    ['HestiaCP (Ücretsiz)','hestia',-20,'TRY','monthly',0],
                ],
            ],
            [
                'name'=>'PHP Sürümü', 'input_type'=>'select', 'is_required'=>1,
                'description'=>'Sitenizin uyumlu olduğu PHP sürümü.',
                'values'=>[
                    ['PHP 8.3 (Önerilen)','php83',0,'TRY','monthly',1],
                    ['PHP 8.2','php82',0,'TRY','monthly',0],
                    ['PHP 8.1','php81',0,'TRY','monthly',0],
                    ['PHP 8.0','php80',0,'TRY','monthly',0],
                    ['PHP 7.4 (EOL)','php74',0,'TRY','monthly',0],
                ],
            ],
            [
                'name'=>'İşletim Sistemi (VPS için)', 'input_type'=>'select', 'is_required'=>0,
                'description'=>'VPS sunucunun işletim sistemi.',
                'values'=>[
                    ['Ubuntu 24.04 LTS','ubuntu24',0,'TRY','monthly',1],
                    ['Ubuntu 22.04 LTS','ubuntu22',0,'TRY','monthly',0],
                    ['Debian 12','debian12',0,'TRY','monthly',0],
                    ['AlmaLinux 9','alma9',0,'TRY','monthly',0],
                    ['Rocky Linux 9','rocky9',0,'TRY','monthly',0],
                    ['CentOS 7 (EOL)','centos7',0,'TRY','monthly',0],
                ],
            ],
            [
                'name'=>'Lisans Süresi', 'input_type'=>'radio', 'is_required'=>1,
                'description'=>'Script/uygulama lisans süresi.',
                'values'=>[
                    ['1 Yıl','1y',0,'TRY','annually',1],
                    ['2 Yıl (%10 indirimli)','2y',-10,'TRY','annually',0],
                    ['3 Yıl (%20 indirimli)','3y',-20,'TRY','annually',0],
                    ['Ömür Boyu','lifetime',500,'TRY','onetime',0],
                ],
            ],
        ];

        foreach ($options as $opt) {
            if (Connection::selectOne("SELECT id FROM product_options WHERE name = ?", [$opt['name']])) continue;
            $optId = Connection::insert('product_options', [
                'product_id'  => null,
                'name'        => $opt['name'],
                'slug'        => Slug::make($opt['name']),
                'input_type'  => $opt['input_type'],
                'is_required' => $opt['is_required'],
                'is_active'   => 1,
                'sort_order'  => 0,
                'description' => $opt['description'],
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
            foreach ($opt['values'] as $i => $v) {
                Connection::insert('product_option_values', [
                    'option_id'   => $optId,
                    'label'       => $v[0],
                    'value_key'   => $v[1],
                    'price_delta' => $v[2],
                    'currency'    => $v[3],
                    'period'      => $v[4],
                    'is_default'  => $v[5],
                    'is_active'   => 1,
                    'sort_order'  => $i,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    // ─── 6. Kuponlar ───
    private function seedCoupons(): void
    {
        $coupons = [
            ['WELCOME10',   'İlk Sipariş %10',   'percent', 10,  0, 'İlk siparişinizde %10 indirim.'],
            ['SUMMER25',    'Yaz Kampanyası %25','percent', 25,  0, 'Tüm ürünlerde %25 indirim.'],
            ['HOSTING50',   'Hosting %50',       'percent', 50,  0, 'Hosting siparişlerinde %50 indirim.'],
            ['DOMAIN100',   'Domain -100 TL',    'fixed',  100,  0, '.com veya .com.tr domainlerde 100 TL indirim.'],
            ['VIP2026',     'VIP Müşteri %30',   'percent', 30,  0, 'Sadık müşterilere özel indirim.'],
            ['CYBERMONDAY', 'Siber Pazartesi %40','percent',40,  0, 'Tek gün geçerli süper indirim.'],
            ['NEWYEAR2026', 'Yeni Yıl %35',      'percent', 35,  0, 'Yeni yıl kampanyası.'],
            ['REFERAL500',  'Referans -500 TL',  'fixed',  500,  0, 'Referans getirenler için özel.'],
        ];

        foreach ($coupons as $c) {
            if (Connection::selectOne("SELECT id FROM coupons WHERE code = ?", [$c[0]])) continue;
            Connection::insert('coupons', [
                'code'        => $c[0],
                'name'        => $c[1],
                'type'        => $c[2],
                'value'       => $c[3],
                'usage_limit' => $c[4] ?: null,
                'ends_at'     => date('Y-m-d H:i:s', time() + 90 * 86400),
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // ─── 7. Demo Müşteriler ───
    private function seedDemoCustomers(): void
    {
        $customers = [
            ['ahmet.yilmaz@demo.com',      'Ahmet',   'Yılmaz',   '05321112233', 'Yılmaz Yazılım',    500.00],
            ['ayse.demir@demo.com',        'Ayşe',    'Demir',    '05339990011', null,                150.00],
            ['mehmet.kaya@demo.com',       'Mehmet',  'Kaya',     '05553334455', 'Kaya Ticaret',      0.00],
            ['zeynep.aksoy@demo.com',      'Zeynep',  'Aksoy',    '05308887766', 'Aksoy Bilişim',     1250.00],
            ['emre.sahin@demo.com',        'Emre',    'Şahin',    '05455556677', null,                75.50],
            ['fatma.arslan@demo.com',      'Fatma',   'Arslan',   '05321119988', 'Arslan Danışmanlık',300.00],
        ];

        foreach ($customers as $c) {
            if (Connection::selectOne("SELECT id FROM customers WHERE email = ?", [$c[0]])) continue;
            Connection::insert('customers', [
                'email'              => $c[0],
                'password_hash'      => password_hash('Demo1234!', PASSWORD_DEFAULT),
                'first_name'         => $c[1],
                'last_name'          => $c[2],
                'phone'              => $c[3],
                'company'            => $c[4],
                'country'            => 'TR',
                'status'             => 'active',
                'is_individual'      => $c[4] ? 0 : 1,
                'preferred_language' => 'tr',
                'preferred_currency' => 'TRY',
                'balance'            => $c[5],
                'email_verified_at'  => date('Y-m-d H:i:s'),
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // ─── 8. Vendors ───
    private function seedVendors(): void
    {
        $vendors = [
            ['ahmet.yilmaz@demo.com',  'Yılmaz Yazılım',     'Kurumsal ve e-ticaret siteleri, WordPress temalar.', 15.0],
            ['zeynep.aksoy@demo.com',  'Aksoy Digital',      'Premium WordPress temaları + SEO paketleri.',        20.0],
            ['fatma.arslan@demo.com',  'Arslan SEO Ajansı',  'SEO analiz + optimizasyon hizmetleri.',              18.0],
            ['emre.sahin@demo.com',    'Şahin Design Studio','Logo, kurumsal kimlik ve grafik tasarım.',           15.0],
            ['mehmet.kaya@demo.com',   'Kaya Script Store',  'Hazır PHP scriptleri ve otomasyon araçları.',        20.0],
        ];

        foreach ($vendors as $v) {
            $customer = Connection::selectOne("SELECT id FROM customers WHERE email = ?", [$v[0]]);
            if (!$customer) continue;
            if (Connection::selectOne("SELECT id FROM vendors WHERE customer_id = ?", [$customer['id']])) continue;

            $slug = Slug::make($v[1]);
            Connection::insert('vendors', [
                'customer_id'      => (int) $customer['id'],
                'shop_name'        => $v[1],
                'shop_slug'        => $slug,
                'description'      => $v[2],
                'contact_email'    => $v[0],
                'country'          => 'TR',
                'commission_rate'  => $v[3],
                'status'           => 'approved',
                'approved_at'      => date('Y-m-d H:i:s'),
                'rating_avg'       => round(mt_rand(42, 50) / 10, 1),
                'rating_count'     => mt_rand(15, 200),
                'total_sales'      => mt_rand(5000, 50000),
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // ─── 9. Marketplace İlanları (multi-vendor + kategoriler) ───
    private function seedMarketplace(): void
    {
        // Kategori oluştur (yoksa)
        $cats = [
            ['🎨 WordPress Temalar','wp-themes'],
            ['🛠 PHP Scriptler','php-scripts'],
            ['📱 Mobil Uygulama Şablonları','mobile-templates'],
            ['🎯 SEO Paketleri','seo-packages'],
            ['🖼 Logo & Grafik Tasarım','logo-design'],
            ['📝 İçerik & Metin Yazımı','content-writing'],
            ['⚡ Site Hızlandırma','site-speed'],
            ['🔒 Güvenlik & SSL','security'],
        ];
        $catIds = [];
        foreach ($cats as $c) {
            $exists = Connection::selectOne("SELECT id FROM marketplace_categories WHERE slug = ?", [$c[1]]);
            if ($exists) { $catIds[$c[1]] = (int) $exists['id']; continue; }
            $catIds[$c[1]] = Connection::insert('marketplace_categories', [
                'name'       => $c[0],
                'slug'       => $c[1],
                'is_active'  => 1,
                'sort_order' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // İlanlar
        $listings = [
            // WordPress Temaları
            ['MedikaPro — Sağlık WordPress Teması', 'wp-themes', 'Yılmaz Yazılım', 899, 'Doktor, klinik, hastane siteleri için premium WordPress teması.'],
            ['ShopMax — E-Ticaret WooCommerce Teması', 'wp-themes', 'Aksoy Digital', 1299, 'Multi-vendor WooCommerce teması. Elementor + WPBakery uyumlu.'],
            ['RestoElegance — Restoran Teması', 'wp-themes', 'Yılmaz Yazılım', 699, 'Restoran, kafe menü + online rezervasyon teması.'],
            ['LawyerX — Hukuk Bürosu', 'wp-themes', 'Aksoy Digital', 799, 'Avukatlık büroları için minimal, güven veren tema.'],
            ['EduLMS — Eğitim Platform Teması', 'wp-themes', 'Aksoy Digital', 1499, 'LearnDash + WooCommerce entegrasyonlu LMS teması.'],

            // PHP Scriptler
            ['URL Kısaltma Servisi', 'php-scripts', 'Kaya Script Store', 499, 'Bit.ly benzeri URL kısaltma servisi PHP scripti. Analitik dahil.'],
            ['Online Randevu Sistemi', 'php-scripts', 'Kaya Script Store', 799, 'Multi-servis online randevu sistemi. SMS + mail bildirim.'],
            ['Anket & Oylama Sistemi', 'php-scripts', 'Kaya Script Store', 399, 'Anket oluşturma, cevap toplama, sonuç grafik gösterimi.'],
            ['CV Yayın Sistemi', 'php-scripts', 'Kaya Script Store', 599, 'İK ajansları için online CV veritabanı + filtreleme.'],

            // Mobil Şablonlar
            ['DeliveryApp — Yemek Sipariş Uygulaması', 'mobile-templates', 'Şahin Design Studio', 2499, 'Flutter + Firebase yemek sipariş şablonu.'],
            ['FitTrack — Fitness Uygulaması', 'mobile-templates', 'Şahin Design Studio', 1999, 'React Native fitness takip uygulaması şablonu.'],
            ['TaxiApp — Ulaşım Uygulaması', 'mobile-templates', 'Şahin Design Studio', 2999, 'Uber tarzı taxi uygulaması, sürücü + müşteri.'],

            // SEO Paketleri
            ['Starter SEO Paketi', 'seo-packages', 'Arslan SEO Ajansı', 999, 'Aylık: 10 anahtar kelime, 5 makale, teknik SEO düzeltme.'],
            ['Pro SEO Paketi', 'seo-packages', 'Arslan SEO Ajansı', 2499, 'Aylık: 30 anahtar kelime, 15 makale, backlink, rakip analiz.'],
            ['Enterprise SEO', 'seo-packages', 'Arslan SEO Ajansı', 4999, 'Aylık: sınırsız anahtar kelime, özel danışmanlık.'],
            ['Google Ads Yönetimi', 'seo-packages', 'Arslan SEO Ajansı', 1999, 'Aylık Google Ads hesap yönetimi + optimizasyon.'],

            // Logo & Tasarım
            ['Premium Logo Tasarımı', 'logo-design', 'Şahin Design Studio', 799, '3 konsept + sınırsız revize + kaynak dosyalar.'],
            ['Kurumsal Kimlik Paketi', 'logo-design', 'Şahin Design Studio', 1999, 'Logo + kartvizit + antetli kağıt + zarf tasarımı.'],
            ['Sosyal Medya Görsel Paketi', 'logo-design', 'Şahin Design Studio', 599, '10 adet sosyal medya post tasarımı.'],

            // İçerik
            ['SEO Uyumlu Blog Yazısı (5 adet)', 'content-writing', 'Arslan SEO Ajansı', 799, '5 adet 1000+ kelimelik SEO uyumlu Türkçe blog yazısı.'],
            ['Ürün Açıklama Yazma (20 ürün)', 'content-writing', 'Arslan SEO Ajansı', 599, 'E-ticaret sitesi için 20 ürün açıklaması.'],

            // Hızlandırma
            ['Site Hızlandırma Servisi', 'site-speed', 'Yılmaz Yazılım', 999, 'PageSpeed 90+ garantisi. Cache + görsel + kod optimizasyonu.'],
            ['WordPress Performans Optimizasyonu', 'site-speed', 'Yılmaz Yazılım', 1299, 'WP için tam performans paketi.'],

            // Güvenlik
            ['Malware Temizleme (tek seferlik)', 'security', 'Kaya Script Store', 799, 'Hacklenmiş site için malware tespiti + temizleme.'],
            ['Aylık Güvenlik Tarama', 'security', 'Kaya Script Store', 199, 'Aylık otomatik güvenlik + zafiyet tarama raporu.'],
        ];

        foreach ($listings as $l) {
            $vendor = Connection::selectOne("SELECT id, customer_id FROM vendors WHERE shop_name = ?", [$l[2]]);
            $slug = Slug::make($l[0]);
            if (Connection::selectOne("SELECT id FROM marketplace_listings WHERE slug = ?", [$slug])) continue;

            Connection::insert('marketplace_listings', [
                'seller_id'       => (int) ($vendor['customer_id'] ?? 1),
                'category_id'     => $catIds[$l[1]] ?? null,
                'vendor_id'       => $vendor['id'] ?? null,
                'title'           => $l[0],
                'slug'            => $slug,
                'description'     => $l[4],
                'price'           => $l[3],
                'currency'        => 'TRY',
                'status'          => 'active',
                'commission_rate' => 10.0,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // ─── 10. Örnek Lisanslar ───
    private function seedLicenses(): void
    {
        $customers = Connection::select("SELECT id, email FROM customers WHERE email LIKE '%@demo.com' LIMIT 5");
        if (!$customers) return;

        $products = [
            ['Ahost Site Builder Pro', 'single_domain', 1],
            ['Ahost Mobile Builder', 'single_package', 1],
            ['Ahost URL Kısaltma Script', 'multi_domain', 5],
            ['Ahost Randevu Sistemi', 'unlimited', 999],
            ['Ahost SEO Analiz Tool', 'single_domain', 1],
        ];

        foreach ($products as $i => $p) {
            $customer = $customers[$i % count($customers)];
            $key = 'AHOST-' . strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2)));

            if (Connection::selectOne("SELECT id FROM licenses WHERE license_key = ?", [$key])) continue;

            $licId = Connection::insert('licenses', [
                'license_key'    => $key,
                'customer_id'    => (int) $customer['id'],
                'product_name'   => $p[0],
                'product_version'=> 'v1.0.0',
                'license_type'   => $p[1],
                'max_domains'    => $p[2],
                'status'         => 'active',
                'issued_at'      => date('Y-m-d H:i:s'),
                'expires_at'     => date('Y-m-d H:i:s', strtotime('+1 year')),
                'source'         => 'ahost',
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            // Bazılarına örnek aktivasyon ekle
            if ($i < 3) {
                Connection::insert('license_activations', [
                    'license_id'      => $licId,
                    'identifier'      => 'ornek' . ($i+1) . '.com',
                    'identifier_type' => 'domain',
                    'ip'              => '192.168.' . mt_rand(1, 255) . '.' . mt_rand(1, 255),
                    'activated_at'    => date('Y-m-d H:i:s'),
                    'last_seen_at'    => date('Y-m-d H:i:s'),
                    'is_active'       => 1,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
};
