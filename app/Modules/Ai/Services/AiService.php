<?php

declare(strict_types=1);

namespace App\Modules\Ai\Services;

use App\Core\Database\Connection;
use App\Core\Env;
use App\Modules\Ai\Contracts\AiProviderInterface;
use App\Modules\Ai\Providers\HeuristicProvider;
use App\Modules\Ai\Providers\OpenAiProvider;
use App\Modules\Ai\Providers\GeminiProvider;
use App\Modules\Ai\Providers\ClaudeProvider;
use App\Modules\Ai\Providers\OpenAiCompatibleProvider;

final class AiService
{
    public static function provider(?string $preferred = null): AiProviderInterface
    {
        $sm = \App\Services\Settings\SettingsManager::class;
        $providerName = $preferred ?: (string) $sm::get('ai.provider', 'heuristic', 'AI_PROVIDER');
        $apiKey       = (string) $sm::get('ai.api_key',  '',          'AI_API_KEY');
        $model        = (string) $sm::get('ai.model',    'gpt-4o-mini', 'AI_MODEL');

        if ($providerName === 'gemini') {
            $key = (string) $sm::get('ai.gemini_api_key', '', 'GEMINI_API_KEY');
            $geminiModel = (string) $sm::get('ai.gemini_model', 'gemini-2.0-flash', 'GEMINI_MODEL');
            if ($key !== '') return new GeminiProvider($key, $geminiModel);
        }
        if ($providerName === 'claude') {
            $key = (string) $sm::get('ai.claude_api_key', '', 'CLAUDE_API_KEY');
            $claudeModel = (string) $sm::get('ai.claude_model', 'claude-3-5-sonnet-latest', 'CLAUDE_MODEL');
            if ($key !== '') return new ClaudeProvider($key, $claudeModel);
        }
        if ($providerName === 'deepseek' || $providerName === 'mistral') {
            $key = (string) $sm::get('ai.' . $providerName . '_api_key', '', strtoupper($providerName) . '_API_KEY');
            $defaultEndpoint = $providerName === 'deepseek' ? 'https://api.deepseek.com/chat/completions' : 'https://api.mistral.ai/v1/chat/completions';
            $endpoint = (string) $sm::get('ai.' . $providerName . '_endpoint', $defaultEndpoint, strtoupper($providerName) . '_ENDPOINT');
            $providerModel = (string) $sm::get('ai.' . $providerName . '_model', $providerName === 'deepseek' ? 'deepseek-chat' : 'mistral-small-latest', strtoupper($providerName) . '_MODEL');
            if ($key !== '') return new OpenAiCompatibleProvider($providerName, $key, $endpoint, $providerModel);
        }
        if ($providerName === 'openai' && $apiKey !== '') return new OpenAiProvider($apiKey, $model);
        return new HeuristicProvider();
    }

