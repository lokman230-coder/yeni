<?php
// Public page hero cards: compact, theme-friendly, admin-editable.

if (!function_exists('ao_site_hero_ensure_schema')) {
    function ao_site_hero_ensure_schema(): void {
        try {
            db()->exec("CREATE TABLE IF NOT EXISTS site_hero_cards (
                id INT AUTO_INCREMENT PRIMARY KEY,
                page_key VARCHAR(120) NOT NULL,
                label VARCHAR(160) NULL,
                kicker VARCHAR(160) NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                primary_label VARCHAR(120) NULL,
                primary_url VARCHAR(255) NULL,
                secondary_label VARCHAR(120) NULL,
                secondary_url VARCHAR(255) NULL,
                background VARCHAR(255) NULL,
                text_color VARCHAR(40) NULL,
                accent_color VARCHAR(40) NULL,
                max_width VARCHAR(40) NULL,
                padding_value VARCHAR(80) NULL,
                title_size VARCHAR(40) NULL,
                title_weight VARCHAR(20) NULL,
                body_size VARCHAR(40) NULL,
                radius_value VARCHAR(40) NULL,
                align_value VARCHAR(30) NULL,
                sort_order INT DEFAULT 10,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_page_key(page_key),
                KEY idx_active_sort(is_active, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } catch (Throwable $e) {
            error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
        }

        try {
            $count = (int)db()->query('SELECT COUNT(*) FROM site_hero_cards')->fetchColumn();
            $missingRows = [
                ['site-araclari','Site Araçları','Site Araçları','Domain, SEO, SSL ve teknik kontrolleri tek ekranda çalıştırın.','WHOIS, DNS, SSL, hız, SEO ve güvenlik araçlarını popup sonuç kartlarıyla kullanın.','Araçları Gör','#araclar','Domain Sorgula','domain','', '', '#ff675d','1280px','24px 36px','clamp(28px,3vw,42px)','630','15px','24px','left',90,1],
                ['sitebuilder','Site Builder','AI Site Builder','Dakikalar içinde profesyonel web sitesi tasarlayın.','Şablonlar, blok editör, önizleme ve yayın paketlerini Ahost One akışında kullanın.','Site Oluştur','sitebuilder/create-demo','Paketleri Gör','urun-grubu/sitebuilder','', '', '#ff675d','1280px','24px 36px','clamp(28px,3vw,42px)','630','15px','24px','left',92,1],
                ['mobilebuilder','Mobile Builder','AI Mobile Builder','Kod yazmadan mobil uygulama planlayın.','APK/AAB, PWA, şablon ve kaynak kod süreçlerini tek merkezden başlatın.','Uygulama Oluştur','mobilebuilder/create-demo','Paketleri Gör','urun-grubu/mobilebuilder','', '', '#ff675d','1280px','24px 36px','clamp(28px,3vw,42px)','630','15px','24px','left',94,1],
                ['iletisim','İletişim','Ahost One','Sorularınız için destek talebi açın.','Satış öncesi görüşmeler, domain, hosting ve teknik destek için en hızlı yol destek talebi oluşturmaktır.','Destek Talebi Aç','client/support','Bilgi Bankası','bilgi-bankasi','', '', '#ff675d','1120px','22px 34px','clamp(27px,2.8vw,40px)','620','15px','24px','left',100,1],
            ];
            $missingStmt = db()->prepare('INSERT IGNORE INTO site_hero_cards(page_key,label,kicker,title,description,primary_label,primary_url,secondary_label,secondary_url,background,text_color,accent_color,max_width,padding_value,title_size,title_weight,body_size,radius_value,align_value,sort_order,is_active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            foreach ($missingRows as $row) $missingStmt->execute($row);
            if ($count === 0) {
                $rows = [
                    ['home','Ana Sayfa','Ahost One','İşinizi büyüten dijital çözümler tek merkezde.','Hosting, domain, sunucu, web tasarım, mobil uygulama, SEO ve dijital hizmetleri modern bir satın alma deneyimiyle keşfedin.','Ürünleri Keşfet','urunler','Domain Sorgula','domain','', '', '#ff675d','1420px','28px 44px','clamp(34px,4vw,58px)','650','17px','28px','left',10,1],
                    ['domain','Domain','Domain Center UX Pro','Markanız için doğru domaini bulun.','Uygun domainleri sorgulayın, WHOIS/DNS/SSL araçlarıyla teknik durumu analiz edin ve sepete ekleyin.','Domain Sorgula','domain','Transfer Başlat','domain-transfer','', '', '#ff675d','1280px','26px 40px','clamp(32px,3.4vw,50px)','650','16px','26px','left',20,1],
                    ['hosting','Hosting','Hosting Merkezi','İhtiyacınıza uygun hosting paketini seçin.','Performans, güvenlik, yedekleme ve destek seçenekleriyle paketleri karşılaştırın.','Paketleri Gör','hosting','Domain Sorgula','domain','', '', '#ff675d','1380px','24px 38px','clamp(30px,3.2vw,46px)','640','16px','26px','left',30,1],
                    ['web-tasarim','Web Tasarım','Web Tasarım Merkezi','Web projenizi sade, hızlı ve premium bir yapıyla başlatın.','Kurumsal site, e-ticaret, portal ve özel yazılım ihtiyaçlarınızı tek süreçte planlayın.','Site Tasarlat','teklif','Paketleri Gör','urun-grubu/web-tasarim','', '', '#ff675d','1320px','24px 38px','clamp(30px,3.2vw,46px)','640','16px','26px','left',40,1],
                    ['mobil-uygulama','Mobil Uygulama','Mobil Uygulama Hizmetleri','Android ve iOS projeleri için uçtan uca çözüm.','Tasarım, geliştirme, mağaza yayını ve entegrasyon adımlarını tek merkezden başlatın.','Uygulama Tasarlat','urun-grubu/mobil-uygulama','Teklif Al','teklif','', '', '#ff675d','1320px','24px 38px','clamp(30px,3.2vw,46px)','640','16px','26px','left',50,1],
                    ['marketplace','Marketplace','Ahost Marketplace Pro','Domain, yazılım, tasarım ve dijital hizmetleri keşfedin.','İlanları filtreleyin, teklif verin veya dijital ürünlerinizi satışa çıkarın.','İlanları Keşfet','marketplace#ilanlar','İlan Oluştur','marketplace#ilan-olustur','', '', '#ff675d','1320px','24px 38px','clamp(30px,3.2vw,46px)','640','16px','26px','left',60,1],
                    ['referanslar','Referanslar','Seçili Çalışmalar','Fikirleri çalışan dijital ürünlere dönüştürüyoruz.','Web siteleri ve Android uygulamalarını kategorilerle yan yana keşfedin.','Teklif Al','teklif','Çözümleri İnceleyin','urunler','', '', '#ff675d','1320px','24px 38px','clamp(30px,3.2vw,46px)','640','16px','26px','left',70,1],
                    ['bilgi-bankasi','Bilgi Bankası','Ahost One Yardım','Hosting, domain, WordPress ve panel işlemleri için pratik yanıtlar.','Sık kullanılan çözümleri arayın; gerekirse destek talebi oluşturarak ekibimize ulaşın.','Destek Talebi','support','Site Araçları','site-araclari','', '', '#ff675d','1280px','24px 36px','clamp(28px,3vw,44px)','640','16px','24px','left',80,1],
                ];
                $stmt = db()->prepare('INSERT IGNORE INTO site_hero_cards(page_key,label,kicker,title,description,primary_label,primary_url,secondary_label,secondary_url,background,text_color,accent_color,max_width,padding_value,title_size,title_weight,body_size,radius_value,align_value,sort_order,is_active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                foreach ($rows as $row) $stmt->execute($row);
            }
        } catch (Throwable $e) {
            error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
        }
    }
}

if (!function_exists('ao_site_hero_page_options')) {
    function ao_site_hero_page_options(): array {
        $items = [
            '' => 'Otomatik / Geçerli rota',
            'home' => 'Ana Sayfa',
            'domain' => 'Domain Sorgula',
            'domain-transfer' => 'Domain Transfer',
            'hosting' => 'Hosting',
            'web-tasarim' => 'Web Tasarım',
            'mobil-uygulama' => 'Mobil Uygulama',
            'android-uygulama' => 'Android Uygulama',
            'dijital-hizmetler' => 'Dijital Hizmetler',
            'marketplace' => 'Marketplace',
            'referanslar' => 'Referanslar',
            'bilgi-bankasi' => 'Bilgi Bankası',
            'site-araclari' => 'Site Araçları',
            'sitebuilder' => 'Site Builder',
            'mobilebuilder' => 'Mobile Builder',
            'iletisim' => 'İletişim',
            'hakkimizda' => 'Hakkımızda',
            'urunler' => 'Ürünler',
        ];
        try {
            $groups = db()->query("SELECT name, slug FROM product_groups WHERE slug<>'' ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($groups as $group) {
                $items['urun-grubu/'.$group['slug']] = 'Ürün Grubu: '.$group['name'];
            }
        } catch (Throwable $e) {}
        return $items;
    }
}

if (!function_exists('ao_site_hero_normalize_page_key')) {
    function ao_site_hero_normalize_page_key(?string $key = null): string {
        $key = trim((string)($key ?? ($_SERVER['AHOST_ROUTE_RESOLVED'] ?? '')), '/');
        if ($key === '' || $key === 'home' || $key === 'index') return 'home';
        return preg_replace('~[^a-z0-9/_-]+~i', '-', $key) ?: 'home';
    }
}

if (!function_exists('ao_site_hero_find')) {
    function ao_site_hero_find(?string $pageKey = null): ?array {
        ao_site_hero_ensure_schema();
        $pageKey = ao_site_hero_normalize_page_key($pageKey);
        $candidates = [$pageKey];
        if (str_contains($pageKey, '/')) {
            $parts = explode('/', $pageKey);
            $candidates[] = $parts[0];
        }
        $candidates[] = 'global';
        $candidates = array_values(array_unique(array_filter($candidates)));
        try {
            $placeholders = implode(',', array_fill(0, count($candidates), '?'));
            $q = db()->prepare("SELECT * FROM site_hero_cards WHERE is_active=1 AND page_key IN ($placeholders) ORDER BY FIELD(page_key,$placeholders), sort_order, id DESC LIMIT 1");
            $q->execute(array_merge($candidates, $candidates));
            $row = $q->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('ao_site_hero_url')) {
    function ao_site_hero_url(string $value): string {
        $value = trim($value);
        if ($value === '') return '#';
        if (preg_match('~^(https?:)?//|^#|^mailto:|^tel:~i', $value)) return $value;
        return url($value);
    }
}

if (!function_exists('ao_site_hero_style')) {
    function ao_site_hero_style(array $hero): string {
        $map = [
            'background' => '--ao-hero-bg',
            'text_color' => '--ao-hero-color',
            'accent_color' => '--ao-hero-accent',
            'max_width' => '--ao-hero-max',
            'padding_value' => '--ao-hero-padding',
            'title_size' => '--ao-hero-title-size',
            'title_weight' => '--ao-hero-title-weight',
            'body_size' => '--ao-hero-body-size',
            'radius_value' => '--ao-hero-radius',
            'align_value' => '--ao-hero-align',
        ];
        $style = [];
        foreach ($map as $field => $var) {
            $value = trim((string)($hero[$field] ?? ''));
            if ($value !== '') $style[] = $var.':'.$value;
        }
        return $style ? ' style="'.e(implode(';', $style)).'"' : '';
    }
}

if (!function_exists('ao_site_hero_render')) {
    function ao_site_hero_render(?string $pageKey = null, array $fallback = []): string {
        $hero = ao_site_hero_find($pageKey);
        if (!$hero && trim((string)($fallback['title'] ?? '')) !== '') {
            $hero = [
                'page_key' => ao_site_hero_normalize_page_key($pageKey),
                'kicker' => $fallback['kicker'] ?? '',
                'title' => $fallback['title'] ?? 'Ahost One',
                'description' => $fallback['description'] ?? ($fallback['summary'] ?? ''),
                'primary_label' => $fallback['primary_label'] ?? '',
                'primary_url' => $fallback['primary_url'] ?? '#',
                'secondary_label' => $fallback['secondary_label'] ?? '',
                'secondary_url' => $fallback['secondary_url'] ?? '#',
                'background' => $fallback['background'] ?? '',
                'text_color' => $fallback['text_color'] ?? '',
                'accent_color' => $fallback['accent_color'] ?? '',
                'max_width' => $fallback['max_width'] ?? '',
                'padding_value' => $fallback['padding_value'] ?? '',
                'title_size' => $fallback['title_size'] ?? '',
                'title_weight' => $fallback['title_weight'] ?? '',
                'body_size' => $fallback['body_size'] ?? '',
                'radius_value' => $fallback['radius_value'] ?? '',
                'align_value' => $fallback['align_value'] ?? '',
            ];
        }
        if (!$hero) return '';
        ob_start();
        ?>
        <header class="ao-content-hero ao-managed-hero" data-builder-block="managed-hero" data-hero-key="<?= e($hero['page_key']) ?>"<?= ao_site_hero_style($hero) ?>>
            <?php if (trim((string)($hero['kicker'] ?? '')) !== ''): ?><span class="ao-content-kicker"><?= e($hero['kicker']) ?></span><?php endif; ?>
            <h1><?= e($hero['title'] ?: ($fallback['title'] ?? 'Ahost One')) ?></h1>
            <?php if (trim((string)($hero['description'] ?? '')) !== ''): ?><p><?= e($hero['description']) ?></p><?php endif; ?>
            <?php if (trim((string)($hero['primary_label'] ?? $hero['secondary_label'] ?? '')) !== ''): ?>
                <div class="ao-content-actions">
                    <?php if (trim((string)($hero['primary_label'] ?? '')) !== ''): ?><a class="ao-content-btn" href="<?= e(ao_site_hero_url((string)($hero['primary_url'] ?? '#'))) ?>"><?= e($hero['primary_label']) ?></a><?php endif; ?>
                    <?php if (trim((string)($hero['secondary_label'] ?? '')) !== ''): ?><a class="ao-content-btn secondary" href="<?= e(ao_site_hero_url((string)($hero['secondary_url'] ?? '#'))) ?>"><?= e($hero['secondary_label']) ?></a><?php endif; ?>
                </div>
            <?php endif; ?>
        </header>
        <?php
        return trim(ob_get_clean());
    }
}

if (isset($route) && $route === 'admin/site-heroes') {
    require_admin();
    ao_site_hero_ensure_schema();
    view('site-heroes/index', ['pageTitle' => 'Hero Kartları']);
    exit;
}

if (isset($route) && $_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/site-heroes/save') {
    require_admin();
    verify_csrf();
    ao_site_hero_ensure_schema();
    $id = (int)($_POST['id'] ?? 0);
    $pageKey = ao_site_hero_normalize_page_key($_POST['page_key'] ?? '');
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') {
        flash('error', 'Hero başlığı zorunlu.');
        redirect_to('admin/site-heroes'.($id ? '?edit='.$id : ''));
    }
    $data = [
        $pageKey,
        trim((string)($_POST['label'] ?? '')),
        trim((string)($_POST['kicker'] ?? '')),
        $title,
        trim((string)($_POST['description'] ?? '')),
        trim((string)($_POST['primary_label'] ?? '')),
        trim((string)($_POST['primary_url'] ?? '')),
        trim((string)($_POST['secondary_label'] ?? '')),
        trim((string)($_POST['secondary_url'] ?? '')),
        trim((string)($_POST['background'] ?? '')),
        trim((string)($_POST['text_color'] ?? '')),
        trim((string)($_POST['accent_color'] ?? '')),
        trim((string)($_POST['max_width'] ?? '')),
        trim((string)($_POST['padding_value'] ?? '')),
        trim((string)($_POST['title_size'] ?? '')),
        trim((string)($_POST['title_weight'] ?? '')),
        trim((string)($_POST['body_size'] ?? '')),
        trim((string)($_POST['radius_value'] ?? '')),
        trim((string)($_POST['align_value'] ?? '')),
        (int)($_POST['sort_order'] ?? 10),
        (($_POST['is_active'] ?? '1') === '1') ? 1 : 0,
    ];
    try {
        if ($id > 0) {
            $data[] = $id;
            db()->prepare('UPDATE site_hero_cards SET page_key=?,label=?,kicker=?,title=?,description=?,primary_label=?,primary_url=?,secondary_label=?,secondary_url=?,background=?,text_color=?,accent_color=?,max_width=?,padding_value=?,title_size=?,title_weight=?,body_size=?,radius_value=?,align_value=?,sort_order=?,is_active=? WHERE id=?')->execute($data);
            flash('success', 'Hero kartı güncellendi.');
        } else {
            db()->prepare('INSERT INTO site_hero_cards(page_key,label,kicker,title,description,primary_label,primary_url,secondary_label,secondary_url,background,text_color,accent_color,max_width,padding_value,title_size,title_weight,body_size,radius_value,align_value,sort_order,is_active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE label=VALUES(label),kicker=VALUES(kicker),title=VALUES(title),description=VALUES(description),primary_label=VALUES(primary_label),primary_url=VALUES(primary_url),secondary_label=VALUES(secondary_label),secondary_url=VALUES(secondary_url),background=VALUES(background),text_color=VALUES(text_color),accent_color=VALUES(accent_color),max_width=VALUES(max_width),padding_value=VALUES(padding_value),title_size=VALUES(title_size),title_weight=VALUES(title_weight),body_size=VALUES(body_size),radius_value=VALUES(radius_value),align_value=VALUES(align_value),sort_order=VALUES(sort_order),is_active=VALUES(is_active)')->execute($data);
            flash('success', 'Hero kartı kaydedildi.');
        }
    } catch (Throwable $e) {
        flash('error', 'Hero kaydedilemedi: '.$e->getMessage());
    }
    redirect_to('admin/site-heroes');
}

if (isset($route) && $_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/site-heroes/delete') {
    require_admin();
    verify_csrf();
    ao_site_hero_ensure_schema();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            db()->prepare('DELETE FROM site_hero_cards WHERE id=?')->execute([$id]);
            flash('success', 'Hero kartı silindi.');
        } catch (Throwable $e) {
            flash('error', 'Hero silinemedi: '.$e->getMessage());
        }
    }
    redirect_to('admin/site-heroes');
}
