<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Core\Database\Connection;
use App\Support\Encrypter;

/**
 * Merkezi ayar yöneticisi.
 *
 * Öncelik sırası (get):
 *   1. settings tablosundaki değer (encrypted ise decrypt edilir)
 *   2. .env değeri (fallback)
 *   3. verilen $default
 *
 * Set:
 *   - type='encrypted' ise değer AES-256-GCM ile şifrelenir
 *   - is_public=1 ise public API'lerden okunabilir (site adı vb)
 *
 * Cache: request-scope memoization (aynı request içinde tekrar tekrar DB'ye gidilmez)
 */
final class SettingsManager
{
    private static array $cache = [];
    private static bool $loaded = false;

    /**
     * @param string $key    Örn: 'iyzico.api_key', 'paytr.merchant_id'
     * @param mixed  $default
     * @param string|null $envFallback Env variable adı (verilirse .env'e fallback)
     */
    public static function get(string $key, mixed $default = null, ?string $envFallback = null): mixed
    {
        self::ensureLoaded();

        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        if ($envFallback !== null) {
            $envVal = env($envFallback);
            if ($envVal !== null && $envVal !== '') return $envVal;
        }
        return $default;
    }

    /**
     * @param string $type 'string'|'int'|'bool'|'json'|'encrypted'
     */
    public static function set(string $key, mixed $value, string $type = 'string', ?string $group = null, bool $isPublic = false): void
    {
        $store = match ($type) {
            'int'       => (string) (int) $value,
            'bool'      => $value ? '1' : '0',
            'json'      => is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE),
            'encrypted' => $value === '' ? '' : Encrypter::encrypt((string) $value),
            default     => (string) $value,
        };

        $existing = Connection::selectOne("SELECT id FROM settings WHERE `key` = ?", [$key]);
        if ($existing) {
            Connection::update('settings', [
                'value'     => $store,
                'type'      => $type,
                'group'     => $group,
                'is_public' => $isPublic ? 1 : 0,
            ], 'id = ?', [$existing['id']]);
        } else {
            Connection::insert('settings', [
                'key'       => $key,
                'value'     => $store,
                'type'      => $type,
                'group'     => $group,
                'is_public' => $isPublic ? 1 : 0,
            ]);
        }

        // Cache invalidate
        self::$cache[$key] = self::decode($store, $type);
    }

    public static function forget(string $key): void
    {
        Connection::delete('settings', '`key` = ?', [$key]);
        unset(self::$cache[$key]);
    }

    /** Tüm ayarları eager load — birinci get çağrısında bir kere çalışır */
    private static function ensureLoaded(): void
    {
        if (self::$loaded) return;
        try {
            $rows = Connection::select("SELECT `key`, value, type FROM settings");
            foreach ($rows as $r) {
                self::$cache[$r['key']] = self::decode((string) ($r['value'] ?? ''), (string) $r['type']);
            }
        } catch (\Throwable) {
            // Migration henüz çalıştırılmamış olabilir
        }
        self::$loaded = true;
    }

    private static function decode(string $raw, string $type): mixed
    {
        if ($raw === '' || $raw === null) return null;
        return match ($type) {
            'int'       => (int) $raw,
            'bool'      => $raw === '1' || $raw === 'true',
            'json'      => json_decode($raw, true) ?? $raw,
            'encrypted' => (function() use ($raw) {
                try { return Encrypter::decrypt($raw); }
                catch (\Throwable) { return null; }
            })(),
            default     => $raw,
        };
    }

    /** Bir gruptaki tüm ayarlar (admin listeleme için) */
    public static function group(string $group): array
    {
        try {
            $rows = Connection::select("SELECT * FROM settings WHERE `group` = ? ORDER BY `key`", [$group]);
            $out = [];
            foreach ($rows as $r) {
                // Encrypted değerleri UI'da göstermeyiz — sadece 'set/not set' bilgisi
                $isSecret = $r['type'] === 'encrypted';
                $out[] = [
                    'key'       => $r['key'],
                    'value'     => $isSecret ? ($r['value'] ? '••••••••' : '') : self::decode((string) $r['value'], (string) $r['type']),
                    'has_value' => !empty($r['value']),
                    'type'      => $r['type'],
                    'is_secret' => $isSecret,
                ];
            }
            return $out;
        } catch (\Throwable) { return []; }
    }

    /** Cache'i sıfırla (test için) */
    public static function reset(): void
    {
        self::$cache = [];
        self::$loaded = false;
    }
}
