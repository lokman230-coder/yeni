<?php
// Ahost One Assistant: local action engine first, optional API only as a helper.

if (!function_exists('ao_ai_assistant_run')) {
    function ao_ai_assistant_run(string $prompt, string $audience = 'guest', bool $allowApi = true): array
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            return ['ok' => false, 'message' => 'Asistan komutu boş olamaz.', 'actions' => []];
        }

        $lower = mb_strtolower($prompt, 'UTF-8');
        $blocked = ['şifre', 'password', 'api key', 'veritabanını sil', 'database sil', 'drop table', 'truncate', 'dosya sil', 'delete file'];
        foreach ($blocked as $word) {
            if (str_contains($lower, $word)) {
                return [
                    'ok' => false,
                    'message' => 'Bu istek güvenlik nedeniyle otomatik işleme alınmadı.',
                    'actions' => [],
                ];
            }
        }

        $actions = [];
        if ($audience === 'admin') {
            if (str_contains($lower, 'domain')) {
                $actions[] = ['type' => 'suggest_route', 'label' => 'Admin Domain Center', 'url' => url('admin/domain-center')];
            }
            if (str_contains($lower, 'hosting') || str_contains($lower, 'sunucu')) {
                $actions[] = ['type' => 'suggest_route', 'label' => 'Admin Hosting & Sunucu', 'url' => url('admin/hosting-server')];
            }
            if (str_contains($lower, 'ürün') || str_contains($lower, 'urun') || str_contains($lower, 'paket') || str_contains($lower, 'fiyat')) {
                $actions[] = ['type' => 'suggest_route', 'label' => 'Admin Ürün Merkezi', 'url' => url('admin/product-center')];
                $actions[] = ['type' => 'suggest_route', 'label' => 'Ürünler ve Fiyatlandırma', 'url' => url('admin/product-center/products')];
            }
            if (str_contains($lower, 'tema') || str_contains($lower, 'renk') || str_contains($lower, 'builder')) {
                $actions[] = ['type' => 'suggest_route', 'label' => 'Admin Builder', 'url' => url('admin/builder-pro?target=site&template=home')];
                $actions[] = ['type' => 'suggest_route', 'label' => 'PRISM Tema Ayarları', 'url' => url('admin/theme-center/prism-builder')];
            }
        } else {
            if (str_contains($lower, 'domain')) {
                $actions[] = ['type' => 'suggest_route', 'label' => 'Domain sorgulama', 'url' => url('domain')];
            }
            if (str_contains($lower, 'hosting') || str_contains($lower, 'paket')) {
                $actions[] = ['type' => 'suggest_route', 'label' => 'Hosting paketleri', 'url' => url('hosting')];
            }
        }
        if (str_contains($lower, 'site oluştur') || str_contains($lower, 'site builder') || str_contains($lower, 'web sitesi')) {
            if ($audience === 'admin') {
                $actions[] = ['type' => 'suggest_route', 'label' => 'Admin Site Builder', 'url' => url('admin/site-builder/ai-design')];
            } elseif ($audience === 'customer') {
                $actions[] = ['type' => 'suggest_route', 'label' => 'Müşteri Site Builder', 'url' => url('client/site-builder#ai-yardimi')];
            } else {
                $actions[] = ['type' => 'suggest_route', 'label' => 'AI ile Tasarlamak İçin Paket Al', 'url' => url('urun-grubu/sitebuilder')];
                $actions[] = ['type' => 'suggest_route', 'label' => 'AI Olmadan Devam Et', 'url' => url('sitebuilder/create-demo')];
            }
        }
        if (str_contains($lower, 'mobil uygulama') || str_contains($lower, 'mobile builder') || str_contains($lower, 'uygulama oluştur')) {
            if ($audience === 'admin') {
                $actions[] = ['type' => 'suggest_route', 'label' => 'Admin Mobile Builder', 'url' => url('admin/mobile-builder/ai-app')];
            } elseif ($audience === 'customer') {
                $actions[] = ['type' => 'suggest_route', 'label' => 'Müşteri Mobile Builder', 'url' => url('client/mobile-builder#ai-yardimi')];
            } else {
                $actions[] = ['type' => 'suggest_route', 'label' => 'AI ile Tasarlamak İçin Paket Al', 'url' => url('urun-grubu/mobilebuilder')];
                $actions[] = ['type' => 'suggest_route', 'label' => 'AI Olmadan Devam Et', 'url' => url('mobilebuilder/create-demo')];
            }
        }

        $message = $actions
            ? 'İsteğinize göre güvenli işlem önerileri hazırlandı.'
            : 'İsteğiniz alındı. Bu konuda bilgi bankası ve yerel kurallara göre yardımcı olabilirim.';

        return ['ok' => true, 'message' => $message, 'actions' => $actions];
    }
}

