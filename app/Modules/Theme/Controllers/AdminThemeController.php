<?php
declare(strict_types=1);

namespace App\Modules\Theme\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Services\Theme\ThemeManager;

final class AdminThemeController
{
    public function index(Request $request): Response
    {
        return Response::html((new View())->render('theme::admin.index', [
            'title' => 'Temalar',
            'themes' => ThemeManager::all(),
            'active' => ThemeManager::active(),
            'success' => flash('success'),
            'error' => flash('error'),
        ]));
    }

    public function activate(Request $request): Response
    {
        $slug = trim((string) $request->input('theme', ''));
        if ($slug === '' || !ThemeManager::setActive($slug)) {
            SessionManager::flash('error', 'Tema bulunamadi veya aktif edilemedi.');
            return Response::redirect('/admin/temalar');
        }

        SessionManager::flash('success', 'Tema aktif edildi: ' . $slug);
        return Response::redirect('/admin/temalar');
    }
}
