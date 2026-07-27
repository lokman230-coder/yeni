<?php

declare(strict_types=1);

namespace App\Modules\Ai\Services;

use App\Core\Database\Connection;
use App\Modules\Ai\Providers\HeuristicProvider;
use App\Modules\Ai\Providers\OpenAiProvider;
use App\Services\Settings\SettingsManager;

/**
 * AI ile içerik üreteci — ürün açıklaması, blog taslağı, SEO meta.
 *
 * Sağlayıcı olarak SettingsManager'dan OpenAI/Heuristic seçilir.
 * OpenAI yoksa Heuristic ile "template dolu" içerik üretir (kaba ama kullanılabilir).
 */
final class ContentGenerator
{
    /**
     * Jenerik içerik üretici (Builder AI için).
     * @param string $kind 'block' | 'product' | 'blog' | 'seo'
     * @param array $context ['topic', 'block_type' vb.]
     * @return array
     */
    public static function generate(string $kind, array $context): array
    {
        $topic = (string) ($context['topic'] ?? '');
        $blockType = (string) ($context['block_type'] ?? 'hero');

        // Basit heuristic — üretim öbeği
        $titleMap = [
            'hero'         => "$topic ile Farkı Yaşayın",
            'features'     => "Neden $topic?",
            'cta'          => "$topic için Hemen Başlayın",
            'pricing'      => "$topic Fiyatlandırma",
            'testimonials' => "Müşterilerimiz $topic Hakkında Ne Diyor?",
            'faq'          => "$topic Hakkında Sık Sorulanlar",
        ];
        $subtitleMap = [
            'hero'         => "Profesyonel çözümlerle işinizi büyütün.",
            'features'     => "Sunduğumuz avantajları keşfedin.",
            'cta'          => "İhtiyacınız olan tüm araçlar burada.",
            'pricing'      => "Her bütçeye uygun paketler.",
            'testimonials' => "Binlerce mutlu müşteri ile.",
            'faq'          => "Merak ettiklerinizin cevapları burada.",
        ];

        return [
            'title'       => $titleMap[$blockType] ?? "$topic",
            'subtitle'    => $subtitleMap[$blockType] ?? '',
            'description' => "$topic hakkında bilgi almak için doğru yerdesiniz. Hemen keşfetmeye başlayın.",
        ];
    }

    /**
     * Ürün için satış odaklı Türkçe açıklama üretir.
     *
     * @param array $product ['name','type'?,'short_description'?]
     * @return array{ok:bool, short:string, long:string, features:array<int,string>}
     */
    public static function productDescription(array $product): array
    {
        $provider = self::provider();
        $name = (string) ($product['name'] ?? 'Ürün');
        $type = (string) ($product['type'] ?? 'hosting');

        if ($provider instanceof OpenAiProvider) {
            $r = self::openaiProduct($provider, $name, $type);
            if ($r !== null) return $r;
        }
        // Heuristic fallback
        return self::heuristicProduct($name, $type);
    }

    /**
     * Blog yazısı taslağı üretir.
     *
     * @return array{ok:bool, title:string, excerpt:string, body_html:string}
     */
    public static function blogPost(string $topic, ?string $angle = null): array
    {
        $provider = self::provider();
        if ($provider instanceof OpenAiProvider) {
            $r = self::openaiBlog($provider, $topic, $angle);
            if ($r !== null) return $r;
        }
        return self::heuristicBlog($topic, $angle);
    }

    /**
     * SEO meta title + description öner.
     * @return array{ok:bool, title:string, description:string, keywords:string}
     */
    public static function seoMeta(string $pageTitle, string $pageContent = ''): array
    {
        $provider = self::provider();
        if ($provider instanceof OpenAiProvider) {
            $r = self::openaiSeo($provider, $pageTitle, $pageContent);
            if ($r !== null) return $r;
        }
        return self::heuristicSeo($pageTitle, $pageContent);
    }

    // ---- Provider selector ----

