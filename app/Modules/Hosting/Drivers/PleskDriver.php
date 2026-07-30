<?php

declare(strict_types=1);

namespace App\Modules\Hosting\Drivers;

use App\Modules\Hosting\Contracts\HostingPanelInterface;
use App\Services\Logger\ApiLogger;

/**
 * Plesk REST API v2 sürücüsü.
 * https://docs.plesk.com/en-US/obsidian/api-rpc/
 */
final class PleskDriver implements HostingPanelInterface
{
    public function __construct(
        private string $hostname = '',
        private int    $port = 8443,
        private string $username = 'admin',
        private string $apiKey = '',
        private bool   $useSsl = true
    ) {}

    public function id(): string    { return 'plesk'; }
    public function label(): string { return 'Plesk'; }

    private function call(string $method, string $path, array $body = []): array
    {
        $scheme = $this->useSsl ? 'https' : 'http';
        $url = "{$scheme}://{$this->hostname}:{$this->port}/api/v2{$path}";
        $ch = curl_init($url);
        $headers = ['Content-Type: application/json'];
        if ($this->apiKey !== '') {
            $headers[] = 'X-API-Key: ' . $this->apiKey;
        } else {
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->username}:{$this->apiKey}");
        }
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER      => $headers,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_CUSTOMREQUEST   => $method,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_TIMEOUT         => 30,
        ]);
        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $start = microtime(true);
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        $duration = (int) ((microtime(true) - $start) * 1000);
        $decoded = $raw ? (json_decode((string)$raw, true) ?: []) : [];
        ApiLogger::log('plesk:' . $this->hostname, $path, $method, $body, $decoded, $code, $duration, $err ?: null);
        return ['code' => $code, 'body' => $decoded];
    }

    public function createAccount(array $request): array
    {
        $r = $this->call('POST', '/clients', [
            'name'     => $request['name'] ?? $request['username'] ?? '',
            'login'    => $request['username'] ?? '',
            'password' => $request['password'] ?? '',
            'email'    => $request['email'] ?? '',
            'type'     => 'customer',
        ]);
        $success = $r['code'] === 201;
        return ['success' => $success, 'username' => $request['username'] ?? '', 'message' => $r['body']['message'] ?? ''];
    }

    public function suspendAccount(string $username, string $reason = ''): bool
    {
        $r = $this->call('PUT', "/clients/{$username}", ['status' => 'suspended']);
        return $r['code'] < 400;
    }

    public function unsuspendAccount(string $username): bool
    {
        $r = $this->call('PUT', "/clients/{$username}", ['status' => 'active']);
        return $r['code'] < 400;
    }

    public function terminateAccount(string $username): bool
    {
        $r = $this->call('DELETE', "/clients/{$username}");
        return $r['code'] < 400;
    }

    public function changePassword(string $username, string $newPassword): bool
    {
        $r = $this->call('PUT', "/clients/{$username}", ['password' => $newPassword]);
        return $r['code'] < 400;
    }

    public function changePackage(string $username, string $newPackage): bool
    {
        $r = $this->call('PUT', "/clients/{$username}", ['plan' => $newPackage]);
        return $r['code'] < 400;
    }

    public function installCronJobs(string $username, array $jobs): array
    {
        $out = ['success' => true, 'installed' => 0, 'errors' => []];
        foreach ($jobs as $job) {
            $r = $this->call('POST', '/cli/scheduled-task/call', [
                'params' => [
                    '--create',
                    $username,
                    '-command',
                    $job['command'] ?? '',
                    '-minute',
                    $job['minute'] ?? '*',
                    '-hour',
                    $job['hour'] ?? '*',
                    '-dom',
                    $job['day'] ?? '*',
                    '-month',
                    $job['month'] ?? '*',
                    '-dow',
                    $job['weekday'] ?? '*',
                ],
            ]);
            if ($r['code'] < 400) {
                $out['installed']++;
                continue;
            }
            $out['success'] = false;
            $out['errors'][] = (string)($r['body']['message'] ?? 'Plesk cron eklenemedi');
        }
        return $out;
    }

    public function getUsage(string $username): array
    {
        $r = $this->call('GET', "/clients/{$username}/resource-usage");
        return [
            'disk_mb'      => isset($r['body']['disk_usage']) ? (int)($r['body']['disk_usage'] / 1024 / 1024) : null,
            'bandwidth_mb' => isset($r['body']['traffic']) ? (int)($r['body']['traffic'] / 1024 / 1024) : null,
            'status'       => $r['body']['status'] ?? 'active',
        ];
    }

    public function testConnection(): array
    {
        $r = $this->call('GET', '/server');
        return ['success' => $r['code'] < 400, 'raw' => $r['body']];
    }
}
