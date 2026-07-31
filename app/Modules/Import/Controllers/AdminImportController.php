<?php

declare(strict_types=1);

namespace App\Modules\Import\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Import\ImportManager;
use App\Modules\Import\Services\ImportService;
use App\Services\Auth\AuthService;
use App\Services\Logger\ActivityLog;

final class AdminImportController
{
    public function index(Request $request): Response
    {
        $recentJobs = Connection::select(
            "SELECT id, source, type, status, total, imported, skipped, errors, created_at, completed_at
             FROM import_jobs ORDER BY id DESC LIMIT 30"
        );

        $view = new View();
        return Response::html($view->render('import::admin.index', [
            'title'   => 'Veri Aktarimi',
            'sources' => ImportManager::all(),
            'jobs'    => $recentJobs,
            'success' => flash('success'),
            'error'   => flash('error'),
        ]));
    }

    public function connect(Request $request): Response
    {
        $source = (string) $request->param('source');
        $driver = ImportManager::driver($source);
        if (!$driver) return Response::notFound('Bilinmeyen kaynak');

        $view = new View();
        return Response::html($view->render('import::admin.connect', [
            'title'  => 'Baglanti: ' . $driver->label(),
            'source' => $source,
            'driver' => $driver,
            'fields' => $driver->configFields(),
            'test'   => SessionManager::getFlash('_import_test'),
            'counts' => SessionManager::getFlash('_import_counts'),
            'config' => SessionManager::getFlash('_import_config', []),
        ]));
    }

    public function testConnection(Request $request): Response
    {
        $source = (string) $request->param('source');
        $driver = ImportManager::driver($source);
        if (!$driver) return Response::notFound();

        $config = self::extractConfig($request, $driver->configFields());
        $test = $driver->testConnection($config);
        SessionManager::flash('_import_test', $test);
        SessionManager::flash('_import_config', $config);

        if ($test['ok']) {
            SessionManager::flash('_import_counts', $driver->counts($config));
        }

        return Response::redirect("/admin/veri-aktarimi/baglan/$source");
    }

