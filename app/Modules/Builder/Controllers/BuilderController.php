<?php

declare(strict_types=1);

namespace App\Modules\Builder\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Builder\Services\BlockRegistry;
use App\Modules\Builder\Services\ExportService;
use App\Modules\Builder\Services\ProjectRepository;
use App\Modules\Builder\Services\TemplateLibrary;
use App\Services\Auth\AuthService;

final class BuilderController
{
    /** Builder ana sayfa: kullanıcının projeleri + yeni proje wizard. */
    public function index(Request $request): Response
    {
        $kind = $this->kindFromPath($request);
        $view = new View();

        if (!AuthService::isCustomer()) {
            // Giriş yapmamış → demo/tanıtım sayfası
            return Response::html($view->render('builder::demo', [
                'title'    => $kind === 'mobile' ? 'Mobile Builder' : 'Site Builder',
                'kind'     => $kind,
                'sectors'  => $kind === 'mobile' ? TemplateLibrary::mobileSectors() : TemplateLibrary::siteSectors(),
            ]));
        }

        $customer = AuthService::customer();
        $projects = ProjectRepository::forCustomer((int)$customer['id'], $kind);

        return Response::html($view->render('builder::projects', [
            'title'    => $kind === 'mobile' ? 'Mobile Builder Projelerim' : 'Site Builder Projelerim',
            'kind'     => $kind,
            'projects' => $projects,
            'sectors'  => $kind === 'mobile' ? TemplateLibrary::mobileSectors() : TemplateLibrary::siteSectors(),
        ]));
    }

    /** Yeni proje oluştur (POST). */
    public function create(Request $request): Response
    {
        if (!AuthService::isCustomer()) return Response::redirect('/giris');
        $kind = $this->kindFromPath($request);
        $customer = AuthService::customer();

        $name   = trim((string) $request->input('name', ''));
        $sector = (string) $request->input('sector', 'agency');

        if ($name === '') {
            SessionManager::flash('error', 'Proje adı zorunlu.');
            return Response::redirect('/' . ($kind === 'mobile' ? 'mobile-builder' : 'site-builder'));
        }

        $projectId = ProjectRepository::create(
            (int)$customer['id'], $kind, $sector, $name,
            ['app_name' => $name, 'colors' => ['primary' => '#0284c7', 'accent' => '#06b6d4'], 'font' => 'Inter']
        );

        // Başlangıç sayfası oluştur
        $tree = TemplateLibrary::starterTree($kind, $sector, ['app_name' => $name]);
        ProjectRepository::createHomepage($projectId, $name, $tree);

        SessionManager::flash('success', 'Proje oluşturuldu.');
        return Response::redirect('/panel/builder/' . $projectId);
    }

    /** Editor açılışı. */
    public function editor(Request $request): Response
    {
        if (!AuthService::isCustomer()) return Response::redirect('/giris');
        $customer = AuthService::customer();
        $projectId = (int) $request->param('id');
        $project = ProjectRepository::findForCustomer($projectId, (int)$customer['id']);
        if (!$project) return Response::notFound('Proje bulunamadı');

        $pages = ProjectRepository::pages($projectId);
        $activePage = ProjectRepository::homepage($projectId) ?? ($pages[0] ?? null);
        $settings = json_decode((string) $project['settings'], true) ?: [];

        $view = new View();
        return Response::html($view->render('builder::editor', [
            'title'         => 'Editor: ' . $project['name'],
            'project'       => $project,
            'pages'         => $pages,
            'active_page'   => $activePage,
            'settings'      => $settings,
            'block_groups'  => BlockRegistry::grouped($project['kind'], $project['sector']),
            'category_labels' => BlockRegistry::categoryLabels(),
        ]));
    }

    /** Sayfa tree'sini AJAX kaydet. */
    public function saveTree(Request $request): Response
    {
        if (!AuthService::isCustomer()) return Response::json(['ok' => false, 'error' => 'auth'], 401);
        $customer = AuthService::customer();
        $projectId = (int) $request->param('id');
        $pageId = (int) $request->param('page');

        $project = ProjectRepository::findForCustomer($projectId, (int)$customer['id']);
        $page = ProjectRepository::findPage($pageId);
        if (!$project || !$page || (int)$page['project_id'] !== $projectId) {
            return Response::json(['ok' => false, 'error' => 'not_found'], 404);
        }

        $tree = $request->input('tree');
        if (!is_array($tree)) return Response::json(['ok' => false, 'error' => 'invalid'], 400);

        ProjectRepository::savePageTree($pageId, $tree);
        return Response::json(['ok' => true, 'saved_at' => date('c')]);
    }

