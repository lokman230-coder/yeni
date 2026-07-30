<?php

declare(strict_types=1);

namespace App\Modules\Ai\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;
use App\Services\Settings\SettingsManager;

/**
 * Admin > AI Center — kullanım istatistikleri + geçmiş.
 * URL: /admin/ai-center
 */
final class AiCenterController
{
    public function assistant(Request $request): Response { return Response::html((new View())->render('ai::admin.assistant',['title'=>'AI Asistan'])); }

    public function index(Request $request): Response
    {
        $view = new View();
        return Response::html($view->render('ai::admin.center', [
            'title'    => 'AI Center',
            'metrics'  => self::metrics(),
            'recent'   => self::recent(),
            'byContext'=> self::byContext(),
            'byDay'    => self::byDay(30),
            'provider' => SettingsManager::get('ai.provider', 'heuristic'),
            'model'    => SettingsManager::get('ai.model', 'gpt-4o-mini'),
            'hasKey'   => (string) SettingsManager::get('ai.api_key', '') !== '',
        ]));
    }

    private static function metrics(): array
    {
        try {
            $today = date('Y-m-d');
            $month = date('Y-m-01');
            return [
                'total'          => (int) (Connection::selectOne("SELECT COUNT(*) c FROM ai_logs")['c'] ?? 0),
                'today'          => (int) (Connection::selectOne("SELECT COUNT(*) c FROM ai_logs WHERE DATE(created_at) = ?", [$today])['c'] ?? 0),
                'month'          => (int) (Connection::selectOne("SELECT COUNT(*) c FROM ai_logs WHERE DATE(created_at) >= ?", [$month])['c'] ?? 0),
                'tokens_month'   => (int) (Connection::selectOne("SELECT COALESCE(SUM(tokens_used),0) c FROM ai_logs WHERE DATE(created_at) >= ?", [$month])['c'] ?? 0),
                'openai_count'   => (int) (Connection::selectOne("SELECT COUNT(*) c FROM ai_logs WHERE provider = 'openai'")['c'] ?? 0),
                'heuristic_count'=> (int) (Connection::selectOne("SELECT COUNT(*) c FROM ai_logs WHERE provider = 'heuristic'")['c'] ?? 0),
                'sites_generated'=> (int) (Connection::selectOne("SELECT COUNT(*) c FROM ai_logs WHERE context = 'site_generator'")['c'] ?? 0),
            ];
        } catch (\Throwable) {
            return array_fill_keys(['total','today','month','tokens_month','openai_count','heuristic_count','sites_generated'], 0);
        }
    }

    private static function recent(int $limit = 20): array
    {
        try {
            return Connection::select(
                "SELECT id, context, provider, user_type, user_id,
                        LEFT(prompt, 80) prompt_short, tokens_used, created_at
                 FROM ai_logs ORDER BY id DESC LIMIT ?", [$limit]
            );
        } catch (\Throwable) { return []; }
    }

    /** @return array<int, array{context:string, cnt:int}> */
    private static function byContext(): array
    {
        try {
            return Connection::select(
                "SELECT context, COUNT(*) cnt FROM ai_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY context ORDER BY cnt DESC"
            );
        } catch (\Throwable) { return []; }
    }

    /** Son N günün günlük çağrı sayısı */
    private static function byDay(int $days): array
    {
        try {
            $rows = Connection::select(
                "SELECT DATE(created_at) d, COUNT(*) c FROM ai_logs
                 WHERE created_at > DATE_SUB(NOW(), INTERVAL ? DAY)
                 GROUP BY DATE(created_at) ORDER BY d ASC", [$days]
            );
            return $rows;
        } catch (\Throwable) { return []; }
    }
}
