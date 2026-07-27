<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;

/**
 * Admin > Loglar (audit + api + cron + app log)
 */
final class LogsController
{
    public function index(Request $request): Response
    {
        $type = (string) $request->query('type', 'api');
        $data = [];
        try {
            $data = match ($type) {
                'audit'   => Connection::select("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 200"),
                'api'     => Connection::select("SELECT * FROM api_logs ORDER BY id DESC LIMIT 200"),
                'cron'    => Connection::select("SELECT * FROM cron_logs ORDER BY id DESC LIMIT 200"),
                'mail'    => Connection::select("SELECT id, to_email, subject, status, attempts, LEFT(error, 100) error, sent_at, created_at FROM mail_queue ORDER BY id DESC LIMIT 200"),
                'ai'      => Connection::select("SELECT id, context, user_type, user_id, LEFT(prompt, 60) prompt, LEFT(response, 80) response, tokens_used, latency_ms, created_at FROM ai_logs ORDER BY id DESC LIMIT 200"),
                'activity'=> Connection::select("SELECT id, admin_email, action, resource_type, resource_id, summary, ip, created_at FROM admin_activity_logs ORDER BY id DESC LIMIT 200"),
                default   => [],
            };
        } catch (\Throwable) {}

        // App log file (bugün)
        $appLog = '';
        if ($type === 'app') {
            $file = AHO_ROOT . '/storage/logs/app-' . date('Y-m-d') . '.log';
            if (file_exists($file)) $appLog = (string) file_get_contents($file);
        }

        $view = new View();
        return Response::html($view->render('admin::logs.index', [
            'title'   => 'Log Merkezi',
            'type'    => $type,
            'data'    => $data,
            'app_log' => $appLog,
        ]));
    }
}
