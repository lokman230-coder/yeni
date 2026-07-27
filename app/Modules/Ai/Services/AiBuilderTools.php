<?php

declare(strict_types=1);

namespace App\Modules\Ai\Services;

use App\Core\Database\Connection;

/**
 * Builder içinde AI'nin yapabileceği düzenlemeler.
 * Şema: builder_projects (meta) + builder_pages (tree_json içerik)
 * Her tool aktif sayfanın tree_json'unu mutasyonlar.
 */
final class AiBuilderTools
{
    public static function register(): void
    {
        $reg = AiToolRegistry::class;

        // 1) Blok ekle
        $reg::register('builder', [
            'name'        => 'add_block',
            'description' => 'Projeye yeni bir blok ekle.',
            'params'      => [
                'project_id' => ['type' => 'integer'],
                'type'       => ['type' => 'string'],
                'position'   => ['type' => 'integer'],
                'props'      => ['type' => 'object'],
            ],
            'required'    => ['project_id', 'type'],
            'handler'     => function (array $a) {
                $pid = (int) $a['project_id'];
                $page = self::homepage($pid);
                if (!$page) return ['ok' => false, 'message' => 'Proje veya sayfa bulunamadı.'];

                $tree = json_decode($page['tree_json'] ?? '{}', true) ?: ['version' => 1, 'blocks' => []];
                $tree['blocks'] ??= [];

                $newBlock = [
                    'type'  => (string) $a['type'],
                    'props' => (array) ($a['props'] ?? ['title' => 'Yeni Blok', 'subtitle' => '', 'description' => '']),
                ];
                $pos = isset($a['position']) ? (int) $a['position'] : count($tree['blocks']);
                array_splice($tree['blocks'], $pos, 0, [$newBlock]);

                self::saveTree($page['id'], $tree);
                return ['ok' => true, 'message' => "✅ '{$a['type']}' bloğu eklendi (pozisyon $pos)."];
            },
        ]);

        // 2) Blok metin değiştir
        $reg::register('builder', [
            'name'        => 'update_block_text',
            'description' => 'Bir bloktaki metni güncelle.',
            'params'      => [
                'project_id'  => ['type' => 'integer'],
                'block_index' => ['type' => 'integer'],
                'field'       => ['type' => 'string'],
                'value'       => ['type' => 'string'],
            ],
            'required'    => ['project_id', 'block_index', 'field', 'value'],
            'handler'     => function (array $a) {
                $page = self::homepage((int) $a['project_id']);
                if (!$page) return ['ok' => false, 'message' => 'Sayfa bulunamadı.'];

                $tree = json_decode($page['tree_json'] ?? '{}', true) ?: ['blocks' => []];
                $idx = (int) $a['block_index'];
                if (!isset($tree['blocks'][$idx])) return ['ok' => false, 'message' => "Blok #$idx yok."];

                $tree['blocks'][$idx]['props'][(string)$a['field']] = (string) $a['value'];
                self::saveTree($page['id'], $tree);
                return ['ok' => true, 'message' => "✏️ Blok #$idx '{$a['field']}' güncellendi."];
            },
        ]);

        // 3) Renk paleti
        $reg::register('builder', [
            'name'        => 'change_color_palette',
            'description' => 'Renk paletini değiştir.',
            'params'      => [
                'project_id' => ['type' => 'integer'],
                'palette'    => ['type' => 'string'],
            ],
            'required'    => ['project_id', 'palette'],
            'handler'     => function (array $a) {
                $palettes = [
                    'pastel' => ['primary' => '#ffb3ba', 'accent' => '#bae1ff', 'bg' => '#fff5f5'],
                    'dark'   => ['primary' => '#0f172a', 'accent' => '#38bdf8', 'bg' => '#020617'],
                    'ocean'  => ['primary' => '#0ea5e9', 'accent' => '#06b6d4', 'bg' => '#f0f9ff'],
                    'sunset' => ['primary' => '#f97316', 'accent' => '#eab308', 'bg' => '#fff7ed'],
                    'forest' => ['primary' => '#059669', 'accent' => '#65a30d', 'bg' => '#f0fdf4'],
                    'bold'   => ['primary' => '#dc2626', 'accent' => '#7c3aed', 'bg' => '#fafafa'],
                ];
                $p = $palettes[$a['palette']] ?? $palettes['ocean'];
                $pid = (int) $a['project_id'];

                // Palet proje settings'e kaydedilir
                $project = Connection::selectOne("SELECT settings FROM builder_projects WHERE id = ?", [$pid]);
                if (!$project) return ['ok' => false, 'message' => 'Proje bulunamadı.'];

                $settings = json_decode($project['settings'] ?? '{}', true) ?: [];
                $settings['palette'] = $p;
                $settings['palette_name'] = $a['palette'];

                Connection::update('builder_projects', [
                    'settings'   => json_encode($settings, JSON_UNESCAPED_UNICODE),
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$pid]);

                return ['ok' => true, 'message' => "🎨 Palet **{$a['palette']}** olarak değiştirildi."];
            },
        ]);

        // 4) Blok sil
        $reg::register('builder', [
            'name'        => 'delete_block',
            'description' => 'Bir bloğu sil.',
            'params'      => [
                'project_id'  => ['type' => 'integer'],
                'block_index' => ['type' => 'integer'],
                'confirm'     => ['type' => 'boolean'],
            ],
            'required'    => ['project_id', 'block_index'],
            'destructive' => true,
            'handler'     => function (array $a) {
                $page = self::homepage((int) $a['project_id']);
                if (!$page) return ['ok' => false, 'message' => 'Sayfa bulunamadı.'];
                $tree = json_decode($page['tree_json'] ?? '{}', true) ?: ['blocks' => []];
                $idx = (int) $a['block_index'];
                if (!isset($tree['blocks'][$idx])) return ['ok' => false, 'message' => "Blok #$idx yok."];

                array_splice($tree['blocks'], $idx, 1);
                self::saveTree($page['id'], $tree);
                return ['ok' => true, 'message' => "🗑 Blok #$idx silindi."];
            },
        ]);

        // 5) İçerik üret
        $reg::register('builder', [
            'name'        => 'generate_block_content',
            'description' => 'AI ile blok içeriği üret.',
            'params'      => [
                'project_id'  => ['type' => 'integer'],
                'block_index' => ['type' => 'integer'],
                'topic'       => ['type' => 'string'],
            ],
            'required'    => ['project_id', 'block_index', 'topic'],
            'handler'     => function (array $a) {
                $page = self::homepage((int) $a['project_id']);
                if (!$page) return ['ok' => false, 'message' => 'Sayfa bulunamadı.'];
                $tree = json_decode($page['tree_json'] ?? '{}', true) ?: ['blocks' => []];
                $idx = (int) $a['block_index'];
                if (!isset($tree['blocks'][$idx])) return ['ok' => false, 'message' => "Blok #$idx yok."];

                $topic = (string) $a['topic'];
                $gen = ContentGenerator::generate('block', ['topic' => $topic, 'block_type' => $tree['blocks'][$idx]['type'] ?? 'hero']);
                $tree['blocks'][$idx]['props']['title']       = $gen['title'] ?? $topic;
                $tree['blocks'][$idx]['props']['subtitle']    = $gen['subtitle'] ?? '';
                $tree['blocks'][$idx]['props']['description'] = $gen['description'] ?? '';
                self::saveTree($page['id'], $tree);
                return ['ok' => true, 'message' => "🤖 Blok #$idx içeriği güncellendi: **" . $gen['title'] . "**"];
            },
        ]);

        // 6) Blokları listele
        $reg::register('builder', [
            'name'        => 'list_blocks',
            'description' => 'Projedeki blokları listele.',
            'params'      => [
                'project_id' => ['type' => 'integer'],
            ],
            'required'    => ['project_id'],
            'handler'     => function (array $a) {
                $page = self::homepage((int) $a['project_id']);
                if (!$page) return ['ok' => false, 'message' => 'Sayfa bulunamadı.'];
                $tree = json_decode($page['tree_json'] ?? '{}', true) ?: ['blocks' => []];
                $blocks = $tree['blocks'] ?? [];
                if (!$blocks) return ['ok' => true, 'message' => "📄 Proje boş — henüz blok yok."];

                $lines = ["📄 **" . count($blocks) . " blok:**"];
                foreach ($blocks as $i => $b) {
                    $title = $b['props']['title'] ?? '(başlıksız)';
                    $lines[] = "  $i. **{$b['type']}** — $title";
                }
                return ['ok' => true, 'message' => implode("\n", $lines), 'data' => $blocks];
            },
        ]);
    }

    private static function homepage(int $projectId): ?array
    {
        // Anasayfa (is_homepage=1) veya ilk sayfa
        $page = Connection::selectOne(
            "SELECT * FROM builder_pages WHERE project_id = ? ORDER BY is_homepage DESC, sort_order ASC LIMIT 1",
            [$projectId]
        );
        return $page ?: null;
    }

    private static function saveTree(int $pageId, array $tree): void
    {
        Connection::update('builder_pages', [
            'tree_json'  => json_encode($tree, JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$pageId]);
    }
}
