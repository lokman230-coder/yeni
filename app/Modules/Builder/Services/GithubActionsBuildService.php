<?php
declare(strict_types=1);

namespace App\Modules\Builder\Services;

use App\Core\Database\Connection;
use App\Services\Settings\SettingsManager;
use ZipArchive;

final class GithubActionsBuildService
{
    private static function config(): array
    {
        return [
            'owner' => (string) SettingsManager::get('mobile.github_owner', ''),
            'repo' => (string) SettingsManager::get('mobile.github_repo', ''),
            'token' => (string) SettingsManager::get('mobile.github_token', ''),
            'workflow' => (string) SettingsManager::get('mobile.github_workflow', 'mobile-build.yml'),
            'branch' => (string) SettingsManager::get('mobile.github_branch', 'main'),
        ];
    }

    private static function request(string $url, string $method = 'GET', ?array $payload = null, int $timeout = 25): array
    {
        $config = self::config();

        if ($config['owner'] === '' || $config['repo'] === '' || $config['token'] === '') {
            return ['ok' => false, 'error' => 'GitHub settings are incomplete'];
        }

        $headers = [
            'Accept: application/vnd.github+json',
            'Authorization: Bearer ' . $config['token'],
            'X-GitHub-Api-Version: 2022-11-28',
            'User-Agent: Ahost-One',
        ];

        $options = [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
        ];

        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
            $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $data = $raw ? json_decode((string) $raw, true) : [];

        return [
            'ok' => $code >= 200 && $code < 300,
            'status' => $code,
            'data' => is_array($data) ? $data : [],
            'body' => $raw,
            'error' => $err ?: ($data['message'] ?? null),
        ];
    }

    public static function dispatch(string $buildType, string $projectPath, string $appName, string $packageName): array
    {
        $config = self::config();
        $url = "https://api.github.com/repos/{$config['owner']}/{$config['repo']}/actions/workflows/"
            . rawurlencode($config['workflow']) . '/dispatches';

        return self::request($url, 'POST', [
            'ref' => $config['branch'],
            'inputs' => [
                'build_type' => $buildType,
                'project_path' => $projectPath,
                'app_name' => $appName,
                'package_name' => $packageName,
            ],
        ]);
    }

    public static function latestRun(): array
    {
        $config = self::config();
        $url = "https://api.github.com/repos/{$config['owner']}/{$config['repo']}/actions/workflows/"
            . rawurlencode($config['workflow'])
            . '/runs?event=workflow_dispatch&branch=' . rawurlencode($config['branch'])
            . '&per_page=1';

        $result = self::request($url);
        $result['run'] = $result['data']['workflow_runs'][0] ?? null;

        return $result;
    }

    public static function artifacts(int $runId): array
    {
        $config = self::config();
        $url = "https://api.github.com/repos/{$config['owner']}/{$config['repo']}/actions/runs/{$runId}/artifacts";
        $result = self::request($url);
        $result['artifacts'] = $result['data']['artifacts'] ?? [];

        return $result;
    }

    public static function syncJob(int $jobId, int $runId): array
    {
        $config = self::config();
        $run = self::request("https://api.github.com/repos/{$config['owner']}/{$config['repo']}/actions/runs/{$runId}");

        if (!$run['ok']) {
            return $run;
        }

        $job = Connection::selectOne('SELECT build_type FROM mobile_build_jobs WHERE id = ?', [$jobId]) ?: [];
        $buildType = (string) ($job['build_type'] ?? '');
        $status = (string) ($run['data']['status'] ?? 'queued');
        $conclusion = $run['data']['conclusion'] ?? null;
        $mapped = $status === 'completed' ? ($conclusion === 'success' ? 'completed' : 'failed') : 'running';

        $artifactList = self::artifacts($runId);
        $artifact = self::selectArtifact($artifactList['artifacts'] ?? [], $buildType);

        Connection::update('mobile_build_jobs', [
            'github_run_id' => $runId,
            'github_artifact_id' => $artifact['id'] ?? null,
            'status' => $mapped,
            'progress' => $mapped === 'completed' ? 100 : ($mapped === 'failed' ? 0 : 50),
            'error_log' => $mapped === 'failed' ? 'GitHub Actions build failed.' : null,
            'completed_at' => in_array($mapped, ['completed', 'failed'], true) ? date('Y-m-d H:i:s') : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$jobId]);

        return ['ok' => true, 'status' => $mapped, 'artifact' => $artifact];
    }