if (!function_exists('ao_ahostbuilder_ai_plan')) {
    function ao_ahostbuilder_ai_plan(string $prompt, array $context = []): array
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            return ['ok' => false, 'message' => 'AhostBuilder komutu boş olamaz.'];
        }

        $lower = mb_strtolower($prompt, 'UTF-8');
        $blocked = ['sil tüm', 'hepsini sil', 'veritabanı', 'drop table', 'truncate', 'şifre', 'api key', 'shell', 'exec'];
        foreach ($blocked as $word) {
            if (str_contains($lower, $word)) {
                return ['ok' => false, 'message' => 'Bu komut güvenlik nedeniyle sadece manuel onayla yapılabilir.'];
            }
        }

        $type = 'text';
        if (str_contains($lower, 'hero') || str_contains($lower, 'başlık')) $type = 'hero';
        if (str_contains($lower, 'domain')) $type = 'domain';
        if (str_contains($lower, 'ürün') || str_contains($lower, 'paket') || str_contains($lower, 'hosting')) $type = 'product';
        if (str_contains($lower, 'buton')) $type = 'button';
        if (str_contains($lower, 'banner') || str_contains($lower, 'kampanya')) $type = 'campaign';

        $title = match ($type) {
            'hero' => 'Premium Dijital Hizmet Deneyimi',
            'domain' => 'Akıllı Domain Sorgulama',
            'product' => 'Öne Çıkan Paket',
            'button' => 'Yeni Aksiyon Butonu',
            'campaign' => 'Özel Kampanya',
            default => 'Yeni AhostBuilder Bloğu',
        };

        $text = match ($type) {
            'hero' => 'Domain, hosting, tasarım ve destek süreçlerini tek premium deneyimde yönetin.',
            'domain' => 'Alan adınızı yazın; uygunluk, fiyat ve önerileri hızlıca görün.',
            'product' => 'Performans, güvenlik ve destek odaklı esnek paket yapısı.',
            'button' => 'Net aksiyon, zarif görünüm ve yüksek dönüşüm için hazırlandı.',
            'campaign' => 'Sınırlı süreli fırsatları modern bir görsel blokla öne çıkarın.',
            default => 'Bu blok AhostBuilder AI tarafından güvenli taslak olarak hazırlandı.',
        };

        $button = match ($type) {
            'domain' => 'Domain Sorgula',
            'product' => 'Paketi İncele',
            'button' => 'Devam Et',
            'campaign' => 'Fırsatı Gör',
            default => 'Başla',
        };

        $proposal = [
            'type' => $type,
            'title' => $title,
            'text' => $text,
            'button' => $button,
            'props' => [
                'background' => str_contains($lower, 'koyu') ? 'linear-gradient(135deg,#0f172a,#1d4ed8)' : 'linear-gradient(135deg,#fff8ef,#f8fbff)',
                'textColor' => str_contains($lower, 'koyu') ? '#ffffff' : '#0f172a',
                'buttonBg' => str_contains($lower, 'mavi') ? '#2563eb' : '#ff675d',
                'buttonColor' => '#ffffff',
                'radius' => '24px',
                'padding' => '32px',
            ],
        ];

        if (function_exists('ao_ai_call_optional')) {
            $aiPrompt = "Türkçe AhostBuilder blok taslağı üret. Sadece JSON döndür. Alanlar: type,title,text,button,props. Güvenli tasarım önerisi olsun, silme veya sistem işlemi olmasın.\nKomut: ".$prompt;
            $ai = ao_ai_call_optional($aiPrompt);
            if (is_string($ai) && trim($ai) !== '') {
                $clean = trim(preg_replace('/```(?:json)?|```/i', '', $ai));
                $decoded = json_decode($clean, true);
                if (is_array($decoded)) {
                    $proposal = array_replace_recursive($proposal, array_intersect_key($decoded, array_flip(['type','title','text','button','props'])));
                }
            }
        }

        return [
            'ok' => true,
            'message' => 'AhostBuilder AI güvenli blok taslağı hazırladı. Uygula dediğinizde seçili bloğa yazılır.',
            'proposal' => $proposal,
            'context' => [
                'target' => preg_replace('/[^a-z0-9_-]/i', '', (string)($context['target'] ?? 'site')),
                'template' => preg_replace('/[^a-z0-9_-]/i', '', (string)($context['template'] ?? 'home')),
            ],
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/ahostbuilder/assistant/run') {
    require_admin();
    verify_csrf();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(ao_ahostbuilder_ai_plan((string)($_POST['prompt'] ?? ''), [
        'target' => $_POST['target'] ?? 'site',
        'template' => $_POST['template'] ?? 'home',
        'selected_type' => $_POST['selected_type'] ?? '',
    ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/ai-copilot/run') {
    require_admin();
    verify_csrf();
    $result = ao_ai_assistant_run((string)($_POST['prompt'] ?? ''), 'admin', true);
    $_SESSION['ao_ai_assistant_last'] = $result;
    flash(!empty($result['ok']) ? 'success' : 'error', (string)($result['message'] ?? 'Asistan yanıtı üretilemedi.'));
    redirect_to('admin/ai-copilot');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'admin/assistant/run-json') {
    require_admin();
    verify_csrf();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(ao_ai_assistant_run((string)($_POST['prompt'] ?? ''), 'admin', true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'client/assistant/run') {
    require_customer();
    verify_csrf();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(ao_ai_assistant_run((string)($_POST['prompt'] ?? ''), 'customer', true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === 'assistant/run') {
    verify_csrf();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(ao_ai_assistant_run((string)($_POST['prompt'] ?? ''), 'guest', true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
