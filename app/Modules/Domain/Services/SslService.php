<?php

declare(strict_types=1);

namespace App\Modules\Domain\Services;

/**
 * SSL sertifika bilgilerini alır (issuer, tarih, kalan gün, CN).
 * Herhangi bir 3rd party API kullanılmaz; openssl_x509_parse çekirdek.
 */
final class SslService
{
    public static function check(string $host, int $port = 443): array
    {
        $host = self::normalize($host);
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'SNI_enabled'       => true,
                'peer_name'         => $host,
            ],
        ]);

        set_error_handler(fn() => null);
        $client = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno, $errstr, 6,
            STREAM_CLIENT_CONNECT, $context
        );
        restore_error_handler();

        if (!$client) {
            return [
                'active'     => false,
                'issuer'     => null,
                'subject_cn' => null,
                'valid_from' => null,
                'valid_to'   => null,
                'days_left'  => null,
                'error'      => $errstr ?: 'SSL bağlantı kurulamadı',
            ];
        }

        $params = stream_context_get_params($client);
        $cert = $params['options']['ssl']['peer_certificate'] ?? null;
        fclose($client);

        if (!$cert) {
            return ['active' => false, 'error' => 'Sertifika alınamadı'];
        }

        $parsed = openssl_x509_parse($cert) ?: [];
        $validFrom = (int) ($parsed['validFrom_time_t'] ?? 0);
        $validTo   = (int) ($parsed['validTo_time_t'] ?? 0);
        $now = time();
        $daysLeft = $validTo > 0 ? (int) floor(($validTo - $now) / 86400) : null;

        $issuer = $parsed['issuer']['O'] ?? ($parsed['issuer']['CN'] ?? null);
        $subjectCn = $parsed['subject']['CN'] ?? null;
        $altNames = [];
        if (!empty($parsed['extensions']['subjectAltName'])) {
            $altNames = array_map(
                fn($v) => trim(str_replace('DNS:', '', $v)),
                explode(',', (string) $parsed['extensions']['subjectAltName'])
            );
        }

        return [
            'active'      => $validTo > $now,
            'issuer'      => is_array($issuer) ? implode(', ', $issuer) : $issuer,
            'subject_cn'  => $subjectCn,
            'alt_names'   => $altNames,
            'valid_from'  => $validFrom ? date('Y-m-d H:i', $validFrom) : null,
            'valid_to'    => $validTo ? date('Y-m-d H:i', $validTo) : null,
            'days_left'   => $daysLeft,
            'expires_soon'=> $daysLeft !== null && $daysLeft < 30,
            'expired'     => $validTo > 0 && $validTo <= $now,
        ];
    }

    private static function normalize(string $host): string
    {
        $host = trim(strtolower($host));
        $host = preg_replace('#^https?://#', '', $host) ?? $host;
        $host = preg_replace('#/.*$#', '', $host) ?? $host;
        return $host;
    }
}
