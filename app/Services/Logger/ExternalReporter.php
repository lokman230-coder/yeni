<?php

declare(strict_types=1);

namespace App\Services\Logger;

use App\Services\Settings\SettingsManager;

/**
 * Harici hata takip servisi (Sentry, GlitchTip, BugSnag vs).
 *
 * Sentry DSN URL formatı: https://KEY@sentry.io/PROJECT_ID
 * Admin > Ayarlar > Güvenlik > "Sentry DSN" alanı üzerinden yönetilir.
 *
 * Bu sınıf HTTP POST ile Sentry API'ye rapor gönderir.
 * SDK gerekmez — minimal payload.
 */
final class ExternalReporter
{
    public static function report(\Throwable $e, array $context = []): void
    {
        $dsn = (string) SettingsManager::get('security.sentry_dsn', '', 'SENTRY_DSN');
        if ($dsn === '' || !str_starts_with($dsn, 'http')) return;

        // DSN parse: https://KEY@host/PROJECT_ID
        if (!preg_match('#^(https?)://([a-f0-9]+)@([^/]+)/(\d+)$#', $dsn, $m)) return;
        [, $scheme, $key, $host, $projectId] = $m;

        $endpoint = "$scheme://$host/api/$projectId/store/";
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');

        $payload = [
            'event_id'   => str_replace('-', '', self::uuid4()),
            'timestamp'  => $timestamp,
            'platform'   => 'php',
            'level'      => 'error',
            'logger'     => 'ahost-bilisim',
            'server_name'=> (string) ($_SERVER['SERVER_NAME'] ?? 'cli'),
            'release'    => (string) SettingsManager::get('app.version', '1.0.0'),
            'environment'=> (string) env('APP_ENV', 'production'),
            'message'    => $e->getMessage(),
            'exception'  => [
                'values' => [[
                    'type'       => get_class($e),
                    'value'      => $e->getMessage(),
                    'stacktrace' => [
                        'frames' => array_slice(array_map(function ($frame) {
                            return [
                                'filename' => $frame['file'] ?? '?',
                                'lineno'   => $frame['line'] ?? 0,
                                'function' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '?'),
                            ];
                        }, array_reverse($e->getTrace())), -20), // son 20 frame
                    ],
                ]],
            ],
            'tags'       => array_merge(['app' => 'ahost-bilisim'], $context['tags'] ?? []),
            'extra'      => $context['extra'] ?? [],
        ];

        $auth = sprintf(
            'Sentry sentry_version=7, sentry_client=ahost-native/1.0, sentry_timestamp=%d, sentry_key=%s',
            time(), $key
        );

        // Non-blocking send (fire and forget) — 2 sn timeout
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Sentry-Auth: ' . $auth,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 2,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_NOSIGNAL       => true,
        ]);
        @curl_exec($ch);
        curl_close($ch);
    }

    private static function uuid4(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }
}
