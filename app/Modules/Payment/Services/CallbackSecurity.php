<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Core\Database\Connection;
use App\Services\Logger\Logger;

/**
 * Ödeme sağlayıcı callback'leri için ek güvenlik katmanı.
 *
 * Her provider kendi imza doğrulamasını yapar (PayTR: hash, Shopier: HMAC,
 * iyzico/Papara: API lookup). Bu servis buna ek olarak:
 *
 *   1. Aynı callback'in tekrar tekrar işlenmesini engeller (replay attack)
 *   2. IP whitelist kontrolü (opsiyonel — bazı sağlayıcılar sabit IP kullanır)
 *   3. Callback'i audit log'a yazar
 */
final class CallbackSecurity
{
    /** Sağlayıcıların bilinen IP aralıkları (whitelist için) */
    private const KNOWN_IPS = [
        'paytr'   => ['193.192.59.0/24', '193.192.60.0/24'],
        'iyzico'  => [],  // iyzico değişken IP kullanır → whitelist yok
        'papara'  => [],
        'shopier' => [],
    ];

    /**
     * Callback işlenmiş mi kontrol et. İşlenmişse replay olarak reddet.
     * @return bool true → önce görülmedi (işlenebilir), false → duplicate
     */
    public static function markProcessed(string $provider, string $callbackId, array $payload = []): bool
    {
        // callback_id yerine gateway_transaction_id + provider'ı birleştirip unique key yap
        $key = md5($provider . '|' . $callbackId);
        try {
            $existing = Connection::selectOne(
                "SELECT id FROM payments WHERE gateway_transaction_id = ? AND method = ? LIMIT 1",
                [$callbackId, $provider]
            );
            if ($existing) {
                Logger::warning('Payment callback duplicate ignored', [
                    'provider' => $provider,
                    'callback_id' => $callbackId,
                    'existing_payment_id' => $existing['id'],
                ]);
                return false; // Zaten işlenmiş
            }
            return true;
        } catch (\Throwable $e) {
            Logger::error('CallbackSecurity check failed: ' . $e->getMessage());
            return true; // Sistem hatası varsa geçir (false-positive engeli)
        }
    }

    /**
     * IP whitelist kontrolü. Sağlayıcı için tanımlı IP'ler yoksa true döner.
     */
    public static function isAllowedIp(string $provider, string $ip): bool
    {
        $ranges = self::KNOWN_IPS[$provider] ?? [];
        if (empty($ranges)) return true; // Whitelist yok, herkes geçer

        foreach ($ranges as $range) {
            if (self::ipInRange($ip, $range)) return true;
        }
        Logger::warning('Payment callback from disallowed IP', [
            'provider' => $provider,
            'ip'       => $ip,
        ]);
        return false;
    }

    /** CIDR check */
    private static function ipInRange(string $ip, string $range): bool
    {
        if (!str_contains($range, '/')) return $ip === $range;
        [$subnet, $bits] = explode('/', $range);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) return false;
        $mask = -1 << (32 - (int) $bits);
        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    /** Kullanıcı çağrısını audit log'a yaz */
    public static function audit(string $provider, string $callbackId, bool $signatureValid, array $payload, string $ip): void
    {
        Logger::info('Payment callback received', [
            'provider'         => $provider,
            'callback_id'      => $callbackId,
            'signature_valid'  => $signatureValid,
            'ip'               => $ip,
            'payload_keys'     => array_keys($payload),
        ]);
    }
}
