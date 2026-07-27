<?php

declare(strict_types=1);

namespace App\Modules\Ai\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Ai\Services\AiService;
use App\Modules\Ai\Services\ContextBuilder;
use App\Services\Auth\AuthService;

final class AiController
{
    public function chatPublic(Request $request): Response
    {
        return $this->handleChat($request, ContextBuilder::CTX_PUBLIC);
    }

    public function chatCustomer(Request $request): Response
    {
        if (!AuthService::isCustomer()) {
            return Response::json(['ok' => false, 'error' => 'auth'], 401);
        }
        return $this->handleChat($request, ContextBuilder::CTX_CUSTOMER);
    }

    public function chatAdmin(Request $request): Response
    {
        if (!AuthService::isAdmin()) {
            return Response::json(['ok' => false, 'error' => 'auth'], 401);
        }
        return $this->handleChat($request, ContextBuilder::CTX_ADMIN);
    }

    private function handleChat(Request $request, string $context): Response
    {
        $message = trim((string) $request->input('message', ''));
        if ($message === '') {
            return Response::json(['ok' => false, 'error' => 'Mesaj boş.'], 400);
        }
        if (mb_strlen($message) > 2000) {
            return Response::json(['ok' => false, 'error' => 'Mesaj çok uzun (max 2000 karakter).'], 400);
        }

        $userId = null; $userType = null;
        if ($context === ContextBuilder::CTX_CUSTOMER) {
            $c = AuthService::customer();
            $userId = (int) ($c['id'] ?? 0);
            $userType = 'customer';
        } elseif ($context === ContextBuilder::CTX_ADMIN) {
            $a = AuthService::admin();
            $userId = (int) ($a['id'] ?? 0);
            $userType = 'admin';
        }

        // TOOL CALLING — customer/admin bağlamları için gerçek işlem yapabilir
        if (in_array($context, [ContextBuilder::CTX_CUSTOMER, ContextBuilder::CTX_ADMIN], true)) {
            $result = AiService::askWithTools($context, $message, $userId, $userType);
            return Response::json([
                'ok'            => true,
                'reply'         => $result['content'],
                'tool'          => $result['tool'] ?? null,
                'tool_ok'       => $result['ok'] ?? null,
                'redirect'      => $result['redirect'] ?? null,
                'data'          => $result['data'] ?? null,
                'needs_confirm' => $result['needs_confirm'] ?? false,
                'provider'      => $result['provider'] ?? null,
            ]);
        }

        // Public chat — sadece bilgi, tool yok
        $result = AiService::ask($context, $message, $userId, $userType);
        return Response::json([
            'ok'      => true,
            'reply'   => $result['content'],
            'action'  => $result['action'],
            'provider'=> $result['provider'],
            'ms'      => $result['latency_ms'],
        ]);
    }

    /**
     * Builder AI endpoint — proje düzenleme için özel.
     * Route: POST /ai/builder
     */
    public function chatBuilder(\App\Core\Http\Request $request): Response
    {
        if (!AuthService::isCustomer()) {
            return Response::json(['ok' => false, 'error' => 'auth'], 401);
        }
        $projectId = (int) $request->input('project_id', 0);
        $message   = trim((string) $request->input('message', ''));
        if ($projectId <= 0 || $message === '') {
            return Response::json(['ok' => false, 'error' => 'proje ve mesaj zorunlu'], 400);
        }
        $c = AuthService::customer();
        $result = AiService::askWithTools('builder', $message, (int) $c['id'], 'customer', ['project_id' => $projectId]);
        return Response::json([
            'ok'      => true,
            'reply'   => $result['content'],
            'tool'    => $result['tool'] ?? null,
            'tool_ok' => $result['ok'] ?? null,
            'data'    => $result['data'] ?? null,
        ]);
    }
}