    /**
     * Canlı bağlanmak yerine hazır bir .sql (veya .sql içeren .zip) dosyası
     * yükleyerek WHMCS/WISECP/Blesta verisini içe aktarmaya hazırlar.
     * Dump kendi veritabanımıza benzersiz bir önekle yüklenir, sonra driver
     * bu tablolara sanki canlı bağlanmış gibi (host=kendi DB, prefix=üretilen) bakar.
     */
    public function connectSqlUpload(Request $request): Response
    {
        $source = (string) $request->param('source');
        $driver = ImportManager::driver($source);
        if (!$driver) return Response::notFound();

        $file = $request->file('sql_file');
        if (!$file || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            SessionManager::flash('error', 'Dosya yüklenemedi. Tekrar dene.');
            return Response::redirect("/admin/veri-aktarimi/baglan/$source");
        }

        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['sql', 'zip'], true)) {
            SessionManager::flash('error', 'Sadece .sql veya .zip dosyası kabul edilir.');
            return Response::redirect("/admin/veri-aktarimi/baglan/$source");
        }

        $sqlPath = $file['tmp_name'];
        $tmpExtractDir = null;

        try {
            if ($ext === 'zip') {
                $zip = new \ZipArchive();
                if ($zip->open($file['tmp_name']) !== true) {
                    throw new \RuntimeException('Zip dosyası açılamadı (bozuk olabilir).');
                }
                $sqlEntry = null;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'sql') { $sqlEntry = $name; break; }
                }
                if (!$sqlEntry) { $zip->close(); throw new \RuntimeException('Zip içinde .sql dosyası bulunamadı.'); }
                $tmpExtractDir = sys_get_temp_dir() . '/aho-import-' . bin2hex(random_bytes(6));
                mkdir($tmpExtractDir, 0775, true);
                $zip->extractTo($tmpExtractDir, [$sqlEntry]);
                $zip->close();
                $sqlPath = $tmpExtractDir . '/' . $sqlEntry;
            }

            [$ok, $count, $errors, $prefix] = ImportService::importSqlDump($sqlPath, $source);

            if (!$ok || $count === 0) {
                $msg = 'SQL yüklenemedi.' . ($errors ? ' İlk hata: ' . $errors[0] : '');
                SessionManager::flash('error', $msg);
                ImportService::dropSqlDumpTables($prefix);
                return Response::redirect("/admin/veri-aktarimi/baglan/$source");
            }

            // Kendi veritabanımıza, bu dump için üretilen önekle bağlan — driver hiç değişmeden çalışır.
            $db = ahost_config('db');
            $config = [
                'host'     => (string) ($db['host'] ?? 'localhost'),
                'port'     => (string) ($db['port'] ?? ''),
                'database' => (string) ($db['name'] ?? ''),
                'username' => (string) ($db['user'] ?? ''),
                'password' => (string) ($db['pass'] ?? ''),
                'prefix'   => $prefix,
                '_sql_upload' => '1', // temizlik için işaretli
            ];

            $test = $driver->testConnection($config);
            SessionManager::flash('_import_test', $test);
            SessionManager::flash('_import_config', $config);
            if ($test['ok']) {
                SessionManager::flash('_import_counts', $driver->counts($config));
                SessionManager::flash('success', "✓ SQL dosyası yüklendi ($count komut çalıştı). Şimdi aktarılacak veri tiplerini seç.");
            } else {
                SessionManager::flash('error', 'Dosya yüklendi ama tablolar tanınamadı: ' . ($test['message'] ?? 'bilinmeyen hata') . ' (Kaynak panel export formatı farklı olabilir.)');
                ImportService::dropSqlDumpTables($prefix);
            }
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'SQL yükleme başarısız: ' . $e->getMessage());
        } finally {
            if ($tmpExtractDir && is_dir($tmpExtractDir)) {
                array_map('unlink', glob($tmpExtractDir . '/*') ?: []);
                @rmdir($tmpExtractDir);
            }
        }

        return Response::redirect("/admin/veri-aktarimi/baglan/$source");
    }

    public function startImport(Request $request): Response
    {
        try {
            $source = (string) $request->param('source');
            $driver = ImportManager::driver($source);
            if (!$driver) return Response::notFound();

            $config = self::extractConfig($request, $driver->configFields());
            $types = (array) $request->input('types', []);
            $admin = AuthService::admin();
            $created = 0;

            if ($types === []) {
                SessionManager::flash('error', 'Aktarilacak veri tipi secilmedi.');
                SessionManager::flash('_import_config', $config);
                return Response::redirect("/admin/veri-aktarimi/baglan/$source");
            }

            // Import'tan önce hedef tabloların şeması tam mı diye kontrol et,
            // eksikse otomatik tamamla — böylece sessizce veri kaybı olmaz.
            $schemaFixes = ImportService::ensureImportSchema();
            if ($schemaFixes) {
                ActivityLog::log('schema_fixed', 'import_job', 0, 'Import öncesi şema düzeltildi: ' . implode(' | ', $schemaFixes));
            }

            $ordered = ['full_config', 'settings', 'servers', 'registrars', 'products', 'addons', 'custom_fields', 'customers', 'orders', 'invoices', 'domains', 'hosting', 'tickets'];
            foreach ($ordered as $type) {
                if (in_array($type, $types, true)) {
                    $jobId = ImportService::createJob($source, $config, $type, (int)($admin['id'] ?? 0));
                    ActivityLog::log('created', 'import_job', $jobId, "Import job: $source / $type");
                    $created++;
                }
            }

            $msg = "$created import job olusturuldu. Calistir butonu ile baslatin.";
            if ($schemaFixes) {
                $msg .= ' ⚠ Not: içe aktarım öncesi eksik tablo sütunları otomatik tamamlandı (' . implode('; ', $schemaFixes) . '). Daha önce çalıştırdığın import\'ları bu yüzden eksik veri ile bitmiş olabilir, tekrar çalıştırman önerilir.';
            }
            SessionManager::flash('success', $msg);
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Import baslatilamadi: ' . $e->getMessage());
        }

        return Response::redirect('/admin/veri-aktarimi');
    }

    public function runJob(Request $request): Response
    {
        $id = (int) $request->param('id');
        try {
            $result = ImportService::runJob($id, 50);
            if ($result['done']) {
                SessionManager::flash('success', "Job #$id tamamlandi: {$result['imported']} ithal, {$result['skipped']} atlandi, {$result['errors']} hata.");
            } else {
                SessionManager::flash('success', "Job #$id devam ediyor: {$result['imported']} kayit islendi. Devam Et ile surdurun.");
            }
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Hata: ' . $e->getMessage());
        }

        return Response::redirect('/admin/veri-aktarimi');
    }

    public function jobDetail(Request $request): Response
    {
        $id = (int) $request->param('id');
        $job = Connection::selectOne("SELECT * FROM import_jobs WHERE id = ?", [$id]);
        if (!$job) return Response::notFound();

        $view = new View();
        return Response::html($view->render('import::admin.job', [
            'title' => "Job #$id - {$job['source']} / {$job['type']}",
            'job'   => $job,
        ]));
    }

    public function deleteJob(Request $request): Response
    {
        $id = (int) $request->param('id');
        Connection::delete('import_jobs', 'id = ?', [$id]);
        SessionManager::flash('success', "Job #$id silindi. Import edilen kayitlar korundu.");
        return Response::redirect('/admin/veri-aktarimi');
    }

    /** Import'un kullandığı tabloların sütun durumunu gösterir + elle sütun ekleme aracı. */
    public function schemaCheck(Request $request): Response
    {
        $tables = ['customers', 'orders', 'invoices', 'domains', 'hosting_accounts', 'tickets', 'products'];
        $report = [];
        foreach ($tables as $tbl) {
            try {
                $cols = Connection::select("SHOW COLUMNS FROM `$tbl`");
                $names = array_column($cols, 'Field');
                $report[] = [
                    'table' => $tbl,
                    'exists' => true,
                    'has_imported_from' => in_array('imported_from', $names, true),
                    'has_external_id' => in_array('external_id', $names, true),
                    'columns' => $names,
                ];
            } catch (\Throwable) {
                $report[] = ['table' => $tbl, 'exists' => false, 'has_imported_from' => false, 'has_external_id' => false, 'columns' => []];
            }
        }

        $tableRows = Connection::select('SHOW TABLES');
        $allTables = [];
        foreach ($tableRows as $row) {
            $allTables[] = array_values($row)[0] ?? '';
        }
        $allTables = array_values(array_filter($allTables));
        sort($allTables);

        return Response::html((new View())->render('import::admin.schema', [
            'title'  => 'Import Şema Kontrolü',
            'report' => $report,
            'allTables' => $allTables,
        ]));
    }

    public function schemaAutoFix(Request $request): Response
    {
        $fixed = ImportService::ensureImportSchema();
        SessionManager::flash('success', $fixed ? ('✓ Düzeltildi: ' . implode(' | ', $fixed)) : 'Zaten her şey tamamdı, eksik sütun bulunamadı.');
        return Response::redirect('/admin/veri-aktarimi/sema-kontrol');
    }

    /** Elle tablo/sütun ekleme — sadece güvenli, nullable ADD COLUMN. */
    public function schemaManualAdd(Request $request): Response
    {
        $table = trim((string) $request->input('table', ''));
        $column = trim((string) $request->input('column', ''));
        $type = strtoupper(trim((string) $request->input('type', 'VARCHAR')));
        $length = (int) $request->input('length', 191);

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            SessionManager::flash('error', 'Tablo/sütun adı geçersiz karakter içeriyor (sadece harf, rakam, alt çizgi).');
            return Response::redirect('/admin/veri-aktarimi/sema-kontrol');
        }

        $allowedTypes = ['VARCHAR', 'TEXT', 'INT', 'BIGINT', 'DECIMAL', 'DATE', 'DATETIME', 'TINYINT', 'JSON'];
        if (!in_array($type, $allowedTypes, true)) {
            SessionManager::flash('error', 'Desteklenmeyen tip: ' . $type);
            return Response::redirect('/admin/veri-aktarimi/sema-kontrol');
        }

        try {
            $tableRows = Connection::select('SHOW TABLES');
            $realTables = [];
            foreach ($tableRows as $row) {
                $realTables[] = array_values($row)[0] ?? '';
            }
            if (!in_array($table, $realTables, true)) {
                SessionManager::flash('error', "Böyle bir tablo yok: $table");
                return Response::redirect('/admin/veri-aktarimi/sema-kontrol');
            }
            $existingCols = array_column(Connection::select("SHOW COLUMNS FROM `$table`"), 'Field');
            if (in_array($column, $existingCols, true)) {
                SessionManager::flash('error', "'$column' sütunu zaten var, tekrar eklenmedi.");
                return Response::redirect('/admin/veri-aktarimi/sema-kontrol');
            }

            $sqlType = match ($type) {
                'VARCHAR' => "VARCHAR(" . max(1, min(1000, $length)) . ")",
                'DECIMAL' => "DECIMAL(14,4)",
                default   => $type,
            };
            // Her zaman NULL / opsiyonel eklenir — mevcut satırları bozmaz.
            Connection::query("ALTER TABLE `$table` ADD COLUMN `$column` $sqlType NULL");

            \App\Services\Logger\ActivityLog::log('schema.manual_add', 'database', 0, "Elle sütun eklendi: $table.$column ($sqlType)");
            SessionManager::flash('success', "✓ `$table`.`$column` ($sqlType) eklendi.");
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Eklenemedi: ' . $e->getMessage());
        }

        return Response::redirect('/admin/veri-aktarimi/sema-kontrol');
    }

    private static function extractConfig(Request $request, array $fields): array
    {
        $config = [];
        foreach ($fields as $key => $spec) {
            $config[$key] = $request->input("config_$key", $spec['default'] ?? '');
        }

        return $config;
    }
}
