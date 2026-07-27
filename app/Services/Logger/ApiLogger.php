<?php

declare(strict_types=1);

namespace App\Services\Logger;

use App\Core\Database\Connection;

/**
 * Dış API çağrılarını `api_logs` tablosuna kaydeder.
 * Hassas alanlar (password, api_key) otomatik maskelenir.
 */
final class ApiLogger
{
    private const REDACT_KEYS = ['password', 'api_key', 'apikey', 'merchant_key', 'merchant_salt', 'token', 'auth'];

    public static function log(
        string $integration,
        string $endpoint,
        string $method,
        mixed $requestBody,
        mixed $responseBody,
        ?int $httpCode = null,
        ?int $durationMs = null,
        ?string $error = null,
        ?string $relatedEntityType = null,
        ?int $relatedEntityId = null
    ): void {
        try {
            Connection::insert('api_logs', [
                'integration'   => $integration,
                'endpoint'      => $endpoint,
                'method'        => strtoupper($method),
                'request_body'  => self::stringify(self::redact($requestBody)),
                'response_body' => self::stringify($responseBody),
                'http_code'     => $httpCode,
                'duration_ms'   => $durationMs,
                'error'         => $error,
                'related_entity_type' => $relatedEntityType,
                'related_entity_id'   => $relatedEntityId,
            ]);
        } catch (\Throwable) {
            // sessizce yut - api log DB'ye yazamasa uygulama patlamasın
        }
    }

    private static function redact(mixed $value): mixed
    {
        if (!is_array($value)) return $value;
        foreach ($value as $k => $v) {
            $keyLower = strtolower((string) $k);
            foreach (self::REDACT_KEYS as $needle) {
                if (str_contains($keyLower, $needle)) {
                    $value[$k] = '***REDACTED***';
                    continue 2;
                }
            }
            if (is_array($v)) $value[$k] = self::redact($v);
        }
        return $value;
    }

    private static function stringify(mixed $data): string
    {
        if (is_string($data)) return mb_substr($data, 0, 8000);
        return mb_substr(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '{}', 0, 8000);
    }
}
