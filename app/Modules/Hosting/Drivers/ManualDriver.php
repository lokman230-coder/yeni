<?php

declare(strict_types=1);

namespace App\Modules\Hosting\Drivers;

use App\Modules\Hosting\Contracts\HostingPanelInterface;

/**
 * Manuel hosting sürücüsü.
 * API çağrısı yapmaz; admin işlemleri elle yapar. Otomasyon "pending" bırakır.
 */
final class ManualDriver implements HostingPanelInterface
{
    public function id(): string    { return 'manual'; }
    public function label(): string { return 'Manuel (Otomasyon yok)'; }

    public function createAccount(array $request): array
    {
        return ['success' => true, 'username' => $request['username'] ?? '', 'message' => 'Manuel: admin işlem yapacak.'];
    }
    public function suspendAccount(string $username, string $reason = ''): bool { return true; }
    public function unsuspendAccount(string $username): bool { return true; }
    public function terminateAccount(string $username): bool { return true; }
    public function changePassword(string $username, string $newPassword): bool { return true; }
    public function changePackage(string $username, string $newPackage): bool { return true; }
    public function installCronJobs(string $username, array $jobs): array { return ['success' => true, 'installed' => 0, 'errors' => []]; }
    public function getUsage(string $username): array { return ['disk_mb' => null, 'bandwidth_mb' => null, 'status' => 'active']; }
    public function testConnection(): array { return ['success' => true, 'message' => 'Manuel mod — bağlantı gerekmez.']; }
}
