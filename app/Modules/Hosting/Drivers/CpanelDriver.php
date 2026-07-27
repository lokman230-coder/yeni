<?php

declare(strict_types=1);

namespace App\Modules\Hosting\Drivers;

use App\Modules\Hosting\Contracts\HostingPanelInterface;
use App\Services\Logger\ApiLogger;

/**
 * WHM/cPanel API v1 sürücüsü.
 * https://api.docs.cpanel.net/whm/introduction/
 *
 * Config: hostname, port (2087), username (root/reseller), api_token
 */
final class CpanelDriver implements HostingPanelInterface
{
    public function __construct(
        private string $hostname = '',
        private int    $port = 2087,
        private string $username = 'root',
        private string $apiToken = '',
        private bool   $useSsl = true
    ) {}

    public function id(): string    { return 'cpanel'; }
    public function label(): string { return 'cPanel/WHM'; }

    private function call(string $function, array $params = []): array
    {
        $scheme = $this->useSsl ? 'https' : 'http';
        $url = "{$scheme}://{$this->hostname}:{$this->port}/json-api/{$function}?" . http_build_query($params + ['api.version' => 1]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER      => ["Authorization: whm {$this->username}:{$this->apiToken}"],
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_TIMEOUT         => 30,
        ]);
        $start = microtime(true);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        $duration = (int) ((microtime(true) - $start) * 1000);
        $decoded = $body ? (json_decode((string)$body, true) ?: ['raw' => $body]) : [];
        ApiLogger::log('cpanel:' . $this->hostname, $function, 'GET', $params, $decoded, $code, $duration, $err ?: null);

        if ($body === false || $code >= 400) {
            return ['success' => false, 'message' => $err ?: 'HTTP ' . $code];
        }
        return $decoded;
    }

    public function createAccount(array $request): array
    {
        $r = $this->call('createacct', [
            'username' => $request['username'] ?? '',
            'domain'   => $request['domain'] ?? '',
            'password' => $request['password'] ?? '',
            'plan'     => $request['package'] ?? '',
            'contactemail' => $request['email'] ?? '',
        ]);
        $ok = ($r['metadata']['result'] ?? 0) === 1 || !empty($r['result'][0]['status']);
        return [
            'success'  => $ok,
            'username' => $request['username'] ?? '',
            'message'  => $r['metadata']['reason'] ?? ($r['result'][0]['statusmsg'] ?? ''),
        ];
    }

    public function suspendAccount(string $username, string $reason = ''): bool
    {
        $r = $this->call('suspendacct', ['user' => $username, 'reason' => $reason]);
        return ($r['metadata']['result'] ?? 0) === 1;
    }

    public function unsuspendAccount(string $username): bool
    {
        $r = $this->call('unsuspendacct', ['user' => $username]);
        return ($r['metadata']['result'] ?? 0) === 1;
    }

    public function terminateAccount(string $username): bool
    {
        $r = $this->call('removeacct', ['user' => $username]);
        return ($r['metadata']['result'] ?? 0) === 1;
    }

    public function changePassword(string $username, string $newPassword): bool
    {
        $r = $this->call('passwd', ['user' => $username, 'pass' => $newPassword]);
        return ($r['metadata']['result'] ?? 0) === 1;
    }

    public function changePackage(string $username, string $newPackage): bool
    {
        $r = $this->call('changepackage', ['user' => $username, 'pkg' => $newPackage]);
        return ($r['metadata']['result'] ?? 0) === 1;
    }

    public function getUsage(string $username): array
    {
        $r = $this->call('accountsummary', ['user' => $username]);
        $acct = $r['acct'][0] ?? [];
        return [
            'disk_mb'      => isset($acct['diskused']) ? (int) filter_var($acct['diskused'], FILTER_SANITIZE_NUMBER_INT) : null,
            'bandwidth_mb' => null,
            'status'       => $acct['suspended'] ? 'suspended' : 'active',
        ];
    }

    public function testConnection(): array
    {
        $r = $this->call('version');
        return [
            'success' => !empty($r['version']),
            'version' => $r['version'] ?? '?',
            'raw'     => $r,
        ];
    }
}
