<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Core\Database\Connection;
use App\Support\Slug;

/**
 * Package Options — Lokasyon, Panel, OS, PHP, Lisans, Tema, Mobil platform
 * Rapor Madde 5.3
 */
final class OptionService
{
    /** Ürüne ait aktif opsiyonlar (+ değerleri) */
    public static function forProduct(int $productId): array
    {
        $db = Connection::pdo();

        $st = $db->prepare(
            'SELECT * FROM product_options
             WHERE (product_id = ? OR product_id IS NULL)
               AND is_active = 1
             ORDER BY sort_order ASC, id ASC'
        );
        $st->execute([$productId]);
        $options = $st->fetchAll();

        foreach ($options as &$opt) {
            $vs = $db->prepare(
                'SELECT * FROM product_option_values
                 WHERE option_id = ? AND is_active = 1
                 ORDER BY sort_order ASC, id ASC'
            );
            $vs->execute([(int) $opt['id']]);
            $opt['values'] = $vs->fetchAll();
        }
        unset($opt);

        return $options;
    }

    public static function allForAdmin(?int $productId = null): array
    {
        $db = Connection::pdo();
        if ($productId !== null) {
            $st = $db->prepare(
                'SELECT * FROM product_options
                 WHERE product_id = ? OR product_id IS NULL
                 ORDER BY product_id IS NULL, sort_order ASC'
            );
            $st->execute([$productId]);
        } else {
            $st = $db->query('SELECT * FROM product_options ORDER BY id DESC');
        }
        return $st->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Connection::pdo();
        $st = $db->prepare('SELECT * FROM product_options WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }
        $vs = $db->prepare('SELECT * FROM product_option_values WHERE option_id = ? ORDER BY sort_order');
        $vs->execute([$id]);
        $row['values'] = $vs->fetchAll();
        return $row;
    }

    public static function save(array $data, array $values, ?int $id = null): int
    {
        $db = Connection::pdo();

        $payload = [
            'product_id'  => !empty($data['product_id']) ? (int) $data['product_id'] : null,
            'name'        => trim((string) ($data['name'] ?? '')),
            'slug'        => Slug::make((string) ($data['slug'] ?? $data['name'] ?? '')),
            'input_type'  => in_array($data['input_type'] ?? 'select', ['select','radio','checkbox'], true) ? $data['input_type'] : 'select',
            'is_required' => !empty($data['is_required']) ? 1 : 0,
            'is_active'   => !empty($data['is_active']) ? 1 : 0,
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
            'description' => (string) ($data['description'] ?? '') ?: null,
        ];

        if ($id === null) {
            $cols = implode(',', array_keys($payload));
            $ph   = implode(',', array_fill(0, count($payload), '?'));
            $st = $db->prepare("INSERT INTO product_options ($cols, created_at, updated_at) VALUES ($ph, NOW(), NOW())");
            $st->execute(array_values($payload));
            $id = (int) $db->lastInsertId();
        } else {
            $set = implode(',', array_map(fn($k) => "$k = ?", array_keys($payload)));
            $st = $db->prepare("UPDATE product_options SET $set, updated_at = NOW() WHERE id = ?");
            $st->execute([...array_values($payload), $id]);
        }

        // Değerleri toplu güncelle (tam yer değiştirme değil — id gelenler update, id gelmeyenler insert)
        $keepIds = [];
        foreach ($values as $i => $v) {
            $label = trim((string) ($v['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $vRow = [
                'option_id'    => $id,
                'label'        => $label,
                'value_key'    => Slug::make((string) ($v['value_key'] ?? $label)),
                'price_delta'  => (float) ($v['price_delta'] ?? 0),
                'currency'     => (string) ($v['currency'] ?? 'TRY'),
                'period'       => (string) ($v['period'] ?? 'monthly'),
                'is_default'   => !empty($v['is_default']) ? 1 : 0,
                'is_active'    => isset($v['is_active']) ? (int)(bool)$v['is_active'] : 1,
                'sort_order'   => (int) ($v['sort_order'] ?? $i),
            ];
            if (!empty($v['id'])) {
                $set = implode(',', array_map(fn($k) => "$k = ?", array_keys($vRow)));
                $st = $db->prepare("UPDATE product_option_values SET $set, updated_at = NOW() WHERE id = ?");
                $st->execute([...array_values($vRow), (int) $v['id']]);
                $keepIds[] = (int) $v['id'];
            } else {
                $cols = implode(',', array_keys($vRow));
                $ph   = implode(',', array_fill(0, count($vRow), '?'));
                $st = $db->prepare("INSERT INTO product_option_values ($cols, created_at, updated_at) VALUES ($ph, NOW(), NOW())");
                $st->execute(array_values($vRow));
                $keepIds[] = (int) $db->lastInsertId();
            }
        }

        if ($keepIds) {
            $in = implode(',', array_map('intval', $keepIds));
            $db->prepare("DELETE FROM product_option_values WHERE option_id = ? AND id NOT IN ($in)")->execute([$id]);
        } else {
            $db->prepare('DELETE FROM product_option_values WHERE option_id = ?')->execute([$id]);
        }

        return $id;
    }

    public static function delete(int $id): void
    {
        Connection::pdo()->prepare('DELETE FROM product_options WHERE id = ?')->execute([$id]);
    }

    /**
     * Seçilen opsiyonların toplam fiyat farkı (TRY cinsinden)
     * @param array $selected [option_id => value_id, ...]
     */
    public static function calculateDelta(int $productId, array $selected): float
    {
        if (!$selected) {
            return 0.0;
        }
        $db = Connection::pdo();
        $ids = array_map('intval', array_values($selected));
        $ids = array_filter($ids);
        if (!$ids) {
            return 0.0;
        }
        $in = implode(',', $ids);
        $rows = $db->query("SELECT price_delta, currency FROM product_option_values WHERE id IN ($in)")->fetchAll();
        $sum = 0.0;
        foreach ($rows as $r) {
            // Not: currency dönüşümü CartService entegrasyonunda yapılır
            $sum += (float) $r['price_delta'];
        }
        return $sum;
    }

    /**
     * Seçilen opsiyonların snapshot'ını cart_item_options'a yaz
     */
    public static function attachToCartItem(int $cartItemId, array $selected): void
    {
        if (!$selected) {
            return;
        }
        $db = Connection::pdo();
        // Önce eskileri sil
        $db->prepare('DELETE FROM cart_item_options WHERE cart_item_id = ?')->execute([$cartItemId]);

        foreach ($selected as $optionId => $valueId) {
            $valueId = (int) $valueId;
            if ($valueId <= 0) {
                continue;
            }
            $st = $db->prepare(
                'SELECT po.name AS option_name, pov.label, pov.price_delta
                 FROM product_option_values pov
                 JOIN product_options po ON po.id = pov.option_id
                 WHERE pov.id = ? LIMIT 1'
            );
            $st->execute([$valueId]);
            $row = $st->fetch();
            if (!$row) {
                continue;
            }
            $ins = $db->prepare(
                'INSERT INTO cart_item_options
                 (cart_item_id, option_id, value_id, label_snapshot, value_snapshot, price_delta_snapshot, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $ins->execute([
                $cartItemId,
                (int) $optionId,
                $valueId,
                (string) $row['option_name'],
                (string) $row['label'],
                (float) $row['price_delta'],
            ]);
        }
    }

    public static function forCartItem(int $cartItemId): array
    {
        $st = Connection::pdo()->prepare(
            'SELECT * FROM cart_item_options WHERE cart_item_id = ? ORDER BY id ASC'
        );
        $st->execute([$cartItemId]);
        return $st->fetchAll();
    }
}
