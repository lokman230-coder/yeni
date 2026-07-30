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

    public static function files(int $listingId): array
    {
        try {
            return Connection::select(
                "SELECT id, listing_id, version, file_name, checksum_sha256, changelog, created_at
                 FROM marketplace_files WHERE listing_id = ? AND is_active = 1 ORDER BY id DESC",
                [$listingId]
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public static function purchasesForCustomer(int $customerId): array
    {
        try {
            return Connection::select(
                "SELECT p.*, l.title, l.slug
                 FROM marketplace_purchases p
                 LEFT JOIN marketplace_listings l ON l.id = p.listing_id
                 WHERE p.buyer_id = ? ORDER BY p.id DESC",
                [$customerId]
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public static function issueDownloadToken(int $purchaseId, int $customerId, ?int $fileId = null): array
    {
        $purchase = Connection::selectOne(
            "SELECT * FROM marketplace_purchases WHERE id = ? AND buyer_id = ? AND status = 'paid'",
            [$purchaseId, $customerId]
        );
        if (!$purchase) {
            return ['ok' => false, 'error' => 'purchase_not_found'];
        }

        $file = $fileId
            ? Connection::selectOne("SELECT * FROM marketplace_files WHERE id = ? AND listing_id = ? AND is_active = 1", [$fileId, $purchase['listing_id']])
            : Connection::selectOne("SELECT * FROM marketplace_files WHERE listing_id = ? AND is_active = 1 ORDER BY id DESC", [$purchase['listing_id']]);

        if (!$file) {
            return ['ok' => false, 'error' => 'file_not_found'];
        }

        $token = bin2hex(random_bytes(32));
        Connection::insert('marketplace_download_tokens', [
            'purchase_id' => $purchaseId,
            'file_id' => (int) $file['id'],
            'customer_id' => $customerId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            'download_count' => 0,
            'max_downloads' => 5,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'token' => $token, 'url' => '/marketplace/download/' . $token, 'expires_in' => 3600];
    }

    public static function resolveDownload(string $token): array
    {
        if ($token === '' || strlen($token) < 32) {
            return ['ok' => false, 'error' => 'invalid_token'];
        }

        $row = Connection::selectOne(
            "SELECT t.*, f.file_name, f.file_path, f.checksum_sha256
             FROM marketplace_download_tokens t
             JOIN marketplace_files f ON f.id = t.file_id
             WHERE t.token_hash = ? LIMIT 1",
            [hash('sha256', $token)]
        );

        if (!$row) {
            return ['ok' => false, 'error' => 'token_not_found'];
        }
        if (strtotime((string) $row['expires_at']) < time()) {
            return ['ok' => false, 'error' => 'token_expired'];
        }
        if ((int) $row['download_count'] >= (int) $row['max_downloads']) {
            return ['ok' => false, 'error' => 'download_limit_reached'];
        }

        $path = (string) $row['file_path'];
        if (!is_file($path)) {
            $path = AHO_ROOT . '/' . ltrim($path, '/\\');
        }
        if (!is_file($path)) {
            return ['ok' => false, 'error' => 'file_missing'];
        }

        Connection::update('marketplace_download_tokens', [
            'download_count' => (int) $row['download_count'] + 1,
            'last_downloaded_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$row['id']]);

        return ['ok' => true, 'path' => $path, 'file_name' => $row['file_name'] ?: basename($path)];
    }
}
