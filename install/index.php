<?php
/**
 * Ahost Bilişim - Kurulum Sihirbazı
 * Kullanım: Tarayıcıdan /install/ adresine gidin.
 * Kurulum tamamlanınca install/.lock dosyası oluşur ve sayfa kilitlenir.
 */

declare(strict_types=1);

define('AHO_START', microtime(true));
define('AHO_ROOT', dirname(__DIR__));

if (file_exists(AHO_ROOT . '/install/.lock')) {
    http_response_code(403);
    echo '<h1>Kurulum tamamlanmış. /install/ pasifleştirildi.</h1>';
    exit;
}

require AHO_ROOT . '/app/Core/bootstrap.php';

use App\Core\Config;
use App\Core\Database\Connection;
use App\Core\Database\Migrator;
use App\Core\Env;
use App\Services\Auth\PasswordHasher;

Env::load(AHO_ROOT . '/.env');
Config::load(AHO_ROOT . '/config');

$step = (int) ($_GET['step'] ?? 1);
$errors = [];
$success = null;

// Step 2: DB test + .env yazma
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? '127.0.0.1');
    $port = trim($_POST['db_port'] ?? '3306');
    $db   = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = $_POST['db_pass'] ?? '';

    if ($db === '' || $user === '') {
        $errors[] = 'Veritabanı adı ve kullanıcı adı zorunludur.';
    } else {
        try {
            $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db}", $user, $pass);
        } catch (PDOException $e) {
            $errors[] = 'Veritabanı bağlantısı başarısız: ' . $e->getMessage();
        }

        if (!$errors) {
            try {
                // .env dosyasını güncelle
                $envPath = AHO_ROOT . '/.env';
                $env = file_exists($envPath) ? file_get_contents($envPath) : false;
                if ($env === false || $env === '') {
                    $examplePath = AHO_ROOT . '/.env.example';
                    if (!file_exists($examplePath)) {
                        throw new RuntimeException('.env ve .env.example dosyaları bulunamadı. Beklenen konum: ' . $examplePath);
                    }
                    $env = file_get_contents($examplePath);
                    if ($env === false) {
                        throw new RuntimeException('.env.example dosyası okunamadı (izin sorunu olabilir): ' . $examplePath);
                    }
                }

                $env = preg_replace('/^DB_HOST=.*/m',     "DB_HOST={$host}", $env);
                $env = preg_replace('/^DB_PORT=.*/m',     "DB_PORT={$port}", $env);
                $env = preg_replace('/^DB_DATABASE=.*/m', "DB_DATABASE={$db}", $env);
                $env = preg_replace('/^DB_USERNAME=.*/m', "DB_USERNAME={$user}", $env);
                $env = preg_replace('/^DB_PASSWORD=.*/m', "DB_PASSWORD={$pass}", $env);

                if (file_put_contents($envPath, $env) === false) {
                    throw new RuntimeException('.env dosyasına yazılamadı (izin sorunu olabilir): ' . $envPath);
                }

                header('Location: /install/?step=3');
                exit;
            } catch (Throwable $e) {
                $errors[] = 'Kurulum dosyası hatası: ' . $e->getMessage();
            }
        }
    }
}

// Step 4: Migration + Seed
if ($step === 4) {
    try {
        $migrator = new Migrator(AHO_ROOT);
        $executed = $migrator->run();
        // Seed
        foreach (glob(AHO_ROOT . '/database/seeds/*.php') as $file) {
            $seeder = require $file;
            if (is_object($seeder) && method_exists($seeder, 'run')) {
                $seeder->run();
            }
        }
        $success = 'Kurulum tamamlandı! ' . count($executed) . ' migration çalıştırıldı.';
    } catch (Throwable $e) {
        $errors[] = 'Kurulum hatası: ' . $e->getMessage();
    }
}

