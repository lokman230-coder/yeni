<?php

declare(strict_types=1);

namespace App\Modules\Hosting\Contracts;

/**
 * Hosting paneli (cPanel / DA / Plesk / Manuel) sürücü arayüzü.
 */
interface HostingPanelInterface
{
    public function id(): string;
    public function label(): string;

    /**
     * Yeni hesap oluştur.
     * @param array $request  ['domain','username','password','package','email','plan','quota',...]
     * @return array{success:bool, username?:string, message?:string}
     */
    public function createAccount(array $request): array;

    public function suspendAccount(string $username, string $reason = ''): bool;
    public function unsuspendAccount(string $username): bool;
    public function terminateAccount(string $username): bool;
    public function changePassword(string $username, string $newPassword): bool;
    public function changePackage(string $username, string $newPackage): bool;

    /** Disk + bandwidth kullanımı */
    public function getUsage(string $username): array;

    /** Bağlantı testi */
    public function testConnection(): array;
}
