<?php

declare(strict_types=1);

namespace App\Modules\Ai\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\SessionManager;
use App\Core\View;
use App\Modules\Ai\Services\SectorDetector;
use App\Modules\Ai\Services\SiteGenerator;
use App\Modules\Builder\Services\TemplateLibrary;
use App\Services\Auth\AuthService;

/**
 * "AI ile Site Oluştur" — 3 adımlı akış:
 *   /ai/site-olustur                (GET)  → prompt formu
 *   /ai/site-olustur/onizle         (POST) → sektör tahminini göster + onay
 *   /ai/site-olustur/uret           (POST) → SiteGenerator::generate + editöre yönlendir
 */
final class SiteGeneratorController
{
    public function form(Request $request): Response
    {
        $view = new View();
        return Response::html($view->render('ai::site-generator.form', [
            'title'       => 'AI ile Site Oluştur',
            'sectors'     => TemplateLibrary::siteSectors(),
            'error'       => flash('error'),
            'examples'    => self::examplePrompts(),
        ]));
    }

    public function preview(Request $request): Response
    {
        $prompt = trim((string) $request->input('prompt', ''));
        if (mb_strlen($prompt) < 4) {
            SessionManager::flash('error', 'Lütfen en az 4 karakterlik bir açıklama yazın.');
            return Response::redirect('/ai/site-olustur');
        }
        $detected = SectorDetector::detect($prompt);
        $view = new View();
        return Response::html($view->render('ai::site-generator.preview', [
            'title'    => 'Sektör Onayı',
            'prompt'   => $prompt,
            'detected' => $detected,
            'sectors'  => TemplateLibrary::siteSectors(),
        ]));
    }

    public function generate(Request $request): Response
    {
        $prompt = trim((string) $request->input('prompt', ''));
        $sector = trim((string) $request->input('sector', ''));
        $name   = trim((string) $request->input('name', ''));

        if ($prompt === '') {
            SessionManager::flash('error', 'Prompt boş.');
            return Response::redirect('/ai/site-olustur');
        }

        // Giriş yoksa demo müşteri (id=1) yerine login'e yönlendir
        if (!AuthService::isCustomer()) {
            SessionManager::set('after_login_redirect', '/ai/site-olustur');
            SessionManager::flash('info', 'AI ile site oluşturmak için lütfen giriş yapın.');
            return Response::redirect('/giris');
        }
        $customer = AuthService::customer();

        // kind: site | mobile
        $kind = in_array($request->input('kind', 'site'), ['site','mobile'], true) ? $request->input('kind') : 'site';

        $options = ['kind' => $kind];
        if ($sector !== '') $options['sector'] = $sector;
        if ($name !== '')   $options['name']   = $name;

        $r = SiteGenerator::generate((int) $customer['id'], $prompt, $options);
        if (!$r['ok']) {
            SessionManager::flash('error', 'Oluşturma başarısız: ' . ($r['error'] ?? 'bilinmeyen hata'));
            return Response::redirect('/ai/site-olustur?kind=' . $kind);
        }
        $label = $kind === 'mobile' ? 'mobil uygulama' : 'site';
        SessionManager::flash('success', "✓ '{$r['name']}' $label oluşturuldu ({$r['sector']} sektörü). Şimdi editörde düzenleyebilirsiniz.");
        $editorPath = $kind === 'mobile' ? '/panel/mobile-builder/' : '/panel/site-builder/';
        return Response::redirect($editorPath . $r['project_id'] . '/editor');
    }

    /** @return array<int,array{prompt:string,label:string,icon:string}> */
    private static function examplePrompts(): array
    {
        return [
            ['icon' => '🦷', 'label' => 'Diş Kliniği', 'prompt' => 'Ali Diş Kliniği için modern, güven veren bir diş hekimi sitesi yap'],
            ['icon' => '🍕', 'label' => 'Restoran',   'prompt' => 'Napoli Pizza için sıcak, iştah açıcı bir restoran sitesi'],
            ['icon' => '👗', 'label' => 'E-Ticaret',  'prompt' => 'Şık kadın giyim satan bir e-ticaret sitesi'],
            ['icon' => '💇', 'label' => 'Kuaför',     'prompt' => 'Merve Güzellik Salonu için randevu odaklı yerel işletme sitesi'],
            ['icon' => '🎓', 'label' => 'Kurs',       'prompt' => 'Online İngilizce dil kursu için akademi sitesi'],
            ['icon' => '📻', 'label' => 'Radyo',      'prompt' => 'FM99 için canlı yayın odaklı radyo sitesi'],
            ['icon' => '💼', 'label' => 'Ajans',      'prompt' => 'Dijital pazarlama ajansı için kurumsal site'],
            ['icon' => '🖼️', 'label' => 'Portfolyo',  'prompt' => 'Freelance grafik tasarımcı için portfolyo sitesi'],
        ];
    }
}
