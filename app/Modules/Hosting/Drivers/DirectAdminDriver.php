<?php

declare(strict_types=1);

namespace App\Modules\Hosting\Drivers;

use App\Modules\Hosting\Contracts\HostingPanelInterface;
use App\Services\Logger\ApiLogger;

/**
 * DirectAdmin CMD_API sürücüsü.
 * Kimlik: username + password (veya login key).
 */
final class DirectAdminDriver implements HostingPanelInterface
{
    public function __construct(
        private string $hostname = '',
        private int    $port = 2222,
        private string $username = '',
        private string $password = '',
        private bool   $useSsl = true
    ) {}

    public function id(): string    { return 'directadmin'; }
    public function label(): string { return 'DirectAdmin'; }

    private function call(string $endpoint, array $params = [], string $method = 'POST'): array
    {
        $scheme = $this->useSsl ? 'https' : 'http';
        $url = "{$scheme}://{$this->hostname}:{$this->port}/{$endpoint}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_USERPWD         => "{$this->username}:{$this->password}",
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_TIMEOUT         => 30,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } else {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
        }

        $start = microtime(true);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        $duration = (int) ((microtime(true) - $start) * 1000);
        parse_str((string) $body, $parsed);
        ApiLogger::log('directadmin:' . $this->hostname, $endpoint, $method, $params, $parsed, $code, $duration, $err ?: null);
        return $parsed;
    }

    public function createAccount(array $request): array
    {
        $r = $this->call('CMD_API_ACCOUNT_USER', [
            'action'   => 'create',
            'add'      => 'Submit',
            'username' => $request['username'] ?? '',
            'email'    => $request['email'] ?? '',
            'passwd'   => $request['password'] ?? '',
            'passwd2'  => $request['password'] ?? '',
            'domain'   => $request['domain'] ?? '',
            'package'  => $request['package'] ?? '',
            'ip'       => $request['ip'] ?? '',
            'notify'   => 'no',
        ]);
        $ok = ($r['error'] ?? '1') === '0';
        return ['success' => $ok, 'username' => $request['username'] ?? '', 'message' => $r['details'] ?? ($r['text'] ?? '')];
    }

    public function suspendAccount(string $username, string $reason = ''): bool
    {
        $r = $this->call('CMD_API_SELECT_USERS', ['suspend' => 'Suspend', 'select0' => $username]);
        return ($r['error'] ?? '1') === '0';
    }

    public function unsuspendAccount(string $username): bool
    {
        $r = $this->call('CMD_API_SELECT_USERS', ['unsuspend' => 'Unsuspend', 'select0' => $username]);
        return ($r['error'] ?? '1') === '0';
    }

    public function terminateAccount(string $username): bool
    {
        $r = $this->call('CMD_API_SELECT_USERS', ['delete' => 'Delete', 'select0' => $username, 'confirmed' => 'Confirm']);
        return ($r['error'] ?? '1') === '0';
    }

    public function changePassword(string $username, string $newPassword): bool
    {
        $r = $this->call('CMD_API_USER_PASSWD', ['username' => $username, 'passwd' => $newPassword, 'passwd2' => $newPassword]);
        return ($r['error'] ?? '1') === '0';
    }

    public function changePackage(string $username, string $newPackage): bool
    {
        $r = $this->call('CMD_API_MODIFY_USER', ['action' => 'package', 'user' => $username, 'package' => $newPackage]);
        return ($r['error'] ?? '1') === '0';
    }

    public function getUsage(string $username): array
    {
        $r = $this->call('CMD_API_SHOW_USER_USAGE', ['user' => $username], 'GET');
        return [
            'disk_mb'      => isset($r['quota']) ? (int) $r['quota'] : null,
            'bandwidth_mb' => isset($r['bandwidth']) ? (int) $r['bandwidth'] : null,
            'status'       => $r['suspended'] ?? 'active',
        ];
    }

    public function testConnection(): array
    {
        $r = $this->call('CMD_API_LOGIN_TEST', [], 'GET');
        return ['success' => !empty($r), 'raw' => $r];
    }
}
