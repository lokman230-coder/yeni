<?php

declare(strict_types=1);

namespace App\Core\Http;

final class Response
{
    private int $status = 200;
    private array $headers = [];
    private string $body = '';

    public static function make(string $body = '', int $status = 200, array $headers = []): self
    {
        $r = new self();
        $r->body = $body;
        $r->status = $status;
        foreach ($headers as $k => $v) {
            $r->header($k, $v);
        }
        return $r;
    }

    public static function html(string $html, int $status = 200): self
    {
        return self::make($html, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function json(array $data, int $status = 200): self
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return self::make($body ?: '{}', $status, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return self::make('', $status, ['Location' => self::withBasePath($url)]);
    }

    public static function notFound(string $body = 'Not Found'): self
    {
        if (!self::expectsJson()) {
            $html = self::renderNotFound($body);
            if ($html !== null) {
                return self::html($html, 404);
            }
        }
        return self::make($body, 404, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function error(string $body = 'Server Error', int $status = 500): self
    {
        return self::make($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function status(int $code): self
    {
        $this->status = $code;
        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function send(): void
    {
        $body = $this->body;
        $contentType = $this->headers['Content-Type'] ?? '';
        if (str_contains($contentType, 'text/html') && defined('AHO_BASE_PATH') && AHO_BASE_PATH !== '') {
            $body = self::rewriteHtmlBasePaths($body);
        }
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }
        echo $body;
    }

    /**
     * Sistem hangi klasöre kurulursa kurulsun (kök ya da alt klasör) doğru
     * çalışsın diye: üretilen HTML içindeki kök-göreli linkleri
     * (href="/...", src="/...", action="/...", fetch('/...') vb.) ve
     * yönlendirmeleri gerçek kurulum yoluyla (AHO_BASE_PATH) önekler.
     * Zaten kökte kuruluysa (AHO_BASE_PATH boşsa) hiçbir şey değişmez.
     */
    private static function rewriteHtmlBasePaths(string $html): string
    {
        $base = AHO_BASE_PATH;
        $original = $html;
        // "//" (protokolden bağımsız harici URL) ile başlayanlara dokunma.
        $html = preg_replace('/\b(href|src|action)="\/(?!\/)/', '$1="' . $base . '/', $html) ?? $original;
        $html = preg_replace('/\b(href|src|action)=\'\/(?!\/)/', "$1='" . $base . '/', $html) ?? $html;
        // Sık kullanılan JS kalıpları (fetch/location.href) için de aynısı.
        $html = preg_replace('/(fetch\(\s*[\'"])\/(?!\/)/', '$1' . $base . '/', $html) ?? $html;
        $html = preg_replace('/(location\.href\s*=\s*[\'"])\/(?!\/)/', '$1' . $base . '/', $html) ?? $html;
        return $html;
    }

    private static function withBasePath(string $url): string
    {
        if (defined('AHO_BASE_PATH') && AHO_BASE_PATH !== '' && str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return AHO_BASE_PATH . $url;
        }
        return $url;
    }

    public function getBody(): string { return $this->body; }
    public function getStatus(): int { return $this->status; }
    public function getHeaders(): array { return $this->headers; }

    private static function expectsJson(): bool
    {
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $requested = (string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        return str_contains($accept, 'application/json') || strtolower($requested) === 'xmlhttprequest';
    }

    private static function renderNotFound(string $message): ?string
    {
        if (!defined('AHO_ROOT')) {
            return null;
        }
        $path = function_exists('ao_request_path_no_base')
            ? ao_request_path_no_base()
            : (parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
        $isAdmin = str_starts_with($path, '/admin');
        $tpl = AHO_ROOT . '/themes/default/partials/' . ($isAdmin ? '404-admin.php' : '404.php');
        if (!file_exists($tpl)) {
            return null;
        }

        ob_start();
        include $tpl;
        return (string)ob_get_clean();
    }
}
