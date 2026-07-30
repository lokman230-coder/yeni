<?php
declare(strict_types=1);

namespace App\Modules\Builder\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\Auth\AuthService;

final class MobileBuildController
{
    public function status(Request $request): Response
    {
        if (!AuthService::isCustomer()) {
            return Response::json(['ok' => false], 401);
        }

        $customer = AuthService::customer();
        $id = (int) $request->param('id');
        $job = Connection::selectOne(
            'SELECT id, build_type, status, progress, artifact_path, error_log, updated_at
             FROM mobile_build_jobs WHERE id = ? AND customer_id = ?',
            [$id, $customer['id']]
        );

        if (!$job) {
            return Response::json(['ok' => false, 'error' => 'not_found'], 404);
        }

        $job['ready'] = !empty($job['artifact_path'])
            && is_file((string) $job['artifact_path'])
            && $job['status'] === 'completed';

        return Response::json(['ok' => true, 'job' => $job]);
    }

    public function index(Request $request): Response
    {
        if (!AuthService::isCustomer()) {
            return Response::redirect('/giris');
        }

        $customer = AuthService::customer();
        $jobs = Connection::select(
            'SELECT * FROM mobile_build_jobs WHERE customer_id = ? ORDER BY id DESC',
            [$customer['id']]
        );

        return Response::html((new \App\Core\View())->render('builder::mobile-builds', [
            'title' => 'Mobile Buildlerim',
            'jobs' => $jobs,
        ]));
    }

    public function download(Request $request): Response
    {
        if (!AuthService::isCustomer()) {
            return Response::redirect('/giris');
        }

        $customer = AuthService::customer();
        $id = (int) $request->param('id');
        $job = Connection::selectOne(
            'SELECT * FROM mobile_build_jobs WHERE id = ? AND customer_id = ?',
            [$id, $customer['id']]
        );

        if (!$job || empty($job['artifact_path']) || !is_file((string) $job['artifact_path'])) {
            return Response::notFound('Build file is not ready yet.');
        }

        if (($job['status'] ?? '') !== 'completed') {
            return Response::notFound('Build file is not ready yet.');
        }

        if (!empty($job['invoice_id'])) {
            $invoice = Connection::selectOne('SELECT status FROM invoices WHERE id = ?', [$job['invoice_id']]);
            if (!$invoice || $invoice['status'] !== 'paid') {
                return Response::notFound('Payment is required before download.');
            }
        }

        $path = (string) $job['artifact_path'];
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $type = strtolower((string) ($job['build_type'] ?? $ext));
        $safeExt = $ext !== '' ? $ext : ($type === 'aab' ? 'aab' : ($type === 'apk' ? 'apk' : 'zip'));
        $name = 'ahost-mobile-build-' . $id . '.' . $safeExt;
        $mime = match ($safeExt) {
            'apk' => 'application/vnd.android.package-archive',
            'aab' => 'application/octet-stream',
            'zip' => 'application/zip',
            default => 'application/octet-stream',
        };

        return Response::make((string) file_get_contents($path), 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'Content-Length' => (string) filesize($path),
        ]);
    }
}
