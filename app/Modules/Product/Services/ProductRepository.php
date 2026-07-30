<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Core\Database\Connection;

/**
 * Ürün + fiyat + ek paket + özel alan sorguları.
 */
final class ProductRepository
{
    public static function types(): array
    {
        return [
            'hosting'        => 'Hosting',
            'vps'            => 'VPS',
            'dedicated'      => 'Dedicated Sunucu',
            'domain'         => 'Domain',
            'ssl'            => 'SSL Sertifikası',
            'email_hosting'  => 'E-posta Hosting',
            'radio_hosting'  => 'Radyo Hosting',
            'site_builder'   => 'Site Builder',
            'mobile_builder' => 'Mobile Builder',
            'web_design'     => 'Web Tasarım',
            'mobile_app'     => 'Mobil Uygulama',
            'digital_service'=> 'Dijital Hizmet',
            'license'        => 'Lisans',
            'marketplace'    => 'Marketplace',
            'custom'         => 'Özel Hizmet',
        ];
    }

    public static function all(array $filters = []): array
    {
        $where = ['1=1']; $params = [];
        if (!empty($filters['status'])) { $where[] = 'p.status = ?'; $params[] = $filters['status']; }
        if (!empty($filters['type']))   { $where[] = 'p.type = ?';   $params[] = $filters['type']; }
        if (!empty($filters['group_id'])) { $where[] = 'p.group_id = ?'; $params[] = $filters['group_id']; }
        if (!empty($filters['q'])) {
            $where[] = '(p.name LIKE ? OR p.slug LIKE ?)';
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }
        $sql = "SELECT p.*, g.name AS group_name
                FROM products p
                LEFT JOIN product_groups g ON g.id = p.group_id
                WHERE " . implode(' AND ', $where) . "
                AND p.deleted_at IS NULL
                ORDER BY p.sort_order ASC, p.id DESC";
        try { return Connection::select($sql, $params); }
        catch (\Throwable) { return []; }
    }

    public static function find(int $id): ?array
    {
        try {
            return Connection::selectOne(
                "SELECT p.*, g.name AS group_name FROM products p
                 LEFT JOIN product_groups g ON g.id = p.group_id
                 WHERE p.id = ? AND p.deleted_at IS NULL",
                [$id]
            );
        } catch (\Throwable) { return null; }
    }

    public static function findBySlug(string $slug): ?array
    {
        try {
            return Connection::selectOne(
                "SELECT * FROM products WHERE slug = ? AND deleted_at IS NULL AND status = 'active'",
                [$slug]
            );
        } catch (\Throwable) { return null; }
    }

    public static function byType(string $type): array
    {
        try {
            return Connection::select(
                "SELECT * FROM products WHERE type = ? AND status = 'active' AND deleted_at IS NULL
                 ORDER BY sort_order ASC, id ASC",
                [$type]
            );
        } catch (\Throwable) { return []; }
    }

    public static function groups(): array
    {
        try {
            return Connection::select(
                "SELECT * FROM product_groups WHERE is_active = 1 ORDER BY sort_order ASC, name ASC"
            );
        } catch (\Throwable) { return []; }
    }

