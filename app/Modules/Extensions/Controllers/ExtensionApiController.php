<?php
declare(strict_types=1);

namespace App\Modules\Extensions\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Extensions\Services\ExtensionService;

final class ExtensionApiController
{
    public function chatStart(Request $request): Response
    {
        $id = ExtensionService::startConversation([
            'visitor_name' => trim((string) $request->input('name', '')),
            'visitor_email' => trim((string) $request->input('email', '')),
            'visitor_ip' => $request->ip(),
            'department' => trim((string) $request->input('department', 'general')) ?: 'general',
            'source' => trim((string) $request->input('source', 'widget')) ?: 'widget',
            'message' => trim((string) $request->input('message', '')),
        ]);

        return Response::json(['ok' => true, 'conversation_id' => $id]);
    }

    public function chatMessage(Request $request): Response
    {
        $conversationId = (int) $request->input('conversation_id', 0);
        $message = trim((string) $request->input('message', ''));

        if ($conversationId <= 0 || $message === '') {
            return Response::json(['ok' => false, 'error' => 'missing_params'], 400);
        }

        ExtensionService::addChatMessage($conversationId, 'visitor', null, $message);

        return Response::json(['ok' => true]);
    }

    public function chatStatus(Request $request): Response
    {
        $conversationId = (int) $request->param('id');

        return Response::json([
            'ok' => true,
            'conversation' => ExtensionService::conversation($conversationId),
            'messages' => ExtensionService::messages($conversationId),
        ]);
    }

    public function formSubmit(Request $request): Response
    {
        $slug = (string) $request->param('slug');
        $payload = $request->all();
        unset($payload['_csrf'], $payload['slug']);

        $result = ExtensionService::submitForm($slug, $payload, $request->ip());

        return Response::json($result, !empty($result['ok']) ? 200 : 404);
    }

    public function activePopups(Request $request): Response
    {
        return Response::json(['ok' => true, 'popups' => ExtensionService::activePopups()]);
    }

    public function popupEvent(Request $request): Response
    {
        ExtensionService::trackPopupEvent(
            (int) $request->input('popup_id', 0),
            (string) $request->input('event_type', 'view'),
            (string) $request->input('visitor_key', ''),
            (string) $request->input('url', $request->header('referer') ?? '')
        );

        return Response::json(['ok' => true]);
    }

    public function integrationEvent(Request $request): Response
    {
        $secret = (string) $request->header('x-ahost-webhook-secret');
        $result = ExtensionService::recordIntegrationEvent(
            (string) $request->input('event_name', 'custom.event'),
            $request->all(),
            $secret
        );

        return Response::json($result, !empty($result['ok']) ? 200 : 403);
    }
}
