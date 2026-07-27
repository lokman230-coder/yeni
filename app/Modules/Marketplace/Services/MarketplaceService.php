<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Services;

use App\Core\Database\Connection;

final class MarketplaceService
{
    public static function categories(): array
    {
        try {
            return Connection::select("SELECT * FROM marketplace_categories WHERE is_active = 1 ORDER BY sort_order, name");
        } catch (\Throwable) { return []; }
    }

    public static function listings(array $filters = []): array
    {
        $where = ['l.status = ?']; $params = ['active'];
        if (!empty($filters['category_id'])) { $where[] = 'l.category_id = ?'; $params[] = $filters['category_id']; }
        if (!empty($filters['q'])) {
            $where[] = '(l.title LIKE ? OR l.description LIKE ?)';
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }
        try {
            return Connection::select(
                "SELECT l.*, c.name AS category_name, cs.first_name AS seller_first, cs.last_name AS seller_last
                 FROM marketplace_listings l
                 LEFT JOIN marketplace_categories c ON c.id = l.category_id
                 LEFT JOIN customers cs ON cs.id = l.seller_id
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY l.created_at DESC LIMIT 100",
                $params
            );
        } catch (\Throwable) { return []; }
    }

    public static function findBySlug(string $slug): ?array
    {
        try {
            return Connection::selectOne(
                "SELECT l.*, c.name AS category_name FROM marketplace_listings l
                 LEFT JOIN marketplace_categories c ON c.id = l.category_id
                 WHERE l.slug = ?", [$slug]
            );
        } catch (\Throwable) { return null; }
    }
}
