<?php

declare(strict_types=1);

namespace App\Modules\Hosting\Contracts;

interface HostingPanelInterface
{
    public function id(): string;
    public function label(): string;

    public function createAccount(array $request): array;
    public function suspendAccount(string $username, string $reason = ''): bool;
    public function unsuspendAccount(string $username): bool;
    public function terminateAccount(string $username): bool;
    public function changePassword(string $username, string $newPassword): bool;
    public function changePackage(string $username, string $newPackage): bool;

    /**
     * @param array<int,array{minute:string,hour:string,day:string,month:string,weekday:string,command:string}> $jobs
     * @return array{success:bool, installed:int, errors:array<int,string>}
     */
    public function installCronJobs(string $username, array $jobs): array;

    public function getUsage(string $username): array;
    public function testConnection(): array;
}
