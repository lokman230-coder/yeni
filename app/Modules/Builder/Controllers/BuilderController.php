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
    public function index(Request $request): Response
    {
        $kind = $this->kindFromPath($request);
        $view = new View();

        $projects = AuthService::isCustomer()
            ? ProjectRepository::forCustomer((int)AuthService::customer()['id'], $kind)
            : [];

        return Response::html($view->render('builder::projects', [
            'title' => $kind === 'mobile' ? 'Mobile Builder Projelerim' : 'Site Builder Projelerim',
            'kind' => $kind,
            'projects' => $projects,
            'sectors' => $kind === 'mobile' ? TemplateLibrary::mobileSectors() : TemplateLibrary::siteSectors(),
            'selected_sector' => (string) $request->query('sector', ''),
        ]));
    }

    public function create(Request $request): Response
    {
        $kind = $this->kindFromPath($request);
        $customer = AuthService::isCustomer() ? AuthService::customer() : null;
        $guestToken = $customer ? null : $this->guestToken();
        $name = trim((string) $request->input('name', ''));
        $sector = (string) $request->input('sector', 'agency');
        $templateId = (int) $request->input('template_id', 0);
        $template = $templateId > 0 ? TemplateLibrary::get($templateId) : null;

        if ($template && (string)($template['kind'] ?? '') === $kind) {
            $sector = (string)($template['sector'] ?? $sector);
        }

        $sectorLabels = $kind === 'mobile' ? TemplateLibrary::mobileSectors() : TemplateLibrary::siteSectors();
        if (!isset($sectorLabels[$sector])) {
            $sector = array_key_first($sectorLabels) ?: ($kind === 'mobile' ? 'corporate' : 'agency');
        }

        if ($name === '') {
            $base = (string)($template['name'] ?? ($sectorLabels[$sector]['label'] ?? ($kind === 'mobile' ? 'Mobil Uygulama' : 'Web Sitesi')));
            $name = $base . ' Projem';
        }

        $projectId = ProjectRepository::create(
            $customer ? (int)$customer['id'] : null,
            $kind,
            $sector,
            $name,
            ['app_name' => $name, 'colors' => ['primary' => '#0284c7', 'accent' => '#06b6d4'], 'font' => 'Inter'],
            $templateId > 0 ? $templateId : null,
            $guestToken
        );

        $tree = [];
        if ($template && !empty($template['tree_json'])) {
            $tree = json_decode((string)$template['tree_json'], true) ?: [];
        }
        if (!$tree) {
            $tree = TemplateLibrary::starterTree($kind, $sector, ['app_name' => $name]);
        }
        ProjectRepository::createHomepage($projectId, $name, $tree);

        SessionManager::flash('success', 'Proje olusturuldu.');
        return Response::redirect('/panel/builder/' . $projectId);
    }

    public function editor(Request $request): Response
    {
        $projectId = (int) $request->param('id');
        $project = $this->projectForCurrentVisitor($projectId);
        if (!$project) {
            return Response::notFound('Proje bulunamadi');
        }

        $pages = ProjectRepository::pages($projectId);
        $activePage = ProjectRepository::homepage($projectId) ?? ($pages[0] ?? null);
        $settings = json_decode((string) $project['settings'], true) ?: [];

        return Response::html((new View())->render('builder::editor', [
            'title' => 'Editor: ' . $project['name'],
            'project' => $project,
            'pages' => $pages,
            'active_page' => $activePage,
            'settings' => $settings,
            'block_groups' => BlockRegistry::grouped($project['kind'], $project['sector']),
            'category_labels' => BlockRegistry::categoryLabels(),
            'is_guest' => !AuthService::isCustomer(),
        ]));
    }

    public function saveTree(Request $request): Response
    {
        $projectId = (int) $request->param('id');
        $pageId = (int) $request->param('page');

        $project = $this->projectForCurrentVisitor($projectId);
        $page = ProjectRepository::findPage($pageId);
        if (!$project || !$page || (int)$page['project_id'] !== $projectId) {
            return Response::json(['ok' => false, 'error' => 'not_found'], 404);
        }

        $tree = $request->input('tree');
        if (!is_array($tree)) {
            return Response::json(['ok' => false, 'error' => 'invalid'], 400);
        }

        ProjectRepository::savePageTree($pageId, $tree);
        return Response::json(['ok' => true, 'saved_at' => date('c')]);
    }

    public function saveSettings(Request $request): Response
    {
        $projectId = (int) $request->param('id');
        $project = $this->projectForCurrentVisitor($projectId);
        if (!$project) {
            return Response::json(['ok' => false], 404);
        }

        $existing = json_decode((string) $project['settings'], true) ?: [];
        $updates = (array) $request->input('settings', []);
        $merged = array_replace_recursive($existing, $updates);
        ProjectRepository::updateSettings($projectId, $merged);
        return Response::json(['ok' => true, 'settings' => $merged]);
    }

    public function preview(Request $request): Response
    {
        $projectId = (int) $request->param('id');
        $project = $this->projectForCurrentVisitor($projectId);
        if (!$project) {
            return Response::notFound();
        }

        $pages = ProjectRepository::pages($projectId);
        return Response::html(ExportService::siteToHtml($project, $pages));
    }

    public function export(Request $request): Response
    {
        $projectId = (int) $request->param('id');
        $project = $this->projectForCurrentVisitor($projectId);
        if (!$project) {
            return Response::notFound();
        }

        $pages = ProjectRepository::pages($projectId);
        $dir = AHO_ROOT . '/storage/builder-exports';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $fileName = 'ahost-' . $project['slug'] . '-' . date('YmdHis') . '.zip';
        $path = $dir . '/' . $fileName;
        ExportService::toZip($project, $pages, $path);

        return Response::make((string) file_get_contents($path), 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function upload(Request $request): Response
    {
        $projectId = (int) $request->input('project_id', 0);
        $project = $this->projectForCurrentVisitor($projectId);
        if (!$project) {
            return Response::json(['ok' => false, 'error' => 'proje yok'], 404);
        }

        $file = $_FILES['file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return Response::json(['ok' => false, 'error' => 'dosya yok']);
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            return Response::json(['ok' => false, 'error' => '5MB ustu kabul edilmez']);
        }

        $mime = mime_content_type($file['tmp_name']) ?: '';
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/svg+xml' => 'svg'];
        if (!isset($allowed[$mime])) {
            return Response::json(['ok' => false, 'error' => 'sadece gorsel dosyalar']);
        }

        $dir = AHO_ROOT . '/public/uploads/builder/' . $projectId;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        $path = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return Response::json(['ok' => false, 'error' => 'yuklenemedi']);
        }

        return Response::json(['ok' => true, 'url' => '/uploads/builder/' . $projectId . '/' . $name, 'size' => filesize($path)]);
    }

    private function kindFromPath(Request $request): string
    {
        return str_starts_with($request->path(), '/mobile-builder') || str_contains($request->path(), '/mobile') ? 'mobile' : 'site';
    }

    private function guestToken(): string
    {
        $token = (string) SessionManager::get('builder_guest_token', '');
        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            SessionManager::set('builder_guest_token', $token);
        }
        return $token;
    }

    private function projectForCurrentVisitor(int $projectId): ?array
    {
        if (AuthService::isCustomer()) {
            return ProjectRepository::findForCustomer($projectId, (int)AuthService::customer()['id']);
        }
        return ProjectRepository::findForGuest($projectId, $this->guestToken());
    }
}
