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

            $ordered = ['full_config', 'settings', 'servers', 'registrars', 'products', 'addons', 'custom_fields', 'customers', 'orders', 'invoices', 'domains', 'hosting', 'tickets'];
            foreach ($ordered as $type) {
                if (in_array($type, $types, true)) {
                    $jobId = ImportService::createJob($source, $config, $type, (int)($admin['id'] ?? 0));
                    ActivityLog::log('created', 'import_job', $jobId, "Import job: $source / $type");
                    $created++;
                }
            }

            SessionManager::flash('success', "$created import job olusturuldu. Calistir butonu ile baslatin.");
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

    private static function extractConfig(Request $request, array $fields): array
    {
        $config = [];
        foreach ($fields as $key => $spec) {
            $config[$key] = $request->input("config_$key", $spec['default'] ?? '');
        }

        return $config;
    }
}
