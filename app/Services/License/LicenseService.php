<?php

declare(strict_types=1);

namespace App\Services\License;

use App\Core\Database\Connection;

/**
 * Lisans yönetim servisi.
 *
 * Kullanım örneği:
 *   $license = LicenseService::issue([
 *       'customer_id'   => 5,
 *       'product_id'    => 12,
 *       'product_name'  => 'Ahost Site Builder',
 *       'license_type'  => 'single_domain',
 *       'max_domains'   => 1,
 *       'expires_at'    => '2027-01-01',
 *       'invoice_id'    => 100,
 *   ]);
 *
 * Doğrulama (script içinden çağırılır):
 *   $result = LicenseService::verify('AHOST-XXXX-XXXX-XXXX-XXXX', 'musteri.com');
 *   → ['valid' => true, 'expires_at' => '2027-01-01', 'domains_used' => 1, 'max_domains' => 1]
 */
final class LicenseService
{
    /** Yeni lisans üret + kaydet */
    public static function issue(array $data): array
    {
        $key = self::generateKey();
        $now = date('Y-m-d H:i:s');

        $insert = [
            'license_key'      => $key,
            'customer_id'      => (int) $data['customer_id'],
            'product_id'       => $data['product_id'] ?? null,
            'order_id'         => $data['order_id'] ?? null,
            'invoice_id'       => $data['invoice_id'] ?? null,
            'product_name'     => (string) ($data['product_name'] ?? 'Ahost Script'),
            'product_version'  => $data['product_version'] ?? null,
            'license_type'     => (string) ($data['license_type'] ?? 'single_domain'),
            'max_domains'      => (int) ($data['max_domains'] ?? 1),
            'status'           => 'active',
            'issued_at'        => $now,
            'expires_at'       => $data['expires_at'] ?? null,
            'purchase_code'    => $data['purchase_code'] ?? null,
            'source'           => (string) ($data['source'] ?? 'ahost'),
            'notes'            => $data['notes'] ?? null,
            'created_at'       => $now,
            'updated_at'       => $now,
        ];

        $id = Connection::insert('licenses', $insert);
        $insert['id'] = $id;
        return $insert;
    }

