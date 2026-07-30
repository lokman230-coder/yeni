<?php

declare(strict_types=1);

namespace App\Modules\Ai\Contracts;

interface AiProviderInterface
{
    public function id(): string;

    /**
     * Chat completion çağrısı.
     * @param array $messages [{role: 'system'|'user'|'assistant', content: '...'}]
     * @param array $options  ['model', 'temperature', 'max_tokens']
     * @return array{content: string, tokens: int, error?: string}
     */
    public function chat(array $messages, array $options = []): array;
}
