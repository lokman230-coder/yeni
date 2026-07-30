<?php
declare(strict_types=1);

namespace App\Modules\Extensions\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View;
use App\Modules\Extensions\Services\ExtensionService;
use App\Modules\Extensions\Services\IntegrationHealthService;

final class ExtensionAdminController
{
    public function liveChat(Request $request): Response
    {
        return Response::html((new View())->render('extensions::admin.live-chat', [
            'title' => 'Live Chat',
            'conversations' => ExtensionService::recentConversations(),
        ]));
    }

    public function chatReply(Request $request): Response
    {
        $id = (int) $request->param('id');
        $message = trim((string) $request->input('message', ''));
        if ($id > 0 && $message !== '') {
            ExtensionService::addChatMessage($id, 'agent', null, $message);
        }

        return Response::redirect('/admin/live-chat');
    }

    public function forms(Request $request): Response
    {
        return Response::html((new View())->render('extensions::admin.forms', [
            'title' => 'Form Builder',
            'forms' => ExtensionService::formsWithCounts(),
            'submissions' => ExtensionService::recentFormSubmissions(),
        ]));
    }

    public function popups(Request $request): Response
    {
        return Response::html((new View())->render('extensions::admin.popups', [
            'title' => 'Popup Builder',
            'popups' => ExtensionService::popupsWithStats(),
        ]));
    }

    public function seo(Request $request): Response
    {
        return Response::html((new View())->render('extensions::admin.seo', [
            'title' => 'SEO Analyzer',
            'audits' => ExtensionService::recentSeoAudits(),
        ]));
    }

    public function seoAnalyze(Request $request): Response
    {
        $url = trim((string) $request->input('url', ''));
        if ($url !== '') {
            ExtensionService::analyzeSeo($url);
        }

        return Response::redirect('/admin/seo-analyzer');
    }

    public function integrations(Request $request): Response
    {
        return Response::html((new View())->render('extensions::admin.integrations', [
            'title' => 'Integrations',
            'webhooks' => ExtensionService::webhooks(),
            'events' => ExtensionService::recentIntegrationEvents(),
        ]));
    }

    public function readiness(Request $request): Response
    {
        return Response::html((new View())->render('extensions::admin.readiness', [
            'title' => 'Production Readiness',
            'report' => IntegrationHealthService::report(),
        ]));
    }

    public function readinessJson(Request $request): Response
    {
        return Response::json(IntegrationHealthService::report());
    }
}
