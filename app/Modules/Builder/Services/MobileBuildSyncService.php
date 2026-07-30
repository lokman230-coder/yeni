<?php

declare(strict_types=1);

namespace App\Modules\Builder\Services;

use App\Core\Database\Connection;
use App\Services\Logger\Logger;

final class MobileBuildSyncService
{
    public static function run(): array
    {
        self::attachMissingGithubRuns();

        $jobs = Connection::select(
            "SELECT id, customer_id, github_run_id, github_artifact_id, status
             FROM mobile_build_jobs
             WHERE status IN ('running','queued') AND github_run_id IS NOT NULL
             ORDER BY id ASC LIMIT 50"
        );

        $done = 0;
        $failed = 0;
        foreach ($jobs as $job) {
            $r = GithubActionsBuildService::syncJob((int)$job['id'], (int)$job['github_run_id']);
            if (empty($r['ok']) || !in_array($r['status'] ?? '', ['completed', 'failed'], true)) {
                continue;
            }

            $r['status'] === 'completed' ? $done++ : $failed++;
            if ($r['status'] === 'completed' && !empty($r['artifact']['id'])) {
                GithubActionsBuildService::saveArtifact((int)$job['id'], (int)$r['artifact']['id']);
            }

            try {
                \App\Services\Notification\NotificationService::push(
                    'customer',
                    (int)($job['customer_id'] ?? 0),
                    'mobile_build',
                    $r['status'] === 'completed' ? 'Mobile build hazir' : 'Mobile build basarisiz',
                    $r['status'] === 'completed' ? 'APK/AAB/PWA dosyaniz indirilebilir.' : 'Build logunu kontrol edin.',
                    '/panel/mobile-buildler',
                    $r['status'] === 'completed' ? 'OK' : '!'
                );
            } catch (\Throwable) {
            }
        }

        Logger::info('Mobile build sync completed', ['jobs' => count($jobs), 'done' => $done, 'failed' => $failed]);
        return ['jobs' => count($jobs), 'done' => $done, 'failed' => $failed];
    }

    private static function attachMissingGithubRuns(): void
    {
        $jobs = Connection::select(
            "SELECT id FROM mobile_build_jobs
             WHERE status = 'running'
               AND github_run_id IS NULL
               AND error_log LIKE 'GitHub workflow tetiklendi%'
             ORDER BY id ASC LIMIT 5"
        );

        if (!$jobs) {
            return;
        }

        $latest = GithubActionsBuildService::latestRun();
        if (empty($latest['ok']) || empty($latest['run']['id'])) {
            return;
        }

        foreach ($jobs as $job) {
            Connection::update('mobile_build_jobs', [
                'github_run_id' => (int)$latest['run']['id'],
                'error_log' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [(int)$job['id']]);
            break;
        }
    }
}
