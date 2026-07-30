<?php

declare(strict_types=1);

namespace App\Modules\SiteTools\Tools;

abstract class AbstractTool implements ToolInterface
{
    protected static function normalizeUrl(string $input): string
    {
        $input = trim($input);
        if (!preg_match('#^https?://#', $input)) $input = 'http://' . $input;
        return $input;
    }

    protected static function normalizeHost(string $input): string
    {
        $input = trim(strtolower($input));
        $input = preg_replace('#^https?://#', '', $input) ?? $input;
        $input = preg_replace('#^www\.#', '', $input) ?? $input;
        $input = preg_replace('#/.*$#', '', $input) ?? $input;
        $input = explode(':', $input)[0];
        return $input;
    }

    protected static function fetch(string $url, int $timeout = 10, bool $followRedirects = true): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FOLLOWLOCATION  => $followRedirects,
            CURLOPT_MAXREDIRS       => 5,
            CURLOPT_TIMEOUT         => $timeout,
            CURLOPT_CONNECTTIMEOUT  => 5,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HEADER          => true,
            CURLOPT_USERAGENT       => 'Mozilla/5.0 (compatible; AhostOneBot/1.0)',
        ]);
        $start = microtime(true);
        $raw = curl_exec($ch);
        $duration = (microtime(true) - $start) * 1000;
        $info = curl_getinfo($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['success' => false, 'error' => $err, 'duration_ms' => (int) $duration];
        }
        $headerSize = $info['header_size'] ?? 0;
        $headers = substr((string) $raw, 0, $headerSize);
        $body    = substr((string) $raw, $headerSize);

        return [
            'success'    => true,
            'http_code'  => (int) ($info['http_code'] ?? 0),
            'headers_raw'=> $headers,
            'headers'    => self::parseHeaders($headers),
            'body'       => $body,
            'duration_ms'=> (int) $duration,
            'total_time_s'=> $info['total_time'] ?? 0,
            'size_bytes' => strlen($body),
            'url'        => $info['url'] ?? $url,
        ];
    }

    protected static function parseHeaders(string $raw): array
    {
        $out = [];
        // Son header set'i (redirect sonrası)
        $blocks = preg_split("/\r\n\r\n/", trim($raw));
        $last = end($blocks) ?: '';
        foreach (explode("\r\n", $last) as $line) {
            if (!str_contains($line, ':')) continue;
            [$k, $v] = explode(':', $line, 2);
            $out[trim($k)] = trim($v);
        }
        return $out;
    }
}
