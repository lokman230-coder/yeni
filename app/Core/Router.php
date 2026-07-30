<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Http\Request;
use App\Core\Http\Response;
use Closure;

/**
 * Basit ama güçlü router — grup, middleware, named route, parametre.
 */
final class Router
{
    /** @var array<int, array{method:string, path:string, handler:mixed, middleware:array, name:?string}> */
    private array $routes = [];

    private array $groupStack = [];

    private array $namedRoutes = [];

    private Container $container;

    private static ?Router $instance = null;

    public function __construct(Container $container)
    {
        $this->container = $container;
        self::$instance = $this;
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Router henüz oluşturulmadı.');
        }
        return self::$instance;
    }

    public function get(string $path, mixed $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    public function put(string $path, mixed $handler): self
    {
        return $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, mixed $handler): self
    {
        return $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, mixed $handler): self
    {
        return $this->add('DELETE', $path, $handler);
    }

    public function any(array $methods, string $path, mixed $handler): self
    {
        foreach ($methods as $m) {
            $this->add(strtoupper($m), $path, $handler);
        }
        return $this;
    }

    public function group(array $attributes, Closure $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    public function name(string $name): self
    {
        $lastKey = array_key_last($this->routes);
        if ($lastKey !== null) {
            $this->routes[$lastKey]['name'] = $name;
            $this->namedRoutes[$name] = $this->routes[$lastKey]['path'];
        }
        return $this;
    }

    public function middleware(string|array $middleware): self
    {
        $lastKey = array_key_last($this->routes);
        if ($lastKey !== null) {
            $existing = $this->routes[$lastKey]['middleware'];
            $add = is_array($middleware) ? $middleware : [$middleware];
            $this->routes[$lastKey]['middleware'] = array_merge($existing, $add);
        }
        return $this;
    }

    private function add(string $method, string $path, mixed $handler): self
    {
        $prefix = '';
        $middleware = [];
        foreach ($this->groupStack as $group) {
            if (!empty($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
            if (!empty($group['middleware'])) {
                $mw = is_array($group['middleware']) ? $group['middleware'] : [$group['middleware']];
                $middleware = array_merge($middleware, $mw);
            }
        }

        $fullPath = '/' . trim($prefix . '/' . trim($path, '/'), '/');
        if ($fullPath === '/' && $path !== '/') {
            $fullPath = '/';
        }

        $this->routes[] = [
            'method'     => $method,
            'path'       => $fullPath,
            'handler'    => $handler,
            'middleware' => $middleware,
            'name'       => null,
        ];

        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path   = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method && !($method === 'HEAD' && $route['method'] === 'GET')) {
                continue;
            }

            $pattern = $this->pathToRegex($route['path']);
            if (preg_match($pattern, $path, $matches)) {
                $params = [];
                foreach ($matches as $k => $v) {
                    if (!is_int($k)) {
                        $params[$k] = $v;
                    }
                }
                $request->setParams($params);

                return $this->runWithMiddleware($request, $route);
            }
        }

        // Not found
        return $this->handleNotFound($request);
    }

    private function pathToRegex(string $path): string
    {
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)(\*|\?)?\}/', function ($m) {
            $name = $m[1];
            $modifier = $m[2] ?? '';
            return match ($modifier) {
                '*'     => '(?<' . $name . '>.+)',           // wildcard: geri kalan tüm path
                '?'     => '(?:/(?<' . $name . '>[^/]+))?',  // optional
                default => '(?<' . $name . '>[^/]+)',        // tek segment
            };
        }, $path);

        $regex = str_replace('/(?:/(?<', '(?:/(?<', $regex);
        return '#^' . $regex . '$#';
    }

    private function runWithMiddleware(Request $request, array $route): Response
    {
        // Global middleware'ler her route'a önce uygulanır (config/app.php)
        $global = (array) Config::get('app.global_middleware', []);
        $middleware = array_merge($global, (array) $route['middleware']);
        $handler = $route['handler'];

        $next = function (Request $req) use ($handler): Response {
            return $this->invoke($handler, $req);
        };

        // Zinciri tersinden sar
        foreach (array_reverse($middleware) as $mw) {
            $current = $next;
            $next = function (Request $req) use ($mw, $current): Response {
                $instance = is_string($mw) ? $this->container->get($this->resolveMiddleware($mw)) : $mw;
                return $instance->handle($req, $current);
            };
        }

        return $next($request);
    }

    private function resolveMiddleware(string $alias): string
    {
        $map = Config::get('app.middleware_aliases', []);
        return $map[$alias] ?? $alias;
    }

    private function invoke(mixed $handler, Request $request): Response
    {
        if ($handler instanceof Closure) {
            $result = $handler($request);
        } elseif (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $result = $this->container->call([$class, $method], ['request' => $request]);
        } elseif (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler);
            $result = $this->container->call([$class, $method], ['request' => $request]);
        } else {
            throw new \RuntimeException('Geçersiz route handler.');
        }

        if ($result instanceof Response) {
            return $result;
        }
        if (is_array($result)) {
            return Response::json($result);
        }
        return Response::html((string)$result);
    }

    private function handleNotFound(Request $request): Response
    {
        if ($request->wantsJson()) {
            return Response::json(['error' => 'Not Found'], 404);
        }
        // Tema 404 varsa
        $path = $request->path();
        $tpl = AHO_ROOT . '/themes/default/partials/' . (str_starts_with($path, '/admin') ? '404-admin.php' : '404.php');
        if (file_exists($tpl)) {
            ob_start();
            $message = 'Not Found';
            include $tpl;
            return Response::html((string)ob_get_clean(), 404);
        }
        return Response::html('<h1>404 - Sayfa Bulunamadı</h1>', 404);
    }

    public function url(string $name, array $params = []): string
    {
        $path = $this->namedRoutes[$name] ?? '/';
        foreach ($params as $k => $v) {
            $path = str_replace(['{' . $k . '}', '{' . $k . '?}'], (string)$v, $path);
        }
        // Kullanılmayan optional param'ları temizle
        $path = preg_replace('/\{[a-zA-Z_][a-zA-Z0-9_]*\??\}/', '', $path) ?? $path;
        return $path;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
