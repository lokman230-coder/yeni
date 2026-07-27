<?php

declare(strict_types=1);

namespace App\Core\Http;

final class Request
{
    private array $query;
    private array $post;
    private array $server;
    private array $cookies;
    private array $files;
    private array $headers;
    private ?array $json = null;
    private array $params = [];

    public function __construct()
    {
        $this->query   = $_GET ?? [];
        $this->post    = $_POST ?? [];
        $this->server  = $_SERVER ?? [];
        $this->cookies = $_COOKIE ?? [];
        $this->files   = $_FILES ?? [];
        $this->headers = $this->extractHeaders();
    }

    public static function capture(): self
    {
        return new self();
    }

    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        // Method override (form içinden _method)
        if ($method === 'POST' && isset($this->post['_method'])) {
            $method = strtoupper($this->post['_method']);
        }
        return $method;
    }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $pos = strpos($uri, '?');
        if ($pos !== false) {
            $uri = substr($uri, 0, $pos);
        }
        return '/' . ltrim($uri, '/');
    }

    public function path(): string
    {
        return rtrim($this->uri(), '/') ?: '/';
    }

    public function ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($this->server[$key])) {
                $ip = trim(explode(',', $this->server[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    public function isJson(): bool
    {
        $ct = $this->header('Content-Type') ?? '';
        return str_contains(strtolower($ct), 'application/json');
    }

    public function wantsJson(): bool
    {
        return $this->isAjax() || $this->isJson()
            || str_contains(strtolower($this->header('Accept') ?? ''), 'application/json');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $data = $this->all();
        return $data[$key] ?? $default;
    }

    public function all(): array
    {
        $json = $this->json();
        return array_merge($this->query, $this->post, $json, $this->params);
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $name): ?string
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? null;
    }

    public function json(): array
    {
        if ($this->json !== null) {
            return $this->json;
        }
        if (!$this->isJson()) {
            return $this->json = [];
        }
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        return $this->json = is_array($decoded) ? $decoded : [];
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function only(array $keys): array
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }

    private function extractHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $name = strtolower(str_replace('_', '-', $key));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }
}
