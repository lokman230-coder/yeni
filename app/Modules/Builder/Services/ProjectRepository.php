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

    public static function create(?int $customerId, string $kind, string $sector, string $name, array $settings = [], ?int $templateId = null, ?string $guestToken = null): int
    {
        $slug = Slug::unique($name, 'builder_projects', 'slug');
        return Connection::insert('builder_projects', [
            'customer_id' => $customerId,
            'guest_token' => $guestToken,
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

    public static function findForGuest(int $id, string $guestToken): ?array
    {
        if ($guestToken === '') return null;
        try {
            return Connection::selectOne(
                "SELECT * FROM builder_projects WHERE id = ? AND guest_token = ?",
                [$id, $guestToken]
            );
        } catch (\Throwable) { return null; }
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
        $page = self::findPage($pageId);
        if ($page) {
            self::createRevision(
                (int) $page['project_id'],
                $pageId,
                null,
                'tree',
                [
                    'tree' => json_decode((string) ($page['tree_json'] ?? '{}'), true) ?: [],
                    'saved_from' => 'autosave',
                ],
                'Before tree autosave'
            );
        }

        Connection::update('builder_pages', [
            'tree_json'  => json_encode($tree, JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$pageId]);
    }

    public static function updateSettings(int $projectId, array $settings): void
    {
        $project = self::find($projectId);
        if ($project) {
            self::createRevision(
                $projectId,
                null,
                isset($project['customer_id']) ? (int) $project['customer_id'] : null,
                'settings',
                [
                    'settings' => json_decode((string) ($project['settings'] ?? '{}'), true) ?: [],
                    'saved_from' => 'settings',
                ],
                'Before settings update'
            );
        }

        Connection::update('builder_projects', [
            'settings'   => json_encode($settings, JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$projectId]);
    }

    public static function revisions(int $projectId, ?int $pageId = null, int $limit = 30): array
    {
        $params = [$projectId];
        $where = 'project_id = ?';
        if ($pageId !== null) {
            $where .= ' AND page_id = ?';
            $params[] = $pageId;
        }

        try {
            return Connection::select(
                "SELECT * FROM builder_revisions WHERE {$where} ORDER BY id DESC LIMIT " . max(1, min(100, $limit)),
                $params
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public static function createRevision(
        int $projectId,
        ?int $pageId,
        ?int $customerId,
        string $type,
        array $snapshot,
        ?string $label = null
    ): void {
        try {
            Connection::insert('builder_revisions', [
                'project_id' => $projectId,
                'page_id' => $pageId,
                'customer_id' => $customerId,
                'revision_type' => $type,
                'snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                'label' => $label,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Fresh installs before migration should not break autosave.
        }
    }

    public static function delete(int $id): void
    {
        Connection::query("DELETE FROM builder_projects WHERE id = ?", [$id]);
    }
}
