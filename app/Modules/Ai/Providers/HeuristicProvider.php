<?php

declare(strict_types=1);

namespace App\Modules\Ai\Providers;

use App\Modules\Ai\Contracts\AiProviderInterface;

/**
 * Ücretsiz "AI" — kural-tabanlı fallback provider.
 * OpenAI/Anthropic API key yoksa devreye girer, temel yönlendirmeler ve cevaplar sağlar.
 * Amaç: Sistemi API bağımlılığı olmadan da demo edilebilir tutmak.
 */
final class HeuristicProvider implements AiProviderInterface
{
    public function id(): string { return 'heuristic'; }

    public function chat(array $messages, array $options = []): array
    {
        $userMsg = '';
        $context = 'public';
        foreach ($messages as $m) {
            if (($m['role'] ?? '') === 'user') $userMsg = mb_strtolower((string)($m['content'] ?? ''), 'UTF-8');
            if (($m['role'] ?? '') === 'system' && str_contains((string)($m['content'] ?? ''), 'customer')) $context = 'customer';
            if (($m['role'] ?? '') === 'system' && str_contains((string)($m['content'] ?? ''), 'admin')) $context = 'admin';
        }

        // Basit intent matching
        $intents = self::intents($context);
        foreach ($intents as $intent) {
            foreach ($intent['keywords'] as $kw) {
                if (str_contains($userMsg, $kw)) {
                    return [
                        'content' => $intent['response'],
                        'action'  => $intent['action'] ?? null,
                        'tokens'  => 0,
                        'provider'=> 'heuristic',
                    ];
                }
            }
        }

        return [
            'content' => self::defaultResponse($context),
            'tokens'  => 0,
            'provider'=> 'heuristic',
        ];
    }

    private static function intents(string $context): array
    {
        $common = [
            ['keywords' => ['hosting', 'hosting al', 'hosting öner'], 'response' => 'Hosting paketlerimize göz atabilirsiniz: Başlangıç 39₺/ay, Business 89₺/ay (en popüler), Kurumsal 189₺/ay. Detay için /hosting sayfasına gidin.', 'action' => 'navigate:/hosting'],
            ['keywords' => ['domain', 'alan adı'], 'response' => 'Domain sorgulama sayfamıza gidebilirsiniz. Popüler uzantılar: .com 85₺, .com.tr 75₺, .net 95₺, .org 110₺.', 'action' => 'navigate:/domain'],
            ['keywords' => ['ssl'], 'response' => 'Tüm hosting paketlerimizde ücretsiz SSL sertifikası dahildir. Site Araçları > SSL Kontrol ile mevcut sertifikanızı test edebilirsiniz.', 'action' => 'navigate:/site-araclari/ssl-kontrol'],
            ['keywords' => ['site oluştur', 'site yap', 'website'], 'response' => 'İki seçeneğiniz var: **AI yardımıyla site oluşturmak için paket almanız** gerekir. Ya da **AI yardımı olmadan** kısa bir demo yapabilirsiniz.', 'action' => 'builder:offer'],
            ['keywords' => ['mobil uygulama', 'mobile app'], 'response' => 'Mobile Builder ile radyo, e-ticaret, kurumsal ve daha fazlası için mobil uygulama oluşturabilirsiniz. APK ve AAB çıktı alabilirsiniz.', 'action' => 'navigate:/mobile-builder'],
            ['keywords' => ['fiyat', 'ne kadar', 'ücret'], 'response' => 'Fiyatlarımız için /hosting, /sunucular veya /marketplace sayfalarımıza bakabilirsiniz. Sepetinize eklediğinizde tam tutarı görürsünüz.', 'action' => 'navigate:/hosting'],
            ['keywords' => ['iletişim', 'destek', 'telefon'], 'response' => 'Bize ulaşmak için: 📞 0850 000 00 00 · ✉️ destek@ahost.web.tr · veya /iletisim sayfasından form doldurun.', 'action' => 'navigate:/iletisim'],
            ['keywords' => ['sepet'], 'response' => 'Sepetinize eklediğiniz ürünleri /sepet sayfasında görebilir, kupon uygulayıp ödemeye geçebilirsiniz.', 'action' => 'navigate:/sepet'],
        ];

        $customer = [
            ['keywords' => ['fatura', 'faturam'], 'response' => 'Faturalarınız Panel > Faturalarım sekmesinde. Bekleyen ödeme varsa hemen ödeyebilirsiniz.', 'action' => 'navigate:/panel'],
            ['keywords' => ['hizmet', 'hizmetim', 'yenile'], 'response' => 'Aktif hizmetlerinizi Panel > Hizmetlerim menüsünden görebilir, yenileyebilirsiniz.', 'action' => 'navigate:/panel'],
            ['keywords' => ['ticket', 'destek talebi'], 'response' => 'Destek talebi oluşturmak için panelden ilgili bölüme gidin (Faz 5b\'de aktif olacak).', 'action' => 'navigate:/panel'],
            ['keywords' => ['şifremi'], 'response' => 'Şifrenizi Panel > Güvenlik bölümünden değiştirebilirsiniz.', 'action' => 'navigate:/panel'],
        ];

        $admin = [
            ['keywords' => ['müşteri ara', 'müşteri bul'], 'response' => 'Admin > Müşteriler sayfasında arama yapabilirsiniz.', 'action' => 'navigate:/admin/musteriler'],
            ['keywords' => ['ürün ekle', 'yeni ürün'], 'response' => 'Admin > Ürün Merkezi > Yeni Ürün butonundan yeni ürün ekleyebilirsiniz.', 'action' => 'navigate:/admin/urun-merkezi/yeni'],
            ['keywords' => ['sipariş'], 'response' => 'Siparişleri Admin > Siparişler menüsünden görüntüleyebilirsiniz.', 'action' => 'navigate:/admin/siparisler'],
            ['keywords' => ['rapor', 'gelir'], 'response' => 'Finansal raporları Admin > Finans menüsünden alabilirsiniz.', 'action' => 'navigate:/admin/finans'],
            ['keywords' => ['sağlık', 'health', 'sistem durumu'], 'response' => 'Sistem durumunu Admin > Health Center\'dan görebilirsiniz.', 'action' => 'navigate:/admin/health-center'],
        ];

        return match ($context) {
            'customer' => array_merge($customer, $common),
            'admin'    => array_merge($admin, [['keywords' => ['hosting'], 'response' => 'Hosting sunucularını Admin > Hosting & Sunucu menüsünden yönetin.', 'action' => 'navigate:/admin/hosting-sunucu']]),
            default    => $common,
        };
    }

    private static function defaultResponse(string $context): string
    {
        return match ($context) {
            'customer' => 'Yardımcı olmaya çalışayım. Fatura, hizmet yenileme, domain, destek talebi veya güvenlik konularında sorabilirsiniz.',
            'admin'    => 'Nasıl yardımcı olabilirim? "müşteri ara", "yeni ürün", "gelir raporu", "sistem durumu" gibi komutlar kullanabilirsiniz.',
            default    => 'Merhaba! Hosting, domain, site builder veya diğer hizmetlerimiz hakkında sorularınızı yanıtlayabilirim. Nasıl yardımcı olabilirim?',
        };
    }
}
