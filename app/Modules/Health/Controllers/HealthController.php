<?php

declare(strict_types=1);

namespace App\Modules\Health\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;
use App\Modules\Health\Services\HealthCheck;
use App\Modules\Health\Services\QaScanner;
use App\Modules\Health\Services\UptimeProbe;

final class HealthController
{
    public function index(Request $request): Response
    {
        $view = new View();
        $appUrl = (string) env('APP_URL', 'http://localhost');
        $probeUrls = array_filter([
            $appUrl,
            $appUrl . '/domain',
            $appUrl . '/hosting',
        ]);
        return Response::html($view->render('health::dashboard', [
            'title'  => 'Health Center',
            'checks' => HealthCheck::all(),
            'probes' => UptimeProbe::checkMany($probeUrls, 8),
        ]));
    }

    public function qa(Request $request): Response
    {
        $view = new View();
        return Response::html($view->render('health::qa', [
            'title'   => 'QA Scan Center',
            'summary' => QaScanner::summary(),
            'routes'  => QaScanner::routes(),
        ]));
    }

    /** Public healthcheck endpoint (uptime monitor için). */
    public function ping(Request $request): Response
    {
        $db = HealthCheck::database();
        $ok = $db['status'] === 'ok';
        return Response::json([
            'status' => $ok ? 'ok' : 'down',
            'db'     => $db['status'],
            'time'   => date('c'),
            'uptime_boot_ms' => (int) ((microtime(true) - AHO_START) * 1000),
        ], $ok ? 200 : 503);
    }
}