// Step 5: Admin oluştur & lock
if ($step === 5 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['admin_email'] ?? '');
    $pass  = $_POST['admin_pass'] ?? '';
    $name  = trim($_POST['admin_name'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli e-posta gerekli.';
    if (strlen($pass) < 8) $errors[] = 'Şifre en az 8 karakter olmalı.';

    if (!$errors) {
        try {
            $role = Connection::selectOne("SELECT id FROM admin_roles WHERE slug = 'super_admin'");
            $exists = Connection::selectOne("SELECT id FROM admins WHERE email = ?", [$email]);
            if ($exists) {
                Connection::update('admins', [
                    'password_hash' => PasswordHasher::hash($pass),
                    'full_name'     => $name ?: 'Süper Admin',
                    'is_active'     => 1,
                ], 'email = ?', [$email]);
            } else {
                Connection::insert('admins', [
                    'username'      => strstr($email, '@', true),
                    'email'         => $email,
                    'password_hash' => PasswordHasher::hash($pass),
                    'full_name'     => $name ?: 'Süper Admin',
                    'role_id'       => $role['id'] ?? null,
                    'is_active'     => 1,
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
            }
            // Lock file
            file_put_contents(AHO_ROOT . '/install/.lock', date('c'));
            header('Location: /install/?step=6');
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Admin oluşturma hatası: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kurulum - Ahost Bilişim</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/components/buttons.css">
    <link rel="stylesheet" href="/assets/css/components/forms.css">
    <link rel="stylesheet" href="/assets/css/components/cards.css">
    <style>
        body{background:var(--aho-color-bg-soft);padding:var(--aho-space-8) var(--aho-space-4)}
        .aho-install{max-width:640px;margin:0 auto}
        .aho-install__brand{display:flex;align-items:center;gap:var(--aho-space-3);margin-bottom:var(--aho-space-6);justify-content:center}
        .aho-install__brand h1{font-size:var(--aho-text-2xl)}
        .aho-install__brand b{color:var(--aho-color-accent-500)}
        .aho-install__steps{display:flex;justify-content:center;gap:var(--aho-space-2);margin-bottom:var(--aho-space-6);flex-wrap:wrap}
        .aho-install__step{padding:6px 12px;background:var(--aho-color-bg-muted);border-radius:var(--aho-radius-full);font-size:var(--aho-text-xs);color:var(--aho-color-ink-500)}
        .aho-install__step.is-active{background:var(--aho-color-accent-500);color:#fff}
        .aho-install__step.is-done{background:var(--aho-color-success);color:#fff}
        .aho-alert--danger{background:#fee2e2;color:#991b1b;padding:var(--aho-space-3);border-radius:var(--aho-radius-md);margin-bottom:var(--aho-space-4)}
        .aho-alert--success{background:#d1fae5;color:#065f46;padding:var(--aho-space-3);border-radius:var(--aho-radius-md);margin-bottom:var(--aho-space-4)}
    </style>
</head>
<body>
<div class="aho-install">
    <div class="aho-install__brand">
        <img src="/assets/img/logo-icon.png" alt="" width="48" height="48" style="border-radius:8px">
        <h1>Ahost <b>Bilişim</b> — Kurulum</h1>
    </div>

    <div class="aho-install__steps">
        <?php foreach ([1=>'Gereksinim', 2=>'Veritabanı', 3=>'Firma', 4=>'Tablolar', 5=>'Yönetici', 6=>'Bitti'] as $i => $label): ?>
            <span class="aho-install__step <?= $step === $i ? 'is-active' : ($step > $i ? 'is-done' : '') ?>">
                <?= $i ?>. <?= $label ?>
            </span>
        <?php endforeach; ?>
    </div>

    <div class="aho-card">
        <?php foreach ($errors as $e): ?>
            <div class="aho-alert--danger"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <?php if ($step === 1): ?>
            <h2 style="margin-bottom:var(--aho-space-4)">1. Sistem Gereksinimleri</h2>
            <ul style="display:flex;flex-direction:column;gap:var(--aho-space-2);font-size:var(--aho-text-sm)">
                <?php
                $checks = [
                    ['PHP ≥ 8.2', version_compare(PHP_VERSION, '8.2.0', '>=')],
                    ['PDO', extension_loaded('pdo')],
                    ['pdo_mysql', extension_loaded('pdo_mysql')],
                    ['mbstring', extension_loaded('mbstring')],
                    ['openssl', extension_loaded('openssl')],
                    ['storage/ yazılabilir', is_writable(AHO_ROOT . '/storage')],
                    ['.env yazılabilir (veya .env.example)', is_writable(AHO_ROOT) || file_exists(AHO_ROOT . '/.env')],
                ];
                $allOk = true;
                foreach ($checks as $c): $allOk = $allOk && $c[1]; ?>
                    <li>
                        <?= $c[1] ? '✅' : '❌' ?>
                        <?= htmlspecialchars($c[0]) ?>
                        <?= $c[1] ? '' : ' — <strong>eksik</strong>' ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($allOk): ?>
                <a href="?step=2" class="aho-btn aho-btn--primary aho-btn--lg aho-btn--block" style="margin-top:var(--aho-space-6)">Devam Et →</a>
            <?php else: ?>
                <p style="margin-top:var(--aho-space-4);color:var(--aho-color-danger)">Eksik gereksinimleri tamamlayıp sayfayı yenileyin.</p>
            <?php endif; ?>

        <?php elseif ($step === 2): ?>
            <h2 style="margin-bottom:var(--aho-space-4)">2. Veritabanı Bağlantısı</h2>
            <form method="post">
                <div class="aho-form-group"><label class="aho-form-label">Host</label>
                    <input class="aho-form-input" name="db_host" value="127.0.0.1" required></div>
                <div class="aho-form-group"><label class="aho-form-label">Port</label>
                    <input class="aho-form-input" name="db_port" value="3306" required></div>
                <div class="aho-form-group"><label class="aho-form-label aho-form-label--required">Veritabanı Adı</label>
                    <input class="aho-form-input" name="db_name" required></div>
                <div class="aho-form-group"><label class="aho-form-label aho-form-label--required">Kullanıcı</label>
                    <input class="aho-form-input" name="db_user" required></div>
                <div class="aho-form-group"><label class="aho-form-label">Şifre</label>
                    <input type="password" class="aho-form-input" name="db_pass"></div>
                <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg aho-btn--block">Bağlantıyı Test Et →</button>
            </form>

        <?php elseif ($step === 3): ?>
            <h2 style="margin-bottom:var(--aho-space-4)">3. Firma Bilgileri</h2>
            <p style="color:var(--aho-color-ink-500);margin-bottom:var(--aho-space-4)">
                Firma bilgilerinizi admin panelinden ayarlar bölümünden düzenleyebilirsiniz.
                Şimdi tablolar oluşturulacak.
            </p>
            <a href="?step=4" class="aho-btn aho-btn--primary aho-btn--lg aho-btn--block">Tabloları Oluştur →</a>

        <?php elseif ($step === 4): ?>
            <h2 style="margin-bottom:var(--aho-space-4)">4. Veritabanı Kurulumu</h2>
            <?php if ($success): ?>
                <div class="aho-alert--success"><?= htmlspecialchars($success) ?></div>
                <a href="?step=5" class="aho-btn aho-btn--primary aho-btn--lg aho-btn--block">Devam Et →</a>
            <?php else: ?>
                <p style="color:var(--aho-color-ink-500)">Migration/seed çalıştı ancak sonuç görünmüyor. Sayfayı yenileyin.</p>
            <?php endif; ?>

        <?php elseif ($step === 5): ?>
            <h2 style="margin-bottom:var(--aho-space-4)">5. Yönetici Hesabı</h2>
            <form method="post">
                <div class="aho-form-group"><label class="aho-form-label aho-form-label--required">Ad Soyad</label>
                    <input class="aho-form-input" name="admin_name" value="Süper Admin" required></div>
                <div class="aho-form-group"><label class="aho-form-label aho-form-label--required">E-posta</label>
                    <input type="email" class="aho-form-input" name="admin_email" required></div>
                <div class="aho-form-group"><label class="aho-form-label aho-form-label--required">Şifre (min 8 karakter)</label>
                    <input type="password" class="aho-form-input" name="admin_pass" required minlength="8"></div>
                <button type="submit" class="aho-btn aho-btn--primary aho-btn--lg aho-btn--block">Hesabı Oluştur ve Kurulumu Bitir →</button>
            </form>

        <?php elseif ($step === 6): ?>
            <h2 style="margin-bottom:var(--aho-space-4)">🎉 Kurulum Tamamlandı!</h2>
            <div class="aho-alert--success">
                Ahost Bilişim kurulumu başarıyla tamamlandı. Bu sayfa (/install/) artık pasifleştirildi.
            </div>
            <p style="margin-bottom:var(--aho-space-4)">Devam etmek için:</p>
            <div style="display:flex;gap:var(--aho-space-2);flex-wrap:wrap">
                <a href="/admin/giris" class="aho-btn aho-btn--primary aho-btn--lg">Admin Panele Giriş →</a>
                <a href="/" class="aho-btn aho-btn--outline aho-btn--lg">Siteyi Görüntüle</a>
            </div>
            <p style="color:var(--aho-color-ink-500);margin-top:var(--aho-space-6);font-size:var(--aho-text-sm)">
                Güvenlik için <code>install/</code> klasörünü sunucudan silebilirsiniz.
            </p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
