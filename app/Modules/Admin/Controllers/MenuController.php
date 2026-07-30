<?php
declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;

final class MenuController
{
    private static function file(): string
    {
        return AHO_ROOT . '/storage/menu-config.json';
    }

    private static function defaults(): array
    {
        return [
            ['label' => 'Ana sayfa', 'url' => '/', 'location' => 'header', 'active' => 1],
            ['label' => 'Urunler', 'url' => '/urunler', 'location' => 'header', 'active' => 1],
            ['label' => 'Domain', 'url' => '/domain', 'location' => 'header', 'active' => 1],
            ['label' => 'Bilgi Bankasi', 'url' => '/bilgi-bankasi', 'location' => 'footer', 'active' => 1],
            ['label' => 'Iletisim', 'url' => '/iletisim', 'location' => 'footer', 'active' => 1],
        ];
    }

    private static function all(): array
    {
        $file = self::file();

        if (!is_file($file)) {
            return self::defaults();
        }

        $items = json_decode((string) file_get_contents($file), true);

        return is_array($items) ? $items : self::defaults();
    }

    private static function write(array $items): void
    {
        @mkdir(dirname(self::file()), 0775, true);
        file_put_contents(
            self::file(),
            json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    public function index(Request $request): Response
    {
        $view = new View();

        return Response::html($view->render('admin::menu/index', [
            'title' => 'Menu Yonetimi',
            'items' => self::all(),
        ]));
    }

    public function save(Request $request): Response
    {
        $items = self::all();
        $items[] = [
            'label' => trim((string) $request->input('label', '')),
            'url' => trim((string) $request->input('url', '')),
            'location' => (string) $request->input('location', 'header'),
            'active' => 1,
        ];

        self::write($items);
        SessionManager::flash('success', 'Menu ogesi eklendi.');

        return Response::redirect('/admin/menu-yonetimi');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->input('id', -1);
        $items = self::all();

        if (isset($items[$id])) {
            array_splice($items, $id, 1);
            self::write($items);
        }

        return Response::redirect('/admin/menu-yonetimi');
    }

    public function toggle(Request $request): Response
    {
        $id = (int) $request->input('id', -1);
        $items = self::all();

        if (isset($items[$id])) {
            $items[$id]['active'] = empty($items[$id]['active']) ? 1 : 0;
            self::write($items);
        }

        return Response::redirect('/admin/menu-yonetimi');
    }
}
