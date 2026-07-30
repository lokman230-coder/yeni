<?php
declare(strict_types=1);
namespace App\Modules\SiteTools\Tools;

final class SecurityHeadersTool extends AbstractTool {
    public function slug(): string { return 'guvenlik-basliklari'; }
    public function label(): string { return 'Güvenlik Başlıkları'; }
    public function description(): string { return 'CSP, HSTS, X-Frame, Referrer, Permissions-Policy vb. kontrolü.'; }
    public function icon(): string { return '🛡️'; }
    public function inputPlaceholder(): string { return 'https://ornekdomain.com'; }
    public function run(string $input): array {
        $url = self::normalizeUrl($input);
        $res = self::fetch($url, 10);
        if (!$res['success']) return ['success' => false, 'error' => $res['error']];

        $checks = [
            'Strict-Transport-Security'   => 'HSTS — HTTPS zorlama',
            'Content-Security-Policy'     => 'CSP — XSS koruması',
            'X-Frame-Options'             => 'Clickjacking koruması',
            'X-Content-Type-Options'      => 'MIME sniff koruması',
            'Referrer-Policy'             => 'Referrer bilgisi kontrolü',
            'Permissions-Policy'          => 'Kamera/mikrofon/konum izni kontrolü',
            'X-XSS-Protection'            => 'Eski XSS koruması (isteğe bağlı)',
        ];

        $out = [];
        $passed = 0;
        foreach ($checks as $header => $desc) {
            $exists = isset($res['headers'][$header]);
            if ($exists) $passed++;
            $out[] = ['header' => $header, 'description' => $desc, 'present' => $exists, 'value' => $exists ? $res['headers'][$header] : null];
        }

        $score = (int) round(($passed / count($checks)) * 100);
        $grade = match (true) {
            $score >= 85 => 'A',
            $score >= 70 => 'B',
            $score >= 55 => 'C',
            $score >= 40 => 'D',
            default      => 'F',
        };

        return ['success' => true, 'data' => [
            'url' => $res['url'],
            'checks' => $out, 'score' => $score, 'grade' => $grade,
        ], 'render' => 'security-headers'];
    }
}
