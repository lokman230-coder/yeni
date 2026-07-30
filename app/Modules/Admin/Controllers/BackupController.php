<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Admin\Services\BackupService;
use App\Services\Logger\ActivityLog;

final class BackupController
{
    public function index(Request $request): Response
    {
        $view = new View();
        return Response::html($view->render('admin::backup.index', [
            'title'   => 'Yedekleme',
            'backups' => BackupService::listBackups(),
            'success' => flash('success'),
            'error'   => flash('error'),
        ]));
    }

    public function createDb(Request $request): Response
    {
        $r = BackupService::createDbBackup();
        if ($r['ok']) {
            ActivityLog::log('created', 'backup', null, "DB backup: {$r['file']} (" . self::humanSize($r['size']) . ")");
            SessionManager::flash('success', "✓ DB yedeği oluşturuldu: {$r['file']} (" . self::humanSize($r['size']) . ")");
        } else {
            SessionManager::flash('error', 'DB yedekleme başarısız: ' . ($r['error'] ?? ''));
        }
        return Response::redirect('/admin/yedekleme');
    }

    public function createStorage(Request $request): Response
    {
        $r = BackupService::createStorageBackup();
        if ($r['ok']) {
            ActivityLog::log('created', 'backup', null, "Storage backup: {$r['file']} (" . self::humanSize($r['size']) . ")");
            SessionManager::flash('success', "✓ Storage yedeği oluşturuldu: {$r['file']} (" . self::humanSize($r['size']) . ")");
        } else {
            SessionManager::flash('error', 'Storage yedekleme başarısız: ' . ($r['error'] ?? ''));
        }
        return Response::redirect('/admin/yedekleme');
    }

    public function download(Request $request): Response
    {
        $name = (string) $request->param('name');
        $path = BackupService::backupPath($name);
        if (!$path) return Response::notFound('Yedek bulunamadı');
        return Response::make(file_get_contents($path), 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'Content-Length'      => (string) filesize($path),
        ]);
    }

    public function delete(Request $request): Response
    {
        $name = (string) $request->param('name');
        if (BackupService::deleteBackup($name)) {
            ActivityLog::log('deleted', 'backup', null, "Yedek silindi: $name");
            SessionManager::flash('success', "$name silindi.");
        } else {
            SessionManager::flash('error', 'Silinemedi.');
        }
        return Response::redirect('/admin/yedekleme');
    }

    private static function humanSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }
}
