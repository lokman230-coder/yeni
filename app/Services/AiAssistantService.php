<?php

if (!function_exists('ao_ai_assistant_ensure_schema')) {
    function ao_ai_assistant_ensure_schema(): void {
        try {
            db()->exec("CREATE TABLE IF NOT EXISTS ai_assistant_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                actor_type VARCHAR(30) NOT NULL,
                actor_id INT NULL,
                prompt TEXT NULL,
                intent VARCHAR(80) NULL,
                status VARCHAR(40) DEFAULT 'info',
                response LONGTEXT NULL,
                action_json LONGTEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY actor_lookup(actor_type, actor_id),
                KEY intent_idx(intent),
                KEY status_idx(status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
            error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
        }
    }

    function ao_ai_assistant_slug(string $text): string {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = strtr($text, ['ş'=>'s','ı'=>'i','ğ'=>'g','ü'=>'u','ö'=>'o','ç'=>'c']);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim((string)$text, '-') ?: 'icerik';
    }

    function ao_ai_assistant_clean_prompt(string $prompt): string {
        $prompt = trim(strip_tags($prompt));
        return mb_substr($prompt, 0, 2000, 'UTF-8');
    }

    function ao_ai_assistant_actor(string $role): array {
        if ($role === 'admin') {
            $admin = function_exists('current_admin') ? current_admin() : null;
            return ['type'=>'admin', 'id'=>(int)($admin['id'] ?? ($_SESSION['admin_id'] ?? 0)), 'row'=>$admin ?: []];
        }
        if ($role === 'customer') {
            $customer = function_exists('current_customer') ? current_customer() : null;
            return ['type'=>'customer', 'id'=>(int)($customer['id'] ?? ($_SESSION['customer_id'] ?? 0)), 'row'=>$customer ?: []];
        }
        return ['type'=>'guest', 'id'=>0, 'row'=>[]];
    }

    function ao_ai_assistant_is_unsafe(string $prompt, string $role): ?string {
        $p = mb_strtolower($prompt, 'UTF-8');
        $danger = [
            'tüm müşterileri sil', 'müşterileri sil', 'veritabanını sil', 'database sil', 'db sil',
            'dosyaları sil', 'tema klasörünü sil', 'şifreleri göster', 'kredi kartı', 'kart bilgisi',
            'başkasının faturası', 'başka müşterinin', 'yetki atlat', 'admin şifresi', 'sql çalıştır',
            'php kodu çalıştır', 'shell çalıştır'
        ];
        foreach ($danger as $needle) {
            if (str_contains($p, $needle)) return 'Bu istek güvenlik nedeniyle engellendi.';
        }
        if ($role !== 'admin' && preg_match('/\b(sil|pasifleştir|aktif et|admin|ayar|tema|ürün ekle|sayfa oluştur)\b/u', $p)) {
            return 'Bu işlem sadece admin yetkisiyle yapılabilir.';
        }
        return null;
    }

    function ao_ai_assistant_detect_intent(string $prompt, string $role): array {
        $p = mb_strtolower($prompt, 'UTF-8');
        preg_match('/([a-z0-9çğıöşü\-]+\.[a-z]{2,}(?:\.[a-z]{2,})?)/iu', $prompt, $domainMatch);
        $domain = strtolower((string)($domainMatch[1] ?? ''));

        if ($domain !== '' && preg_match('/sorgula|domain|müsait|uygun|alabilir/i', $p)) {
            return ['intent'=>'domain_check', 'domain'=>$domain];
        }
        if (preg_match('/ticket|destek|talep|yardım|sorun/u', $p)) {
            return ['intent'=>'support_ticket'];
        }
        if ($role === 'admin' && preg_match('/sayfa oluştur|sayfa ekle|yeni sayfa/u', $p)) {
            return ['intent'=>'admin_create_page'];
        }
        if ($role === 'admin' && preg_match('/ürün ekle|ürün oluştur|paket ekle|hosting ekle/u', $p)) {
            return ['intent'=>'admin_create_product'];
        }
        if ($role === 'admin' && preg_match('/tema|builder|header|footer|blok|renk|buton/u', $p)) {
            return ['intent'=>'admin_design_help'];
        }
        if (preg_match('/site builder|site oluştur|web site oluştur|web sitesi oluştur|sayfa oluştur/u', $p)) {
            return ['intent'=>'site_builder_help'];
        }
        if (preg_match('/mobile builder|mobil uygulama|uygulama oluştur|android|ios/u', $p)) {
            return ['intent'=>'mobile_builder_help'];
        }
        if (preg_match('/ürün|paket|hosting|web tasarım|mobil|ssl|fiyat/u', $p)) {
            return ['intent'=>'product_help'];
        }
        return ['intent'=>'knowledge_answer'];
    }

    function ao_ai_assistant_title_from_prompt(string $prompt, string $fallback): string {
        $prompt = preg_replace('/\b(sayfa|oluştur|ekle|ürün|paket|hosting|yeni|lütfen|admin)\b/iu', ' ', $prompt);
        $prompt = trim(preg_replace('/\s+/', ' ', (string)$prompt));
        if ($prompt === '') $prompt = $fallback;
        return mb_substr($prompt, 0, 110, 'UTF-8');
    }

    function ao_ai_assistant_external_hint(string $prompt, string $role): string {
        if (!function_exists('ao_ai_call_optional')) return '';
        $provider = (string)admin_setting('default_ai_provider', admin_setting('ai_provider', ''));
        $hasKey = trim((string)admin_setting($provider.'_api_key', admin_setting('ai_api_key', ''))) !== '';
        if (!$hasKey) return '';
        try {
            $answer = ao_ai_call_optional(
                "Türkçe cevap ver. Rol: {$role}. Kullanıcı isteğini güvenli şekilde yorumla, işlem yapma, sadece kısa öneri ver: ".$prompt,
                $provider
            );
            return is_string($answer) ? trim($answer) : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    function ao_ai_assistant_log(array $actor, string $prompt, string $intent, string $status, string $response, array $action = []): void {
        ao_ai_assistant_ensure_schema();
        try {
            db()->prepare('INSERT INTO ai_assistant_logs(actor_type,actor_id,prompt,intent,status,response,action_json) VALUES(?,?,?,?,?,?,?)')
                ->execute([$actor['type'], (int)$actor['id'], $prompt, $intent, $status, $response, json_encode($action, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        } catch (Throwable $e) {
            error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
        }
    }

    function ao_ai_assistant_create_customer_ticket(int $customerId, string $subject, string $message): int {
        try {
            if (function_exists('ao_support_ensure_ticket_link_columns')) ao_support_ensure_ticket_link_columns();
            else db()->exec("CREATE TABLE IF NOT EXISTS tickets (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NULL, subject VARCHAR(255) NOT NULL, department VARCHAR(120) DEFAULT 'Genel', priority VARCHAR(40) DEFAULT 'medium', status VARCHAR(40) DEFAULT 'open', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY customer_id(customer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
            error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
        }
        $cols = function_exists('ao_support_ticket_columns') ? ao_support_ticket_columns() : [];
        if (!$cols) {
            try {
                foreach (db()->query('SHOW COLUMNS FROM tickets')->fetchAll() as $col) $cols[$col['Field'] ?? $col[0]] = true;
            } catch (Throwable $e) {}
        }
        $data = [
            'customer_id'=>$customerId,
            'subject'=>$subject,
            'department'=>'Teknik Destek',
            'priority'=>'medium',
            'status'=>'open',
        ];
        if (isset($cols['message'])) $data['message'] = $message;
        if (isset($cols['created_at'])) $data['created_at'] = date('Y-m-d H:i:s');
        $fields = array_values(array_filter(array_keys($data), fn($field) => isset($cols[$field]) || $field === 'created_at'));
        if (!in_array('subject', $fields, true)) throw new Exception('Ticket tablosu konu alanını desteklemiyor.');
        $sql = 'INSERT INTO tickets('.implode(',', $fields).') VALUES('.implode(',', array_fill(0, count($fields), '?')).')';
        db()->prepare($sql)->execute(array_map(fn($field) => $data[$field], $fields));
        $ticketId = (int)db()->lastInsertId();
        try {
            db()->exec("CREATE TABLE IF NOT EXISTS ticket_replies (id INT AUTO_INCREMENT PRIMARY KEY, ticket_id INT NOT NULL, sender_type VARCHAR(40) DEFAULT 'customer', message LONGTEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY ticket_id(ticket_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            db()->prepare('INSERT INTO ticket_replies(ticket_id,sender_type,message) VALUES(?,"customer",?)')->execute([$ticketId, $message]);
        } catch (Throwable $e) {
            error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
        }
        return $ticketId;
    }

    function ao_ai_assistant_run(string $prompt, string $role = 'guest', bool $apply = true): array {
        ao_ai_assistant_ensure_schema();
        $role = in_array($role, ['admin','customer','guest'], true) ? $role : 'guest';
        $prompt = ao_ai_assistant_clean_prompt($prompt);
        $actor = ao_ai_assistant_actor($role);
        if ($prompt === '') return ['ok'=>false, 'intent'=>'empty', 'message'=>'Komut boş olamaz.', 'actions'=>[]];

        if ($blocked = ao_ai_assistant_is_unsafe($prompt, $role)) {
            ao_ai_assistant_log($actor, $prompt, 'blocked', 'blocked', $blocked);
            return ['ok'=>false, 'intent'=>'blocked', 'message'=>$blocked, 'actions'=>[]];
        }

        $detected = ao_ai_assistant_detect_intent($prompt, $role);
        $intent = $detected['intent'];
        $actions = [];
        $message = '';

        try {
            if ($intent === 'domain_check') {
                $domain = function_exists('ahost_domain_clean') ? ahost_domain_clean($detected['domain'] ?? '') : strtolower((string)($detected['domain'] ?? ''));
                if ($domain === '') throw new Exception('Geçerli bir domain bulunamadı.');
                $res = function_exists('ao_domain_availability') ? ao_domain_availability($domain) : ['available'=>false, 'message'=>'Domain modülü hazır değil.'];
                $available = !empty($res['available']);
                $message = $available ? "{$domain} müsait görünüyor. İstersen sepete ekleme adımına geçebilirim." : "{$domain} kayıtlı veya sorgu sonucu müsait değil.";
                $actions[] = [
                    'type'=>'domain_check',
                    'domain'=>$domain,
                    'available'=>$available,
                    'route'=>$role === 'admin' ? url('admin/domain-center') : ($available ? url('cart/add?domain='.rawurlencode($domain)) : url('domain')),
                ];
            } elseif ($intent === 'support_ticket') {
                if ($role === 'customer' && $apply) {
                    $customerId = (int)$actor['id'];
                    if ($customerId <= 0) throw new Exception('Destek talebi için müşteri girişi gerekir.');
                    $subject = ao_ai_assistant_title_from_prompt($prompt, 'Asistan destek talebi');
                    $ticketId = ao_ai_assistant_create_customer_ticket($customerId, $subject, $prompt);
                    $message = "Destek talebin oluşturuldu: #{$ticketId}.";
                    $actions[] = ['type'=>'ticket_created', 'ticket_id'=>$ticketId, 'route'=>url('client/support?ticket_id='.$ticketId)];
                } else {
                    $message = $role === 'guest'
                        ? 'Destek talebi açmak için ad, e-posta ve mesaj gerekir. Seni destek formuna yönlendirebilirim.'
                        : 'Ticket açma isteğini algıladım. Müşteri seçimi veya mesaj metniyle devam edebilirim.';
                    $actions[] = ['type'=>'open_support', 'route'=>url($role === 'admin' ? 'admin/support/new' : 'client/support')];
                }
            } elseif ($intent === 'admin_create_page') {
                $title = ao_ai_assistant_title_from_prompt($prompt, 'Yeni Sayfa');
                $slug = ao_ai_assistant_slug($title);
                db()->exec("CREATE TABLE IF NOT EXISTS content_pages(id INT AUTO_INCREMENT PRIMARY KEY, page_type VARCHAR(30) DEFAULT 'page', title VARCHAR(190), slug VARCHAR(190), content MEDIUMTEXT NULL, status VARCHAR(30) DEFAULT 'draft', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                db()->prepare('INSERT INTO content_pages(page_type,title,slug,content,status) VALUES("page",?,?,?,"draft")')
                    ->execute([$title, $slug, '<p>Bu sayfa Ahost One Asistan tarafından taslak oluşturuldu.</p>']);
                $message = "Taslak sayfa oluşturuldu: {$title}. Yayına almadan önce içerik ve SEO alanlarını kontrol et.";
                $actions[] = ['type'=>'page_draft_created', 'title'=>$title, 'route'=>url('admin/pages')];
            } elseif ($intent === 'admin_create_product') {
                $title = ao_ai_assistant_title_from_prompt($prompt, 'Yeni Ürün Paketi');
                $slug = ao_ai_assistant_slug($title);
                db()->exec("CREATE TABLE IF NOT EXISTS product_groups (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL, slug VARCHAR(190) NOT NULL UNIQUE, type VARCHAR(80) DEFAULT 'service', description TEXT NULL, sort_order INT DEFAULT 0, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                db()->exec("CREATE TABLE IF NOT EXISTS products (id INT AUTO_INCREMENT PRIMARY KEY, group_id INT NULL, name VARCHAR(190) NOT NULL, slug VARCHAR(220) UNIQUE NOT NULL, type VARCHAR(60) DEFAULT 'service', short_description TEXT NULL, description TEXT NULL, price DECIMAL(14,2) DEFAULT 0, currency VARCHAR(10) DEFAULT 'TRY', billing_cycle VARCHAR(40) DEFAULT 'monthly', is_active TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY group_id(group_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $groupId = (int)(db()->query("SELECT id FROM product_groups WHERE slug='custom-packages' LIMIT 1")->fetchColumn() ?: 0);
                if ($groupId <= 0) {
                    db()->prepare('INSERT INTO product_groups(name,slug,type,description,is_active) VALUES(?,?,?,?,1)')->execute(['Özel Paketler', 'custom-packages', 'service', 'Asistan ile oluşturulan taslak paketler.']);
                    $groupId = (int)db()->lastInsertId();
                }
                db()->prepare('INSERT INTO products(group_id,name,slug,type,short_description,description,price,currency,billing_cycle,is_active) VALUES(?,?,?,?,?,?,?,?,?,0)')
                    ->execute([$groupId, $title, $slug, 'service', 'Asistan taslağı', 'Bu ürün Ahost One Asistan tarafından pasif taslak olarak oluşturuldu.', 0, 'TRY', 'monthly']);
                $message = "Pasif ürün taslağı oluşturuldu: {$title}. Fiyat, görsel ve paket özelliklerini kontrol edip aktif edebilirsin.";
                $actions[] = ['type'=>'product_draft_created', 'title'=>$title, 'route'=>url('admin/product-center/products')];
            } elseif ($intent === 'admin_design_help') {
                $message = 'Tasarım/builder isteğini algıladım. Güvenli yol: önce ilgili blok veya tema ayarını taslak olarak düzenlemek, sonra önizleme ve onayla yayına almak.';
                $actions[] = ['type'=>'open_builder', 'route'=>url('admin/builder-pro?target=site&template=home')];
                $actions[] = ['type'=>'open_theme', 'route'=>url('admin/theme-center/editor')];
            } elseif ($intent === 'site_builder_help') {
                if ($role === 'admin') {
                    $message = 'Site oluşturma isteğini algıladım. Admin tarafında AI Site Builder ekranından yazıyla taslak site oluşturabilirsiniz.';
                    $actions[] = ['type'=>'site_builder', 'label'=>'Admin Site Builder Aç', 'route'=>url('admin/site-builder/ai-design')];
                } elseif ($role === 'customer') {
                    $message = 'Site oluşturma isteğini algıladım. Müşteri panelindeki Site Builder ekranından AI destekli taslak oluşturabilirsiniz.';
                    $actions[] = ['type'=>'site_builder', 'label'=>'Müşteri Site Builder Aç', 'route'=>url('client/site-builder#ai-yardimi')];
                } else {
                    $message = 'Site oluşturma isteğini algıladım. Girişe yönlendirmeden önce taslak akışını deneyebilir ya da AI destekli paketleri inceleyebilirsiniz.';
                    $actions[] = ['type'=>'site_builder_packages', 'label'=>'AI ile Tasarlamak İçin Paket Al', 'route'=>url('urun-grubu/sitebuilder')];
                    $actions[] = ['type'=>'site_builder_demo', 'label'=>'AI Olmadan Devam Et', 'route'=>url('sitebuilder/create-demo')];
                }
            } elseif ($intent === 'mobile_builder_help') {
                if ($role === 'admin') {
                    $message = 'Mobil uygulama oluşturma isteğini algıladım. Admin Mobile Builder AI ekranından uygulama fikrini yazarak taslak oluşturabilirsiniz.';
                    $actions[] = ['type'=>'mobile_builder', 'label'=>'Admin Mobile Builder Aç', 'route'=>url('admin/mobile-builder/ai-app')];
                } elseif ($role === 'customer') {
                    $message = 'Mobil uygulama oluşturma isteğini algıladım. Müşteri panelindeki Mobile Builder ekranından AI destekli taslak oluşturabilirsiniz.';
                    $actions[] = ['type'=>'mobile_builder', 'label'=>'Müşteri Mobile Builder Aç', 'route'=>url('client/mobile-builder#ai-yardimi')];
                } else {
                    $message = 'Mobil uygulama oluşturma isteğini algıladım. Girişe yönlendirmeden önce demo akışını deneyebilir ya da AI destekli paketleri inceleyebilirsiniz.';
                    $actions[] = ['type'=>'mobile_builder_packages', 'label'=>'AI ile Tasarlamak İçin Paket Al', 'route'=>url('urun-grubu/mobilebuilder')];
                    $actions[] = ['type'=>'mobile_builder_demo', 'label'=>'AI Olmadan Devam Et', 'route'=>url('mobilebuilder/create-demo')];
                }
            } elseif ($intent === 'product_help') {
                if ($role === 'admin') {
                    $message = 'Ürün/paket ve fiyatlandırma isteğini algıladım. Admin Ürün Merkezi üzerinden ürünleri, fiyat periyotlarını, ek paketleri ve özel alanları yönetebilirsin.';
                    $actions[] = ['type'=>'open_products', 'label'=>'Admin Ürün Merkezi', 'route'=>url('admin/product-center')];
                    $actions[] = ['type'=>'open_product_prices', 'label'=>'Ürünler ve Fiyatlandırma', 'route'=>url('admin/product-center/products')];
                    $actions[] = ['type'=>'open_addons', 'label'=>'Ek Paketler', 'route'=>url('admin/product-center/addons')];
                } else {
                    $message = 'Ürün/paket isteğini algıladım. Hosting, web tasarım, mobil uygulama veya SSL paketlerinden birini seçip sepete yönlendirebilirim.';
                    $actions[] = ['type'=>'open_products', 'label'=>'Ürünleri Gör', 'route'=>url('urunler')];
                }
            } else {
                $message = 'Bu konuda yerel bilgi motoru net bir işlem bulamadı. İstersen domain sorgu, ürün öneri, destek talebi, sayfa/ürün taslağı veya builder işlemi olarak yazabilirsin.';
            }

            $hint = ao_ai_assistant_external_hint($prompt, $role);
            if ($hint !== '') $message .= "\n\nAPI destekli öneri: ".$hint;
            ao_ai_assistant_log($actor, $prompt, $intent, 'success', $message, $actions);
            return ['ok'=>true, 'intent'=>$intent, 'message'=>$message, 'actions'=>$actions];
        } catch (Throwable $e) {
            $message = 'Asistan işlemi tamamlayamadı: '.$e->getMessage();
            ao_ai_assistant_log($actor, $prompt, $intent, 'error', $message, $actions);
            return ['ok'=>false, 'intent'=>$intent, 'message'=>$message, 'actions'=>$actions];
        }
    }
}
