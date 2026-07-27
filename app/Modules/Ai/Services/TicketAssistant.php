<?php

declare(strict_types=1);

namespace App\Modules\Ai\Services;

use App\Core\Database\Connection;
use App\Modules\Ai\Providers\HeuristicProvider;
use App\Modules\Ai\Providers\OpenAiProvider;
use App\Services\Settings\SettingsManager;

/**
 * Ticket için AI cevap taslağı üreteci.
 * Ticket'ın konusu + son müşteri mesajı → destek ekibi için öneri.
 */
final class TicketAssistant
{
    /** @return array{ok:bool, suggestion:string, provider:string} */
    public static function suggestReply(int $ticketId): array
    {
        $ticket = Connection::selectOne("SELECT subject FROM tickets WHERE id = ?", [$ticketId]);
        if (!$ticket) return ['ok' => false, 'suggestion' => '', 'provider' => 'none'];

        // Son müşteri mesajını al
        $lastMsg = Connection::selectOne(
            "SELECT message FROM ticket_replies WHERE ticket_id = ? AND author_type = 'customer' ORDER BY id DESC LIMIT 1",
            [$ticketId]
        );
        $customerMessage = $lastMsg['message'] ?? '';

        $apiKey = (string) SettingsManager::get('ai.api_key', '', 'AI_API_KEY');
        $provider = (string) SettingsManager::get('ai.provider', 'heuristic', 'AI_PROVIDER');

        if ($provider === 'openai' && $apiKey !== '') {
            $model = (string) SettingsManager::get('ai.model', 'gpt-4o-mini');
            $p = new OpenAiProvider($apiKey, $model);
            $r = $p->chat([
                ['role'=>'system','content'=>"Sen bir Türkçe teknik destek uzmanısın. Ahost Bilişim hosting/domain firması için nazik, profesyonel ve çözüm odaklı cevaplar yaz. Cevabın 3-5 cümle olsun. Selamla başla, çözüm/adım öner, iyi dileklerle bitir."],
                ['role'=>'user','content'=>"Ticket konusu: " . (string)$ticket['subject'] . "\n\nMüşteri mesajı:\n" . mb_substr($customerMessage, 0, 1500)],
            ], ['temperature' => 0.6, 'max_tokens' => 500]);
            if (empty($r['error'])) {
                return ['ok' => true, 'suggestion' => trim((string)$r['content']), 'provider' => 'openai'];
            }
        }

        // Heuristic fallback — konu anahtar kelimelerine göre şablon
        $sub = mb_strtolower((string)$ticket['subject'] . ' ' . $customerMessage);
        $template = "Merhaba,\n\nMesajınız için teşekkür ederiz. Talebiniz ekibimize iletildi ve incelenmektedir.";
        if (str_contains($sub, 'şifre') || str_contains($sub, 'parola') || str_contains($sub, 'giriş')) {
            $template .= "\n\nHesabınıza giriş yapamıyorsanız, önce 'Şifremi Unuttum' özelliğini kullanarak şifre sıfırlaması yapmayı deneyebilirsiniz: " . rtrim((string) env('APP_URL', ''), '/') . "/sifremi-unuttum";
        } elseif (str_contains($sub, 'ssl') || str_contains($sub, 'sertifika') || str_contains($sub, 'https')) {
            $template .= "\n\nSSL sertifikanız hosting paketinizle birlikte ücretsiz sunulmaktadır. Kurulumu kontrol etmek için Site Araçları > SSL Kontrol aracımızı kullanabilirsiniz.";
        } elseif (str_contains($sub, 'domain') || str_contains($sub, 'alan')) {
            $template .= "\n\nDomain işleminizle ilgili detayları Panel > Domainlerim sekmesinden takip edebilirsiniz. WHOIS bilgilerini güncellemek veya nameserver değiştirmek için aynı sayfadan işlem yapabilirsiniz.";
        } elseif (str_contains($sub, 'fatura') || str_contains($sub, 'ödeme')) {
            $template .= "\n\nFaturalarınızı Panel > Faturalarım sekmesinden görüntüleyebilir, PDF olarak indirebilir ve ödemesi bekleyenleri hemen ödeyebilirsiniz.";
        } elseif (str_contains($sub, 'hız') || str_contains($sub, 'yavaş') || str_contains($sub, 'performans')) {
            $template .= "\n\nSite hızınızı Site Araçları > Hız Testi aracımızla ölçebilirsiniz. Ayrıca sunucumuzda kaynak kullanımınızı Panel > Hizmet Detayı sayfasından takip edebilirsiniz.";
        } else {
            $template .= "\n\nSize daha detaylı yardımcı olabilmemiz için lütfen sorununuzu adım adım açıklar mısınız?";
        }
        $template .= "\n\nİyi çalışmalar dileriz,\nAhost Bilişim Destek Ekibi";

        return ['ok' => true, 'suggestion' => $template, 'provider' => 'heuristic'];
    }
}
