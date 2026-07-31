<?php
// Ahost One v7.0.0 - Kaynak Sistem temel mantığı + Ahost One güçlendirilmiş mimari
// Credits: Ahost Bilişim / Lokman Demir
if (session_status() === PHP_SESSION_NONE) {
    // v26.2.2: Session cookie güvenlik sertleştirmesi. Sadece httponly/samesite/secure
    // bayrakları ayarlanıyor; lifetime/path/domain dokunulmadan mevcut ini ayarlarında kalıyor.
    $ao_https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
    try {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $ao_https]);
    } catch (Throwable $e) { error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    session_start();
}
if (!function_exists('env')) {
    function env($key, $default = null) {
        static $installation = null;
        if ($installation === null) {
            $installation = [];
            $installFile = dirname(__DIR__) . '/config/installation.php';
            if (is_file($installFile)) {
                $loaded = require $installFile;
                $installation = is_array($loaded) ? $loaded : [];
            }
        }
        if (is_array($installation) && array_key_exists($key, $installation)) {
            return $installation[$key];
        }
        $value = getenv((string)$key);
        if ($value === false && isset($_ENV[$key])) $value = $_ENV[$key];
        if ($value === false && isset($_SERVER[$key])) $value = $_SERVER[$key];
        if ($value === false) return $default;
        if (is_string($value)) {
            $lower = strtolower($value);
            if ($lower === 'true') return true;
            if ($lower === 'false') return false;
            if ($lower === 'null') return null;
            if ($lower === 'empty') return '';
        }
        return $value;
    }
}
if (!function_exists('ao_bootstrap_load_config')) {
    function ao_bootstrap_load_config(): array {
        $root = dirname(__DIR__);
        $legacy = $root . '/config/config.php';
        if (is_file($legacy)) {
            $loaded = require $legacy;
            return is_array($loaded) ? $loaded : [];
        }

        $app = [];
        $database = [];
        if (is_file($root . '/config/app.php')) {
            $loaded = require $root . '/config/app.php';
            $app = is_array($loaded) ? $loaded : [];
        }
        if (is_file($root . '/config/database.php')) {
            $loaded = require $root . '/config/database.php';
            $database = is_array($loaded) ? $loaded : [];
        }

        $defaultConnection = (string)($database['default'] ?? 'mysql');
        $db = $database['connections'][$defaultConnection]
            ?? $database['connections']['mysql']
            ?? $database['mysql']
            ?? $database;

        $baseUrl = $app['url'] ?? $app['base_url'] ?? '';
        $requestHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        if (is_string($baseUrl) && preg_match('#^https?://(?:localhost|127\.0\.0\.1)(?::\d+)?(?:/|$)#i', $baseUrl) && !preg_match('#^(?:localhost|127\.0\.0\.1)(?::\d+)?$#', $requestHost)) {
            $baseUrl = '';
        }

        return [
            'app_name' => $app['name'] ?? $app['app_name'] ?? 'Ahost One',
            'version' => $app['version'] ?? '25.0.0-rc25',
            'asset_version' => $app['asset_version'] ?? $app['version'] ?? '25.0.0-rc25',
            'base_url' => $baseUrl,
            'db' => [
                'host' => $db['host'] ?? $db['hostname'] ?? 'localhost',
                'port' => $db['port'] ?? null,
                'name' => $db['database'] ?? $db['name'] ?? '',
                'user' => $db['username'] ?? $db['user'] ?? '',
                'pass' => $db['password'] ?? $db['pass'] ?? '',
                'charset' => $db['charset'] ?? 'utf8mb4',
            ],
            'whm' => $app['whm'] ?? ['enabled' => false, 'hostname' => '', 'username' => '', 'api_token' => ''],
            'security' => $app['security'] ?? ['install_file_auto_renamed' => true],
        ];
    }
}
$config = ao_bootstrap_load_config();
function ahost_config($key = null, $default = null) {
    global $config;
    if ($key === null) return $config;
    $segments = explode('.', $key);
    $value = $config;
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) return $default;
        $value = $value[$segment];
    }
    return $value;
}
if (!function_exists('ao_request_path_no_base')) {
    /**
     * REQUEST_URI'den, sistemin kurulu olduğu alt klasör önekini
     * (AHO_BASE_PATH — örn. "/ahostone") çıkararak gerçek route yolunu
     * döner. Böylece "/admin", "/kurulum" gibi başlangıç kontrolleri
     * sistem hangi klasöre kurulursa kurulsun doğru çalışır.
     */
    function ao_request_path_no_base(): string {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');
        $path = '/' . ltrim($path, '/');
        $base = defined('AHO_BASE_PATH') ? AHO_BASE_PATH : '';
        if ($base !== '') {
            if ($path === $base) {
                $path = '/';
            } elseif (str_starts_with($path, $base . '/')) {
                $path = substr($path, strlen($base));
            }
        }
        return '/' . ltrim($path, '/');
    }
}
function db() {
    static $pdo;
    if ($pdo) return $pdo;
    $db = ahost_config('db');
    $port = isset($db['port']) && (string)$db['port'] !== '' ? ';port='.(int)$db['port'] : '';
    $dsn = "mysql:host={$db['host']}{$port};dbname={$db['name']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    return $pdo;
}
if (!function_exists('get_module_setting')) {
function get_module_setting($moduleSlug, $key, $default = null) {
    $moduleSlug = preg_replace('/[^a-z0-9\-_]/', '', strtolower((string)$moduleSlug));
    $key = preg_replace('/[^a-zA-Z0-9_\.\-]/', '', (string)$key);
    if ($moduleSlug === '' || $key === '') return $default;
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS module_settings (id INT AUTO_INCREMENT PRIMARY KEY, module_slug VARCHAR(120) NOT NULL, setting_key VARCHAR(120) NOT NULL, setting_value LONGTEXT NULL, is_secret TINYINT(1) DEFAULT 0, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_module_setting(module_slug,setting_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $q = db()->prepare('SELECT setting_value FROM module_settings WHERE module_slug=? AND setting_key=? LIMIT 1');
        $q->execute([$moduleSlug, $key]);
        $value = $q->fetchColumn();
        return $value === false ? $default : $value;
    } catch (Throwable $e) {
        error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
        return $default;
    }
}
}
function app_base_path() {
    $configured = rtrim((string)ahost_config('base_url',''), '/');
    if ($configured !== '') return $configured;

    $projectRoot = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__));
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptFile = str_replace('\\', '/', realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) ?: (string)($_SERVER['SCRIPT_FILENAME'] ?? ''));

    if ($scriptName !== '' && $scriptFile !== '' && str_starts_with(strtolower($scriptFile), strtolower(rtrim($projectRoot, '/')))) {
        $relativeScript = '/' . trim(substr($scriptFile, strlen(rtrim($projectRoot, '/'))), '/');
        if ($relativeScript !== '/' && str_ends_with(strtolower($scriptName), strtolower($relativeScript))) {
            $base = substr($scriptName, 0, -strlen($relativeScript));
            $base = '/' . trim($base, '/');
            return $base === '/' ? '' : $base;
        }
    }

    $docRoot = str_replace('\\', '/', realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? '')) ?: (string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($docRoot !== '' && str_starts_with(strtolower($projectRoot), strtolower(rtrim($docRoot, '/')))) {
        $base = substr($projectRoot, strlen(rtrim($docRoot, '/')));
        $base = '/' . trim(str_replace('\\', '/', $base), '/');
        return $base === '/' ? '' : $base;
    }

    $script = preg_replace('~/admin/index\.php$~i', '', $scriptName) ?: $scriptName;
    $script = preg_replace('~/index\.php$~i', '', $script) ?: '';
    $script = rtrim($script, '/');
    return ($script === '' || $script === '/') ? '' : $script;
}
function url($path='') { return app_base_path() . '/' . ltrim($path, '/'); }

if (!function_exists('asset')) {
    function asset($path='') {
        $path = trim((string)$path);
        if ($path === '') return url('public');
        if (preg_match('~^(https?:)?//|^data:~i', $path)) return $path;
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'public/')) return url($path);
        if (str_starts_with($path, 'assets/')) return url('public/'.$path);
        return url('public/assets/'.$path);
    }
}

function ao_asset_version() { return ahost_config('asset_version', ahost_config('version','24.3.1')); }
function assetv($path='') {
    $path = trim((string)$path);
    $version = ao_asset_version();
    if ($path !== '' && !preg_match('~^(https?:)?//|^data:~i', $path)) {
        $assetPath = ltrim($path, '/');
        if (str_starts_with($assetPath, 'public/')) {
            $relativePath = $assetPath;
        } elseif (str_starts_with($assetPath, 'assets/')) {
            $relativePath = 'public/'.$assetPath;
        } else {
            $relativePath = 'public/assets/'.$assetPath;
        }
        $fullPath = dirname(__DIR__) . '/' . str_replace('\\', '/', $relativePath);
        if (is_file($fullPath)) {
            $version = (string)filemtime($fullPath);
        }
    }
    return asset($path) . '?v=' . rawurlencode((string)$version);
}

if (!function_exists('ao_theme_sync_filesystem')) {
    function ao_theme_sync_filesystem() {
        try {
            $base = dirname(__DIR__) . '/themes';
            if (!is_dir($base)) return 0;
            db()->exec("CREATE TABLE IF NOT EXISTS themes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(80) NOT NULL,
                name VARCHAR(160) NOT NULL,
                area VARCHAR(40) DEFAULT 'site',
                description TEXT NULL,
                preview_image VARCHAR(255) NULL,
                preview_url VARCHAR(255) NULL,
                primary_color VARCHAR(20) DEFAULT '#2563eb',
                secondary_color VARCHAR(20) DEFAULT '#0f172a',
                font_family VARCHAR(120) DEFAULT 'Inter, Arial, sans-serif',
                custom_css LONGTEXT NULL,
                is_active TINYINT(1) DEFAULT 0,
                status VARCHAR(30) DEFAULT 'installed',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_theme_area (slug, area)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $files = glob($base.'/*/theme.json') ?: [];
            foreach (['site','admin','client','customer'] as $scope) {
                foreach (glob($base.'/'.$scope.'/*/theme.json') ?: [] as $f) $files[] = $f;
            }
            $synced = 0;
            foreach (array_unique($files) as $file) {
                $json = json_decode((string)@file_get_contents($file), true);
                if (!is_array($json)) continue;
                $dir = basename(dirname($file));
                $parent = basename(dirname(dirname($file)));
                $slug = preg_replace('/[^a-z0-9_-]+/i', '-', (string)($json['slug'] ?? $dir));
                $slug = strtolower(trim($slug, '-_'));
                if ($slug === '') continue;
                $name = trim((string)($json['name'] ?? ucwords(str_replace('-', ' ', $slug))));
                $areas = [];
                if (!empty($json['areas']) && is_array($json['areas'])) $areas = $json['areas'];
                elseif (!empty($json['area'])) $areas = [(string)$json['area']];
                elseif (in_array($parent, ['site','admin','client','customer'], true)) $areas = [$parent === 'customer' ? 'client' : $parent];
                else $areas = ['site'];
                foreach ($areas as $area) {
                    $area = $area === 'customer' ? 'client' : preg_replace('/[^a-z]+/', '', strtolower((string)$area));
                    if (!in_array($area, ['site','admin','client'], true)) continue;
                    $q = db()->prepare('SELECT COUNT(*) FROM themes WHERE area=? AND is_active=1');
                    $q->execute([$area]);
                    $makeActive = ((int)$q->fetchColumn() === 0 && in_array($slug, ['ahost-default','default'], true)) ? 1 : 0;
                    $preview = (string)($json['preview_image'] ?? '');
                    if ($preview !== '' && !preg_match('~^(https?:)?//~i', $preview)) {
                        $preview = 'themes/'.$slug.'/'.ltrim($preview, '/');
                    }
                    db()->prepare('INSERT INTO themes(slug,name,area,description,preview_image,primary_color,secondary_color,font_family,is_active,status)
                        VALUES(?,?,?,?,?,?,?,?,?,"installed")
                        ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),preview_image=VALUES(preview_image),primary_color=VALUES(primary_color),secondary_color=VALUES(secondary_color),font_family=VALUES(font_family),status="installed"')
                        ->execute([
                            $slug,
                            $name,
                            $area,
                            (string)($json['description'] ?? 'themes klasorunden okunan Ahost One tema paketi.'),
                            $preview,
                            (string)($json['primary_color'] ?? '#2563eb'),
                            (string)($json['secondary_color'] ?? '#0f172a'),
                            (string)($json['font_family'] ?? 'Inter, Arial, sans-serif'),
                            $makeActive
                        ]);
                    $synced++;
                }
            }
            return $synced;
        } catch (Throwable $e) {
            error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
            return 0;
        }
    }
}

if (!function_exists('ao_brand_logo_url')) {
    function ao_brand_logo_url($default='assets/img/ahost-logo.webp') {
        $fallbacks = ['assets/img/ahost-logo.webp','assets/img/ahost-logo.svg','assets/img/logo.webp','assets/img/logo.svg'];
        $raw = function_exists('admin_setting') ? trim((string)admin_setting('logo_url','')) : '';
        if ($raw === '') $raw = $default;
        if (preg_match('~^(https?:)?//|^data:~i', $raw)) return $raw;
        $raw = ltrim(str_replace('\\','/',$raw), '/');
        if (str_starts_with($raw, 'public/')) $raw = substr($raw, 7);
        $doc = dirname(__DIR__) . '/public/';
        if ($raw !== '' && file_exists($doc.$raw)) return asset($raw);
        foreach($fallbacks as $fb){ if(file_exists($doc.$fb)) return asset($fb); }
        return 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="180" height="44" viewBox="0 0 180 44"><rect width="180" height="44" rx="12" fill="#0f172a"/><text x="18" y="28" fill="#fff" font-family="Arial" font-size="18" font-weight="700">Ahost One</text></svg>');
    }
}

if (!function_exists('ao_theme_body_class')) {
    function ao_theme_body_class($area='site') {
        $slug = 'default';
        if (function_exists('ao_active_theme')) {
            $theme = ao_active_theme($area);
            $slug = (string)($theme['slug'] ?? $slug);
        }
        $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($slug));
        if ($slug === 'prism') $slug = 'ahost-prism';
        return 'theme-' . ($slug ?: 'default');
    }
}


