<?php

declare(strict_types=1);

namespace App\Modules\Setup\Controllers;

use App\Core\Database\Connection;
use App\Core\Database\Migrator;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Setup\Services\InstallGate;
use App\Services\Auth\PasswordHasher;

/**
 * Kurulum Sihirbazı — 5 adım:
 *   1. Sistem gereksinimleri kontrolü
 *   2. Veritabanı bilgileri (.env yaz)
 *   3. Migration çalıştır
 *   4. Admin hesabı oluştur
 *   5. Site bilgileri (isim, URL, mail)
 *   → Tamamlandı → installed.lock oluştur
 */
final class SetupController
{
    public function index(Request $request): Response
    {
        if (InstallGate::isInstalled()) {
            return Response::redirect('/');
        }
        return Response::redirect('/kurulum/adim/1');
    }

    public function step(Request $request): Response
    {
        if (InstallGate::isInstalled()) {
            return Response::redirect('/');
        }
        $step = max(1, min(5, (int) $request->param('n')));

        $view = new View();
        $data = ['step' => $step, 'title' => "Kurulum — Adım $step/5"];

        switch ($step) {
            case 1:
                $data['checks'] = self::systemChecks();
                break;
            case 2:
                $data['env'] = self::readEnv(['DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD']);
                $data['db_test'] = SessionManager::getFlash('setup_db_test');
                break;
            case 3:
                $data['migrations'] = SessionManager::getFlash('setup_migration_result');
                break;
            case 4:
                $data['admin_created'] = SessionManager::get('setup_admin_created');
                break;
            case 5:
                $data['env'] = self::readEnv(['APP_NAME','APP_URL','MAIL_HOST','MAIL_USERNAME','MAIL_FROM_ADDRESS']);
                break;
        }
        $data['flash_error']   = flash('error');
        $data['flash_success'] = flash('success');

        return Response::html($view->render('setup::wizard', $data));
    }

    // ---- Adım 2: DB test + kaydet -------------------------------------

