<?php

declare(strict_types=1);

namespace App\Modules\Health\Services;

/**
 * Basit HTTP uptime + SSL kontrolü.
 * Bağımsız çalışır (Chromium/Playwright gerektirmez).
 */
final class UptimeProbe
{
    /**
     * URL'e HEAD isteği atar, http kodu + response time + SSL bilgisi döner.
     *
     * @return array{ok:bool, http_code:int, response_time_ms:int, ssl_valid?:bool, ssl_expires_in_days?:int, error?:string}
     */
    public static function check(string $url, int $timeout = 10): array
    {
        $started = microtime(true);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_USERAGENT      => 'AhostBilisim-UptimeProbe/1.0',
            CURLOPT_CERTINFO       => true,
        ]);
        $ok = @curl_exec($ch);
        $ms = (int) round((microtime(true) - $started) * 1000);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        $certInfo = curl_getinfo($ch, CURLINFO_CERTINFO);
        curl_close($ch);

        $result = [
            'ok'               => $ok !== false && $http >= 200 && $http < 500,
            'http_code'        => $http,
            'response_time_ms' => $ms,
        ];

        if ($err) $result['error'] = $err;

        // HTTPS ise SSL bilgisini çıkar
        if (str_starts_with($url, 'https://') && is_array($certInfo) && !empty($certInfo[0])) {
            $cert = $certInfo[0];
            $expireStr = $cert['Expire date'] ?? null;
            if ($expireStr) {
                $expireTs = strtotime($expireStr);
                if ($expireTs !== false) {
                    $result['ssl_valid'] = $expireTs > time();
                    $result['ssl_expires_in_days'] = (int) round(($expireTs - time()) / 86400);
                }
            }
        }
        return $result;
    }

    /** Birden fazla URL'i sırayla kontrol et. */
    public static function checkMany(array $urls, int $timeout = 10): array
    {
        $out = [];
        foreach ($urls as $url) {
            $out[$url] = self::check($url, $timeout);
        }
        return $out;
    }
}
