<?php

declare(strict_types=1);

namespace App\Modules\Builder\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Builder\Services\MobileExportService;
use App\Services\Auth\AuthService;

/**
 * Site Builder ZIP + Mobile Builder APK/AAB/Kaynak kod satın alma ve indirme.
 */
final class ExportController
{
    public function index(Request $request): Response
    {
        if (!AuthService::isCustomer()) return Response::redirect('/giris');
        $customer = AuthService::customer();
        $projectId = (int) $request->param('id');

        $project = Connection::selectOne(
            "SELECT * FROM builder_projects WHERE id = ? AND customer_id = ?",
            [$projectId, $customer['id']]
        );
        if (!$project) return Response::notFound();

        $jobs = Connection::select(
            "SELECT * FROM builder_export_jobs WHERE project_id = ? ORDER BY id DESC",
            [$projectId]
        );

        return Response::html((new View())->render('builder::export', [
            'title'   => 'Export & İndirme — ' . $project['name'],
            'project' => $project,
            'jobs'    => $jobs,
        ]));
    }

    public function request(Request $request): Response
    {
        if (!AuthService::isCustomer()) return Response::redirect('/giris');
        $customer = AuthService::customer();
        $projectId = (int) $request->param('id');
        $type = (string) $request->input('export_type', 'site_zip');

        $project = Connection::selectOne("SELECT * FROM builder_projects WHERE id = ? AND customer_id = ?", [$projectId, $customer['id']]);
        if (!$project) return Response::notFound();

        $allowed = ['site_zip','mobile_apk','mobile_aab','flutter_source','react_native_source','android_source'];
        if (!in_array($type, $allowed, true)) {
            SessionManager::flash('error', 'Geçersiz export tipi.');
            return Response::redirect("/panel/builder/$projectId/export");
        }

        // site_zip → ücretsiz, hemen çalıştır
        if ($type === 'site_zip') {
            $result = MobileExportService::queueBuild($projectId, (int)$customer['id'], $type);
            MobileExportService::processJob($result['job_id']);
            SessionManager::flash('success', '✓ ZIP hazırlandı, indirebilirsin.');
            return Response::redirect("/panel/builder/$projectId/export");
        }

        // Diğer paid export'lar → fatura oluştur, ödeme sonrası queue'ya düşür
        $result = MobileExportService::queueBuild($projectId, (int)$customer['id'], $type);
        $price = (float) $result['price'];

        if ($price <= 0) {
            MobileExportService::processJob($result['job_id']);
            SessionManager::flash('success', '✓ Export hazırlanıyor.');
            return Response::redirect("/panel/builder/$projectId/export");
        }

        // Fatura oluştur → checkout
        try {
            $orderId = Connection::insert('orders', [
                'order_number' => 'EXP-' . date('YmdHis') . '-' . random_int(100, 999),
                'customer_id'  => (int)$customer['id'],
                'status'       => 'pending',
                'subtotal'     => $price,
                'total'        => $price,
                'currency'     => 'TRY',
                'notes'        => "Builder Export: $type (project #$projectId)",
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

            $labels = [
                'mobile_apk' => 'Android APK',
                'mobile_aab' => 'Android AAB',
                'flutter_source' => 'Flutter Kaynak Kod',
                'react_native_source' => 'React Native Kaynak Kod',
                'android_source' => 'Native Android Kaynak Kod',
            ];

            Connection::insert('order_items', [
                'order_id'     => $orderId,
                'product_id'   => 0,
                'product_name' => 'Builder Export: ' . ($labels[$type] ?? $type),
                'period'       => 'onetime',
                'quantity'     => 1,
                'unit_price'   => $price,
                'line_total'   => $price,
                'currency'     => 'TRY',
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

            $invId = Connection::insert('invoices', [
                'invoice_number' => 'EXP-' . date('YmdHis'),
                'order_id'       => $orderId,
                'customer_id'    => (int)$customer['id'],
                'status'         => 'unpaid',
                'issue_date'     => date('Y-m-d'),
                'due_date'       => date('Y-m-d', time() + 7 * 86400),
                'subtotal'       => $price,
                'total'          => $price,
                'balance'        => $price,
                'currency'       => 'TRY',
                'notes'          => "Export job #{$result['job_id']}",
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            // Job'ı fatura ile eşle
            Connection::update('builder_export_jobs',
                ['invoice_id' => $invId, 'updated_at' => date('Y-m-d H:i:s')],
                'id = ?', [$result['job_id']]
            );

            SessionManager::flash('info', "Ödeme sonrası export otomatik hazırlanacak.");
            return Response::redirect("/odeme/$invId");
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Sipariş oluşturulamadı: ' . $e->getMessage());
            return Response::redirect("/panel/builder/$projectId/export");
        }
    }

    public function download(Request $request): Response
    {
        if (!AuthService::isCustomer()) return Response::redirect('/giris');
        $customer = AuthService::customer();
        $jobId = (int) $request->param('id');

        $job = Connection::selectOne(
            "SELECT * FROM builder_export_jobs WHERE id = ? AND customer_id = ?",
            [$jobId, $customer['id']]
        );
        if (!$job) return Response::notFound();
        if ($job['status'] !== 'ready' || !$job['output_path'] || !is_file($job['output_path'])) {
            return Response::notFound('Dosya henüz hazır değil.');
        }

        // Ödeme kontrolü (invoice varsa paid mi?)
        if ($job['invoice_id']) {
            $inv = Connection::selectOne("SELECT status FROM invoices WHERE id = ?", [$job['invoice_id']]);
            if (!$inv || $inv['status'] !== 'paid') {
                SessionManager::flash('error', 'Ödeme tamamlanmadan indiremezsin.');
                return Response::redirect('/panel/faturalarim');
            }
        }

        // Downloaded işaretle
        Connection::update('builder_export_jobs', ['status' => 'downloaded', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$jobId]);

        $fileName = basename($job['output_path']);
        return Response::make(
            (string) file_get_contents($job['output_path']),
            200,
            [
                'Content-Type'        => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Content-Length'      => (string) filesize($job['output_path']),
            ]
        );
    }
}
