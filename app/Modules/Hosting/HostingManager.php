<?php

declare(strict_types=1);

namespace App\Modules\Hosting;

use App\Core\Database\Connection;
use App\Modules\Hosting\Contracts\HostingPanelInterface;
use App\Modules\Hosting\Drivers\CpanelDriver;
use App\Modules\Hosting\Drivers\DirectAdminDriver;
use App\Modules\Hosting\Drivers\ManualDriver;
use App\Modules\Hosting\Drivers\PleskDriver;
use App\Support\Encrypter;

/**
 * Hosting sunucu → driver eşleştirici.
 */
final class HostingManager
{
    public static function forServer(int $serverId): HostingPanelInterface
    {
        try {
            $s = Connection::selectOne("SELECT * FROM hosting_servers WHERE id = ? AND is_active = 1", [$serverId]);
        } catch (\Throwable) {
            $s = null;
        }
        if (!$s) return new ManualDriver();

        $password = self::decrypt($s['password_encrypted'] ?? null);
        $apiKey   = self::decrypt($s['api_key_encrypted'] ?? null);

        return match ($s['panel']) {
            'cpanel' => new CpanelDriver(
                (string) $s['hostname'],
                (int) $s['port'],
                (string) $s['username'],
                $apiKey ?: $password ?: '',
                (bool) $s['use_ssl']
            ),
            'da' => new DirectAdminDriver(
                (string) $s['hostname'],
                (int) $s['port'],
                (string) $s['username'],
                $password ?: '',
                (bool) $s['use_ssl']
            ),
            'plesk' => new PleskDriver(
                (string) $s['hostname'],
                (int) $s['port'],
                (string) $s['username'],
                $apiKey ?: '',
                (bool) $s['use_ssl']
            ),
            default => new ManualDriver(),
        };
    }

    private static function decrypt(?string $encrypted): ?string
    {
        if ($encrypted === null || $encrypted === '') return null;
        try { return Encrypter::decrypt($encrypted); }
        catch (\Throwable) { return null; }
    }
}
