<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Core\Database\Connection;

/**
 * TLD fiyatlandırma servisi.
 * Registrar'dan gelen maliyet fiyatı üzerine kar marjı ekleyerek satış fiyatı üretir.
 *
 * Flow:
 *   1) Registrar API'den maliyet fiyatı çek (cache)
 *   2) tld_configs.markup_type ve markup_value'ya göre satış fiyatı hesapla
 *   3) min_price kontrolü yap
 *   4) Cache'te 24 saat sakla
 */
final class TldPricingService
{
    /**
     * Bir TLD için satış fiyatı hesapla.
     * @return array{register:float, renew:float, transfer:float, currency:string, cost:float, markup:string}
     */
    public static function priceFor(string $tld, int $years = 1): array
    {
        $tld = strtolower(ltrim(trim($tld), '.'));

        // 1) TLD config
        $config = Connection::selectOne(
            "SELECT * FROM tld_configs WHERE tld = ? AND is_active = 1 LIMIT 1",
            [$tld]
        );
        if (!$config) {
            // Config yoksa default markup uygula
            $config = [
                'markup_type'  => 'percent',
                'markup_value' => 30,
                'min_price'    => 100,
            ];
        }

        // 2) Maliyet — domain_pricing tablosundan
        $pricing = Connection::selectOne(
            "SELECT * FROM domain_pricing WHERE tld = ? AND period_years <= ? AND is_active = 1 ORDER BY period_years DESC LIMIT 1",
            [$tld, $years]
        );

        if (!$pricing) {
            // Default fallback fiyatı
            $costRegister = 200;
            $costRenew    = 200;
            $costTransfer = 200;
        } else {
            $costRegister = (float) $pricing['register_price'];
            $costRenew    = (float) $pricing['renew_price'];
            $costTransfer = (float) ($pricing['transfer_price'] ?? $pricing['register_price']);
        }

        // 3) Markup uygula
        $register = self::applyMarkup($costRegister, $config);
        $renew    = self::applyMarkup($costRenew, $config);
        $transfer = self::applyMarkup($costTransfer, $config);

        return [
            'register'  => round($register, 2),
            'renew'     => round($renew, 2),
            'transfer'  => round($transfer, 2),
            'currency'  => $pricing['currency'] ?? 'TRY',
            'cost'      => $costRegister,
            'markup'    => ($config['markup_type'] ?? 'percent') === 'percent'
                          ? "%" . number_format((float)$config['markup_value'], 2) . " kar"
                          : "+" . number_format((float)$config['markup_value'], 2) . " sabit",
        ];
    }

    private static function applyMarkup(float $cost, array $config): float
    {
        $type = (string) ($config['markup_type'] ?? 'percent');
        $value = (float) ($config['markup_value'] ?? 30);
        $price = $type === 'percent'
            ? $cost * (1 + $value / 100)
            : $cost + $value;

        // Min price koruması
        $min = (float) ($config['min_price'] ?? 0);
        if ($min > 0 && $price < $min) {
            $price = $min;
        }
        return $price;
    }

    /** TLD'nin belge isteyip istemediğini kontrol et */
    public static function requiresDocuments(string $tld): array
    {
        $tld = strtolower(ltrim(trim($tld), '.'));
        $config = Connection::selectOne("SELECT requires_documents, required_documents_json FROM tld_configs WHERE tld = ?", [$tld]);
        if (!$config || !(int)$config['requires_documents']) {
            return ['required' => false, 'documents' => []];
        }
        $docs = json_decode((string)$config['required_documents_json'], true) ?: [];
        return ['required' => true, 'documents' => $docs];
    }

    /** Belge tiplerinin insan okunur etiketi */
    public static function documentLabel(string $type): string
    {
        return match ($type) {
            'tckn'            => 'TC Kimlik Numarası',
            'tax_id'          => 'Vergi Kimlik No',
            'trademark_cert'  => 'Marka Tescil Belgesi',
            'id_card'         => 'Kimlik Fotokopisi',
            'company_reg'     => 'Ticaret Sicil Belgesi',
            'domain_owner_doc'=> 'Domain Sahiplik Belgesi',
            default           => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    /** Bir tld için config güncelle veya oluştur */
    public static function upsert(array $data): int
    {
        $tld = strtolower(ltrim(trim((string)$data['tld']), '.'));
        $existing = Connection::selectOne("SELECT id FROM tld_configs WHERE tld = ?", [$tld]);

        $payload = [
            'tld'                    => $tld,
            'label'                  => (string) ($data['label'] ?? '.' . $tld),
            'default_registrar_id'   => $data['default_registrar_id'] ?? null,
            'markup_type'            => in_array($data['markup_type'] ?? 'percent', ['percent','fixed'], true) ? $data['markup_type'] : 'percent',
            'markup_value'           => (float) ($data['markup_value'] ?? 30),
            'min_price'              => $data['min_price'] ?? null,
            'requires_documents'     => !empty($data['requires_documents']) ? 1 : 0,
            'required_documents_json'=> !empty($data['required_documents']) ? json_encode($data['required_documents'], JSON_UNESCAPED_UNICODE) : null,
            'allow_transfer'         => !empty($data['allow_transfer']) ? 1 : 0,
            'allow_backorder'        => !empty($data['allow_backorder']) ? 1 : 0,
            'min_years'              => max(1, (int) ($data['min_years'] ?? 1)),
            'max_years'              => max(1, (int) ($data['max_years'] ?? 10)),
            'is_popular'             => !empty($data['is_popular']) ? 1 : 0,
            'is_active'              => !empty($data['is_active']) ? 1 : 0,
            'sort_order'             => (int) ($data['sort_order'] ?? 0),
            'updated_at'             => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            Connection::update('tld_configs', $payload, 'id = ?', [$existing['id']]);
            return (int) $existing['id'];
        }
        $payload['created_at'] = date('Y-m-d H:i:s');
        return Connection::insert('tld_configs', $payload);
    }
}
