<?php

declare(strict_types=1);

namespace App\Modules\Builder\Services;

use App\Core\Database\Connection;
use App\Services\Settings\SettingsManager;

final class MobileBuildService
{
    public static function create(int $customerId, int $projectId, string $type = 'source'): int
    {
        $allowed = ['source', 'pwa'];
        if ($type === 'apk' && (bool) SettingsManager::get('mobile.enable_apk', false)) $allowed[] = 'apk';
        if ($type === 'aab' && (bool) SettingsManager::get('mobile.enable_aab', false)) $allowed[] = 'aab';
        if ($type === 'source' && !(bool) SettingsManager::get('mobile.enable_source', true)) $type = 'pwa';
        if (!in_array($type, $allowed, true)) $type = 'source';

        return Connection::insert('mobile_build_jobs', [
            'customer_id' => $customerId,
            'project_id' => $projectId,
            'build_type' => $type,
            'status' => 'queued',
            'progress' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function dispatch(int $id): array
    {
        $job = Connection::selectOne('SELECT * FROM mobile_build_jobs WHERE id = ?', [$id]);
        if (!$job) return ['ok' => false, 'error' => 'job_not_found'];

        $url = rtrim((string) SettingsManager::get('mobile.worker_url', ''), '/');
        $key = (string) SettingsManager::get('mobile.worker_key', '');

        if ($url !== '' && $key !== '') {
            return self::dispatchWorker($job, $url, $key);
        }

        if (self::hasGithubConfig()) {
            return self::dispatchGithub($job);
        }

        if (in_array($job['build_type'], ['source', 'pwa'], true)) {
            Connection::update('mobile_build_jobs', [
                'status' => 'local_ready',
                'progress' => 100,
                'completed_at' => date('Y-m-d H:i:s'),
                'error_log' => 'Worker/GitHub yok; yerel kaynak/PWA modu hazir.',
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);
            return ['ok' => true, 'mode' => 'local', 'status' => 'local_ready'];
        }

        Connection::update('mobile_build_jobs', [
            'status' => 'waiting_worker',
            'error_log' => 'APK/AAB icin Mobile Build Worker veya GitHub Actions ayarlari gerekli.',
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
        return ['ok' => false, 'error' => 'build_backend_not_configured', 'fallback' => ['source', 'pwa']];
    }

    public static function status(int $id): ?array
    {
        return Connection::selectOne('SELECT * FROM mobile_build_jobs WHERE id = ?', [$id]);
    }

    private static function dispatchWorker(array $job, string $url, string $key): array
    {
        $payload = [
            'job_id' => (int) $job['id'],
            'build_type' => $job['build_type'],
            'project_id' => (int) $job['project_id'],
        ];

        $ch = curl_init($url . '/build');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Worker-Key: ' . $key],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $data = $raw ? json_decode((string) $raw, true) : [];
        if ($code >= 400 || !is_array($data) || empty($data['job_id'])) {
            Connection::update('mobile_build_jobs', [
                'status' => 'failed',
                'error_log' => $err ?: ($data['error'] ?? 'Worker baglanti hatasi'),
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [(int) $job['id']]);
            return ['ok' => false, 'error' => $err ?: ($data['error'] ?? 'worker_error')];
        }

        Connection::update('mobile_build_jobs', [
            'status' => 'running',
            'progress' => 5,
            'worker_job_id' => (string) $data['job_id'],
            'started_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $job['id']]);

        return ['ok' => true, 'mode' => 'worker', 'worker_job_id' => $data['job_id']];
    }

    private static function dispatchGithub(array $job): array
    {
        $project = Connection::selectOne('SELECT * FROM builder_projects WHERE id = ?', [(int) $job['project_id']]) ?: [];
        $appName = (string) ($project['name'] ?? ('Ahost Mobile #' . $job['id']));
        $packageName = self::packageName($project, (int) $job['id']);
        $projectPath = trim((string) SettingsManager::get('mobile.github_project_path', ''));
        if ($projectPath === '') {
            $projectPath = 'projects/' . (int) $job['project_id'];
        }
        $projectPath = strtr($projectPath, [
            '{project_id}' => (string) (int) $job['project_id'],
            '{job_id}' => (string) (int) $job['id'],
        ]);

        $result = GithubActionsBuildService::dispatch((string) $job['build_type'], $projectPath, $appName, $packageName);
        if (empty($result['ok'])) {
            Connection::update('mobile_build_jobs', [
                'status' => 'failed',
                'error_log' => 'GitHub Actions dispatch failed: ' . (string) ($result['error'] ?? 'unknown'),
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [(int) $job['id']]);
            return ['ok' => false, 'mode' => 'github', 'error' => $result['error'] ?? 'github_dispatch_failed'];
        }

        $runId = null;
        sleep(2);
        $latest = GithubActionsBuildService::latestRun();
        if (!empty($latest['ok']) && !empty($latest['run']['id'])) {
            $runId = (int) $latest['run']['id'];
        }

        Connection::update('mobile_build_jobs', [
            'status' => 'running',
            'progress' => 5,
            'github_run_id' => $runId,
            'started_at' => date('Y-m-d H:i:s'),
            'error_log' => $runId ? null : 'GitHub workflow tetiklendi; run id sonraki senkronizasyonda bekleniyor.',
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $job['id']]);

        return ['ok' => true, 'mode' => 'github', 'github_run_id' => $runId];
    }

    private static function hasGithubConfig(): bool
    {
        return (string) SettingsManager::get('mobile.github_owner', '') !== ''
            && (string) SettingsManager::get('mobile.github_repo', '') !== ''
            && (string) SettingsManager::get('mobile.github_token', '') !== '';
    }

    private static function packageName(array $project, int $jobId): string
    {
        $base = strtolower((string) ($project['slug'] ?? $project['name'] ?? ('app-' . $jobId)));
        $base = preg_replace('/[^a-z0-9]+/', '.', $base) ?: ('app.' . $jobId);
        $base = trim($base, '.');
        if (!str_contains($base, '.')) {
            $base = 'app.' . $base;
        }
        return 'com.ahostone.' . $base;
    }
}
