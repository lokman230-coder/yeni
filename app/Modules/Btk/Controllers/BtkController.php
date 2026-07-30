<?php

declare(strict_types=1);

namespace App\Modules\Btk\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;
use App\Modules\Btk\Services\BtkExporter;
use App\Services\Auth\AuthService;

final class BtkController
{
    public function index(Request $request): Response
    {
        $view = new View();
        $exports = [];
        try {
            $exports = Connection::select("SELECT * FROM btk_exports ORDER BY id DESC LIMIT 20");
        } catch (\Throwable) {}
        return Response::html($view->render('btk::index', [
            'title'   => 'BTK / Yer Sağlayıcı Raporu',
            'exports' => $exports,
        ]));
    }

    public function download(Request $request): Response
    {
        $type = (string) $request->param('type');
        if (!in_array($type, ['hosting','domains','customers','all'], true)) {
            return Response::notFound();
        }

        $dir = AHO_ROOT . '/storage/btk-exports';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $fileName = 'btk-' . $type . '-' . date('YmdHis') . '.csv';
        $path = $dir . '/' . $fileName;

        $result = BtkExporter::generateCsv($type, $path);
        if (!$result['ok']) return Response::error('Export başarısız: ' . ($result['error'] ?? ''));

        $admin = AuthService::admin();
        try {
            Connection::insert('btk_exports', [
                'admin_id'   => (int)($admin['id'] ?? 0) ?: null,
                'type'       => $type,
                'file_path'  => $path,
                'row_count'  => $result['row_count'],
                'size_bytes' => $result['size_bytes'],
            ]);
        } catch (\Throwable) {}

        return Response::make(
            (string) file_get_contents($path),
            200,
            [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]
        );
    }
}
