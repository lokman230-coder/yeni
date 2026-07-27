<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Http\Request;
use App\Core\Http\Response;

/**
 * Ahost Bilişim uygulama orkestratörü.
 */
final class Application
{
    private string $basePath;
    private Container $container;
    private Router $router;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        $this->container = Container::getInstance();
    }

    public static function boot(string $basePath): self
    {
        $app = new self($basePath);

        // 1. Env yükle
        Env::load($basePath . '/.env');

        // 2. Config yükle
        Config::load($basePath . '/config');

        // 3. Error handler
        ErrorHandler::register((bool) Env::get('APP_DEBUG', false));

        // 4. Session başlat
        SessionManager::start();

        // 5. Container'a temel bağlamalar
        $app->container->instance(Application::class, $app);
        $app->container->instance(Container::class, $app->container);

        // 6. Router
        $app->router = new Router($app->container);
        $app->container->instance(Router::class, $app->router);

        // 7. Modülleri yükle
        ModuleLoader::boot($app->container, $app->router, $basePath);

        // 8. Route dosyalarını yükle
        $webRoutes = $basePath . '/routes/web.php';
        if (file_exists($webRoutes)) {
            $router = $app->router;
            require $webRoutes;
        }

        return $app;
    }

    public function run(): void
    {
        $request = Request::capture();
        $this->container->instance(Request::class, $request);

        $response = $this->router->dispatch($request);
        $response->send();
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? '/' . ltrim($path, '/') : '');
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function router(): Router
    {
        return $this->router;
    }
}