    /**
     * Lisans doğrulama — script müşterinin sunucusunda kurulur, her ayarda buraya HTTP çağırır.
     */
    public static function verify(string $licenseKey, string $identifier, string $identifierType = 'domain'): array
    {
        $licenseKey = strtoupper(trim($licenseKey));
        $identifier = self::normalizeIdentifier($identifier, $identifierType);

        // Rate limit: aynı license+IP dakikada max 60 istek
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $recent = Connection::selectOne(
                "SELECT COUNT(*) c FROM license_verifications
                 WHERE license_key = ? AND ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)",
                [$licenseKey, $ip]
            );
            if ((int)($recent['c'] ?? 0) > 60) {
                self::logVerification(null, $licenseKey, $identifier, 'rate_limited', 'Too many requests');
                return ['valid' => false, 'error' => 'rate_limited', 'message' => 'Çok fazla istek. 1 dakika bekleyin.'];
            }
        } catch (\Throwable) {}

        $license = Connection::selectOne("SELECT * FROM licenses WHERE license_key = ? LIMIT 1", [$licenseKey]);
        if (!$license) {
            self::logVerification(null, $licenseKey, $identifier, 'invalid', 'Lisans bulunamadı');
            return ['valid' => false, 'error' => 'invalid', 'message' => 'Geçersiz lisans anahtarı.'];
        }

        // Status kontrolü
        if ($license['status'] === 'revoked') {
            self::logVerification((int)$license['id'], $licenseKey, $identifier, 'revoked');
            return ['valid' => false, 'error' => 'revoked', 'message' => 'Lisans iptal edilmiş.'];
        }
        if ($license['status'] === 'suspended') {
            self::logVerification((int)$license['id'], $licenseKey, $identifier, 'revoked', 'Askıya alındı');
            return ['valid' => false, 'error' => 'suspended', 'message' => 'Lisans askıya alınmış.'];
        }

        // Süre kontrolü
        if (!empty($license['expires_at']) && strtotime((string)$license['expires_at']) < time()) {
            Connection::update('licenses', ['status' => 'expired'], 'id = ?', [$license['id']]);
            self::logVerification((int)$license['id'], $licenseKey, $identifier, 'expired');
            return ['valid' => false, 'error' => 'expired', 'message' => 'Lisans süresi dolmuş.', 'expires_at' => $license['expires_at']];
        }

        // Aktivasyon kontrolü + otomatik ekle
        $existing = Connection::selectOne(
            "SELECT * FROM license_activations WHERE license_id = ? AND identifier = ? LIMIT 1",
            [$license['id'], $identifier]
        );

        if (!$existing) {
            // Yeni aktivasyon — max_domains kontrolü
            if ($license['license_type'] !== 'unlimited') {
                $count = (int) (Connection::selectOne(
                    "SELECT COUNT(*) c FROM license_activations WHERE license_id = ? AND is_active = 1",
                    [$license['id']]
                )['c'] ?? 0);

                if ($count >= (int)$license['max_domains']) {
                    self::logVerification((int)$license['id'], $licenseKey, $identifier, 'domain_mismatch',
                        "Max aktivasyon sınırı doldu ({$license['max_domains']})");
                    return [
                        'valid'   => false,
                        'error'   => 'domain_limit_reached',
                        'message' => "Bu lisans en fazla {$license['max_domains']} domain/paket için kullanılabilir.",
                        'used'    => $count,
                        'max'     => (int)$license['max_domains'],
                    ];
                }
            }

            // Aktivasyonu kaydet
            Connection::insert('license_activations', [
                'license_id'      => $license['id'],
                'identifier'      => $identifier,
                'identifier_type' => $identifierType,
                'ip'              => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent'      => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250),
                'activated_at'    => date('Y-m-d H:i:s'),
                'last_seen_at'    => date('Y-m-d H:i:s'),
                'is_active'       => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Mevcut aktivasyonu güncelle
            if (!(int)$existing['is_active']) {
                self::logVerification((int)$license['id'], $licenseKey, $identifier, 'revoked', 'Aktivasyon deaktive');
                return ['valid' => false, 'error' => 'activation_disabled'];
            }
            Connection::update('license_activations',
                ['last_seen_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
                'id = ?', [$existing['id']]
            );
        }

        // License meta güncelle
        Connection::update('licenses', [
            'last_verified_at'   => date('Y-m-d H:i:s'),
            'verification_count' => (int)$license['verification_count'] + 1,
            'updated_at'         => date('Y-m-d H:i:s'),
        ], 'id = ?', [$license['id']]);

        $activeCount = (int) (Connection::selectOne(
            "SELECT COUNT(*) c FROM license_activations WHERE license_id = ? AND is_active = 1",
            [$license['id']]
        )['c'] ?? 0);

        self::logVerification((int)$license['id'], $licenseKey, $identifier, 'valid');

        return [
            'valid'         => true,
            'license_type'  => $license['license_type'],
            'max_domains'   => (int)$license['max_domains'],
            'domains_used'  => $activeCount,
            'expires_at'    => $license['expires_at'],
            'product_name'  => $license['product_name'],
            'issued_at'     => $license['issued_at'],
        ];
    }

    /** Lisans anahtarı üret: AHOST-XXXX-XXXX-XXXX-XXXX */
    public static function generateKey(string $prefix = 'AHOST'): string
    {
        do {
            $parts = [$prefix];
            for ($i = 0; $i < 4; $i++) {
                $parts[] = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            }
            $key = implode('-', $parts);
            $exists = Connection::selectOne("SELECT 1 FROM licenses WHERE license_key = ?", [$key]);
        } while ($exists);
        return $key;
    }

    /** CodeCanyon envato purchase code doğrulama (opsiyonel — envato API) */
    public static function verifyEnvatoPurchase(string $purchaseCode): array
    {
        // Purchase code format: 12345678-1234-1234-1234-123456789012 (36 char UUID)
        if (!preg_match('/^[a-f0-9-]{36}$/i', $purchaseCode)) {
            return ['valid' => false, 'error' => 'Geçersiz format'];
        }

        // Bizim DB'mizde bu code varsa direkt geçir
        $existing = Connection::selectOne("SELECT * FROM licenses WHERE purchase_code = ?", [$purchaseCode]);
        if ($existing) {
            return ['valid' => true, 'license' => $existing];
        }

        // Envato API'ye sor (isteğe bağlı — token gerekir)
        $token = (string) \App\Services\Settings\SettingsManager::get('envato.personal_token', '');
        if ($token === '') {
            return ['valid' => false, 'error' => 'Envato token yok. Admin > Ayarlar > Envato\'dan gir.'];
        }

        $ch = curl_init("https://api.envato.com/v3/market/author/sale?code=" . urlencode($purchaseCode));
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $token"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || !$resp) {
            return ['valid' => false, 'error' => 'Envato doğrulama başarısız (HTTP ' . $code . ')'];
        }

        $data = json_decode((string)$resp, true);
        if (!$data || empty($data['item'])) {
            return ['valid' => false, 'error' => 'Envato sale bulunamadı'];
        }

        return [
            'valid' => true,
            'envato' => [
                'buyer'      => $data['buyer'] ?? null,
                'sold_at'    => $data['sold_at'] ?? null,
                'item_id'    => $data['item']['id'] ?? null,
                'item_name'  => $data['item']['name'] ?? null,
                'license'    => $data['license'] ?? 'regular',
                'supported_until' => $data['supported_until'] ?? null,
            ],
        ];
    }

    /** Aktivasyonu kaldır (müşteri farklı domain'e taşımak istiyor) */
    public static function deactivate(int $licenseId, string $identifier): array
    {
        $identifier = self::normalizeIdentifier($identifier);
        $activation = Connection::selectOne(
            "SELECT * FROM license_activations WHERE license_id = ? AND identifier = ?",
            [$licenseId, $identifier]
        );
        if (!$activation) {
            return ['ok' => false, 'error' => 'Aktivasyon yok'];
        }
        Connection::update('license_activations', ['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$activation['id']]);
        return ['ok' => true];
    }

    /** Lisans revoke (iptal — bir daha çalışmaz) */
    public static function revoke(int $licenseId, ?string $reason = null): void
    {
        Connection::update('licenses', ['status' => 'revoked', 'notes' => $reason, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$licenseId]);
    }

    private static function normalizeIdentifier(string $identifier, string $type = 'domain'): string
    {
        $identifier = trim($identifier);
        if ($type === 'domain') {
            $identifier = strtolower($identifier);
            $identifier = preg_replace('#^https?://#', '', $identifier);
            $identifier = preg_replace('#^www\.#', '', $identifier);
            $identifier = strtok($identifier, '/');
        }
        return $identifier;
    }

    private static function logVerification(?int $licenseId, string $key, ?string $identifier, string $result, ?string $response = null): void
    {
        try {
            Connection::insert('license_verifications', [
                'license_id'  => $licenseId,
                'license_key' => $key,
                'identifier'  => $identifier,
                'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent'  => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250),
                'result'      => $result,
                'response'    => $response,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {}
    }
}
