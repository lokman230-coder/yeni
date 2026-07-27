<?php

declare(strict_types=1);

namespace App\Modules\Builder\Services;

use App\Core\Database\Connection;
use App\Support\Slug;

final class ProjectRepository
{
    public static function forCustomer(int $customerId, ?string $kind = null): array
    {
        $where = 'customer_id = ?';
        $params = [$customerId];
        if ($kind) { $where .= ' AND kind = ?'; $params[] = $kind; }
        try {
            return Connection::select(
                "SELECT * FROM builder_projects WHERE {$where} ORDER BY updated_at DESC",
                $params
            );
        } catch (\Throwable) { return []; }
    }

    public static function find(int $id): ?array
    {
        try {
            return Connection::selectOne("SELECT * FROM builder_projects WHERE id = ?", [$id]);
        } catch (\Throwable) { return null; }
    }

    public static function findForCustomer(int $id, int $customerId): ?array
    {
        try {
            return Connection::selectOne(
                "SELECT * FROM builder_projects WHERE id = ? AND customer_id = ?",
                [$id, $customerId]
            );
        } catch (\Throwable) { return null; }
    }

    public static function create(int $customerId, string $kind, string $sector, string $name, array $settings = [], ?int $templateId = null): int
    {
        $slug = Slug::unique($name, 'builder_projects', 'slug');
        return Connection::insert('builder_projects', [
            'customer_id' => $customerId,
            'kind'        => $kind,
            'sector'      => $sector,
            'template_id' => $templateId,
            'name'        => $name,
            'slug'        => $slug,
            'settings'    => json_encode($settings, JSON_UNESCAPED_UNICODE),
            'status'      => 'draft',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public static function pages(int $projectId): array
    {
        try {
            return Connection::select(
                "SELECT * FROM builder_pages WHERE project_id = ? ORDER BY is_homepage DESC, sort_order ASC, id ASC",
                [$projectId]
            );
        } catch (\Throwable) { return []; }
    }

    public static function homepage(int $projectId): ?array
    {
        try {
            return Connection::selectOne(
                "SELECT * FROM builder_pages WHERE project_id = ? AND is_homepage = 1 LIMIT 1",
                [$projectId]
            );
        } catch (\Throwable) { return null; }
    }

    public static function findPage(int $pageId): ?array
    {
        try {
            return Connection::selectOne("SELECT * FROM builder_pages WHERE id = ?", [$pageId]);
        } catch (\Throwable) { return null; }
    }

    public static function createHomepage(int $projectId, string $projectName, array $tree): int
    {
        return Connection::insert('builder_pages', [
            'project_id'  => $projectId,
            'name'        => 'Ana Sayfa',
            'slug'        => 'anasayfa',
            'is_homepage' => 1,
            'tree_json'   => json_encode($tree, JSON_UNESCAPED_UNICODE),
            'seo_title'   => $projectName,
            'is_published'=> 1,
            'sort_order'  => 0,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public static function savePageTree(int $pageId, array $tree): void
    {
        Connection::update('builder_pages', [
            'tree_json'  => json_encode($tree, JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$pageId]);
    }

    public static function updateSettings(int $projectId, array $settings): void
    {
        Connection::update('builder_projects', [
            'settings'   => json_encode($settings, JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$projectId]);
    }

    public static function delete(int $id): void
    {
        Connection::query("DELETE FROM builder_projects WHERE id = ?", [$id]);
    }
}
