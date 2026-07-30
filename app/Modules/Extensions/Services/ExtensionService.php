<?php
declare(strict_types=1);

namespace App\Modules\Extensions\Services;

use App\Core\Database\Connection;

final class ExtensionService
{
    public static function startConversation(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = Connection::insert('live_chat_conversations', [
            'visitor_name' => $data['visitor_name'] ?: null,
            'visitor_email' => $data['visitor_email'] ?: null,
            'visitor_ip' => $data['visitor_ip'] ?: null,
            'department' => $data['department'] ?: 'general',
            'source' => $data['source'] ?: 'widget',
            'status' => 'pending',
            'last_message_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (!empty($data['message'])) {
            self::addChatMessage($id, 'visitor', null, (string) $data['message']);
        }

        return $id;
    }

    public static function addChatMessage(int $conversationId, string $senderType, ?int $senderId, string $message): void
    {
        $now = date('Y-m-d H:i:s');
        Connection::insert('live_chat_messages', [
            'conversation_id' => $conversationId,
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'message' => $message,
            'is_read' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        Connection::update('live_chat_conversations', [
            'status' => $senderType === 'agent' ? 'active' : 'pending',
            'last_message_at' => $now,
            'updated_at' => $now,
        ], 'id = ?', [$conversationId]);
    }

    public static function conversation(int $id): ?array
    {
        try {
            return Connection::selectOne('SELECT * FROM live_chat_conversations WHERE id = ?', [$id]);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function messages(int $conversationId): array
    {
        try {
            return Connection::select(
                'SELECT * FROM live_chat_messages WHERE conversation_id = ? ORDER BY id ASC LIMIT 200',
                [$conversationId]
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public static function recentConversations(): array
    {
        try {
            return Connection::select(
                "SELECT c.*, (SELECT message FROM live_chat_messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) last_message
                 FROM live_chat_conversations c ORDER BY COALESCE(c.last_message_at, c.created_at) DESC LIMIT 100"
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public static function submitForm(string $slug, array $payload, string $ip): array
    {
        $form = Connection::selectOne('SELECT * FROM form_builder_forms WHERE slug = ? AND status = ?', [$slug, 'active']);
        if (!$form) {
            return ['ok' => false, 'error' => 'form_not_found'];
        }

        $email = '';
        foreach (['email', 'e_mail', 'mail'] as $key) {
            if (!empty($payload[$key]) && filter_var($payload[$key], FILTER_VALIDATE_EMAIL)) {
                $email = (string) $payload[$key];
                break;
            }
        }

        $id = Connection::insert('form_builder_submissions', [
            'form_id' => (int) $form['id'],
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'submitter_email' => $email ?: null,
            'submitter_ip' => $ip,
            'status' => 'new',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'submission_id' => $id];
    }

    public static function formsWithCounts(): array
    {
        try {
            return Connection::select(
                "SELECT f.*, (SELECT COUNT(*) FROM form_builder_submissions s WHERE s.form_id = f.id) submission_count
                 FROM form_builder_forms f ORDER BY f.id DESC LIMIT 100"
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public static function recentFormSubmissions(): array
    {
        try {
            return Connection::select(
                "SELECT s.*, f.name form_name, f.slug form_slug
                 FROM form_builder_submissions s LEFT JOIN form_builder_forms f ON f.id = s.form_id
                 ORDER BY s.id DESC LIMIT 100"
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public static function activePopups(): array
    {
        try {
            return Connection::select(
                "SELECT id, name, trigger_type, content_json, display_limit FROM popup_builder_popups WHERE status = 'active' ORDER BY id DESC"
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public static function trackPopupEvent(int $popupId, string $eventType, string $visitorKey, string $url): void
    {
        Connection::insert('popup_builder_events', [
            'popup_id' => $popupId > 0 ? $popupId : null,
            'event_type' => preg_replace('/[^a-z0-9._-]+/i', '_', $eventType) ?: 'view',
            'visitor_key' => $visitorKey ?: null,
            'url' => mb_substr($url, 0, 500),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function popupsWithStats(): array
    {
        try {
            return Connection::select(
                "SELECT p.*,
                    (SELECT COUNT(*) FROM popup_builder_events e WHERE e.popup_id = p.id AND e.event_type = 'view') view_count,
                    (SELECT COUNT(*) FROM popup_builder_events e WHERE e.popup_id = p.id AND e.event_type IN ('submit','click')) conversion_count
                 FROM popup_builder_popups p ORDER BY p.id DESC LIMIT 100"
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public static function analyzeSeo(string $url): array
    {
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        $findings = [];
        $score = 100;
        $html = self::fetchUrl($url);

        if ($html === null) {
            $score = 0;
            $findings[] = ['level' => 'error', 'message' => 'URL could not be fetched'];
        } else {
            $title = self::matchTag($html, 'title');
            $description = self::matchMeta($html, 'description');
            $h1Count = preg_match_all('/<h1\b/i', $html);

            if ($title === '') { $score -= 25; $findings[] = ['level' => 'error', 'message' => 'Missing title tag']; }
            if (mb_strlen($title) > 65) { $score -= 10; $findings[] = ['level' => 'warning', 'message' => 'Title is longer than 65 characters']; }
            if ($description === '') { $score -= 20; $findings[] = ['level' => 'error', 'message' => 'Missing meta description']; }
            if ($h1Count !== 1) { $score -= 15; $findings[] = ['level' => 'warning', 'message' => 'Page should have exactly one H1']; }
            if (!str_contains($html, 'application/ld+json')) { $score -= 10; $findings[] = ['level' => 'info', 'message' => 'Structured data was not detected']; }
        }

        $score = max(0, min(100, $score));
        $id = Connection::insert('seo_audits', [
            'url' => $url,
            'score' => $score,
            'findings_json' => json_encode($findings, JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'audit_id' => $id, 'score' => $score, 'findings' => $findings];
    }

    public static function recentSeoAudits(): array
    {
        try {
            return Connection::select('SELECT * FROM seo_audits ORDER BY id DESC LIMIT 100');
        } catch (\Throwable) {
            return [];
        }
    }

    public static function recordIntegrationEvent(string $eventName, array $payload, string $secret = ''): array
    {
        $eventName = preg_replace('/[^a-z0-9._-]+/i', '_', $eventName) ?: 'custom.event';
        $webhooks = Connection::select(
            'SELECT * FROM integration_webhooks WHERE event_name = ? AND is_active = 1',
            [$eventName]
        );

        if ($webhooks !== [] && $secret !== '') {
            foreach ($webhooks as $webhook) {
                if (!empty($webhook['secret']) && hash_equals((string) $webhook['secret'], $secret)) {
                    $secret = '';
                    break;
                }
            }
            if ($secret !== '') {
                return ['ok' => false, 'error' => 'invalid_secret'];
            }
        }

        $id = Connection::insert('integration_events', [
            'event_name' => $eventName,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'delivery_status' => $webhooks === [] ? 'no_subscribers' : 'queued',
            'attempts' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'event_id' => $id, 'subscribers' => count($webhooks)];
    }

    public static function webhooks(): array
    {
        try {
            return Connection::select('SELECT * FROM integration_webhooks ORDER BY id DESC LIMIT 100');
        } catch (\Throwable) {
            return [];
        }
    }

    public static function recentIntegrationEvents(): array
    {
        try {
            return Connection::select('SELECT * FROM integration_events ORDER BY id DESC LIMIT 100');
        } catch (\Throwable) {
            return [];
        }
    }

    private static function fetchUrl(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_USERAGENT => 'AhostOneSeoAnalyzer/1.0',
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return is_string($body) && $code >= 200 && $code < 400 ? $body : null;
    }

    private static function matchTag(string $html, string $tag): string
    {
        return preg_match('#<' . preg_quote($tag, '#') . '\b[^>]*>(.*?)</' . preg_quote($tag, '#') . '>#is', $html, $m)
            ? trim(strip_tags($m[1]))
            : '';
    }

    private static function matchMeta(string $html, string $name): string
    {
        return preg_match('/<meta\b(?=[^>]*\bname=["\']' . preg_quote($name, '/') . '["\'])(?=[^>]*\bcontent=["\']([^"\']*)["\'])[^>]*>/i', $html, $m)
            ? trim($m[1])
            : '';
    }
}