function redirect_to($path='') { header('Location: ' . url($path)); exit; }
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function render_view($area, $path, $data = []) {
    extract($data);
    $pageTitle = $pageTitle ?? 'Ahost One';
    $base = __DIR__ . "/Views/{$area}";
    // v24.3.1: tek standart müşteri paneli görünüm klasörü app/Views/customer.
    // Eski rotalar client_view() çağırsa bile müşteri paneli artık customer view'larını kullanır.
    if ($area === 'client' && is_dir(__DIR__ . '/Views/customer')) {
        $base = __DIR__ . '/Views/customer';
    }
    $header = $base . '/partials/header.php';
    $view   = $base . '/' . $path . '.php';
    $footer = $base . '/partials/footer.php';

    // Ahost One Theme System: aktif tema dosyası varsa onu kullan, yoksa çekirdek view fallback kalsın.
    // Böylece /themes altındaki tema paketleri default temaya bağımlı olmadan site/admin/client alanını özelleştirebilir.
    if ($area !== 'auth' && function_exists('ao_theme_view_path')) {
        $themeArea = ($area === 'customer' || $area === 'client') ? 'client' : $area;
        $themeHeader = ao_theme_view_path($themeArea, 'partials/header');
        $themeView = ao_theme_view_path($themeArea, $path);
        $themeFooter = ao_theme_view_path($themeArea, 'partials/footer');
        if ($themeHeader) { $header = $themeHeader; }
        if ($themeView) { $view = $themeView; }
        if ($themeFooter) { $footer = $themeFooter; }
    }
    if ($area === 'auth') {
        $basicHeader = function() use ($pageTitle, $path) {
            $flash = function_exists('get_flash') ? get_flash() : null;
            $isAdmin = str_starts_with((string)$path, 'admin-');
            echo '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
            echo '<title>'.e($pageTitle ?? ($isAdmin ? 'Admin Girişi' : 'Giriş')).'</title>';
            foreach (['css/core/tokens.css','css/core/reset.css','css/core/typography.css','css/components/buttons.css','css/components/forms.css','css/components/cards.css'] as $fallbackCss) {
                echo '<link rel="stylesheet" href="'.e(assetv($fallbackCss)).'">';
            }
            echo '<link rel="stylesheet" href="'.e(assetv('css/components/auth.css')).'">';
            echo '<link rel="stylesheet" href="'.e(assetv('css/auth/login.css')).'">';
            echo '</head><body data-app="auth" class="auth-body ao-full-ui-reset"><main class="auth-shell"><section class="auth-hero">';
            echo '<span class="badge">Ahost One</span><h1>'.($isAdmin ? 'Yönetim merkezine hoş geldiniz.' : 'Müşteri paneline hoş geldiniz.').'</h1>';
            echo '<p>Güvenli oturum ile devam edin.</p></section><section class="auth-card"><div class="auth-brand"><b>Ahost One</b><span>'.($isAdmin ? 'Admin Paneli' : 'Müşteri Paneli').'</span></div>';
            if ($flash) echo '<div class="auth-alert '.e($flash['type'] ?? 'info').'">'.e($flash['message'] ?? '').'</div>';
        };
        $basicFooter = function() {
            echo '</section></main></body></html>';
        };
        $builtinView = function() use ($path) {
            if ($path !== 'admin-login') return false;
            echo '<h1>Admin Girişi</h1><p>Ahost One yönetim paneline güvenli oturumla giriş yapın.</p>';
            echo '<form method="post" action="'.e(url('admin/login')).'">';
            echo function_exists('csrf_field') ? csrf_field() : '';
            echo '<label>E-posta</label><input type="email" name="email" required placeholder="admin@site.com">';
            echo '<label>Şifre</label><input type="password" name="password" required placeholder="********">';
            echo '<button type="submit">Admin Paneline Gir</button></form>';
            echo '<div class="auth-links"><a href="'.e(url('admin/forgot-password')).'">Şifremi Unuttum</a><a href="'.e(url('')).'">Siteye Dön</a><a href="'.e(url('client/login')).'">Müşteri Paneline Git</a></div>';
            return true;
        };
        if (is_file($view)) {
            if (is_file($header)) { require $header; } else { $basicHeader(); }
            require $view;
            if (is_file($footer)) { require $footer; } else { $basicFooter(); }
            return;
        }
        if ($path === 'admin-login') {
            if (is_file($header)) { require $header; } else { $basicHeader(); }
            $builtinView();
            if (is_file($footer)) { require $footer; } else { $basicFooter(); }
            return;
        }
    }
    if (!is_file($header) || !is_file($view) || !is_file($footer)) {
        http_response_code(500);
        echo '<h1>Görünüm dosyası bulunamadı</h1>';
        echo '<p>Eksik görünüm: ' . e($area . '/' . $path) . '</p>';
        return;
    }
    $normalizedArea = $area === 'client' ? 'customer' : $area;
    if ($normalizedArea === 'customer') {
        $probe = @file_get_contents($view, false, null, 0, 8192);
        if (is_string($probe) && preg_match('~<!doctype\s+html|<html\b|<body\b~i', $probe)) {
            require $view;
            return;
        }
    }
    require $header;
    require $view;
    require $footer;
}
function view($path, $data = []) { render_view('admin', $path, $data); }
function site_view($path, $data = []) { render_view('site', $path, $data); }
function customer_view($path, $data = []) { render_view('customer', $path, $data); }
function client_view($path, $data = []) { render_view('client', $path, $data); }
function auth_view($path, $data = []) { render_view('auth', $path, $data); }
function table_count($table) { try { return (int)db()->query("SELECT COUNT(*) FROM `$table`")->fetchColumn(); } catch (Throwable $e) { return 0; } }

