<?php

declare(strict_types=1);

namespace App\Modules\Ai\Providers;

use App\Modules\Ai\Contracts\AiProviderInterface;
use App\Services\Logger\ApiLogger;

/**
 * OpenAI Chat Completions API.
 * .env: AI_PROVIDER=openai, AI_API_KEY, AI_MODEL (default: gpt-4o-mini)
 */
final class OpenAiProvider implements AiProviderInterface
{
    public function __construct(
        private string $apiKey,
        private string $model = 'gpt-4o-mini'
    ) {}

    public function id(): string { return 'openai'; }

    public function chat(array $messages, array $options = []): array
    {
        if ($this->apiKey === '') {
            return ['content' => '', 'tokens' => 0, 'error' => 'API key tanımlı değil'];
        }

        $payload = [
            'model'       => $options['model'] ?? $this->model,
            'messages'    => $messages,
            'temperature' => $options['temperature'] ?? 0.4,
            'max_tokens'  => $options['max_tokens'] ?? 500,
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $start = microtime(true);
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        $duration = (int) ((microtime(true) - $start) * 1000);

        $data = $raw ? (json_decode((string)$raw, true) ?: []) : [];
        ApiLogger::log('openai', '/chat/completions', 'POST', ['model' => $this->model, 'msg_count' => count($messages)], ['choices_count' => count($data['choices'] ?? [])], $code, $duration, $err ?: null);

        if ($raw === false || $code >= 400) {
            return ['content' => '', 'tokens' => 0, 'error' => $err ?: 'HTTP ' . $code . ' - ' . ($data['error']['message'] ?? '')];
        }
        return [
            'content'  => $data['choices'][0]['message']['content'] ?? '',
            'tokens'   => (int) ($data['usage']['total_tokens'] ?? 0),
            'provider' => 'openai',
            'model'    => $this->model,
        ];
    }
}
