<?php
declare(strict_types=1);

namespace App\Modules\Ai\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Ai\Services\AiService;

final class ProviderTestController
{
    public function test(Request $request): Response
    {
        $provider = (string) $request->input('provider', 'heuristic');
        $allowed = ['heuristic', 'openai', 'gemini', 'claude', 'deepseek', 'mistral'];

        if (!in_array($provider, $allowed, true)) {
            return Response::json(['ok' => false, 'error' => 'invalid_provider'], 400);
        }

        return Response::json(AiService::testProvider($provider));
    }
}
