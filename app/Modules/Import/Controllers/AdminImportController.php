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
use App\Support\Encrypter;

/**
 * Admin > Veri Aktarımı ekranı.
 * /admin/veri-aktarimi
 */
final class AdminImportController
{
    /** Ana ekran: kaynak seç + son job'lar listesi */
    public function index(Request $request): Response
    {
        $recentJobs = Connection::select(
            "SELECT id, source, type, status, total, imported, skipped, errors, created_at, completed_at
             FROM import_jobs ORDER BY id DESC LIMIT 30"
        );
        $view = new View();
        return Response::html($view->render('import::admin.index', [
            'title'   => 'Veri Aktarımı',
            'sources' => ImportManager::all(),
            'jobs'    => $recentJobs,
            'success' => flash('success'),
            'error'   => flash('error'),
        ]));
    }

    /** Kaynak seçildikten sonra config form */
    public function connect(Request $request): Response
    {
        $source = (string) $request->param('source');
        $driver = ImportManager::driver($source);
        if (!$driver) return Response::notFound('Bilinmeyen kaynak');
        $view = new View();
        return Response::html($view->render('import::admin.connect', [
            'title'  => 'Bağlantı: ' . $driver->label(),
            'source' => $source,
            'driver' => $driver,
            'fields' => $driver->configFields(),
            'test'   => SessionManager::getFlash('_import_test'),
            'counts' => SessionManager::getFlash('_import_counts'),
            'config' => SessionManager::getFlash('_import_config', []),
        ]));
    }

    /** Bağlantı testi (AJAX değil, form submit — sonucu ekranda gösterir) */
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

    /** Import job başlat */
    public function startImport(Request $request): Response
    {
        $source = (string) $request->param('source');
        $driver = ImportManager::driver($source);
        if (!$driver) return Response::notFound();
        $config = self::extractConfig($request, $driver->configFields());
        $types = (array) $request->input('types', []);
        $admin = AuthService::admin();
        $created = 0;

        // ÖNEMLİ sıra: customer → product → order → invoice → domain → hosting → ticket
        $ordered = ['customers', 'products', 'orders', 'invoices', 'domains', 'hosting', 'tickets'];
        foreach ($ordered as $t) {
            if (in_array($t, $types, true)) {
                $jobId = ImportService::createJob($source, $config, $t, (int)($admin['id'] ?? 0));
                ActivityLog::log('created', 'import_job', $jobId, "Import job: $source / $t");
                $created++;
            }
        }
        SessionManager::flash('success', "$created import job oluşturuldu. 'Çalıştır' butonu ile başlatın.");
        return Response::redirect('/admin/veri-aktarimi');
    }

    /** Job'ı çalıştır (batch işle) */
    public function runJob(Request $request): Response
    {
        $id = (int) $request->param('id');
        try {
            $r = ImportService::runJob($id, 50);
            if ($r['done']) {
                SessionManager::flash('success', "✓ Job #$id tamamlandı: {$r['imported']} ithal, {$r['skipped']} atlandı, {$r['errors']} hata.");
            } else {
                SessionManager::flash('success', "Job #$id devam ediyor: {$r['imported']} kayıt işlendi. 'Devam Et' ile sürdürün.");
            }
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Hata: ' . $e->getMessage());
        }
        return Response::redirect('/admin/veri-aktarimi');
    }

    /** Job detay + hata log */
    public function jobDetail(Request $request): Response
    {
        $id = (int) $request->param('id');
        $job = Connection::selectOne("SELECT * FROM import_jobs WHERE id = ?", [$id]);
        if (!$job) return Response::notFound();
        $view = new View();
        return Response::html($view->render('import::admin.job', [
            'title' => "Job #$id · {$job['source']} / {$job['type']}",
            'job'   => $job,
        ]));
    }

    /** Job sil (mapping'ler kalır — duplicate önleme için) */
    public function deleteJob(Request $request): Response
    {
        $id = (int) $request->param('id');
        Connection::delete('import_jobs', 'id = ?', [$id]);
        SessionManager::flash('success', "Job #$id silindi (import edilen kayıtlar korundu).");
        return Response::redirect('/admin/veri-aktarimi');
    }

    private static function extractConfig(Request $request, array $fields): array
    {
        $config = [];
        foreach ($fields as $key => $spec) {
            $val = $request->input("config_$key", $spec['default'] ?? '');
            $config[$key] = $val;
        }
        return $config;
    }
}
