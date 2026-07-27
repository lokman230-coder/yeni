<?php

declare(strict_types=1);

namespace App\Modules\Ai\Services;

/**
 * AI Tool Registry — Function Calling / Tool Use altyapısı
 *
 * Her bağlam (customer/admin/builder) kendine ait toolları tanımlar.
 * Bir tool = { name, description, params, handler, context, permission }
 *
 * AI (LLM veya heuristic) mesajı analiz eder, uygun tool'u seçer, argümanlarla çağırır.
 * Tool çalışır, sonuç user'a doğal dilde döner.
 *
 * Güvenlik:
 *  - Her tool bir context'e bağlıdır: customer/admin/builder — cross-context yasak
 *  - Tool çağırıldığında permission handler'ı kontrol eder (customer_id/admin_id kendi kaydına mı?)
 *  - Yıkıcı işlemler (delete, refund) için "confirm=true" argümanı zorunlu
 *  - Tüm çağrılar audit_logs'a düşer
 */
final class AiToolRegistry
{
    /** @var array<string, array<string, array>> [context => [name => tool]] */
    private static array $tools = [];

    public static function register(string $context, array $tool): void
    {
        $name = $tool['name'] ?? throw new \InvalidArgumentException('Tool name required');
        self::$tools[$context][$name] = $tool;
    }

    /** Bağlama ait tüm tool'ları döner. */
    public static function forContext(string $context): array
    {
        self::bootDefaults();
        return self::$tools[$context] ?? [];
    }

    /** OpenAI function-calling formatına dönüştür (tools=[...] parametresi için). */
    public static function toOpenAiFunctions(string $context): array
    {
        $result = [];
        foreach (self::forContext($context) as $tool) {
            $result[] = [
                'type' => 'function',
                'function' => [
                    'name'        => $tool['name'],
                    'description' => $tool['description'] ?? '',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => $tool['params'] ?? [],
                        'required'   => $tool['required'] ?? [],
                    ],
                ],
            ];
        }
        return $result;
    }

    /**
     * Bir tool'u çalıştır.
     * @return array{ok:bool, message:string, data?:mixed}
     */
    public static function call(string $context, string $name, array $args, ?int $userId, ?string $userType): array
    {
        self::bootDefaults();
        $tool = self::$tools[$context][$name] ?? null;
        if (!$tool) {
            return ['ok' => false, 'message' => "Tool bulunamadı: $name"];
        }

        // Permission check
        if (isset($tool['permission']) && is_callable($tool['permission'])) {
            $allowed = $tool['permission']($args, $userId, $userType);
            if (!$allowed) {
                return ['ok' => false, 'message' => 'Bu işlemi yapmaya yetkin yok.'];
            }
        }

        // Yıkıcı işlem confirm kontrolü
        if (!empty($tool['destructive']) && empty($args['confirm'])) {
            return [
                'ok' => false,
                'message' => "Bu işlem geri alınamaz. Emin misin? Onaylıyorsan 'evet' de.",
                'needs_confirm' => true,
            ];
        }

        // Handler çağır
        try {
            $result = ($tool['handler'])($args, $userId, $userType);

            // Audit log
            self::audit($context, $name, $args, $userId, $userType, $result);

            return is_array($result) ? $result : ['ok' => true, 'message' => (string) $result];
        } catch (\Throwable $e) {
            self::audit($context, $name, $args, $userId, $userType, ['ok' => false, 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Hata: ' . $e->getMessage()];
        }
    }

    private static function audit(string $ctx, string $name, array $args, ?int $uid, ?string $utype, mixed $result): void
    {
        try {
            \App\Services\Logger\ActivityLog::log(
                "ai.tool.$name",
                $utype ?? 'anonymous',
                $uid,
                "AI tool '$name' [$ctx] çağrıldı",
                [
                    'args'    => $args,
                    'success' => is_array($result) ? ($result['ok'] ?? false) : true,
                ]
            );
        } catch (\Throwable) {}
    }

    /** Default tool'ları yükle (idempotent) */
    private static bool $booted = false;
    private static function bootDefaults(): void
    {
        if (self::$booted) return;
        self::$booted = true;
        AiCustomerTools::register();
        AiAdminTools::register();
        AiBuilderTools::register();
    }
}