    public function saveDb(Request $request): Response
    {
        $host = trim((string) $request->input('db_host', '127.0.0.1'));
        $port = (int) $request->input('db_port', 3306);
        $db   = trim((string) $request->input('db_database', ''));
        $user = trim((string) $request->input('db_username', ''));
        $pass = (string) $request->input('db_password', '');
        $freshInstall = (string) $request->input('fresh_install', '') === '1';
        $freshConfirm = trim((string) $request->input('fresh_confirm', ''));

        // Test bağlantı
        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
            new \PDO($dsn, $user, $pass, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_TIMEOUT => 5]);
            SessionManager::flash('setup_db_test', ['ok' => true, 'msg' => 'Bağlantı başarılı ✓']);
        } catch (\Throwable $e) {
            SessionManager::flash('setup_db_test', ['ok' => false, 'msg' => 'Bağlantı hatası: ' . $e->getMessage()]);
            return Response::redirect('/kurulum/adim/2');
        }

        // .env güncelle
        self::writeInstallationConfig([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST'       => $host,
            'DB_PORT'       => (string) $port,
            'DB_DATABASE'   => $db,
            'DB_USERNAME'   => $user,
            'DB_PASSWORD'   => $pass,
            'INSTALL_FRESH_DATABASE' => $freshInstall && $freshConfirm === 'SIFIR KURULUM' ? '1' : '0',
        ]);

        SessionManager::flash('success', 'Veritabanı bilgileri kaydedildi.');
        // Migration ve seed bir sonraki admin adımında otomatik çalışır.
        return Response::redirect('/kurulum/adim/4');
    }

    // ---- Adım 3: Migration -------------------------------------------

    public function runMigrations(Request $request): Response
    {
        try {
            $migrator = new Migrator(AHO_ROOT);
            $executed = $migrator->run();
            $log = $executed
                ? "Çalıştırılan migrationlar:\n" . implode("\n", $executed)
                : 'Zaten güncel — çalıştırılacak yeni migration yok.';
            SessionManager::flash('setup_migration_result', ['ok' => true, 'log' => $log]);
            SessionManager::flash('success', 'Tüm migrationlar başarıyla çalıştırıldı.');
        } catch (\Throwable $e) {
            SessionManager::flash('setup_migration_result', ['ok' => false, 'log' => $e->getMessage()]);
            SessionManager::flash('error', 'Migration başarısız oldu. Log\'u kontrol edin.');
        }
        return Response::redirect('/kurulum/adim/3');
    }

    // ---- Adım 4: Admin oluştur ----------------------------------------

    public function createAdmin(Request $request): Response
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $first = trim((string) $request->input('first_name', 'Süper'));
        $last  = trim((string) $request->input('last_name',  'Admin'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 8) {
            SessionManager::flash('error', 'Geçerli e-posta ve en az 8 karakter şifre gerekli.');
            return Response::redirect('/kurulum/adim/4');
        }

        try {
            // cPanel kurulumunda kullanıcıdan ekstra migration adımı isteme.
            // DB bağlantısı yeni request'te .env'den yüklendiği için burada çalıştırılır.
            self::resetDatabaseIfRequested();
            (new Migrator(AHO_ROOT))->run();
            // Taze kurulumda varsayılan ürün, kur, vergi, ayar ve içerikleri yükle.
            // Seeder çıktıları HTTP yanıtına basılmasın; cPanel kurulumunda
            // Location header'ının bozulmasını önlemek için tamponla.
            ob_start();
            try {
                foreach (glob(AHO_ROOT . '/database/seeds/*.php') as $seedFile) {
                    $seeder = require $seedFile;
                    if (is_object($seeder) && method_exists($seeder, 'run')) {
                        $seeder->run();
                    }
                }
            } finally {
                ob_end_clean();
            }

            // Rol var mı? Yoksa seed çalıştır
            $roleCount = Connection::selectOne("SELECT COUNT(*) c FROM admin_roles");
            if ((int) $roleCount['c'] === 0) {
                Connection::insert('admin_roles', [
                    'name' => 'Süper Admin',
                    'slug' => 'super_admin',
                    'description' => 'Sistemdeki tüm yetkilere sahip',
                    'is_system' => 1,
                ]);
            }
            $role = Connection::selectOne("SELECT id FROM admin_roles WHERE slug = 'super_admin' OR is_system = 1 ORDER BY id ASC LIMIT 1");

            $exists = Connection::selectOne("SELECT id FROM admins WHERE email = ?", [$email]);
            if ($exists) {
                Connection::update('admins', [
                    'password_hash' => PasswordHasher::hash($password),
                    'full_name'     => trim($first . ' ' . $last),
                    'is_active'     => 1,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ], 'id = ?', [$exists['id']]);
                $adminId = (int) $exists['id'];
            } else {
                $adminId = Connection::insert('admins', [
                    'username'      => strstr($email, '@', true) ?: 'admin',
                    'email'         => $email,
                    'password_hash' => PasswordHasher::hash($password),
                    'full_name'     => trim($first . ' ' . $last),
                    'role_id'       => (int) $role['id'],
                    'is_active'     => 1,
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
            }
            SessionManager::set('setup_admin_created', ['ok' => true, 'email' => $email, 'id' => $adminId]);
            SessionManager::flash('success', "Süper admin oluşturuldu: $email");
            // Site bilgileri opsiyonel bırakılır; cPanel kurulumu admin bilgileriyle tamamlanır.
            try {
                Connection::query("REPLACE INTO settings (`group`, `key`, `value`) VALUES ('site','name',?)", ['Ahost One']);
            } catch (\Throwable) {}
            InstallGate::markInstalled(['admin_email' => $email, 'app_name' => 'Ahost One', 'app_url' => '']);
            return Response::redirect('/kurulum/tamamlandi');
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Admin oluşturulamadı: ' . $e->getMessage());
            return Response::redirect('/kurulum/adim/4');
        }
        return Response::redirect('/kurulum/adim/5');
    }

    // ---- Adım 5: Site bilgileri + finalize ----------------------------

    public function saveSite(Request $request): Response
    {
        $env = [
            'APP_NAME' => (string) $request->input('app_name', 'Ahost Bilişim'),
            'APP_URL'  => rtrim((string) $request->input('app_url', 'http://localhost'), '/'),
        ];
        // Mail opsiyonel
        foreach (['MAIL_HOST','MAIL_PORT','MAIL_USERNAME','MAIL_PASSWORD','MAIL_FROM_ADDRESS','MAIL_FROM_NAME','MAIL_ENCRYPTION'] as $k) {
            $v = (string) $request->input(strtolower($k), '');
            if ($v !== '') $env[$k] = $v;
        }
        self::updateEnv($env);

        // Settings tablosuna da yaz
        try {
            $siteName = $env['APP_NAME'];
            Connection::query("REPLACE INTO settings (`group`, `key`, `value`) VALUES ('site','name',?)", [$siteName]);
        } catch (\Throwable) {}

        // Kurulum tamamlandı → lock oluştur
        InstallGate::markInstalled([
            'admin_email' => (string) SessionManager::get('setup_admin_created')['email'] ?? '',
            'app_name'    => $env['APP_NAME'],
            'app_url'     => $env['APP_URL'],
        ]);

        return Response::redirect('/kurulum/tamamlandi');
    }

    public function done(Request $request): Response
    {
        $view = new View();
        return Response::html($view->render('setup::done', [
            'title' => 'Kurulum Tamamlandı',
        ]));
    }

    // ---- Helpers ------------------------------------------------------

    /** @return array<string, array{ok:bool, label:string, value:string, required:bool}> */
    private static function systemChecks(): array
    {
        $writable = fn($p) => is_writable($p);
        return [
            'php'       => ['ok' => PHP_VERSION_ID >= 80200, 'label' => 'PHP >= 8.2',       'value' => PHP_VERSION,                           'required' => true],
            'pdo_mysql' => ['ok' => extension_loaded('pdo_mysql'), 'label' => 'pdo_mysql',  'value' => extension_loaded('pdo_mysql')?'✓':'✗', 'required' => true],
            'mbstring'  => ['ok' => extension_loaded('mbstring'),  'label' => 'mbstring',   'value' => extension_loaded('mbstring')?'✓':'✗',  'required' => true],
            'curl'      => ['ok' => extension_loaded('curl'),      'label' => 'curl',       'value' => extension_loaded('curl')?'✓':'✗',      'required' => true],
            'openssl'   => ['ok' => extension_loaded('openssl'),   'label' => 'openssl',    'value' => extension_loaded('openssl')?'✓':'✗',   'required' => true],
            'gd'        => ['ok' => extension_loaded('gd'),        'label' => 'gd',         'value' => extension_loaded('gd')?'✓':'✗',        'required' => true],
            'zip'       => ['ok' => extension_loaded('zip'),       'label' => 'zip',        'value' => extension_loaded('zip')?'✓':'✗',       'required' => true],
            'json'      => ['ok' => extension_loaded('json'),      'label' => 'json',       'value' => extension_loaded('json')?'✓':'✗',      'required' => true],
            'config_write' => ['ok' => $writable(AHO_ROOT . '/config'), 'label' => 'config/ yazılabilir', 'value' => $writable(AHO_ROOT . '/config')?'✓':'✗', 'required' => true],
            'stor_write'=> ['ok' => $writable(AHO_ROOT . '/storage'), 'label' => 'storage/ yazılabilir', 'value' => $writable(AHO_ROOT . '/storage')?'✓':'✗', 'required' => true],
            'soap'      => ['ok' => extension_loaded('soap'), 'label' => 'soap (e-fatura için)', 'value' => extension_loaded('soap')?'✓':'✗ (opsiyonel)', 'required' => false],
        ];
    }

    private static function readEnv(array $keys): array
    {
        $out = [];
        $envPath = AHO_ROOT . '/.env';
        if (!is_file($envPath)) return array_fill_keys($keys, '');
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $ln) {
            if (str_starts_with(trim($ln), '#')) continue;
            [$k, $v] = array_pad(explode('=', $ln, 2), 2, '');
            $k = trim($k);
            $v = trim($v, " \t\"'");
            if (in_array($k, $keys, true)) {
                $out[$k] = $v;
            }
        }
        return array_merge(array_fill_keys($keys, ''), $out);
    }

    /** cPanel kurulumunda kullanıcıya .env düzenlettirmeden PHP config üretir. */
    private static function writeInstallationConfig(array $updates): void
    {
        $path = AHO_ROOT . '/config/installation.php';
        $existing = is_file($path) ? (require $path) : [];
        $values = array_merge(is_array($existing) ? $existing : [], $updates);
        if ((string)($values['APP_KEY'] ?? '') === '') {
            $values['APP_KEY'] = \App\Support\Encrypter::generateKey();
        }
        $content = "<?php\n// Bu dosya install.php tarafından oluşturulur. Gerçek sırları GitHub'a yüklemeyin.\nreturn " . var_export($values, true) . ";\n";
        if (file_put_contents($path, $content, LOCK_EX) === false) {
            throw new \RuntimeException('config/installation.php yazılamadı. Klasör yazma iznini kontrol edin.');
        }
        @chmod($path, 0640);
    }

    private static function resetDatabaseIfRequested(): void
    {
        $path = AHO_ROOT . '/config/installation.php';
        $config = is_file($path) ? (require $path) : [];
        if (!is_array($config) || (string)($config['INSTALL_FRESH_DATABASE'] ?? '0') !== '1') {
            return;
        }

        $tables = Connection::select("SELECT TABLE_NAME name FROM information_schema.tables WHERE table_schema = DATABASE()");

        Connection::query('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($tables as $table) {
                $name = (string)($table['name'] ?? '');
                if ($name === '' || !preg_match('/^[A-Za-z0-9_]+$/', $name)) {
                    continue;
                }
                Connection::query('DROP TABLE IF EXISTS `' . $name . '`');
            }
        } finally {
            Connection::query('SET FOREIGN_KEY_CHECKS=1');
        }

        $config['INSTALL_FRESH_DATABASE'] = '0';
        $content = "<?php\n// Bu dosya install.php tarafindan olusturulur. Gercek sirlari GitHub'a yuklemeyin.\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($path, $content, LOCK_EX);
        @chmod($path, 0640);
    }

    /** .env dosyasını atomic olarak günceller. */
    private static function updateEnv(array $updates): void
    {
        $envPath = AHO_ROOT . '/.env';
        $current = is_file($envPath) ? file_get_contents($envPath) : '';
        $lines = $current !== '' ? explode("\n", $current) : [];

        $seen = array_fill_keys(array_keys($updates), false);
        foreach ($lines as $i => $line) {
            $trim = trim($line);
            if ($trim === '' || str_starts_with($trim, '#')) continue;
            [$k] = array_pad(explode('=', $line, 2), 2, '');
            $k = trim($k);
            if (isset($updates[$k])) {
                $v = $updates[$k];
                $lines[$i] = $k . '=' . (preg_match('/\s/', $v) ? "\"$v\"" : $v);
                $seen[$k] = true;
            }
        }
        foreach ($updates as $k => $v) {
            if (!$seen[$k]) {
                $lines[] = $k . '=' . (preg_match('/\s/', $v) ? "\"$v\"" : $v);
            }
        }
        file_put_contents($envPath, implode("\n", $lines));
        @chmod($envPath, 0640);
    }
}