    /**
     * AI'a soru sor + aksiyon kontrolü + log.
     */
    public static function ask(string $context, string $message, ?int $userId = null, ?string $userType = null): array
    {
        $systemPrompt = ContextBuilder::systemPrompt($context);
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $message],
        ];

        $start = microtime(true);
        $preferred = match ($context) {
            'builder_mobile', 'mobile_builder' => (string) \App\Services\Settings\SettingsManager::get('ai.mobile_provider', 'gemini', 'AI_MOBILE_PROVIDER'),
            'builder' => (string) \App\Services\Settings\SettingsManager::get('ai.builder_provider', 'claude', 'AI_BUILDER_PROVIDER'),
            'admin' => (string) \App\Services\Settings\SettingsManager::get('ai.admin_provider', 'openai', 'AI_ADMIN_PROVIDER'),
            'customer', 'public' => (string) \App\Services\Settings\SettingsManager::get('ai.chat_provider', 'openai', 'AI_CHAT_PROVIDER'),
            default => null,
        };
        $provider = self::provider($preferred ?: null);
        $result = $provider->chat($messages);

        if (!empty($result['error']) && ($result['provider'] ?? $provider->id()) !== 'heuristic') {
            $fallback = self::provider('heuristic');
            $fallbackResult = $fallback->chat($messages);
            $fallbackResult['fallback_from'] = $result['provider'] ?? $provider->id();
            $fallbackResult['error'] = null;
            $result = $fallbackResult;
        }
        $latency = (int) ((microtime(true) - $start) * 1000);

        // Aksiyon parse (heuristic provider action döner; OpenAI cevabında JSON aranır)
        $action = $result['action'] ?? self::extractAction($result['content'] ?? '');

        // Bağlam güvenliği: aksiyon uygunsuz ise düşür
        if (is_array($action) && !empty($action['url'])) {
            if (!ContextBuilder::isActionAllowed($context, $action['action'] ?? '', $action['url'])) {
                $action = null;
            }
        } elseif (is_string($action) && str_contains($action, ':')) {
            [$kind, $url] = explode(':', $action, 2);
            if (!ContextBuilder::isActionAllowed($context, $kind, $url)) {
                $action = null;
            }
        }

        // Log
        try {
            Connection::insert('ai_logs', [
                'context'      => $context,
                'user_type'    => $userType,
                'user_id'      => $userId,
                'prompt'       => mb_substr($message, 0, 2000),
                'response'     => mb_substr((string)($result['content'] ?? ''), 0, 4000),
                'action_taken' => is_array($action) ? json_encode($action) : (is_string($action) ? $action : null),
                'tokens_used'  => (int) ($result['tokens'] ?? 0),
                'estimated_cost' => self::estimateCost((string) ($result['provider'] ?? $provider->id()), (int) ($result['tokens'] ?? 0)),
                'provider'     => (string) ($result['provider'] ?? $provider->id()),
                'latency_ms'   => $latency,
                'error'        => $result['error'] ?? null,
            ]);
        } catch (\Throwable) {
            // sessiz
        }

        return [
            'content'  => $result['content'] ?? '',
            'action'   => $action,
            'provider' => $result['provider'] ?? 'unknown',
            'fallback_from' => $result['fallback_from'] ?? null,
            'latency_ms' => $latency,
            'error'    => $result['error'] ?? null,
        ];
    }

    /**
     * AI'a sor + TOOL CALLING (gerçek işlem yapabilir).
     *
     * Akış:
     *  1) Heuristic pattern match → tool tespit
     *  2) OpenAI kullanıyorsa function calling
     *  3) Tool argümanlarını doğrula
     *  4) AiToolRegistry::call() ile çalıştır
     *  5) Sonucu doğal dilde döndür
     */
    public static function askWithTools(string $context, string $message, ?int $userId = null, ?string $userType = null, array $extraArgs = []): array
    {
        // 1) Heuristic tool detection (basit anahtar kelime eşleştirme)
        $detected = self::detectTool($context, $message);
        if ($detected) {
            $args = array_merge($detected['args'], $extraArgs);
            $toolResult = AiToolRegistry::call($context, $detected['name'], $args, $userId, $userType);

            // AI logs
            try {
                Connection::insert('ai_logs', [
                    'context'      => $context,
                    'user_type'    => $userType,
                    'user_id'      => $userId,
                    'prompt'       => mb_substr($message, 0, 2000),
                    'response'     => mb_substr((string)($toolResult['message'] ?? ''), 0, 4000),
                    'action_taken' => json_encode(['tool' => $detected['name'], 'args' => $args]),
                    'tokens_used'  => 0,
                    'latency_ms'   => 0,
                    'error'        => empty($toolResult['ok']) ? ($toolResult['message'] ?? null) : null,
                ]);
            } catch (\Throwable) {}

            return [
                'content'   => $toolResult['message'] ?? 'İşlem tamamlandı.',
                'tool'      => $detected['name'],
                'ok'        => (bool) ($toolResult['ok'] ?? false),
                'redirect'  => $toolResult['redirect'] ?? null,
                'data'      => $toolResult['data'] ?? null,
                'needs_confirm' => $toolResult['needs_confirm'] ?? false,
                'provider'  => 'heuristic_tools',
            ];
        }

        // 2) Tool bulunamadıysa normal chat'e düş
        return self::ask($context, $message, $userId, $userType);
    }

    /**
     * Heuristic tool detection — basit anahtar kelime eşleştirme.
     * Prod'da OpenAI function calling kullanılır (daha akıllı).
     */
    private static function detectTool(string $context, string $message): ?array
    {
        $msg = mb_strtolower($message, 'UTF-8');

        // === CUSTOMER ===
        if ($context === ContextBuilder::CTX_CUSTOMER) {
            if (preg_match('/(ticket|talep|destek).*(aç|oluştur|yarat)/u', $msg) ||
                preg_match('/(aç|oluştur).*(ticket|talep|destek)/u', $msg)) {
                // Konu ve mesajı ayır
                if (preg_match('/konu[\s:]+(.+?)(?:\s+mesaj[\s:]|$)/iu', $message, $s) &&
                    preg_match('/mesaj[\s:]+(.+)/iu', $message, $m)) {
                    return ['name' => 'create_ticket', 'args' => ['subject' => trim($s[1]), 'message' => trim($m[1])]];
                }
                return ['name' => 'create_ticket', 'args' => ['subject' => 'AI destek talebi', 'message' => $message]];
            }
            if (preg_match('/(hizmet|servis|paket).*özet/u', $msg)) {
                return ['name' => 'my_services_summary', 'args' => []];
            }
            if (preg_match('/(fatura).*ode|ode.*fatura/u', $msg)) {
                if (preg_match('/#?(\d+)/', $msg, $m)) {
                    return ['name' => 'pay_invoice', 'args' => ['invoice_id' => (int)$m[1]]];
                }
            }
            if (preg_match('/(domain).*(yenile|renew)/u', $msg)) {
                if (preg_match('/#?(\d+)/', $msg, $m)) {
                    $years = 1;
                    if (preg_match('/(\d+)\s*yıl/u', $msg, $y)) $years = (int)$y[1];
                    return ['name' => 'renew_domain', 'args' => ['domain_id' => (int)$m[1], 'years' => $years]];
                }
            }
            if (preg_match('/(şifre|password).*(sıfırla|reset|yenile)/u', $msg)) {
                if (preg_match('/#?(\d+)/', $msg, $m)) {
                    return ['name' => 'request_password_reset', 'args' => ['service_id' => (int)$m[1]]];
                }
            }
            if (preg_match('/(2fa|iki faktör|güvenlik)/u', $msg)) {
                return ['name' => 'toggle_2fa', 'args' => []];
            }
            // Navigate
            foreach (['hizmetler'=>'hizmet','domainler'=>'domain','faturalar'=>'fatura','odemeler'=>'ödeme','destek'=>'destek','profil'=>'profil','guvenlik'=>'güvenlik','referans'=>'referans'] as $page => $keyword) {
                if (str_contains($msg, $keyword . ' git') || preg_match("/$keyword.*(?:sayfa|aç|göster)/u", $msg)) {
                    return ['name' => 'navigate', 'args' => ['page' => $page]];
                }
            }
        }

        // === ADMIN ===
        if ($context === ContextBuilder::CTX_ADMIN) {
            if (preg_match('/kupon.*(oluştur|yarat|ekle)/u', $msg)) {
                $code = 'AI' . rand(1000,9999);
                $pct = 10;
                if (preg_match('/([A-Z0-9]{4,20})/', $message, $m)) $code = $m[1];
                if (preg_match('/%?(\d+)\s*(?:indirim|percent|%)/u', $msg, $m)) $pct = (int)$m[1];
                return ['name' => 'create_coupon', 'args' => ['code' => $code, 'discount_pct' => $pct]];
            }
            if (preg_match('/(dashboard|özet|gelir|rapor)/u', $msg)) {
                return ['name' => 'dashboard_summary', 'args' => []];
            }
            if (preg_match('/müşteri.*(ara|bul|listele)/u', $msg)) {
                if (preg_match('/["\']([^"\']+)["\']/', $message, $m)) {
                    return ['name' => 'find_customer', 'args' => ['query' => $m[1]]];
                }
                // "müşteri ara ahmet" gibi
                if (preg_match('/(?:ara|bul|listele)\s+(.+)/iu', $message, $m)) {
                    return ['name' => 'find_customer', 'args' => ['query' => trim($m[1])]];
                }
            }
            if (preg_match('/bakım.*(aç|kapat|on|off)/u', $msg)) {
                $action = preg_match('/aç|on/u', $msg) ? 'on' : 'off';
                return ['name' => 'maintenance_mode', 'args' => ['action' => $action, 'confirm' => str_contains($msg, 'onay')]];
            }
            if (preg_match('/(hatırlatma|reminder).*(yolla|gönder)/u', $msg)) {
                return ['name' => 'send_payment_reminders', 'args' => ['confirm' => str_contains($msg, 'onay')]];
            }
            if (preg_match('/cache.*(temizle|clear)/u', $msg)) {
                return ['name' => 'clear_cache', 'args' => []];
            }
            if (preg_match('/(sağlık|health|kontrol)/u', $msg)) {
                return ['name' => 'health_check', 'args' => []];
            }
        }

        // === BUILDER ===
        if ($context === 'builder') {
            if (preg_match('/blok.*(ekle|oluştur|yarat)/u', $msg)) {
                $type = 'hero';
                foreach (['hero','features','cta','footer','text','gallery','pricing','testimonials','contact','faq'] as $t) {
                    if (str_contains($msg, $t)) { $type = $t; break; }
                }
                return ['name' => 'add_block', 'args' => ['type' => $type]];
            }
            if (preg_match('/renk|palet|tema/u', $msg)) {
                $palette = 'ocean';
                foreach (['pastel','dark','ocean','sunset','forest','bold'] as $p) {
                    if (str_contains($msg, $p) || str_contains($msg, 'karanlık') && $p==='dark') { $palette = $p; break; }
                }
                return ['name' => 'change_color_palette', 'args' => ['palette' => $palette]];
            }
            if (preg_match('/blok(?:ları)?.*(listele|göster)/u', $msg)) {
                return ['name' => 'list_blocks', 'args' => []];
            }
            if (preg_match('/başlık.*değiştir|değiştir.*başlık/u', $msg)) {
                if (preg_match('/["\']([^"\']+)["\']/', $message, $m)) {
                    return ['name' => 'update_block_text', 'args' => ['block_index' => 0, 'field' => 'title', 'value' => $m[1]]];
                }
            }
            if (preg_match('/blok.*sil|kaldır/u', $msg)) {
                if (preg_match('/#?(\d+)/', $msg, $m)) {
                    return ['name' => 'delete_block', 'args' => ['block_index' => (int)$m[1], 'confirm' => str_contains($msg, 'onay')]];
                }
            }
        }

        return null;
    }

    private static function extractAction(string $content): array|string|null
    {
        // JSON code block ara
        if (preg_match('/\{[^{}]*"action"\s*:\s*"[^"]+"[^{}]*\}/', $content, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) return $decoded;
        }
        return null;
    }

    public static function testProvider(string $providerName): array
    {
        $provider = self::provider($providerName);
        $start = microtime(true);
        $result = $provider->chat([
            ['role' => 'system', 'content' => 'Reply with exactly: ok'],
            ['role' => 'user', 'content' => 'connection test'],
        ], ['max_tokens' => 10, 'temperature' => 0]);

        return [
            'ok' => empty($result['error']) && trim((string) ($result['content'] ?? '')) !== '',
            'provider' => $result['provider'] ?? $provider->id(),
            'model' => $result['model'] ?? null,
            'tokens' => (int) ($result['tokens'] ?? 0),
            'estimated_cost' => self::estimateCost((string) ($result['provider'] ?? $provider->id()), (int) ($result['tokens'] ?? 0)),
            'latency_ms' => (int) ((microtime(true) - $start) * 1000),
            'error' => $result['error'] ?? null,
        ];
    }

    public static function estimateCost(string $provider, int $tokens): float
    {
        if ($tokens <= 0) {
            return 0.0;
        }

        $perMillion = match ($provider) {
            'openai' => 0.60,
            'gemini' => 0.35,
            'claude' => 3.00,
            'deepseek' => 0.30,
            'mistral' => 0.80,
            default => 0.0,
        };

        return round($tokens / 1000000 * $perMillion, 6);
    }
}