function admin_pref($key, $default=null) {
    try {
        $adminId = (int)($_SESSION['admin_id'] ?? 0);
        if ($adminId <= 0) return $default;
        db()->exec("CREATE TABLE IF NOT EXISTS admin_preferences (id INT AUTO_INCREMENT PRIMARY KEY, admin_id INT NOT NULL, pref_key VARCHAR(120) NOT NULL, pref_value TEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_admin_pref(admin_id,pref_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $s=db()->prepare('SELECT pref_value FROM admin_preferences WHERE admin_id=? AND pref_key=? LIMIT 1'); $s->execute([$adminId,$key]); $v=$s->fetchColumn();
        return $v===false ? $default : $v;
    } catch(Throwable $e) { return $default; }
}
function save_admin_pref($key, $value) {
    try {
        $adminId=(int)($_SESSION['admin_id'] ?? 0); if($adminId<=0) return false;
        db()->exec("CREATE TABLE IF NOT EXISTS admin_preferences (id INT AUTO_INCREMENT PRIMARY KEY, admin_id INT NOT NULL, pref_key VARCHAR(120) NOT NULL, pref_value TEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_admin_pref(admin_id,pref_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $q=db()->prepare('INSERT INTO admin_preferences(admin_id,pref_key,pref_value) VALUES(?,?,?) ON DUPLICATE KEY UPDATE pref_value=VALUES(pref_value)'); return $q->execute([$adminId,$key,$value]);
    } catch(Throwable $e) { return false; }
}
function ao_tcmb_usd_try_rate() {
    $today = date('Y-m-d');
    $cachedDate = (string)admin_setting('tcmb_usd_try_cached_date','');
    $cachedRate = (float)admin_setting('tcmb_usd_try_cached_rate','0');
    if ($cachedDate === $today && $cachedRate > 0) return $cachedRate;
    $rate = 0.0;
    try {
        $ctx = stream_context_create(['http'=>['timeout'=>2], 'https'=>['timeout'=>2]]);
        $body = @file_get_contents('https://www.tcmb.gov.tr/kurlar/today.xml', false, $ctx);
        $xml = $body ? @simplexml_load_string($body) : null;
        if ($xml) {
            foreach ($xml->Currency as $currency) {
                if ((string)$currency['CurrencyCode'] === 'USD') {
                    $raw = str_replace(',', '.', (string)$currency->ForexSelling);
                    $rate = (float)$raw;
                    break;
                }
            }
        }
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    if ($rate > 0) {
        save_setting('tcmb_usd_try_cached_date', $today);
        save_setting('tcmb_usd_try_cached_rate', number_format($rate, 6, '.', ''));
        save_setting('usd_try_rate', number_format($rate, 6, '.', ''));
        return $rate;
    }
    if ($cachedRate > 0) return $cachedRate;
    return (float)admin_setting('usd_try_rate','45.00');
}
function ao_currency_rate($from='USD',$to='TRY') {
    $from=strtoupper($from); $to=strtoupper($to); if($from===$to) return '1.00';
    $key='currency_rate_'.$from.'_'.$to;
    $raw=admin_setting($key, null);
    if(($raw===null || $raw==='') && $from==='USD' && $to==='TRY') $raw=ao_tcmb_usd_try_rate();
    if($raw===null || $raw==='') $raw=admin_setting('usd_try_rate','45.00');
    $margin=(float)admin_setting(strtolower($from.'_'.$to).'_margin_percent', admin_setting('currency_margin_percent','5.00'));
    $rate=(float)$raw;
    if($margin!==0.0) $rate = $rate + ($rate*$margin/100);
    return number_format($rate, 2, '.', '');
}
function ao_current_currency(){
    // Read cookie first, fallback to admin default
    $c = $_COOKIE['ao_currency'] ?? null;
    if($c){ $c = strtoupper(preg_replace('/[^A-Z]/','',$c)); if($c) return $c; }
    return strtoupper(admin_setting('default_currency','TRY')) ?: 'TRY';
}

// v26.0.0 - Site genelinde tutarlı para birimi: tüm sayfalar aynı kur/sembol tablosunu kullanır,
// böylece kullanıcı para birimini değiştirip başka sayfaya geçtiğinde seçim korunur.
function ao_currency_options(){
    static $cache = null;
    if($cache !== null) return $cache;
    $symbols = ['TRY'=>'₺','TL'=>'₺','USD'=>'$','EUR'=>'€','GBP'=>'£'];
    $options = ['TRY'=>['code'=>'TRY','label'=>'TRY','symbol'=>'₺','rate'=>1.0]];
    try {
        $rows = db()->query("SELECT currency_code, final_rate FROM currency_rates WHERE final_rate > 0 ORDER BY FIELD(currency_code,'USD','EUR','GBP'), currency_code")->fetchAll();
        foreach($rows as $r){
            $code = strtoupper(trim((string)($r['currency_code'] ?? '')));
            if($code === '' || $code === 'TRY' || $code === 'TL') continue;
            $options[$code] = ['code'=>$code,'label'=>$code,'symbol'=>$symbols[$code] ?? $code,'rate'=>(float)$r['final_rate']];
        }
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    if(!isset($options['USD'])){
        $options['USD'] = ['code'=>'USD','label'=>'USD','symbol'=>'$','rate'=>(float)ao_currency_rate('USD','TRY')];
    }
    return $cache = $options;
}

// TRY bazında saklanan bir tutarı, kullanıcının o an seçili olan (cookie/varsayılan) para birimine
// çevirip biçimlendirir. data-price-base her zaman TRY tutarını taşır; JS bunu okuyup anlık
// para birimi değişiminde sayfa yenilemeden güncelleyebilir, sayfa geçişlerinde ise sunucu tarafı
// bu fonksiyon zaten doğru para birimiyle basar (cookie kalıcı olduğu için tutarlılık korunur).
function ao_format_price_try($tryAmount, $cycleLabel = null, $offerText = 'Teklif Al'){
    $try = (float)$tryAmount;
    if($try <= 0) {
        return '<strong>'.htmlspecialchars($offerText, ENT_QUOTES).'</strong>';
    }
    $options = ao_currency_options();
    $current = ao_current_currency();
    if(!isset($options[$current])) $current = 'TRY';
    $rate = (float)($options[$current]['rate'] ?? 1.0);
    $symbol = $options[$current]['symbol'] ?? $current;
    if($current === 'TRY'){
        $shown = $try;
        $display = number_format($shown, 2, ',', '.').' '.$symbol;
    } else {
        $shown = $rate > 0 ? ($try / $rate) : $try;
        $display = $symbol.number_format($shown, 2, '.', ',');
    }
    $cycleHtml = $cycleLabel ? ' <span>/ '.htmlspecialchars($cycleLabel, ENT_QUOTES).'</span>' : '';
    return '<strong class="ao-price" data-price-base="'.htmlspecialchars(number_format($try, 2, '.', ''), ENT_QUOTES).'">'
        .htmlspecialchars($display, ENT_QUOTES).'</strong>'.$cycleHtml;
}

function ao_price_html($amount, $currency='TRY'){
    // Render a span with data-base-price (stored as TRY) and formatted visible price according to current currency/cookie
    $base = (float)$amount;
    // assume input amount is in TRY; if currency provided and not TRY, convert to TRY using rate
    if(strtoupper($currency)!=='TRY'){
        try{ $rate = (float)ao_currency_rate(strtoupper($currency),'TRY'); if($rate>0) $base = $base * $rate; }catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    }
    $current = ao_current_currency();
    $display = '';
    if($current === 'USD'){
        $rate = (float)ao_currency_rate('USD','TRY') ?: 1.0; $v = $base / $rate; $display = number_format($v,2,'.',',').' $';
    } else { $display = number_format($base,2,'.',',').' ₺'; }
    return '<span class="ao-price" data-base-price="'.htmlspecialchars((string)$base,ENT_QUOTES).'">'.htmlspecialchars($display,ENT_QUOTES).'</span>';
}
function ao_days_until($date) {
    if(!$date || $date==='0000-00-00') return 9999;
    try { $today=new DateTime(date('Y-m-d')); $d=new DateTime($date); return (int)$today->diff($d)->format('%r%a'); } catch(Throwable $e) { return 9999; }
}
function ao_tc_algorithm_valid($tc) {
    $tc=preg_replace('/\D/','',(string)$tc);
    if(strlen($tc)!==11 || $tc[0]==='0' || preg_match('/^(\d)\1{10}$/',$tc)) return false;
    $d=array_map('intval',str_split($tc));
    $odd=$d[0]+$d[2]+$d[4]+$d[6]+$d[8]; $even=$d[1]+$d[3]+$d[5]+$d[7];
    return ((($odd*7)-$even)%10)===$d[9] && (array_sum(array_slice($d,0,10))%10)===$d[10];
}
function ao_identity_verify($first,$last,$birthDate,$tc) {
    if(!ao_tc_algorithm_valid($tc)) return ['ok'=>false,'message'=>'TC Kimlik No algoritma kontrolünden geçmedi.'];
    $year=(int)substr((string)$birthDate,0,4); if($year<1900 || $year>(int)date('Y')) return ['ok'=>false,'message'=>'Doğum tarihi geçersiz.'];
    // Resmi doğrulama adaptörü aktifse burada MERNIS/NVI servis adaptörü çağrılır. Fresh install offline olduğu için algoritma + zorunlu alan kontrolü kullanılır.
    return ['ok'=>true,'message'=>'Kimlik bilgileri doğrulama altyapısından geçti.'];
}
function ao_schema_ensure_v186() {
    try { db()->exec("ALTER TABLE customers ADD COLUMN tc_identity_no VARCHAR(11) NULL AFTER phone"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE customers ADD COLUMN birth_date DATE NULL AFTER tc_identity_no"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE customers ADD COLUMN identity_verified TINYINT(1) DEFAULT 0 AFTER birth_date"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE customers ADD COLUMN identity_verified_at DATETIME NULL AFTER identity_verified"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE services ADD COLUMN auto_renew TINYINT(1) DEFAULT 1 AFTER next_due_date"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE services ADD COLUMN suspend_at DATETIME NULL AFTER auto_renew"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("ALTER TABLE services ADD COLUMN terminate_at DATETIME NULL AFTER suspend_at"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS customer_payment_methods (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, provider VARCHAR(80) NOT NULL, provider_customer_token VARCHAR(190) NULL, card_token VARCHAR(190) NOT NULL, card_brand VARCHAR(40) NULL, masked_card VARCHAR(40) NULL, is_default TINYINT(1) DEFAULT 0, status VARCHAR(30) DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY customer_id(customer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS renewal_automation_logs (id INT AUTO_INCREMENT PRIMARY KEY, service_id INT NULL, domain_id INT NULL, customer_id INT NULL, action VARCHAR(80) NOT NULL, channel VARCHAR(40) NULL, status VARCHAR(40) DEFAULT 'pending', message TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY customer_id(customer_id), KEY action(action)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS hosting_automation_settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(120) UNIQUE, setting_value TEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    foreach(['hosting_suspend_day'=>'1','hosting_terminate_day'=>'16','hosting_reminder_days'=>'1,3,7,10,15','notify_mail'=>'1','notify_sms'=>'1','notify_whatsapp'=>'1','auto_renew_credit_first'=>'1','stored_card_mode'=>'token_only'] as $k=>$v){ try{db()->prepare('INSERT IGNORE INTO hosting_automation_settings(setting_key,setting_value) VALUES(?,?)')->execute([$k,$v]);}catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); } }
}


// v22.2.0 global menu helpers: admin/site/mobile menus are shared, not tied to a single admin session.
if (!function_exists('ao_default_menu_items_v222')) {
function ao_default_menu_items_v222($type='site') {
    $menus = [
        'admin' => [
            ['label'=>'Müşteriler','url'=>'admin/customers','children'=>[]],
            ['label'=>'Paketler / Ürünler','url'=>'admin/product-center','children'=>[]],
            ['label'=>'Siparişler','url'=>'admin/orders','children'=>[]],
            ['label'=>'Domain','url'=>'admin/domain-center','children'=>[]],
            ['label'=>'Ayarlar','url'=>'admin/settings','children'=>[]],
        ],
        'site' => [
            ['label'=>'Domain Center','url'=>'domain','children'=>[['label'=>'Domain Sorgula','url'=>'domain'],['label'=>'Transfer','url'=>'domain#transfer'],['label'=>'WHOIS','url'=>'domain#whois']]],
            ['label'=>'Hosting Center','url'=>'hosting','children'=>[['label'=>'Web Hosting','url'=>'hosting'],['label'=>'VPS','url'=>'vps']]],
            ['label'=>'Site Builder','url'=>'sitebuilder','children'=>[]],
            ['label'=>'Mobile Builder','url'=>'mobilebuilder','children'=>[]],
            ['label'=>'Web Tasarım','url'=>'web-tasarim','children'=>[]],
            ['label'=>'Mobil Uygulama','url'=>'mobil-uygulama','children'=>[]],
            ['label'=>'Dijital Hizmetler','url'=>'dijital-hizmetler','children'=>[]],
            ['label'=>'Site Araçları','url'=>'site-araclari','children'=>[]],
            ['label'=>'Marketplace','url'=>'marketplace','children'=>[]],
            ['label'=>'Referanslar','url'=>'referanslar','children'=>[]],
        ],
        'footer' => [
            ['label'=>'Hosting','url'=>'hosting','children'=>[['label'=>'Paylaşımlı Hosting','url'=>'hosting'],['label'=>'VPS Sunucu','url'=>'vps'],['label'=>'Domain Sorgula','url'=>'domain']]],
            ['label'=>'Yardım','url'=>'bilgi-bankasi','children'=>[['label'=>'Bilgi Bankası','url'=>'bilgi-bankasi'],['label'=>'Destek Talebi','url'=>'client/support'],['label'=>'İletişim','url'=>'teklif']]],
            ['label'=>'Kurumsal','url'=>'hakkimizda','children'=>[['label'=>'Hakkımızda','url'=>'hakkimizda'],['label'=>'Gizlilik Politikası','url'=>'gizlilik-politikasi'],['label'=>'KVKK','url'=>'kvkk']]],
        ],
        'topbar' => [
            ['label'=>'Duyurular','url'=>'duyurular','children'=>[]],
            ['label'=>'Blog','url'=>'blog','children'=>[]],
            ['label'=>'İletişim','url'=>'teklif','children'=>[]],
        ],
        'corporate' => [
            ['label'=>'Hakkımızda','url'=>'hakkimizda','children'=>[]],
            ['label'=>'İletişim','url'=>'iletisim','children'=>[]],
            ['label'=>'Vizyonumuz','url'=>'vizyonumuz','children'=>[]],
            ['label'=>'Misyonumuz','url'=>'misyonumuz','children'=>[]],
        ],
        'mobile' => [
            ['label'=>'Ana Sayfa','url'=>'','icon'=>'⌂','children'=>[]],
            ['label'=>'Domain','url'=>'domain','icon'=>'🌐','children'=>[]],
            ['label'=>'Kategori','url'=>'#mobile-categories','icon'=>'▦','children'=>[
                ['label'=>'Hosting Paketleri','url'=>'hosting','children'=>[]],
                ['label'=>'Domain İşlemleri','url'=>'domain','children'=>[]],
                ['label'=>'Web Tasarım','url'=>'web-tasarim','children'=>[]],
                ['label'=>'Mobil Uygulama','url'=>'mobil-uygulama','children'=>[]],
                ['label'=>'Site Builder','url'=>'sitebuilder','children'=>[]],
                ['label'=>'Mobile Builder','url'=>'mobilebuilder','children'=>[]],
                ['label'=>'Site Araçları','url'=>'site-araclari','children'=>[]],
                ['label'=>'Marketplace','url'=>'marketplace','children'=>[]],
            ]],
            ['label'=>'Sepet','url'=>'cart','icon'=>'🛒','children'=>[]],
            ['label'=>'Panel','url'=>'client/login','icon'=>'👤','children'=>[]],
        ],
    ];
    return $menus[$type] ?? $menus['site'];
}
function ao_normalize_menu_items_v222($items, $level=0) {
    $clean=[];
    if (!is_array($items)) return $clean;
    foreach($items as $it){
        $label=trim((string)($it['label']??''));
        $url=trim((string)($it['url']??''));
        if($label==='') continue;
        $labelMap = [
            'SiteBuilder' => 'Site Builder',
            'MobileBuilder' => 'Mobile Builder',
            'SiteBuilder Pro' => 'Site Builder Pro',
            'MobileBuilder Pro' => 'Mobile Builder Pro',
            'SiteBuilder / MobileBuilder' => 'Site Builder / Mobile Builder',
        ];
        if (isset($labelMap[$label])) $label = $labelMap[$label];
        if ($url === '#') {
            $routeMap = [
                'Tasarım' => 'tasarim',
                'Web Tasarım' => 'web-tasarim',
                'Site Builder' => 'sitebuilder',
                'Mobile Builder' => 'mobilebuilder',
                'Dijital Hizmetler' => 'dijital-hizmetler',
            ];
            if (isset($routeMap[$label])) $url = $routeMap[$label];
        }
        $row=['label'=>$label,'url'=>$url,'children'=>[]];
        foreach(['icon','target','visibility','language','badge','css_class','rel'] as $metaKey){
            if(isset($it[$metaKey])) $row[$metaKey]=trim((string)$it[$metaKey]);
        }
        if($level < 3 && !empty($it['children']) && is_array($it['children'])) $row['children']=ao_normalize_menu_items_v222($it['children'],$level+1);
        $clean[]=$row;
    }
    return $clean;
}
function ao_menu_ensure_table_v222(){
    try { db()->exec("CREATE TABLE IF NOT EXISTS ao_menus (id INT AUTO_INCREMENT PRIMARY KEY, menu_type VARCHAR(30) NOT NULL UNIQUE, items_json MEDIUMTEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_get_menu_v222($type='site'){
    $type = ao_menu_type_allowed_v222($type) ? $type : 'site';
    ao_menu_ensure_table_v222();
    try {
        $q=db()->prepare('SELECT items_json FROM ao_menus WHERE menu_type=? LIMIT 1');
        $q->execute([$type]);
        $json=$q->fetchColumn();
        if($json){
            $arr=json_decode($json,true);
            if(is_array($arr)) {
                $items = ao_normalize_menu_items_v222($arr);
                return function_exists('ao_module_filter_menu_items') ? ao_module_filter_menu_items($items) : $items;
            }
        }
    } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $items = ao_default_menu_items_v222($type);
    return function_exists('ao_module_filter_menu_items') ? ao_module_filter_menu_items($items) : $items;
}
function ao_save_menu_v222($type, $items){
    $type = ao_menu_type_allowed_v222($type) ? $type : 'site';
    $items = ao_normalize_menu_items_v222($items);
    ao_menu_ensure_table_v222();
    $json=json_encode($items, JSON_UNESCAPED_UNICODE);
    try { $q=db()->prepare('INSERT INTO ao_menus(menu_type,items_json) VALUES(?,?) ON DUPLICATE KEY UPDATE items_json=VALUES(items_json), updated_at=NOW()'); return $q->execute([$type,$json]); } catch(Throwable $e) { return false; }
}
function ao_menu_type_allowed_v222($type){ return in_array($type,['admin','site','mobile','footer','topbar','corporate'],true); }
function ao_render_menu_links_v222($items, $class='') {
    $html='';
    foreach((array)$items as $item){
        $label=trim((string)($item['label']??'')); if($label==='') continue;
        $href=function_exists('ao_menu_url_v222') ? ao_menu_url_v222($item['url']??'') : url($item['url']??'');
        $target=trim((string)($item['target']??''));
        $attrs=$target==='_blank' ? ' target="_blank" rel="noopener"' : '';
        $children=$item['children']??[];
        if($children){
            $html.='<span class="'.e($class ?: 'ao-menu-parent').'"><a href="'.e($href).'"'.$attrs.'>'.e($label).'</a><span class="ao-submenu">'.ao_render_menu_links_v222($children,$class).'</span></span>';
        } else {
            $html.='<a href="'.e($href).'"'.$attrs.'>'.e($label).'</a>';
        }
    }
    return $html;
}
function ao_menu_url_v222($u){
    $u=trim((string)$u);
    if($u==='' || $u==='/') return url('');
    if(preg_match('~^(https?:)?//|^mailto:|^tel:|^#~i',$u)) return $u;
    return url($u);
}
}



// v24.1.2 Security MFA helpers: admin/customer Mail OTP, SMS OTP and Google Authenticator/TOTP.
function ao_mfa_ensure_schema() {
    try { db()->exec("CREATE TABLE IF NOT EXISTS auth_otp_tokens (id INT AUTO_INCREMENT PRIMARY KEY, user_type VARCHAR(30) NOT NULL, user_id INT NOT NULL, method VARCHAR(30) NOT NULL, code_hash VARCHAR(255) NOT NULL, destination VARCHAR(190) NULL, expires_at DATETIME NOT NULL, attempts INT DEFAULT 0, used_at DATETIME NULL, ip_address VARCHAR(80) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY user_lookup(user_type,user_id), KEY method(method), KEY expires_at(expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS auth_mfa_profiles (id INT AUTO_INCREMENT PRIMARY KEY, user_type VARCHAR(30) NOT NULL, user_id INT NOT NULL, enabled TINYINT(1) DEFAULT 0, preferred_method VARCHAR(30) DEFAULT 'mail', totp_secret VARCHAR(80) NULL, recovery_codes TEXT NULL, verified_at DATETIME NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_mfa_user(user_type,user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("CREATE TABLE IF NOT EXISTS auth_login_events (id INT AUTO_INCREMENT PRIMARY KEY, user_type VARCHAR(30) NOT NULL, user_id INT NULL, email VARCHAR(190) NULL, event_type VARCHAR(80) NOT NULL, method VARCHAR(30) NULL, status VARCHAR(40) DEFAULT 'info', ip_address VARCHAR(80) NULL, user_agent VARCHAR(255) NULL, message TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY user_lookup(user_type,user_id), KEY event_type(event_type), KEY status(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    try { db()->exec("INSERT IGNORE INTO settings(setting_key,setting_value) VALUES
        ('admin_mfa_policy','optional'),('customer_mfa_policy','optional'),
        ('mfa_mail_enabled','1'),('mfa_totp_enabled','1'),('mfa_sms_enabled','0'),
        ('mfa_default_method','mail'),('mfa_otp_ttl_minutes','5'),('mfa_max_attempts','5'),
        ('mfa_sms_sender','AhostOne')"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_mfa_policy($userType) { return (string)admin_setting($userType === 'admin' ? 'admin_mfa_policy' : 'customer_mfa_policy', 'optional'); }
function ao_mfa_methods_enabled() {
    $out=[];
    if ((string)admin_setting('mfa_mail_enabled','1') === '1') $out[]='mail';
    if ((string)admin_setting('mfa_totp_enabled','1') === '1') $out[]='totp';
    if ((string)admin_setting('mfa_sms_enabled','0') === '1') $out[]='sms';
    return $out ?: ['mail'];
}
function ao_mfa_default_method($userType, $userId) {
    ao_mfa_ensure_schema();
    try { $q=db()->prepare('SELECT preferred_method FROM auth_mfa_profiles WHERE user_type=? AND user_id=? LIMIT 1'); $q->execute([$userType,$userId]); $m=$q->fetchColumn(); if($m && in_array($m, ao_mfa_methods_enabled(), true)) return $m; } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    $m=(string)admin_setting('mfa_default_method','mail'); return in_array($m, ao_mfa_methods_enabled(), true) ? $m : ao_mfa_methods_enabled()[0];
}
function ao_mfa_is_required_for_user($userType, $userId) {
    $policy=ao_mfa_policy($userType);
    if ($policy === 'off') return false;
    if ($policy === 'required') return true;
    ao_mfa_ensure_schema();
    try { $q=db()->prepare('SELECT enabled FROM auth_mfa_profiles WHERE user_type=? AND user_id=? LIMIT 1'); $q->execute([$userType,$userId]); return (string)$q->fetchColumn()==='1'; } catch(Throwable $e) { return false; }
}
function ao_mfa_log($userType,$userId,$email,$event,$method,$status,$message='') {
    ao_mfa_ensure_schema();
    try { db()->prepare('INSERT INTO auth_login_events(user_type,user_id,email,event_type,method,status,ip_address,user_agent,message) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$userType,$userId,$email,$event,$method,$status,$_SERVER['REMOTE_ADDR']??'', substr($_SERVER['HTTP_USER_AGENT']??'',0,255), $message]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
function ao_mfa_user_email($userType, $user) { return (string)($user['email'] ?? ''); }
function ao_mfa_user_phone($userType, $user) { return (string)($user['phone'] ?? $user['mobile'] ?? ''); }
function ao_mfa_send_mail($email, $code) {
    $subject='Ahost One Giriş Doğrulama Kodu';
    $body="Ahost One giriş doğrulama kodunuz: {$code}\n\nBu kod kısa süre içinde geçerliliğini kaybeder.";
    try { @mail($email, $subject, $body); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return true;
}
function ao_mfa_send_sms($phone, $code) {
    if (function_exists('ao_iletimerkezi_send')) {
        $result=ao_iletimerkezi_send($phone,'Ahost One doğrulama kodunuz: '.$code,'mfa_otp');
        return !empty($result['ok']);
    }
    // OTP değerini hiçbir zaman loglara yazma.
    try { db()->prepare('INSERT INTO module_update_logs(module_key,action,status,message) VALUES(?,?,?,?)')->execute(['security-mfa','sms_otp','error','SMS sağlayıcı adaptörü aktif değil.']); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return false;
}
function ao_mfa_start_challenge($userType, array $user, $redirectAfter='') {
    ao_mfa_ensure_schema();
    $id=(int)($user['id'] ?? 0); $email=ao_mfa_user_email($userType,$user);
    if ($id<=0 || !ao_mfa_is_required_for_user($userType,$id)) {
        if($userType==='admin') $_SESSION['admin_id']=$id; else $_SESSION['customer_id']=$id;
        if(function_exists('ao_session_mark_authenticated')) ao_session_mark_authenticated($userType);
        ao_mfa_log($userType,$id,$email,'login','password','success','MFA gerekmeden giriş tamamlandı.');
        redirect_to($redirectAfter ?: ($userType==='admin'?'admin':'client'));
    }
    $method=ao_mfa_default_method($userType,$id);
    $_SESSION['mfa_pending']=['user_type'=>$userType,'user_id'=>$id,'email'=>$email,'phone'=>ao_mfa_user_phone($userType,$user),'method'=>$method,'redirect'=>$redirectAfter ?: ($userType==='admin'?'admin':'client'), 'created_at'=>time()];
    if ($method==='mail' || $method==='sms') ao_mfa_generate_otp($userType,$id,$method,$method==='mail'?$email:ao_mfa_user_phone($userType,$user));
    ao_mfa_log($userType,$id,$email,'mfa_required',$method,'pending','Şifre doğru, MFA bekleniyor.');
    redirect_to('auth/mfa');
}
function ao_mfa_generate_otp($userType,$userId,$method,$destination='') {
    ao_mfa_ensure_schema();
    $code=(string)random_int(100000,999999);
    $ttl=max(1,(int)admin_setting('mfa_otp_ttl_minutes','5'));
    try { db()->prepare('INSERT INTO auth_otp_tokens(user_type,user_id,method,code_hash,destination,expires_at,ip_address) VALUES(?,?,?,?,?,DATE_ADD(NOW(), INTERVAL ? MINUTE),?)')->execute([$userType,$userId,$method,password_hash($code,PASSWORD_DEFAULT),$destination,$ttl,$_SERVER['REMOTE_ADDR']??'']); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    if($method==='mail') ao_mfa_send_mail($destination,$code);
    if($method==='sms') ao_mfa_send_sms($destination,$code);
    return $code;
}
function ao_base32_decode_mfa($b32) {
    $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $b32=strtoupper(preg_replace('/[^A-Z2-7]/i','',$b32)); $bits=''; $out='';
    foreach(str_split($b32) as $ch){ $v=strpos($alphabet,$ch); if($v===false) continue; $bits.=str_pad(decbin($v),5,'0',STR_PAD_LEFT); }
    foreach(str_split($bits,8) as $byte){ if(strlen($byte)===8) $out.=chr(bindec($byte)); }
    return $out;
}
function ao_totp_secret() { $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $s=''; for($i=0;$i<32;$i++) $s.=$alphabet[random_int(0,31)]; return $s; }
function ao_totp_code($secret,$timeSlice=null) {
    if($timeSlice===null) $timeSlice=floor(time()/30);
    $key=ao_base32_decode_mfa($secret); $time=pack('N*',0).pack('N*',$timeSlice);
    $hash=hash_hmac('sha1',$time,$key,true); $offset=ord(substr($hash,-1)) & 0x0F;
    $truncated=((ord($hash[$offset]) & 0x7F) << 24) | ((ord($hash[$offset+1]) & 0xFF) << 16) | ((ord($hash[$offset+2]) & 0xFF) << 8) | (ord($hash[$offset+3]) & 0xFF);
    return str_pad((string)($truncated % 1000000),6,'0',STR_PAD_LEFT);
}
function ao_totp_verify($secret,$code) { $code=preg_replace('/\D/','',(string)$code); if(strlen($code)!==6) return false; $slice=floor(time()/30); for($i=-1;$i<=1;$i++){ if(hash_equals(ao_totp_code($secret,$slice+$i),$code)) return true; } return false; }
function ao_mfa_get_totp_secret($userType,$userId,$create=false) {
    ao_mfa_ensure_schema();
    try { $q=db()->prepare('SELECT totp_secret FROM auth_mfa_profiles WHERE user_type=? AND user_id=? LIMIT 1'); $q->execute([$userType,$userId]); $secret=$q->fetchColumn(); if($secret) return $secret; } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    if(!$create) return '';
    $secret=ao_totp_secret();
    try { db()->prepare('INSERT INTO auth_mfa_profiles(user_type,user_id,enabled,preferred_method,totp_secret) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE totp_secret=VALUES(totp_secret), preferred_method=VALUES(preferred_method)')->execute([$userType,$userId,1,'totp',$secret]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    return $secret;
}
function ao_mfa_verify_pending($code) {
    ao_mfa_ensure_schema();
    $p=$_SESSION['mfa_pending'] ?? null; if(!$p) return ['ok'=>false,'message'=>'Bekleyen doğrulama bulunamadı.'];
    $method=$p['method']; $userType=$p['user_type']; $userId=(int)$p['user_id']; $email=$p['email']??''; $max=max(1,(int)admin_setting('mfa_max_attempts','5'));
    if($method==='totp') { $secret=ao_mfa_get_totp_secret($userType,$userId,true); $ok=ao_totp_verify($secret,$code); }
    else {
        $ok=false; try { $q=db()->prepare('SELECT * FROM auth_otp_tokens WHERE user_type=? AND user_id=? AND method=? AND used_at IS NULL AND expires_at>NOW() ORDER BY id DESC LIMIT 1'); $q->execute([$userType,$userId,$method]); $row=$q->fetch(); if($row && (int)$row['attempts']<$max){ $ok=password_verify((string)$code,$row['code_hash']); db()->prepare('UPDATE auth_otp_tokens SET attempts=attempts+1, used_at=IF(?=1,NOW(),used_at) WHERE id=?')->execute([$ok?1:0,$row['id']]); } } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
    }
    if(!$ok){ ao_mfa_log($userType,$userId,$email,'mfa_verify',$method,'failed','Kod hatalı veya süresi dolmuş.'); return ['ok'=>false,'message'=>'Kod hatalı veya süresi dolmuş.']; }
    if($userType==='admin') $_SESSION['admin_id']=$userId; else $_SESSION['customer_id']=$userId;
    if(function_exists('ao_session_mark_authenticated')) ao_session_mark_authenticated($userType);
    ao_mfa_log($userType,$userId,$email,'mfa_verify',$method,'success','MFA doğrulama başarılı.');
    $redirect=$p['redirect'] ?: ($userType==='admin'?'admin':'client'); unset($_SESSION['mfa_pending']); return ['ok'=>true,'redirect'=>$redirect];
}

function ao_session_client_ip() {
    return (string)($_SERVER['REMOTE_ADDR'] ?? '');
}
function ao_session_meta_key($userType) {
    return $userType === 'admin' ? 'admin_session_meta' : 'customer_session_meta';
}
function ao_session_mark_authenticated($userType) {
    $_SESSION[ao_session_meta_key($userType)] = [
        'ip' => ao_session_client_ip(),
        'last_activity' => time(),
        'started_at' => time(),
    ];
}
function ao_session_clear_user($userType) {
    if ($userType === 'admin') unset($_SESSION['admin_id'], $_SESSION['admin_session_meta']);
    else unset($_SESSION['customer_id'], $_SESSION['customer_session_meta']);
}
function ao_session_guard($userType) {
    $idKey = $userType === 'admin' ? 'admin_id' : 'customer_id';
    if (empty($_SESSION[$idKey])) return true;
    $metaKey = ao_session_meta_key($userType);
    $now = time();
    $timeout = max(1, (int)admin_setting('session_timeout_minutes','20')) * 60;
    $ip = ao_session_client_ip();
    $meta = $_SESSION[$metaKey] ?? null;
    if (!is_array($meta) || empty($meta['ip']) || empty($meta['last_activity'])) {
        ao_session_mark_authenticated($userType);
        return true;
    }
    if ((string)$meta['ip'] !== $ip) {
        ao_session_clear_user($userType);
        flash('warning','IP adresiniz değiştiği için güvenliğiniz amacıyla yeniden giriş yapmanız gerekiyor.');
        return false;
    }
    if (($now - (int)$meta['last_activity']) > $timeout) {
        ao_session_clear_user($userType);
        flash('warning','20 dakika işlem yapılmadığı için oturumunuz sonlandırıldı. Lütfen tekrar giriş yapın.');
        return false;
    }
    $_SESSION[$metaKey]['last_activity'] = $now;
    return true;
}
function current_customer() {
    if (empty($_SESSION['customer_id'])) return null;
    if (!ao_session_guard('customer')) return null;
    try { $s=db()->prepare('SELECT * FROM customers WHERE id=? LIMIT 1'); $s->execute([$_SESSION['customer_id']]); return $s->fetch() ?: null; } catch(Throwable $e) { return null; }
}
function require_customer() { if (!current_customer()) redirect_to('client/login'); }
function flash($type, $message) { $_SESSION['flash'] = ['type'=>$type, 'message'=>$message]; }
function get_flash() { $f=$_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf_token'];
}
function csrf_field() { return '<input type="hidden" name="csrf_token" value="'.e(csrf_token()).'">'; }
function verify_csrf() {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    // v25.0.0 RC20: verify_csrf() çağrılan GET aksiyonları da token doğrular.
    // Eski sürüm sadece POST kontrol ediyordu; bu nedenle bazı GET delete/toggle route'ları CSRF korumasız kalıyordu.
    $sent = $method === 'POST' ? ($_POST['csrf_token'] ?? '') : ($_GET['csrf_token'] ?? '');
    if (empty($_SESSION['csrf_token']) || !is_string($sent) || !hash_equals($_SESSION['csrf_token'], $sent)) {
        flash('error','Güvenlik doğrulaması başarısız. Lütfen tekrar deneyin.');
        $resolvedRoute = (string)($_SERVER['AHOST_ROUTE_RESOLVED'] ?? '');
        if ($resolvedRoute === 'admin' || str_starts_with($resolvedRoute, 'admin/')) {
            redirect_to('admin/login');
        }
        if ($resolvedRoute === 'client' || str_starts_with($resolvedRoute, 'client/')) {
            redirect_to('client/login');
        }
        redirect_to($_SERVER['HTTP_REFERER'] ?? '');
    }
}
function current_admin() {
    if (empty($_SESSION['admin_id'])) return null;
    if (!ao_session_guard('admin')) return null;
    try { $s=db()->prepare('SELECT * FROM admins WHERE id=? LIMIT 1'); $s->execute([$_SESSION['admin_id']]); return $s->fetch() ?: null; } catch(Throwable $e) { return null; }
}
function require_admin() { if (!current_admin()) redirect_to('admin/login'); }


// v22.1.0 site header, announcement, language, notification and social helpers
function ao_active_site_announcement() {
    $enabled = (string)admin_setting('site_announcement_enabled','0');
    $text = trim((string)admin_setting('site_announcement_text',''));
    if ($enabled !== '1' || $text === '') return null;
    $now = date('Y-m-d H:i:s');
    $start = trim((string)admin_setting('site_announcement_start',''));
    $end = trim((string)admin_setting('site_announcement_end',''));
    if ($start !== '' && $start > $now) return null;
    if ($end !== '' && $end < $now) return null;
    return [
        'text'=>$text,
        'url'=>trim((string)admin_setting('site_announcement_url','')),
        'style'=>admin_setting('site_announcement_style','info') ?: 'info'
    ];
}
function ao_active_campaign_popup($forcePreview = false) {
    $enabled = (string)admin_setting('site_campaign_popup_enabled', '0') === '1';
    $title = trim((string)admin_setting('site_campaign_popup_title', ''));
    if ((!$enabled && !$forcePreview) || $title === '') return null;
    $now = date('Y-m-d H:i:s');
    $start = trim((string)admin_setting('site_campaign_popup_start', ''));
    $end = trim((string)admin_setting('site_campaign_popup_end', ''));
    if (!$forcePreview && (($start !== '' && $start > $now) || ($end !== '' && $end < $now))) return null;
    return [
        'title' => $title,
        'body' => trim((string)admin_setting('site_campaign_popup_body', '')),
        'button' => trim((string)admin_setting('site_campaign_popup_button', 'Kampanyayı İncele')),
        'url' => trim((string)admin_setting('site_campaign_popup_url', '')),
        'image' => trim((string)admin_setting('site_campaign_popup_image', '')),
        'cooldown' => max(0, min(720, (int)admin_setting('site_campaign_popup_cooldown_hours', '24'))),
        'id' => substr(sha1($title.'|'.$start.'|'.$end), 0, 16),
        'preview' => $forcePreview,
    ];
}
function ao_social_links() {
    $defs = [
        'facebook'=>'Facebook','instagram'=>'Instagram','linkedin'=>'LinkedIn','twitter'=>'X','tiktok'=>'TikTok','youtube'=>'YouTube','whatsapp'=>'WhatsApp','telegram'=>'Telegram','github'=>'GitHub','discord'=>'Discord'
    ];
    $out=[];
    foreach($defs as $key=>$label){
        $url=trim((string)admin_setting($key,''));
        if($url==='') $url=trim((string)admin_setting('social_'.$key,''));
        if($url==='') $url=trim((string)admin_setting('social_'.$key.'_url',''));
        if($url!=='') $out[$key]=['label'=>$label,'url'=>$url];
    }
    return $out;
}
if (!function_exists('ao_social_login_providers')) {
function ao_social_login_providers() {
    $providers = [];
    $googleEnabled = (string)admin_setting('social_login_google_enabled', '0') === '1';
    $googleId = trim((string)admin_setting('google_oauth_client_id', ''));
    $googleSecret = trim((string)admin_setting('google_oauth_client_secret', ''));
    if ($googleEnabled && $googleId !== '' && $googleSecret !== '') {
        $providers['google'] = [
            'label' => 'Google ile devam et',
            'client_id' => $googleId,
            'client_secret' => $googleSecret,
        ];
    }
    $facebookEnabled = (string)admin_setting('social_login_facebook_enabled', '0') === '1';
    $facebookId = trim((string)admin_setting('facebook_oauth_app_id', ''));
    $facebookSecret = trim((string)admin_setting('facebook_oauth_app_secret', ''));
    if ($facebookEnabled && $facebookId !== '' && $facebookSecret !== '') {
        $providers['facebook'] = [
            'label' => 'Facebook ile devam et',
            'client_id' => $facebookId,
            'client_secret' => $facebookSecret,
        ];
    }
    return $providers;
}
}
if (!function_exists('ao_social_login_buttons')) {
function ao_social_login_buttons($context = 'login') {
    $providers = ao_social_login_providers();
    if (!$providers) return '';
    $labels = [
        'google' => 'Google ile devam et',
        'facebook' => 'Facebook ile devam et',
    ];
    $icons = [
        'google' => 'G',
        'facebook' => 'f',
    ];
    $html = '<div class="ao-social-login-buttons" data-social-login-context="'.e((string)$context).'">';
    foreach ($providers as $key => $provider) {
        $label = $provider['label'] ?? ($labels[$key] ?? ucfirst($key).' ile devam et');
        $html .= '<a class="ao-social-login-btn ao-social-login-'.$key.'" href="'.e(url('client/oauth/'.$key)).'"><span>'.e($icons[$key] ?? '•').'</span>'.e($label).'</a>';
    }
    $html .= '</div>';
    return $html;
}
}
function ao_cart_count() {
    $cart = $_SESSION['cart'] ?? $_SESSION['ao_cart'] ?? [];
    if (is_array($cart)) {
        $count=0;
        foreach($cart as $item){ $count += is_array($item) ? (int)($item['qty'] ?? $item['quantity'] ?? 1) : 1; }
        return max(0,$count);
    }
    return 0;
}
function ao_customer_unread_notifications_count() {
    $cid=(int)($_SESSION['customer_id'] ?? 0); if($cid<=0) return 0;
    try {
        return (int)db()->query("SELECT COUNT(*) FROM customer_notifications WHERE customer_id={$cid} AND read_at IS NULL")->fetchColumn();
    } catch(Throwable $e) { return 0; }
}
function ao_available_language_meta(){
    return [
        'tr'=>['label'=>'Türkçe','flag'=>'🇹🇷'], 'en'=>['label'=>'English','flag'=>'🇬🇧'],
        'de'=>['label'=>'Deutsch','flag'=>'🇩🇪'], 'ar'=>['label'=>'العربية','flag'=>'🇸🇦'],
        'ru'=>['label'=>'Русский','flag'=>'🇷🇺'], 'fr'=>['label'=>'Français','flag'=>'🇫🇷'],
        'es'=>['label'=>'Español','flag'=>'🇪🇸']
    ];
}
function ao_language_options() {
    $raw=trim((string)admin_setting('enabled_languages','tr,en'));
    $items=array_filter(array_map('trim', explode(',', $raw)));
    $meta=ao_available_language_meta();
    $out=[]; foreach($items as $code){ $code=strtolower(preg_replace('~[^a-z_-]~i','',$code)); if($code!=='') $out[$code]=$meta[$code]['label'] ?? strtoupper($code); }
    return $out ?: ['tr'=>'Türkçe'];
}
function ao_current_language(){
    if(isset($_GET['lang'])){
        $l=strtolower(preg_replace('~[^a-z_-]~i','',(string)$_GET['lang']));
        if($l!==''){
            $_SESSION['lang']=$l;
            if(!headers_sent()){
                setcookie('ao_lang',$l,time()+31536000,'/');
            }
            return $l;
        }
    }
    return strtolower((string)($_SESSION['lang'] ?? $_COOKIE['ao_lang'] ?? admin_setting('default_language','tr')));
}
function ao_lang_file_path($lang=null){ $lang=$lang ?: ao_current_language(); $lang=strtolower(preg_replace('~[^a-z_-]~i','',(string)$lang)); return dirname(__DIR__).'/lang/'.$lang.'.php'; }
function ao_load_lang($lang=null){
    static $cache=[]; $lang=$lang ?: ao_current_language(); $lang=strtolower((string)$lang);
    if(isset($cache[$lang])) return $cache[$lang];
    $fallback=[]; $fp=dirname(__DIR__).'/lang/tr.php'; if(is_file($fp)){ $x=include $fp; if(is_array($x)) $fallback=$x; }
    $path=ao_lang_file_path($lang); $data=[]; if(is_file($path)){ $x=include $path; if(is_array($x)) $data=$x; }
    return $cache[$lang]=array_replace($fallback,$data);
}
function __t($key,$default=''){
    $map=ao_load_lang(); return $map[$key] ?? ($default!==''?$default:$key);
}

if (!function_exists('ao_enum_slug')) {
    function ao_enum_slug($value) {
        $value = strtolower(trim((string)$value));
        $value = preg_replace('~[^a-z0-9]+~i', '_', $value);
        return trim((string)$value, '_');
    }
}
if (!function_exists('ao_enum_label')) {
    function ao_enum_label($group, $value, $default = '') {
        $raw = trim((string)$value);
        if ($raw === '') return $default !== '' ? $default : '-';
        $slug = ao_enum_slug($raw);
        if ($slug === '') return $default !== '' ? $default : $raw;
        $fallback = $default !== '' ? $default : ucwords(str_replace(['_', '-'], ' ', $raw));
        return __t('enum.'.ao_enum_slug($group).'.'.$slug, $fallback);
    }
}
if (!function_exists('ao_service_status_label')) {
    function ao_service_status_label($status) {
        return ao_enum_label('status', $status, $status ?: '-');
    }
}
if (!function_exists('ao_invoice_status_label')) {
    function ao_invoice_status_label($status) {
        return ao_enum_label('invoice_status', $status, $status ?: '-');
    }
}
if (!function_exists('ao_payment_method_label')) {
    function ao_payment_method_label($method) {
        return ao_enum_label('payment_method', $method, $method ?: '-');
    }
}
if (!function_exists('ao_delivery_status_label')) {
    function ao_delivery_status_label($status) {
        return ao_enum_label('delivery_status', $status, $status ?: '-');
    }
}
if (!function_exists('ao_billing_cycle_label')) {
    function ao_billing_cycle_label($cycle) {
        return ao_enum_label('billing_cycle', $cycle, $cycle ?: '-');
    }
}
if (!function_exists('ao_product_type_label')) {
    function ao_product_type_label($type) {
        return ao_enum_label('product_type', $type, $type ?: '-');
    }
}


if (!function_exists('ao_support_ticket_columns')) {
    function ao_support_ticket_columns() {
        $cols = [];
        try {
            foreach (db()->query('SHOW COLUMNS FROM tickets')->fetchAll() as $col) {
                $name = $col['Field'] ?? ($col[0] ?? '');
                if ($name !== '') $cols[$name] = true;
            }
        } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        return $cols;
    }
}

if (!function_exists('ao_support_ensure_ticket_link_columns')) {
    function ao_support_ensure_ticket_link_columns() {
        try { db()->exec("CREATE TABLE IF NOT EXISTS tickets (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NULL, subject VARCHAR(255) NOT NULL, department VARCHAR(120) DEFAULT 'Genel', priority VARCHAR(40) DEFAULT 'medium', status VARCHAR(40) DEFAULT 'open', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY customer_id(customer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        $cols = ao_support_ticket_columns();
        $adds = [
            'related_type'  => "ALTER TABLE tickets ADD COLUMN related_type VARCHAR(40) NULL AFTER priority",
            'related_id'    => "ALTER TABLE tickets ADD COLUMN related_id INT NULL AFTER related_type",
            'related_label' => "ALTER TABLE tickets ADD COLUMN related_label VARCHAR(255) NULL AFTER related_id",
        ];
        foreach ($adds as $name => $sql) {
            if (empty($cols[$name])) {
                try { db()->exec($sql); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
            }
        }
        try { db()->exec('ALTER TABLE tickets ADD INDEX idx_ticket_related (related_type, related_id)'); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        return true;
    }
}

if (!function_exists('ao_support_related_type_label')) {
    function ao_support_related_type_label($type) {
        $type = strtolower(trim((string)$type));
        $labels = [
            'domain' => __t('support.related.domain', 'Domain'),
            'hosting' => __t('support.related.hosting', 'Hosting / Hizmet'),
            'service' => __t('support.related.hosting', 'Hosting / Hizmet'),
            'general' => __t('support.related.general', 'Genel'),
            '' => __t('support.related.general', 'Genel'),
        ];
        return $labels[$type] ?? ucwords(str_replace(['_', '-'], ' ', $type));
    }
}

if (!function_exists('ao_support_related_display')) {
    function ao_support_related_display($ticket) {
        if (!is_array($ticket)) return '';
        $label = trim((string)($ticket['related_label'] ?? ''));
        $type = strtolower(trim((string)($ticket['related_type'] ?? '')));
        $id = (int)($ticket['related_id'] ?? 0);
        if ($label === '' && $id > 0 && $type !== '') {
            try {
                if ($type === 'domain') {
                    $q = db()->prepare('SELECT domain_name FROM domains WHERE id=? LIMIT 1');
                    $q->execute([$id]);
                    $label = (string)$q->fetchColumn();
                } elseif (in_array($type, ['hosting', 'service'], true)) {
                    $q = db()->prepare('SELECT COALESCE(NULLIF(p.name,""), NULLIF(s.domain,""), CONCAT("Hizmet #",s.id)) FROM services s LEFT JOIN products p ON p.id=s.product_id WHERE s.id=? LIMIT 1');
                    $q->execute([$id]);
                    $label = (string)$q->fetchColumn();
                }
            } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        }
        if ($label === '') return '';
        return ao_support_related_type_label($type) . ': ' . $label;
    }
}

if (!function_exists('ao_support_customer_related_payload')) {
    function ao_support_customer_related_payload($customerId, $type, $id) {
        $customerId = (int)$customerId;
        $type = strtolower(trim((string)$type));
        $id = (int)$id;
        if ($customerId <= 0 || $id <= 0 || $type === '' || $type === 'general') {
            return ['type' => null, 'id' => null, 'label' => null];
        }
        try {
            if ($type === 'domain') {
                $q = db()->prepare('SELECT id, domain_name, status, expiry_date FROM domains WHERE id=? AND customer_id=? LIMIT 1');
                $q->execute([$id, $customerId]);
                $d = $q->fetch();
                if ($d) {
                    $extra = [];
                    if (!empty($d['status'])) $extra[] = $d['status'];
                    if (!empty($d['expiry_date'])) $extra[] = substr((string)$d['expiry_date'], 0, 10);
                    return ['type' => 'domain', 'id' => (int)$d['id'], 'label' => trim((string)$d['domain_name'] . ($extra ? ' · '.implode(' · ', $extra) : ''))];
                }
            }
            if (in_array($type, ['hosting', 'service'], true)) {
                $q = db()->prepare('SELECT s.id, s.domain, s.status, s.billing_cycle, s.next_due_date, p.name product_name, h.whm_username, h.package_name FROM services s LEFT JOIN products p ON p.id=s.product_id LEFT JOIN hosting_accounts h ON h.service_id=s.id WHERE s.id=? AND s.customer_id=? LIMIT 1');
                $q->execute([$id, $customerId]);
                $s = $q->fetch();
                if ($s) {
                    $name = trim((string)($s['product_name'] ?: $s['package_name'] ?: $s['domain'] ?: ('Hizmet #'.$s['id'])));
                    $extra = [];
                    if (!empty($s['domain']) && $s['domain'] !== $name) $extra[] = $s['domain'];
                    if (!empty($s['whm_username'])) $extra[] = $s['whm_username'];
                    if (!empty($s['status'])) $extra[] = $s['status'];
                    if (!empty($s['next_due_date'])) $extra[] = substr((string)$s['next_due_date'], 0, 10);
                    return ['type' => 'hosting', 'id' => (int)$s['id'], 'label' => $name . ($extra ? ' · '.implode(' · ', $extra) : '')];
                }
            }
        } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        return ['type' => null, 'id' => null, 'label' => null];
    }
}

if (!function_exists('ao_domain_display_name')) {
    function ao_domain_display_name($domain): string {
        if (!is_array($domain)) {
            return trim((string)$domain);
        }

        $name = trim((string)($domain['domain_name'] ?? ($domain['domain'] ?? ($domain['full_domain'] ?? ($domain['name'] ?? '')))));
        if ($name === '') {
            $sld = trim((string)($domain['sld'] ?? ''));
            $tld = trim((string)($domain['tld'] ?? ''));
            if ($sld !== '' && $tld !== '') {
                $name = $sld . '.' . ltrim($tld, '.');
            }
        }

        return $name;
    }
}

if (!function_exists('ao_status_tr')) {
    function ao_status_tr($status): string {
        $key = strtolower(trim((string)$status));
        $labels = [
            'active' => 'Aktif',
            'pending' => 'Beklemede',
            'pending_transfer' => 'Transfer Bekliyor',
            'suspended' => 'Askıda',
            'terminated' => 'Sonlandırıldı',
            'cancelled' => 'İptal',
            'expired' => 'Süresi Doldu',
            'paid' => 'Ödendi',
            'unpaid' => 'Ödenmedi',
            'draft' => 'Taslak',
            'refunded' => 'İade edildi',
            'partial' => 'Kısmi ödendi',
            'partially_paid' => 'Kısmi ödendi',
        ];

        return $labels[$key] ?? ((string)$status !== '' ? (string)$status : '-');
    }
}

function ao_sync_language_file($lang,array $translations){
    $dir=dirname(__DIR__).'/lang'; if(!is_dir($dir)) @mkdir($dir,0775,true);
    $path=$dir.'/'.preg_replace('~[^a-z_-]~i','',$lang).'.php';
    $clean=[];
    foreach($translations as $k=>$v){
        if(!is_string($k) || !is_scalar($v)) continue;
        $clean[$k]=(string)$v;
    }
    $body="<?php\nreturn ".var_export($clean,true).";\n";
    return @file_put_contents($path,$body)!==false;
}

function admin_setting($key, $default=null) {
    try { $s=db()->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1'); $s->execute([$key]); $v=$s->fetchColumn(); return $v===false ? $default : $v; } catch(Throwable $e) { return $default; }
}
function sanitize_admin_html($html){
    // Very small whitelist sanitizer: allow common tags and attributes used in footer.
    $allowedTags = ['a','b','strong','i','em','ul','ol','li','p','div','span','h1','h2','h3','h4','h5','br','img'];
    $allowedAttrs = ['href','src','alt','title','class','id','style'];
    // Strip tags not in whitelist
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>');
    libxml_clear_errors();
    $body = $doc->getElementsByTagName('body')->item(0);
    $walker = function($node) use (&$walker,$allowedTags,$allowedAttrs){
        if ($node->nodeType === XML_ELEMENT_NODE) {
            $tag = strtolower($node->nodeName);
            if (!in_array($tag,$allowedTags,true)) {
                // replace with children
                $frag = $node->ownerDocument->createDocumentFragment();
                while($node->childNodes->length) $frag->appendChild($node->childNodes->item(0));
                $node->parentNode->replaceChild($frag,$node);
                return;
            }
            // filter attributes
            $attrs = [];
            foreach(iterator_to_array($node->attributes ?? []) as $a){ $attrs[$a->name]=$a->value; }
            foreach($attrs as $n=>$v){ if(!in_array($n,$allowedAttrs,true)) $node->removeAttribute($n); else { if(in_array($n,['href','src'],true) && preg_match('~^javascript:~i',$v)) $node->removeAttribute($n); } }
        }
        for($i=0;$i<$node->childNodes->length;$i++) $walker($node->childNodes->item($i));
    };
    $walker($body);
    $inner=''; foreach($body->childNodes as $c) $inner .= $doc->saveHTML($c);
    return $inner;
}
function save_setting($key, $value) {
    try { $s=db()->prepare('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)'); return $s->execute([$key,$value]); } catch(Throwable $e) { return false; }
}


// Ahost One v24.6.3 route, mobile navigation and notification helpers
if (!function_exists('ao_current_route_path')) {
    function ao_current_route_path(bool $resolved = true): string {
        $path = $resolved ? ($_SERVER['AHOST_ROUTE_RESOLVED'] ?? '') : '';
        if ($path === '') $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
        $path = strtolower(trim((string)$path, '/'));
        $path = preg_replace('~^index\.php/?~', '', $path);
        return trim($path, '/');
    }
}
if (!function_exists('ao_mobile_nav_active_key_v2463')) {
    function ao_mobile_nav_active_key_v2463(?string $path = null): string {
        $path = trim(strtolower($path ?? ao_current_route_path(true)), '/');
        if ($path === '' || $path === 'home' || $path === 'anasayfa') return 'home';
        if (preg_match('~(^|/)(client|customer)/(support|tickets?|ticket|knowledge-base|bilgi-bankasi)(/|$)~', $path)) return 'support';
        if (preg_match('~(^|/)(client|customer|musteri-paneli|panel|profile|services|invoices|account-users|security)(/|$)~', $path)) return 'panel';
        if (preg_match('~(^|/)(domain|whois|dns|alan-adi|alanadi)(/|$)~', $path)) return 'domain';
        if (preg_match('~(^|/)(hosting|vps|server|sunucu|urun|urunler|products?|package|paket|paketler|cart|checkout|ssl)(/|$)~', $path)) return 'package';
        if (preg_match('~(^|/)(support|destek|ticket|tickets|knowledge-base|bilgi-bankasi|duyurular|iletisim|contact)(/|$)~', $path)) return 'support';
        return 'home';
    }
}
if (!function_exists('ao_mobile_nav_active_class')) {
    function ao_mobile_nav_active_class(string $key): string {
        return ao_mobile_nav_active_key_v2463() === $key ? ' active is-active' : '';
    }
}
if (!function_exists('ao_customer_notifications')) {
    function ao_customer_notifications(int $customerId, bool $includeRead = true, int $limit = 100): array {
        try {
            $sql='SELECT * FROM customer_notifications WHERE customer_id=? AND (hidden IS NULL OR hidden=0)'; $params=[$customerId];
            if(!$includeRead) $sql.=' AND read_at IS NULL';
            $sql.=' ORDER BY id DESC LIMIT '.max(1,(int)$limit);
            $q=db()->prepare($sql); $q->execute($params); return $q->fetchAll() ?: [];
        } catch(Throwable $e) { return []; }
    }
}
if (!function_exists('ao_customer_notification_mark_read')) {
    function ao_customer_notification_mark_read(int $customerId, int $id): bool {
        try { return db()->prepare('UPDATE customer_notifications SET read_at=NOW() WHERE id=? AND customer_id=? AND read_at IS NULL')->execute([$id,$customerId]); } catch(Throwable $e) { return false; }
    }
}
if (!function_exists('ao_customer_notification_mark_all_read')) {
    function ao_customer_notification_mark_all_read(int $customerId): bool {
        try { return db()->prepare('UPDATE customer_notifications SET read_at=NOW() WHERE customer_id=? AND read_at IS NULL')->execute([$customerId]); } catch(Throwable $e) { return false; }
    }
}

if (!function_exists('ao_customer_notification_mark_unread')) {
    function ao_customer_notification_mark_unread(int $customerId, int $id): bool {
        try { return db()->prepare('UPDATE customer_notifications SET read_at=NULL WHERE id=? AND customer_id=? AND read_at IS NOT NULL')->execute([$id,$customerId]); } catch(Throwable $e) { return false; }
    }
}

if (!function_exists('ao_customer_notification_set_pinned')) {
    function ao_customer_notification_set_pinned(int $customerId, int $id, bool $pinned): bool {
            try {
                $q = db()->prepare('UPDATE customer_notifications SET pinned=? WHERE id=? AND customer_id=?');
                return (bool)$q->execute([$pinned ? 1 : 0, $id, $customerId]);
            } catch(Throwable $e) {
                return false;
            }
    }
}

// v26.2.0 Central Module Visibility Gate
// A disabled module is hidden from public/admin/customer navigation and blocked at route level.
if (!function_exists('ao_modgate_slug')) {
    function ao_modgate_slug($value) {
        return preg_replace('/[^a-z0-9\-_]/', '', strtolower((string)$value));
    }
}
if (!function_exists('ao_modgate_normalize_route')) {
    function ao_modgate_normalize_route($route) {
        $raw = trim(strtolower((string)$route));
        $path = parse_url($raw, PHP_URL_PATH);
        $route = is_string($path) ? $path : $raw;
        $route = preg_replace('~[?#].*$~', '', $route);
        $route = trim($route, '/');
        $base = function_exists('app_base_path') ? trim(parse_url(app_base_path(), PHP_URL_PATH) ?: '', '/') : '';
        if ($base !== '' && ($route === $base || str_starts_with($route, $base.'/'))) {
            $route = trim(substr($route, strlen($base)), '/');
        }
        if ($route === 'customer') return 'client';
        if (str_starts_with($route, 'customer/')) return 'client/'.substr($route, 9);
        if ($route === 'musteri') return 'client';
        if (str_starts_with($route, 'musteri/')) return 'client/'.substr($route, 8);
        return $route;
    }
}
if (!function_exists('ao_modgate_always_allowed_slugs')) {
    function ao_modgate_always_allowed_slugs() {
        return ['module-center-pro'=>true, 'module-center'=>true];
    }
}
if (!function_exists('ao_modgate_modules_table_exists')) {
    function ao_modgate_modules_table_exists() {
        static $exists = null;
        if ($exists !== null) return $exists;
        try { db()->query('SELECT 1 FROM modules LIMIT 1'); $exists = true; }
        catch (Throwable $e) { $exists = false; }
        return $exists;
    }
}
if (!function_exists('ao_module_runtime_enabled')) {
    function ao_module_runtime_enabled($slug, $default = true) {
        $slug = ao_modgate_slug($slug);
        if ($slug === '') return true;
        $always = ao_modgate_always_allowed_slugs();
        if (isset($always[$slug])) return true;
        if (!ao_modgate_modules_table_exists()) return (bool)$default;
        try {
            $q = db()->prepare('SELECT is_enabled FROM modules WHERE slug=? LIMIT 1');
            $q->execute([$slug]);
            $value = $q->fetchColumn();
            if ($value === false) return (bool)$default;
            return (int)$value === 1;
        } catch (Throwable $e) {
            return (bool)$default;
        }
    }
}
if (!function_exists('ao_modgate_manifest_route_pairs')) {
    function ao_modgate_manifest_route_pairs() {
        static $pairs = null;
        if ($pairs !== null) return $pairs;
        $pairs = [];
        $root = dirname(__DIR__) . '/modules';
        if (is_dir($root)) {
            try {
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
                foreach ($it as $file) {
                    if ($file->getFilename() !== 'module.json') continue;
                    $json = json_decode((string)@file_get_contents($file->getPathname()), true);
                    if (!is_array($json)) continue;
                    $slug = ao_modgate_slug($json['slug'] ?? basename(dirname($file->getPathname())));
                    if ($slug === '') continue;
                    foreach (['admin_menu', 'user_menu'] as $key) {
                        if (!empty($json[$key]['route'])) $pairs[] = [$slug, ao_modgate_normalize_route($json[$key]['route'])];
                    }
                    if (!empty($json['sub_modules']) && is_array($json['sub_modules'])) {
                        foreach ($json['sub_modules'] as $sub) {
                            $subSlug = ao_modgate_slug($sub['slug'] ?? $slug);
                            foreach (['route', 'admin_route', 'user_route'] as $rk) {
                                if (!empty($sub[$rk])) $pairs[] = [$subSlug ?: $slug, ao_modgate_normalize_route($sub[$rk])];
                            }
                        }
                    }
                }
            } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
        }
        $aliases = [
            'sitebuilder' => ['sitebuilder','sitebuilder/preview','sitebuilder/preview-public','sitebuilder/create-demo','sitebuilder/export','sitebuilder/download','sitebuilder/zip','admin/site-builder','admin/site-builder/pages','admin/site-builder/editor','admin/site-builder/live-editor','admin/site-builder/exports','admin/site-builder/header','admin/site-builder/footer','admin/site-builder/popups','admin/site-builder/forms','admin/site-builder/ai-design','admin/builder/site','client/site-builder'],
            'mobilebuilder' => ['mobilebuilder','mobilebuilder/preview-public','mobilebuilder/create-demo','mobilebuilder/radio','mobilebuilder/export','mobilebuilder/build','mobilebuilder/apk','mobilebuilder/aab','mobilebuilder/zip','mobilebuilder/download','admin/mobile-builder','admin/mobile-builder/editor','admin/mobile-builder/ai','admin/mobile-builder/ai-app','admin/mobile-builder/exports','admin/mobile-builder/build-queue','admin/mobile-builder/build-center','admin/mobile-builder/build-log','admin/mobile-builder/build','admin/mobile-builder/menu','admin/mobile-builder/bottom-bar','admin/mobile-builder/cta','admin/builder/mobile','client/mobile-builder'],
            'marketplace' => ['marketplace','marketplace/offer','marketplace/listing-save','admin/marketplace','admin/marketplace/categories','admin/marketplace/offers','admin/marketplace/escrow','admin/marketplace/auctions'],
            'blog' => ['blog','admin/blog','admin/blog/post','admin/blog/categories'],
            'knowledge-base' => ['bilgi-bankasi','knowledge-base','knowledgebase','admin/knowledge-base','admin/support/knowledgebase'],
            'seo-analyzer' => ['seo-analyzer','admin/seo-analyzer'],
            'ssl-autoinstall' => ['admin/ssl-autoinstall'],
            'backup-manager' => ['admin/backup-manager'],
            'domain-parking' => ['admin/domain-parking'],
            'ai-logo-generator' => ['admin/ai-logo-generator'],
            'theme-marketplace' => ['admin/theme-marketplace','admin/theme-center','admin/theme-center/themes','admin/theme-center/editor','admin/theme-center/quick-actions','client/theme-marketplace'],
            'plugin-marketplace' => ['admin/plugin-marketplace','client/plugin-marketplace'],
            'points-system' => ['admin/points-system'],
            'revenue-analytics' => ['admin/revenue-analytics'],
            'workflow-automation' => ['admin/workflow-automation','admin/automation'],
            'cpanel-api' => ['admin/cpanel-api'],
            'dunning' => ['admin/dunning'],
            'e-invoice' => ['admin/e-invoice'],
            'live-chat' => ['admin/live-chat','admin/support/live-chat'],
            'support-widget-pro' => ['admin/support/widget-settings'],
            'license-center' => ['admin/license-center','admin/license-center/plans','admin/license-center/licenses','admin/license-center/packages','admin/license-center/external','admin/license-injection'],
            'openai' => ['admin/ai-center','admin/ai-center/site-analysis','admin/ai-center/seo','admin/ai-center/automation','admin/ai-center/settings','admin/ai-copilot'],
            'domainnameapi' => ['admin/domain-center/registrars'],
            'provider-center-pro' => ['admin/provider-center'],
            'build-center' => ['admin/build-center','admin/build-center/environment','admin/build-center/sdk-tools','admin/build-center/queue','admin/build-center/logs','admin/build-center/repository','admin/build-center/settings'],
            'currency-translation-pro' => ['admin/currency-center','admin/translation-center'],
        ];
        foreach ($aliases as $slug => $routes) {
            foreach ($routes as $route) $pairs[] = [ao_modgate_slug($slug), ao_modgate_normalize_route($route)];
        }
        $seen = [];
        $out = [];
        foreach ($pairs as $pair) {
            [$slug, $route] = $pair;
            if ($slug === '' || $route === '') continue;
            $key = $slug.'|'.$route;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $out[] = [$slug, $route];
        }
        return $out;
    }
}
if (!function_exists('ao_modgate_slug_for_route')) {
    function ao_modgate_slug_for_route($route) {
        $rawRoute = (string)$route;
        $routeQuery = [];
        $queryString = parse_url($rawRoute, PHP_URL_QUERY);
        if (is_string($queryString) && $queryString !== '') {
            parse_str($queryString, $routeQuery);
        }
        $route = ao_modgate_normalize_route($rawRoute);
        if ($route === '') return '';
        // Modül Merkezi yönetim ekranları her zaman açık kalır; fakat modüllerin kendi route'u
        // admin/module-center/... altında tanımlanmışsa pasif modül kuralından kaçmamalıdır.
        $moduleCenterInternalRoutes = [
            'admin/module-center',
            'admin/module-center/scan',
            'admin/module-center/toggle',
            'admin/module-center/upload',
            'admin/module-center/delete',
            'admin/module-center/download',
            'admin/module-center/config',
            'admin/module-center/config-save',
            'admin/module-center/regenerate-secret',
        ];
        if (in_array($route, $moduleCenterInternalRoutes, true)) return '';
        // Admin pages are management/configuration surfaces. They must remain reachable
        // even when the related feature module is disabled; otherwise normal admin
        // buttons such as Sağ İkonlar incorrectly bounce to Module Center.
        if (str_starts_with($route, 'admin/')) return '';
        if ($route === 'client/modules' || str_starts_with($route, 'client/modules/')) return '';
        if ($route === 'urunler' || $route === 'products') {
            $group = ao_modgate_slug($routeQuery['group'] ?? ($_GET['group'] ?? ''));
            if ($group && ao_modgate_slug_known($group)) return $group;
        }
        if (preg_match('~^(urun-grubu|product-group)/([a-z0-9\-_]+)$~', $route, $m)) {
            $group = ao_modgate_slug($m[2]);
            if (ao_modgate_slug_known($group)) return $group;
        }
        if ($route === 'admin/builder-pro') {
            $target = ao_modgate_slug($routeQuery['target'] ?? ($_GET['target'] ?? ''));
            if ($target === 'site') return 'sitebuilder';
            if ($target === 'mobile') return 'mobilebuilder';
        }
        foreach (ao_modgate_manifest_route_pairs() as $pair) {
            [$slug, $prefix] = $pair;
            if ($route === $prefix || str_starts_with($route, $prefix.'/')) return $slug;
        }
        return '';
    }
}
if (!function_exists('ao_modgate_slug_known')) {
    function ao_modgate_slug_known($slug) {
        $slug = ao_modgate_slug($slug);
        if ($slug === '') return false;
        foreach (ao_modgate_manifest_route_pairs() as $pair) if ($pair[0] === $slug) return true;
        if (!ao_modgate_modules_table_exists()) return false;
        try { $q=db()->prepare('SELECT 1 FROM modules WHERE slug=? LIMIT 1'); $q->execute([$slug]); return (bool)$q->fetchColumn(); } catch(Throwable $e) { return false; }
    }
}
if (!function_exists('ao_module_route_allowed')) {
    function ao_module_route_allowed($route) {
        $slug = ao_modgate_slug_for_route($route);
        return $slug === '' || ao_module_runtime_enabled($slug, true);
    }
}
if (!function_exists('ao_module_filter_menu_items')) {
    function ao_module_filter_menu_items($items) {
        $out = [];
        foreach ((array)$items as $item) {
            $url = trim((string)($item['url'] ?? ($item[1] ?? '')));
            $children = !empty($item['children']) && is_array($item['children']) ? ao_module_filter_menu_items($item['children']) : [];
            $item['children'] = $children;
            $allowed = $url === '' || preg_match('~^mailto:|^tel:|^#~i', $url) || ao_module_route_allowed($url);
            if (!$allowed && !$children) continue;
            if (!$allowed && $children) $item['url'] = '#';
            $out[] = $item;
        }
        return $out;
    }
}
if (!function_exists('ao_module_filter_products')) {
    function ao_module_filter_products($products) {
        $out=[];
        foreach ((array)$products as $p) {
            $slug = ao_modgate_slug($p['module_name'] ?? '');
            $groupSlug = ao_modgate_slug($p['group_slug'] ?? '');
            $candidate = ao_modgate_slug_known($slug) ? $slug : (ao_modgate_slug_known($groupSlug) ? $groupSlug : '');
            if ($candidate !== '' && !ao_module_runtime_enabled($candidate, true)) continue;
            $out[] = $p;
        }
        return $out;
    }
}
if (!function_exists('ao_module_filter_product_groups')) {
    function ao_module_filter_product_groups($groups) {
        $out=[];
        foreach ((array)$groups as $g) {
            $slug = ao_modgate_slug($g['slug'] ?? '');
            if ($slug !== '' && ao_modgate_slug_known($slug) && !ao_module_runtime_enabled($slug, true)) continue;
            $out[] = $g;
        }
        return $out;
    }
}
if (!function_exists('ao_module_guard_current_route')) {
    function ao_module_guard_current_route($route) {
        $slug = ao_modgate_slug_for_route($route);
        if ($slug === '' || ao_module_runtime_enabled($slug, true)) return;
        $route = ao_modgate_normalize_route($route);
        if (str_starts_with($route, 'admin/')) {
            flash('warning', 'Bu modül pasif. Kullanmak için önce Modül Merkezi üzerinden aktif edin.');
            redirect_to('admin/module-center');
        }
        if ($route === 'client' || str_starts_with($route, 'client/')) {
            if (function_exists('current_customer') && current_customer()) flash('warning', 'Bu hizmet şu anda pasif. Admin aktif ettiğinde tekrar kullanabilirsiniz.');
            redirect_to('client');
        }
        http_response_code(404);
        site_view('errors/404', ['pageTitle'=>'Sayfa bulunamadı']);
        exit;
    }
}
if (!function_exists('ao_module_active_customer_modules')) {
    function ao_module_active_customer_modules() {
        $rows = [];
        if (!ao_modgate_modules_table_exists()) return [];
        try { $rows = db()->query('SELECT * FROM modules WHERE is_enabled=1 ORDER BY type,name')->fetchAll() ?: []; } catch(Throwable $e) { return []; }
        $out = [];
        foreach ($rows as $row) {
            $manifest = [];
            if (!empty($row['manifest_json'])) {
                $decoded = json_decode((string)$row['manifest_json'], true);
                if (is_array($decoded)) $manifest = $decoded;
            }
            $slug = ao_modgate_slug($row['slug'] ?? '');
            $route = '';
            if (!empty($manifest['user_menu']['route'])) $route = ao_modgate_normalize_route($manifest['user_menu']['route']);
            if ($route === 'musteri/site-builder') $route = 'client/site-builder';
            if ($route === 'musteri/mobile-builder') $route = 'client/mobile-builder';
            if ($route === '' && $slug === 'sitebuilder') $route = 'client/site-builder';
            if ($route === '' && $slug === 'mobilebuilder') $route = 'client/mobile-builder';
            if ($route === '' && $slug === 'theme-marketplace') $route = 'client/theme-marketplace';
            if ($route === '' && $slug === 'plugin-marketplace') $route = 'client/plugin-marketplace';
            $out[] = [
                'slug' => $slug,
                'name' => $row['name'] ?? ($manifest['name'] ?? $slug),
                'type' => $row['type'] ?? ($manifest['type'] ?? 'other'),
                'description' => $row['description'] ?? ($manifest['description'] ?? ''),
                'route' => $route,
                'features' => is_array($manifest['features'] ?? null) ? array_slice($manifest['features'], 0, 5) : [],
            ];
        }
        return $out;
    }
}

// v26.2.3: Bildirim gönderim fonksiyonları (ao_send_sms, ao_send_whatsapp,
// ao_send_email_notification, ao_notify_event ve yardımcıları) index.php'den buraya
// taşındı. Önceden sadece index.php'de tanımlıydı; index.php'yi hiç yüklemeyen
// cron/ betikleri (sadece bootstrap.php require eden) bu fonksiyonları göremiyordu.
// Kod birebir aynı, sadece tanımlandığı dosya değişti — function_exists guard'ı ile
// index.php tarafında yeniden tanımlanmaya karşı da korunuyor (zaten index.php'den
// kaldırıldı ama ileride biri geri eklerse fatal "cannot redeclare" hatası yerine
// sessizce atlanır).
if (!function_exists('ao_json_config')) {
function ao_json_config($row) {
    $cfg = json_decode($row['config_json'] ?? '{}', true);
    return is_array($cfg) ? $cfg : [];
}
}
if (!function_exists('ao_http_request')) {
function ao_http_request($method, $url, $headers = [], $body = null, $timeout = 12) {
    $method = strtoupper($method ?: 'POST');
    $headerLines = [];
    foreach ($headers as $k=>$v) { $headerLines[] = is_int($k) ? $v : ($k . ': ' . $v); }
    $opts = ['http'=>['method'=>$method,'timeout'=>$timeout,'ignore_errors'=>true,'header'=>implode("\r\n", $headerLines)]];
    if ($body !== null && $method !== 'GET') $opts['http']['content'] = is_string($body) ? $body : http_build_query((array)$body);
    $ctx = stream_context_create($opts);
    $res = @file_get_contents($url, false, $ctx);
    $code = '0';
    $responseHeaders=function_exists('http_get_last_response_headers') ? (http_get_last_response_headers() ?: []) : [];
    foreach ($responseHeaders as $h) if (preg_match('/^HTTP\/\S+\s+(\d+)/', $h, $m)) { $code = $m[1]; break; }
    return ['ok'=>($res !== false && (int)$code >= 200 && (int)$code < 300), 'code'=>$code, 'body'=>$res === false ? '' : $res];
}
}
if (!function_exists('ao_notification_active_channel')) {
function ao_notification_active_channel($type, $provider = null) {
    if ($provider) { $q=db()->prepare('SELECT * FROM notification_channels WHERE channel_type=? AND provider=? ORDER BY priority,id LIMIT 1'); $q->execute([$type,$provider]); }
    else { $q=db()->prepare('SELECT * FROM notification_channels WHERE channel_type=? AND status="active" ORDER BY priority,id LIMIT 1'); $q->execute([$type]); }
    return $q->fetch() ?: null;
}
}
if (!function_exists('ao_notification_log')) {
function ao_notification_log($type,$provider,$recipient,$event,$subject,$message,$status,$code='',$response='',$payload=[]) {
    try { $q=db()->prepare('INSERT INTO notification_logs(channel_type,provider,recipient,event_key,subject,message,status,response_code,response_body,payload_json,sent_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)'); $q->execute([$type,$provider,$recipient,$event,$subject,$message,$status,$code,$response,json_encode($payload,JSON_UNESCAPED_UNICODE),$status==='success'?date('Y-m-d H:i:s'):null]); } catch(Throwable $e){ error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()); }
}
}
if (!function_exists('ao_render_message_template')) {
function ao_render_message_template($text, $vars=[]) { foreach ($vars as $k=>$v) $text=str_replace('{'.$k.'}', (string)$v, $text); return $text; }
}
if (!function_exists('ao_send_sms')) {
function ao_send_sms($to, $message, $event='manual', $provider=null) {
    $to=preg_replace('/[^0-9+]/','',trim((string)$to)); if(!$to) return ['ok'=>false,'message'=>'Telefon numarası boş.'];
    $ch=ao_notification_active_channel('sms',$provider); if(!$ch) { ao_notification_log('sms',$provider ?: '',$to,$event,'',$message,'error','','Aktif SMS kanalı yok.'); return ['ok'=>false,'message'=>'Aktif SMS kanalı yok.']; }
    $cfg=ao_json_config($ch); $payload=['to'=>$to,'message'=>$message,'provider'=>$ch['provider']];
    if ((int)$ch['test_mode']===1) { ao_notification_log('sms',$ch['provider'],$to,$event,'',$message,'success','TEST','Test modu: SMS simüle edildi.',$payload); return ['ok'=>true,'message'=>'Test modunda SMS başarılı simüle edildi.']; }
    try {
        if ($ch['provider']==='netgsm') {
            $url=$cfg['api_url'] ?: 'https://api.netgsm.com.tr/sms/send/get/';
            $params=['usercode'=>$cfg['username']??'','password'=>$cfg['password']??'','gsmno'=>$to,'message'=>$message,'msgheader'=>$cfg['sender_id']??($ch['sender_name']??'')];
            $r=ao_http_request('GET',$url.'?'.http_build_query($params),[]);
        } elseif ($ch['provider']==='twilio') {
            $sid=$cfg['account_sid']??''; $token=$cfg['auth_token']??''; $from=$cfg['from_number']??'';
            $url='https://api.twilio.com/2010-04-01/Accounts/'.$sid.'/Messages.json';
            $auth='Authorization: Basic '.base64_encode($sid.':'.$token);
            $r=ao_http_request('POST',$url,[$auth],['From'=>$from,'To'=>$to,'Body'=>$message]);
        } elseif ($ch['provider']==='iletimerkezi') {
            $url=$cfg['api_url'] ?: 'https://api.iletimerkezi.com/v1/send-sms';
            $apiKey=$cfg['api_key'] ?? ($cfg['username'] ?? '');
            $apiHash=$cfg['api_hash'] ?? ($cfg['password'] ?? '');
            $sender=$cfg['sender'] ?? ($cfg['sender_id'] ?? ($ch['sender_name'] ?? ''));
            if(!$apiKey || !$apiHash || !$sender) throw new Exception('İleti Merkezi API key/hash veya gönderici adı boş.');
            $xml='<?xml version="1.0" encoding="UTF-8"?><request><authentication><key>'.htmlspecialchars($apiKey,ENT_XML1,'UTF-8').'</key><hash>'.htmlspecialchars($apiHash,ENT_XML1,'UTF-8').'</hash></authentication><order><sender>'.htmlspecialchars($sender,ENT_XML1,'UTF-8').'</sender><sendDateTime></sendDateTime><message><text><![CDATA['.$message.']]></text><receipents><number>'.htmlspecialchars($to,ENT_XML1,'UTF-8').'</number></receipents></message></order></request>';
            $r=ao_http_request('POST',$url,['Content-Type: text/xml; charset=UTF-8'],$xml);
        } else {
            $url=$cfg['api_url']??''; if(!$url) throw new Exception('Generic SMS API URL boş.');
            $headers=[]; if(!empty($cfg['auth_header'])) $headers[]=$cfg['auth_header'];
            $body=[($cfg['to_field']??'to')=>$to,($cfg['message_field']??'message')=>$message,'sender'=>$ch['sender_name']??''];
            $r=ao_http_request($cfg['method']??'POST',$url,$headers,http_build_query($body));
        }
        $ok=$r['ok']; ao_notification_log('sms',$ch['provider'],$to,$event,'',$message,$ok?'success':'error',$r['code'],$r['body'],$payload); return ['ok'=>$ok,'message'=>$ok?'SMS gönderildi.':'SMS API hatası: '.$r['code']];
    } catch(Throwable $e) { ao_notification_log('sms',$ch['provider'],$to,$event,'',$message,'error','EXCEPTION',$e->getMessage(),$payload); return ['ok'=>false,'message'=>$e->getMessage()]; }
}
}
if (!function_exists('ao_send_whatsapp')) {
function ao_send_whatsapp($to, $message, $event='manual', $provider=null) {
    $to=preg_replace('/[^0-9+]/','',trim((string)$to)); if(!$to) return ['ok'=>false,'message'=>'WhatsApp numarası boş.'];
    $ch=ao_notification_active_channel('whatsapp',$provider); if(!$ch) { ao_notification_log('whatsapp',$provider ?: '',$to,$event,'',$message,'error','','Aktif WhatsApp kanalı yok.'); return ['ok'=>false,'message'=>'Aktif WhatsApp kanalı yok.']; }
    $cfg=ao_json_config($ch); $payload=['to'=>$to,'message'=>$message,'provider'=>$ch['provider']];
    if ((int)$ch['test_mode']===1) { ao_notification_log('whatsapp',$ch['provider'],$to,$event,'',$message,'success','TEST','Test modu: WhatsApp simüle edildi.',$payload); return ['ok'=>true,'message'=>'Test modunda WhatsApp başarılı simüle edildi.']; }
    try {
        if ($ch['provider']==='meta') {
            $ver=$cfg['api_version'] ?: 'v20.0'; $pid=$cfg['phone_number_id']??''; $token=$cfg['access_token']??''; if(!$pid||!$token) throw new Exception('Meta phone_number_id veya token boş.');
            $url='https://graph.facebook.com/'.$ver.'/'.$pid.'/messages';
            $body=json_encode(['messaging_product'=>'whatsapp','to'=>$to,'type'=>'text','text'=>['preview_url'=>false,'body'=>$message]], JSON_UNESCAPED_UNICODE);
            $r=ao_http_request('POST',$url,['Content-Type: application/json','Authorization: Bearer '.$token],$body);
        } elseif ($ch['provider']==='360dialog') {
            $url=$cfg['api_url'] ?: 'https://waba.360dialog.io/v1/messages'; $key=$cfg['api_key']??'';
            $body=json_encode(['to'=>$to,'type'=>'text','text'=>['body'=>$message]], JSON_UNESCAPED_UNICODE);
            $r=ao_http_request('POST',$url,['Content-Type: application/json','D360-API-KEY: '.$key],$body);
        } else {
            $sid=$cfg['account_sid']??''; $token=$cfg['auth_token']??''; $from=$cfg['from_number']??'whatsapp:';
            $url='https://api.twilio.com/2010-04-01/Accounts/'.$sid.'/Messages.json'; $auth='Authorization: Basic '.base64_encode($sid.':'.$token);
            $twTo=str_starts_with($to,'whatsapp:')?$to:'whatsapp:'.$to; $r=ao_http_request('POST',$url,[$auth],['From'=>$from,'To'=>$twTo,'Body'=>$message]);
        }
        $ok=$r['ok']; ao_notification_log('whatsapp',$ch['provider'],$to,$event,'',$message,$ok?'success':'error',$r['code'],$r['body'],$payload); return ['ok'=>$ok,'message'=>$ok?'WhatsApp gönderildi.':'WhatsApp API hatası: '.$r['code']];
    } catch(Throwable $e) { ao_notification_log('whatsapp',$ch['provider'],$to,$event,'',$message,'error','EXCEPTION',$e->getMessage(),$payload); return ['ok'=>false,'message'=>$e->getMessage()]; }
}
}
if (!function_exists('ao_smtp_send_notification')) {
function ao_smtp_send_notification($to,$subject,$body,$event='manual',$channel=null) {
    $cfg = $channel ? ao_json_config($channel) : [];
    $host = trim((string)($cfg['smtp_host'] ?? admin_setting('smtp_host','')));
    $port = (int)($cfg['smtp_port'] ?? admin_setting('smtp_port',587));
    $secure = strtolower(trim((string)($cfg['smtp_secure'] ?? admin_setting('smtp_secure','tls'))));
    $user = trim((string)($cfg['smtp_user'] ?? admin_setting('smtp_username',admin_setting('smtp_user',''))));
    $pass = (string)($cfg['smtp_pass'] ?? admin_setting('smtp_password',admin_setting('smtp_pass','')));
    $from = trim((string)($cfg['from_email'] ?? admin_setting('smtp_from',$user)));
    $fromName = trim((string)($cfg['from_name'] ?? admin_setting('smtp_from_name','Ahost One')));
    if(!$host || !$from) return ['ok'=>false,'message'=>'SMTP host veya gonderici adresi bos.'];
    $target = ($secure==='ssl' ? 'ssl://' : 'tcp://').$host.':'.($port ?: ($secure==='ssl' ? 465 : 587));
    $socket = @stream_socket_client($target,$errno,$errstr,20,STREAM_CLIENT_CONNECT);
    if(!$socket){ ao_notification_log('email','smtp',$to,$event,$subject,$body,'error','SMTP',$errstr); return ['ok'=>false,'message'=>'SMTP baglantisi kurulamadi: '.$errstr]; }
    stream_set_timeout($socket,20);
    $read=function() use ($socket){ $out=''; while(($line=fgets($socket,515))!==false){ $out.=$line; if(isset($line[3]) && $line[3]===' ') break; } return $out; };
    $send=function($cmd) use ($socket,$read){ fwrite($socket,$cmd."\r\n"); return $read(); };
    $ok=false; $resp='';
    try {
        $resp.=$read();
        $resp.=$send('EHLO '.($_SERVER['SERVER_NAME'] ?? 'localhost'));
        if($secure==='tls' || $secure==='starttls'){
            $resp.=$send('STARTTLS');
            if(!stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new Exception('STARTTLS baslatilamadi.');
            $resp.=$send('EHLO '.($_SERVER['SERVER_NAME'] ?? 'localhost'));
        }
        if($user !== '' || $pass !== ''){
            $resp.=$send('AUTH LOGIN');
            $resp.=$send(base64_encode($user));
            $resp.=$send(base64_encode($pass));
        }
        $resp.=$send('MAIL FROM:<'.$from.'>');
        $resp.=$send('RCPT TO:<'.$to.'>');
        $resp.=$send('DATA');
        $headers = [
            'From: '.($fromName ? '=?UTF-8?B?'.base64_encode($fromName).'?= <'.$from.'>' : $from),
            'To: <'.$to.'>',
            'Reply-To: <'.$from.'>',
            'Subject: =?UTF-8?B?'.base64_encode($subject).'?=',
            'Date: '.date('r'),
            'Message-ID: <'.bin2hex(random_bytes(8)).'.'.time().'@'.preg_replace('/^www\./','',parse_url((string)admin_setting('base_url','http://localhost'),PHP_URL_HOST) ?: 'localhost').'>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit'
        ];
        fwrite($socket,implode("\r\n",$headers)."\r\n\r\n".str_replace("\n.","\n..",$body)."\r\n.\r\n");
        $dataResp=$read(); $resp.=$dataResp;
        $resp.=$send('QUIT');
        $ok=(bool)preg_match('/^(250|2\d\d)\b|\bqueued\b/im',$dataResp);
    } catch(Throwable $e) {
        $resp.=$e->getMessage();
    }
    fclose($socket);
    ao_notification_log('email','smtp',$to,$event,$subject,$body,$ok?'success':'error',$ok?'250':'SMTP',$resp);
    return ['ok'=>$ok,'message'=>$ok?'SMTP mail gonderildi.':'SMTP mail gonderilemedi.'];
}
}
if (!function_exists('ao_send_email_notification')) {
function ao_send_email_notification($to,$subject,$body,$event='manual') {
    $ch=ao_notification_active_channel('email'); $from='no-reply@example.com'; $provider='mail';
    $smtp=(!$ch || (int)($ch['test_mode']??0)!==1) ? ao_smtp_send_notification($to,$subject,$body,$event,$ch) : ['ok'=>false]; if(!empty($smtp['ok']) || (($smtp['message']??'') !== 'SMTP host veya gonderici adresi bos.')) return $smtp;
    if($ch){ $cfg=ao_json_config($ch); $from=$cfg['from_email']??$from; $provider=$ch['provider']; if((int)$ch['test_mode']===1){ ao_notification_log('email',$provider,$to,$event,$subject,$body,'success','TEST','Test modu: Mail simüle edildi.'); return ['ok'=>true,'message'=>'Test modunda mail simüle edildi.']; } }
    $headers='From: '.$from."\r\n".'Content-Type: text/plain; charset=UTF-8'; $ok=@mail($to,$subject,$body,$headers); ao_notification_log('email',$provider,$to,$event,$subject,$body,$ok?'success':'error',$ok?'200':'MAIL',$ok?'Mail gönderildi':'mail() başarısız'); return ['ok'=>$ok,'message'=>$ok?'Mail gönderildi.':'Mail gönderilemedi.'];
}
}
if (!function_exists('ao_notify_event')) {
function ao_notify_event($event,$customerId=0,$vars=[]) {
    $q=db()->prepare('SELECT * FROM notification_templates WHERE event_key=? AND is_active=1 LIMIT 1'); $q->execute([$event]); $tpl=$q->fetch(); if(!$tpl) return [];
    if($customerId){ $c=db()->prepare('SELECT * FROM customers WHERE id=?'); $c->execute([(int)$customerId]); $cust=$c->fetch(); if($cust){ $vars=array_merge(['customer_name'=>trim(($cust['first_name']??'').' '.($cust['last_name']??'')),'customer_email'=>$cust['email']??'','customer_phone'=>$cust['phone']??''],$vars); } }
    $out=[]; if(!empty($vars['customer_phone'])) { $out['sms']=ao_send_sms($vars['customer_phone'],ao_render_message_template($tpl['sms_body']??'',$vars),$event); if(ao_notification_active_channel('whatsapp')) $out['whatsapp']=ao_send_whatsapp($vars['customer_phone'],ao_render_message_template($tpl['whatsapp_body']??'',$vars),$event); }
    if(!empty($vars['customer_email'])) $out['email']=ao_send_email_notification($vars['customer_email'],ao_render_message_template($tpl['email_subject']??$tpl['title'],$vars),ao_render_message_template($tpl['email_body']??'',$vars),$event);
    return $out;
}
}