    public static function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return Connection::insert('products', $data);
    }

    public static function update(int $id, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        Connection::update('products', $data, 'id = ?', [$id]);
    }

    public static function softDelete(int $id): void
    {
        Connection::update('products', ['deleted_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    // --- Prices ---
    public static function prices(int $productId): array
    {
        try {
            return Connection::select(
                "SELECT * FROM product_prices WHERE product_id = ? ORDER BY sort_order ASC, id ASC",
                [$productId]
            );
        } catch (\Throwable) { return []; }
    }

    public static function replacePrices(int $productId, array $prices): void
    {
        Connection::query("DELETE FROM product_prices WHERE product_id = ?", [$productId]);
        $sort = 0;
        foreach ($prices as $p) {
            if (empty($p['period']) || empty($p['source_currency']) || !isset($p['source_price'])) continue;
            if ($p['source_price'] === '' || $p['source_price'] === null) continue;
            Connection::insert('product_prices', [
                'product_id'      => $productId,
                'period'          => $p['period'],
                'source_currency' => strtoupper($p['source_currency']),
                'source_price'    => (float) $p['source_price'],
                'is_active'       => !empty($p['is_active']) ? 1 : 0,
                'sort_order'      => $sort++,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // --- Addons ---
    public static function addons(int $productId): array
    {
        try {
            return Connection::select(
                "SELECT * FROM product_addons WHERE product_id = ? OR product_id IS NULL
                 ORDER BY product_id IS NULL, sort_order ASC, name ASC",
                [$productId]
            );
        } catch (\Throwable) { return []; }
    }

    /**
     * Ürünün addon'larını tamamen değiştir (Ürün formundan gelen liste ile).
     * Boş isimli satırlar atlanır.
     */
    public static function replaceAddons(int $productId, array $rows): void
    {
        Connection::query('DELETE FROM product_addons WHERE product_id = ?', [$productId]);
        $sort = 0;
        foreach ($rows as $r) {
            $name = trim((string) ($r['name'] ?? ''));
            if ($name === '') continue;
            $slug = \App\Support\Slug::make((string) ($r['slug'] ?? $name));
            Connection::insert('product_addons', [
                'product_id'      => $productId,
                'name'            => $name,
                'slug'            => $slug,
                'description'     => (string) ($r['description'] ?? '') ?: null,
                'price'           => (float) ($r['price'] ?? 0),
                'currency'        => (string) ($r['currency'] ?? 'TRY'),
                'period'          => (string) ($r['period'] ?? 'monthly'),
                'addon_type'      => (string) ($r['addon_type'] ?? '') ?: null,
                'automation_code' => (string) ($r['automation_code'] ?? '') ?: null,
                'is_active'       => !empty($r['is_active']) ? 1 : 0,
                'sort_order'      => $sort++,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // --- Custom fields ---
    public static function customFields(int $productId, bool $activeOnly = true): array
    {
        try {
            $sql = "SELECT * FROM product_custom_fields WHERE product_id = ?";
            if ($activeOnly) $sql .= " AND is_active = 1";
            $sql .= " ORDER BY sort_order ASC, id ASC";
            return Connection::select($sql, [$productId]);
        } catch (\Throwable) { return []; }
    }

    /**
     * Ürünün özel alanlarını tamamen değiştir.
     */
    public static function replaceCustomFields(int $productId, array $rows): void
    {
        Connection::query('DELETE FROM product_custom_fields WHERE product_id = ?', [$productId]);
        $sort = 0;
        foreach ($rows as $r) {
            $label = trim((string) ($r['label'] ?? ''));
            if ($label === '') continue;
            $allowedTypes = ['text','textarea','number','ip','url','email','phone','select','radio','checkbox','file','image','password'];
            $type = in_array($r['field_type'] ?? 'text', $allowedTypes, true) ? $r['field_type'] : 'text';
            $showOn = in_array($r['show_on'] ?? 'order', ['order','profile','admin_only'], true) ? $r['show_on'] : 'order';
            // options: select/radio/checkbox için pipe-separated: "Değer 1|Değer 2|Değer 3"
            $optionsJson = null;
            if (in_array($type, ['select','radio','checkbox'], true)) {
                $opts = array_values(array_filter(array_map('trim', explode('|', (string) ($r['options'] ?? '')))));
                $optionsJson = $opts ? json_encode($opts, JSON_UNESCAPED_UNICODE) : null;
            }
            Connection::insert('product_custom_fields', [
                'product_id'  => $productId,
                'label'       => $label,
                'field_key'   => \App\Support\Slug::make((string) ($r['field_key'] ?? $label)),
                'field_type'  => $type,
                'options'     => $optionsJson,
                'is_required' => !empty($r['is_required']) ? 1 : 0,
                'is_active'   => !empty($r['is_active']) ? 1 : 0,
                'show_on'     => $showOn,
                'sort_order'  => $sort++,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
