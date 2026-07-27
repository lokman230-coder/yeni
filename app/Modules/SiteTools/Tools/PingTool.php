<?php
declare(strict_types=1);
namespace App\Modules\SiteTools\Tools;

final class PingTool extends AbstractTool {
    public function slug(): string { return 'ping'; }
    public function label(): string { return 'Ping / Sunucu Yanıtı'; }
    public function description(): string { return '5 kez HTTP ping — erişilebilirlik ve ortalama gecikme.'; }
    public function icon(): string { return '📡'; }
    public function inputPlaceholder(): string { return 'https://ornekdomain.com'; }
    public function run(string $input): array {
        $url = self::normalizeUrl($input);
        $times = []; $codes = [];
        for ($i = 0; $i < 5; $i++) {
            $res = self::fetch($url, 5, false);
            $times[] = $res['success'] ? (int) (($res['total_time_s'] ?? 0) * 1000) : null;
            $codes[] = $res['success'] ? $res['http_code'] : 'ERR';
        }
        $valid = array_filter($times, fn($t) => $t !== null);
        return ['success' => true, 'data' => [
            'url' => $url,
            'attempts' => array_map(fn($t, $c) => ['ms' => $t, 'code' => $c], $times, $codes),
            'avg_ms' => $valid ? (int) round(array_sum($valid) / count($valid)) : null,
            'min_ms' => $valid ? min($valid) : null,
            'max_ms' => $valid ? max($valid) : null,
            'success_rate' => count($valid) * 20, // 5 üzerinden %
        ], 'render' => 'ping'];
    }
}
