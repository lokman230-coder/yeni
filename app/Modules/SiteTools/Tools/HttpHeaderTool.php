<?php
declare(strict_types=1);
namespace App\Modules\SiteTools\Tools;

final class HttpHeaderTool extends AbstractTool {
    public function slug(): string { return 'http-header'; }
    public function label(): string { return 'HTTP Header İnceleme'; }
    public function description(): string { return 'Yanıt başlıklarını (response header) tam olarak gösterir.'; }
    public function icon(): string { return '📄'; }
    public function inputPlaceholder(): string { return 'https://ornekdomain.com'; }
    public function run(string $input): array {
        $url = self::normalizeUrl($input);
        $res = self::fetch($url, 10);
        if (!$res['success']) return ['success' => false, 'error' => $res['error']];
        return ['success' => true, 'data' => [
            'url' => $res['url'],
            'http_code' => $res['http_code'],
            'headers' => $res['headers'],
        ], 'render' => 'http-header'];
    }
}