    private static function provider()
    {
        $name   = (string) SettingsManager::get('ai.provider', 'heuristic', 'AI_PROVIDER');
        $apiKey = (string) SettingsManager::get('ai.api_key',  '',          'AI_API_KEY');
        $model  = (string) SettingsManager::get('ai.model',    'gpt-4o-mini', 'AI_MODEL');
        if ($name === 'openai' && $apiKey !== '') {
            return new OpenAiProvider($apiKey, $model);
        }
        return new HeuristicProvider();
    }

    // ---- OpenAI ----

    private static function openaiProduct(OpenAiProvider $p, string $name, string $type): ?array
    {
        $system = "Sen Türkçe yazan satış odaklı bir copywriter'sın. Sadece JSON döndür, açıklama yazma.";
        $user = "Ürün adı: $name\nTür: $type\n\nJSON formatı: {\"short\":\"1 cümle satış vurgusu, max 120 char\", \"long\":\"3-5 paragraflık HTML açıklama (özellikler, avantajlar, kime uygun)\", \"features\":[\"öğe1\",\"öğe2\",...max 6]}";
        $r = $p->chat([
            ['role'=>'system','content'=>$system],
            ['role'=>'user','content'=>$user],
        ], ['temperature' => 0.7, 'max_tokens' => 800]);

        if (!empty($r['error'])) return null;
        $raw = trim((string) $r['content']);
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $raw) ?? $raw;
        $data = json_decode($raw, true);
        if (!is_array($data)) return null;
        return [
            'ok' => true,
            'short'    => (string) ($data['short'] ?? ''),
            'long'     => (string) ($data['long'] ?? ''),
            'features' => is_array($data['features'] ?? null) ? $data['features'] : [],
        ];
    }

    private static function openaiBlog(OpenAiProvider $p, string $topic, ?string $angle): ?array
    {
        $system = "Sen Türkçe SEO odaklı bir blog yazarısın. Sadece JSON döndür.";
        $user = "Konu: $topic" . ($angle ? "\nAçı: $angle" : '') .
                "\n\nJSON: {\"title\":\"H1 başlık\", \"excerpt\":\"140 char özet\", \"body_html\":\"800-1200 kelimelik HTML blog yazısı (h2, p, ul, strong etiketleri kullan)\"}";
        $r = $p->chat([
            ['role'=>'system','content'=>$system],
            ['role'=>'user','content'=>$user],
        ], ['temperature' => 0.8, 'max_tokens' => 2000]);
        if (!empty($r['error'])) return null;
        $raw = trim((string) $r['content']);
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $raw) ?? $raw;
        $data = json_decode($raw, true);
        if (!is_array($data)) return null;
        return ['ok'=>true,'title'=>(string)($data['title']??''),'excerpt'=>(string)($data['excerpt']??''),'body_html'=>(string)($data['body_html']??'')];
    }

    private static function openaiSeo(OpenAiProvider $p, string $title, string $content): ?array
    {
        $system = "Sen SEO uzmanısın. Türkçe. Sadece JSON.";
        $user = "Sayfa başlığı: $title\nİçerik özeti: " . mb_substr($content, 0, 500) .
                "\n\nJSON: {\"title\":\"55-60 char meta title\", \"description\":\"140-160 char meta description (satış vurgusu ile)\", \"keywords\":\"5-8 virgülle ayrılmış anahtar kelime\"}";
        $r = $p->chat([
            ['role'=>'system','content'=>$system],
            ['role'=>'user','content'=>$user],
        ], ['temperature' => 0.4, 'max_tokens' => 300]);
        if (!empty($r['error'])) return null;
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim((string) $r['content'])) ?? '';
        $data = json_decode($raw, true);
        if (!is_array($data)) return null;
        return ['ok'=>true,'title'=>(string)($data['title']??''),'description'=>(string)($data['description']??''),'keywords'=>(string)($data['keywords']??'')];
    }

    // ---- Heuristic fallbacks (OpenAI yoksa) ----

    private static function heuristicProduct(string $name, string $type): array
    {
        $templates = [
            'hosting' => [
                'short' => "$name — hızlı, güvenli ve uygun fiyatlı hosting çözümü.",
                'features' => ['NVMe SSD disk', 'Ücretsiz SSL sertifikası', 'Günlük otomatik yedekleme', '7/24 Türkçe destek', '%99.9 uptime garantisi', 'cPanel kontrol paneli'],
                'long' => "<h3>$name Hosting Paketi</h3><p>Modern web siteleri için tasarlanmış <strong>$name</strong> hosting paketi ile web sitenizi hızlı, güvenli ve profesyonel bir altyapı üzerinde yayınlayın. NVMe SSD diskler, ücretsiz SSL sertifikası ve günlük yedekleme ile içeriniz rahat olsun.</p><h4>Neden bu paketi seçmelisiniz?</h4><ul><li>⚡ NVMe SSD ile 10x daha hızlı yükleme</li><li>🔒 Ücretsiz Let's Encrypt SSL</li><li>💾 Günlük otomatik yedekleme</li><li>🎧 7/24 Türkçe teknik destek</li><li>📊 %99.9 uptime garantisi</li></ul><p><strong>Kimlere uygun?</strong> Kişisel blog, kurumsal web sitesi, e-ticaret altyapısı veya WordPress sitesi çalıştırmak isteyen herkes için ideal.</p>",
            ],
            'vps' => [
                'short' => "$name — kendi sanal sunucunuz, tam kontrol sizde.",
                'features' => ['Tam root erişimi', 'SSD depolama', 'Ölçeklenebilir kaynaklar', 'DDoS koruması', 'Ubuntu/Debian/CentOS seçimi', 'Anlık yeniden kurulum'],
                'long' => "<h3>$name Sanal Sunucu</h3><p><strong>$name</strong> VPS ile kendi sanal sunucunuza sahip olun. Root erişimi ile istediğiniz yazılımı kurup istediğiniz gibi yönetin. SSD depolama, DDoS koruması ve anlık yeniden kurulum imkanıyla ihtiyaçlarınıza göre büyüyün.</p>",
            ],
            'domain' => [
                'short' => "$name — markanızı internette güvenle temsil edin.",
                'features' => ['Ücretsiz DNS yönetimi', 'WHOIS gizliliği', 'Otomatik yenileme', 'Kolay transfer', '7/24 destek'],
                'long' => "<h3>$name Domain</h3><p>Markanızın internetteki kimliği için $name uzantısı ile kayıt yaptırın. Ücretsiz DNS yönetimi, WHOIS gizliliği ve kolay transfer imkanı.</p>",
            ],
        ];
        $t = $templates[$type] ?? $templates['hosting'];
        return array_merge(['ok' => true], $t);
    }

    private static function heuristicBlog(string $topic, ?string $angle): array
    {
        $title = ucfirst($topic) . ' Hakkında Bilmeniz Gerekenler';
        return [
            'ok' => true,
            'title' => $title,
            'excerpt' => "$topic konusunda merak edilenleri, temel bilgileri ve pratik ipuçlarını bir arada bulacağınız kapsamlı rehber.",
            'body_html' => "<p>$topic, günümüzün en çok konuşulan konularından biri. Bu yazıda temel bilgileri ve pratik ipuçlarını birlikte inceleyelim.</p>
                <h2>$topic Nedir?</h2>
                <p>Detaylı bilgi burada... (AI OpenAI ile bağlıyken içerik otomatik doldurulur)</p>
                <h2>Neden Önemli?</h2>
                <p>Bu konuya dikkat etmemizin sebepleri...</p>
                <h2>Nasıl Uygulanır?</h2>
                <p>Pratik adımlar...</p>
                <p><em>Not: Bu içerik AI Heuristic modda üretilmiştir. Daha zengin içerik için Admin > Ayarlar > AI'dan OpenAI'ı aktifleştirin.</em></p>",
        ];
    }

    private static function heuristicSeo(string $title, string $content): array
    {
        $t = mb_substr($title, 0, 55);
        return [
            'ok' => true,
            'title' => "$t | " . (string) SettingsManager::get('site.name', 'Ahost Bilişim'),
            'description' => mb_substr("$title — " . (mb_substr(strip_tags($content), 0, 130) ?: 'Detaylı bilgi ve profesyonel çözümler.'), 0, 160),
            'keywords' => strtolower(str_replace(' ', ', ', $title)),
        ];
    }
}