    /** Proje ayarlarını AJAX kaydet (renk, font, app_name). */
    public function saveSettings(Request $request): Response
    {
        if (!AuthService::isCustomer()) return Response::json(['ok' => false], 401);
        $customer = AuthService::customer();
        $projectId = (int) $request->param('id');
        $project = ProjectRepository::findForCustomer($projectId, (int)$customer['id']);
        if (!$project) return Response::json(['ok' => false], 404);

        $existing = json_decode((string) $project['settings'], true) ?: [];
        $updates = (array) $request->input('settings', []);
        $merged = array_replace_recursive($existing, $updates);
        ProjectRepository::updateSettings($projectId, $merged);
        return Response::json(['ok' => true, 'settings' => $merged]);
    }

    /** Canlı önizleme (iframe kaynağı). */
    public function preview(Request $request): Response
    {
        if (!AuthService::isCustomer()) return Response::redirect('/giris');
        $customer = AuthService::customer();
        $projectId = (int) $request->param('id');
        $project = ProjectRepository::findForCustomer($projectId, (int)$customer['id']);
        if (!$project) return Response::notFound();

        $pages = ProjectRepository::pages($projectId);
        $html = ExportService::siteToHtml($project, $pages);
        return Response::html($html);
    }

    /** ZIP export. */
    public function export(Request $request): Response
    {
        if (!AuthService::isCustomer()) return Response::redirect('/giris');
        $customer = AuthService::customer();
        $projectId = (int) $request->param('id');
        $project = ProjectRepository::findForCustomer($projectId, (int)$customer['id']);
        if (!$project) return Response::notFound();

        $pages = ProjectRepository::pages($projectId);
        $dir = AHO_ROOT . '/storage/builder-exports';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $fileName = 'ahost-' . $project['slug'] . '-' . date('YmdHis') . '.zip';
        $path = $dir . '/' . $fileName;
        ExportService::toZip($project, $pages, $path);

        return Response::make(
            (string) file_get_contents($path),
            200,
            [
                'Content-Type'        => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]
        );
    }

    /** Builder içinden görsel yükleme (blok arkaplan, hero background vb.) */
    public function upload(Request $request): Response
    {
        if (!AuthService::isCustomer()) {
            return Response::json(['ok' => false, 'error' => 'auth'], 401);
        }
        $customer = AuthService::customer();
        $projectId = (int) $request->input('project_id', 0);
        $project = ProjectRepository::findForCustomer($projectId, (int)$customer['id']);
        if (!$project) {
            return Response::json(['ok' => false, 'error' => 'proje yok'], 404);
        }

        $file = $_FILES['file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return Response::json(['ok' => false, 'error' => 'dosya yok']);
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            return Response::json(['ok' => false, 'error' => '5MB üstü kabul edilmez']);
        }

        $mime = mime_content_type($file['tmp_name']) ?: '';
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/svg+xml' => 'svg'];
        if (!isset($allowed[$mime])) {
            return Response::json(['ok' => false, 'error' => 'sadece görsel dosyalar (jpg/png/webp/gif/svg)']);
        }
        $ext = $allowed[$mime];

        $dir = AHO_ROOT . '/public/uploads/builder/' . $projectId;
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $name = bin2hex(random_bytes(8)) . '.' . $ext;
        $path = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return Response::json(['ok' => false, 'error' => 'yüklenemedi']);
        }

        $url = '/uploads/builder/' . $projectId . '/' . $name;
        return Response::json(['ok' => true, 'url' => $url, 'size' => filesize($path)]);
    }

    private function kindFromPath(Request $request): string
    {
        return str_starts_with($request->path(), '/mobile-builder') || str_contains($request->path(), '/mobile') ? 'mobile' : 'site';
    }
}