    public static function saveArtifact(int $jobId, int $artifactId): array
    {
        $download = self::downloadArtifact($artifactId);

        if (!$download['ok']) {
            return $download;
        }

        $job = Connection::selectOne('SELECT build_type FROM mobile_build_jobs WHERE id = ?', [$jobId]) ?: [];
        $buildType = (string) ($job['build_type'] ?? 'zip');
        $dir = AHO_ROOT . '/storage/mobile-builds/' . $jobId;
        @mkdir($dir, 0775, true);

        $zipPath = $dir . '/artifact.zip';
        if (file_put_contents($zipPath, (string) $download['body'], LOCK_EX) === false) {
            return ['ok' => false, 'error' => 'Artifact storage could not be written'];
        }

        $finalPath = self::extractBuildFile($zipPath, $dir, $buildType) ?: $zipPath;

        Connection::update('mobile_build_jobs', [
            'artifact_path' => $finalPath,
            'status' => 'completed',
            'progress' => 100,
            'completed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$jobId]);

        return ['ok' => true, 'path' => $finalPath, 'zip_path' => $zipPath];
    }

    public static function downloadArtifact(int $artifactId): array
    {
        $config = self::config();

        if ($config['owner'] === '' || $config['repo'] === '' || $config['token'] === '') {
            return ['ok' => false, 'error' => 'GitHub settings are incomplete'];
        }

        $url = "https://api.github.com/repos/{$config['owner']}/{$config['repo']}/actions/artifacts/{$artifactId}/zip";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Accept: application/vnd.github+json',
                'Authorization: Bearer ' . $config['token'],
                'X-GitHub-Api-Version: 2022-11-28',
                'User-Agent: Ahost-One',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        return [
            'ok' => $code === 200,
            'status' => $code,
            'body' => $raw,
            'error' => $err ?: ($code === 200 ? null : 'Artifact could not be downloaded'),
        ];
    }

    private static function selectArtifact(array $artifacts, string $buildType): ?array
    {
        if ($artifacts === []) {
            return null;
        }

        $needle = $buildType === 'aab' ? 'aab' : ($buildType === 'apk' ? 'apk' : '');

        foreach ($artifacts as $artifact) {
            $name = strtolower((string) ($artifact['name'] ?? ''));
            if ($needle !== '' && str_contains($name, $needle)) {
                return $artifact;
            }
        }

        return $artifacts[0];
    }

    private static function extractBuildFile(string $zipPath, string $dir, string $buildType): ?string
    {
        if (!class_exists(ZipArchive::class) || !is_file($zipPath)) {
            return null;
        }

        $extensions = match ($buildType) {
            'apk' => ['apk'],
            'aab' => ['aab'],
            default => ['zip', 'apk', 'aab'],
        };

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return null;
        }

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if (!in_array($ext, $extensions, true)) {
                    continue;
                }

                $base = preg_replace('/[^A-Za-z0-9._-]+/', '-', basename($name));
                $target = $dir . '/' . ($base !== '' ? $base : ('mobile-build.' . $ext));
                $stream = $zip->getStream($name);

                if ($stream === false) {
                    continue;
                }

                $out = fopen($target, 'wb');
                if ($out === false) {
                    fclose($stream);
                    continue;
                }

                stream_copy_to_stream($stream, $out);
                fclose($stream);
                fclose($out);

                return $target;
            }
        } finally {
            $zip->close();
        }

        return null;
    }
}
