<?php

declare(strict_types=1);

namespace App\Modules\Registrar\Contracts;

/**
 * Registrar sürücü arayüzü.
 * Uygulama katmanı somut driver'ı değil arayüzü bilir.
 */
interface RegistrarInterface
{
    public function id(): string;
    public function label(): string;

    /**
     * @param string[] $domains
     * @return array<string, array{available:bool, premium?:bool, price?:float, currency?:string, error?:string}>
     */
    public function check(array $domains): array;

    public function whois(string $domain): array;

    public function dnsRecords(string $domain): array;

    public function register(string $domain, int $years, array $contact, array $nameservers = []): array;
    public function transfer(string $domain, string $eppCode, array $contact): array;
    public function renew(string $domain, int $years): array;

    public function getEppCode(string $domain): string;
    public function setTransferLock(string $domain, bool $locked): bool;
    public function updateNameservers(string $domain, array $nameservers): bool;

    public function info(string $domain): array;
}
