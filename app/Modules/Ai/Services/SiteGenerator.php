<?php

declare(strict_types=1);

namespace App\Modules\Ai\Services;

use App\Modules\Ai\Providers\HeuristicProvider;
use App\Modules\Ai\Providers\OpenAiProvider;
use App\Modules\Builder\Services\ProjectRepository;
use App\Modules\Builder\Services\TemplateLibrary;
use App\Core\Database\Connection;
use App\Services\Logger\Logger;

/**
 * AI Site Generator — "diş hekimi sitem yap" gibi bir prompt'tan
 * eksiksiz bir Builder projesi oluşturur.
 *
 * Akış:
 *   1. SectorDetector::detect() → sektör + isim tahmini + güven
 *   2. sector-özgü içerik şablonlarından hero/features/CTA metinleri üret
 *   3. (opsiyonel) OPENAI_API_KEY varsa OpenAiProvider ile iyileştir
 *   4. TemplateLibrary::starterTree ile blok iskeletini al
 *   5. Blokları AI içeriğiyle doldur
 *   6. ProjectRepository::create + createHomepage
 *
 * Dönen array: ['project_id', 'page_id', 'sector', 'name', 'ai_used', 'tree']
 */
final class SiteGenerator
{
    /**
     * @param int    $customerId Sahibi
     * @param string $prompt     Kullanıcının serbest metin isteği
     * @param array  $options    ['name'?: string, 'sector'?: string, 'use_ai'?: bool]
     * @return array{
     *   ok:bool, project_id?:int, page_id?:int, sector?:string,
     *   name?:string, confidence?:float, ai_used?:bool, error?:string,
     *   matched_keywords?:array<int,string>
     * }
     */
    public static function generate(int $customerId, string $prompt, array $options = []): array
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            return ['ok' => false, 'error' => 'Boş prompt.'];
        }

        // 1) Sektör algılama
        $detected = SectorDetector::detect($prompt);
        $sector = (string) ($options['sector'] ?? $detected['sector']);
        $name = (string) ($options['name'] ?? $detected['app_name_guess'] ?? self::defaultName($sector));

        // 2) İçerik üret
        $useAi = (bool) ($options['use_ai'] ?? true);
        $aiUsed = false;
        $content = self::heuristicContent($sector, $name, $prompt);

        if ($useAi && (string) \App\Services\Settings\SettingsManager::get('ai.api_key', '', 'AI_API_KEY') !== '') {
            try {
                $improved = self::openAiRefine($sector, $name, $prompt, $content);
                if ($improved !== null) {
                    $content = array_merge($content, $improved);
                    $aiUsed = true;
                }
            } catch (\Throwable $e) {
                Logger::warning('OpenAI refine failed, heuristic kullanılıyor', ['err' => $e->getMessage()]);
            }
        }

        // 3) Şablon ağacı — kind: site veya mobile
        $kindOpt = $options['kind'] ?? 'site';
        $kind = in_array($kindOpt, ['site','mobile'], true) ? $kindOpt : 'site';
        $tree = TemplateLibrary::starterTree($kind, $sector, [
            'app_name' => $name,
            'tagline'  => $content['tagline'] ?? '',
        ]);

        // 4) Blokları içerikle doldur
        $tree = self::fillBlocks($tree, $content);

        // 5) Proje + anasayfa oluştur
        try {
            $projectId = ProjectRepository::create(
                $customerId, $kind, $sector, $name,
                [
                    'ai_prompt'   => $prompt,
                    'ai_used'     => $aiUsed,
                    'primary_color' => $content['primary_color'] ?? '#0ea5e9',
                    'generated_at'  => date('Y-m-d H:i:s'),
                ]
            );
            $pageId = ProjectRepository::createHomepage($projectId, $name, $tree);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Proje kaydedilemedi: ' . $e->getMessage()];
        }

        // 6) ai_logs kaydı (opsiyonel — tablo varsa)
        self::logGeneration($customerId, $prompt, $sector, $projectId, $aiUsed);

        return [
            'ok'               => true,
            'project_id'       => $projectId,
            'page_id'          => $pageId,
            'sector'           => $sector,
            'name'             => $name,
            'kind'             => $kind,
            'confidence'       => $detected['confidence'],
            'matched_keywords' => $detected['matched_keywords'],
            'ai_used'          => $aiUsed,
            'tree'             => $tree,
        ];
    }

    /**
     * Sektöre özgü kural-tabanlı metinler.
     * OpenAI yoksa (veya isteğe bağlı) tek başına çalışır.
     *
     * @return array<string,mixed>
     */
    private static function heuristicContent(string $sector, string $name, string $prompt): array
    {
        $base = self::sectorTemplates()[$sector] ?? self::sectorTemplates()['landing'];
        // "{name}" placeholder değiştirme
        array_walk_recursive($base, function (&$v) use ($name) {
            if (is_string($v)) $v = str_replace('{name}', $name, $v);
        });
        return $base;
    }

    /** @return array<string, array<string,mixed>> */
    private static function sectorTemplates(): array
    {
        return [
            'clinic' => [
                'tagline'       => 'Sağlığınız için modern ve güvenilir tedavi',
                'hero_title'    => '{name} — Uzman Kadromuzla Yanınızdayız',
                'hero_subtitle' => 'Deneyimli uzman kadromuz ve son teknoloji ekipmanlarla, sağlığınız için en iyi hizmeti sunuyoruz. Online randevu ile hemen yerinizi ayırın.',
                'hero_cta'      => 'Randevu Al',
                'services_title'=> 'Hizmetlerimiz',
                'services_items'=> ['Genel Muayene', 'Uzman Konsültasyonu', 'Görüntüleme', 'Laboratuvar', 'Kontrol ve Takip'],
                'features_title'=> 'Neden {name}?',
                'features'      => ['Deneyimli Uzman Kadro', 'Modern Cihazlar', 'Hijyenik Ortam', 'Online Randevu', 'Uygun Fiyat', 'Hasta Memnuniyeti'],
                'cta_title'     => 'Randevu için bekliyoruz',
                'cta_button'    => 'Hemen Randevu Al',
                'about'         => '{name} olarak yıllardır bölgemizde güvenilir sağlık hizmeti sunuyoruz. Uzman doktor kadromuz ve modern teknolojimizle sizinleyiz.',
                'primary_color' => '#0891b2',
            ],
            'restaurant' => [
                'tagline'       => 'Damak zevkinize uygun eşsiz lezzetler',
                'hero_title'    => '{name} — Yemek Değil, Deneyim',
                'hero_subtitle' => 'Taze malzemeler, usta ellerden çıkan özenli sunumlar ve sıcak atmosferimizle sizi bekliyoruz.',
                'hero_cta'      => 'Rezervasyon Yap',
                'menu_title'    => 'Menümüz',
                'menu_items'    => ['Başlangıçlar', 'Ana Yemekler', 'Tatlılar', 'İçecekler'],
                'features_title'=> 'Neden Bizi Tercih Etmelisiniz?',
                'features'      => ['Taze Malzeme', 'Usta Şefler', 'Şık Ortam', 'Uygun Fiyat', 'Online Rezervasyon', 'Paket Servis'],
                'cta_title'     => 'Masanızı şimdi ayırtın',
                'cta_button'    => 'Rezervasyon Yap',
                'primary_color' => '#dc2626',
            ],
            'ecommerce' => [
                'tagline'       => 'Kaliteli ürünler, hızlı teslimat',
                'hero_title'    => '{name} — Kaliteli Ürünler Kapınızda',
                'hero_subtitle' => 'Özenle seçilmiş ürünler, kolay ödeme ve hızlı kargo. Alışverişin keyfini {name} ile çıkarın.',
                'hero_cta'      => 'Alışverişe Başla',
                'features_title'=> 'Bize Güvenmeniz İçin Sebepler',
                'features'      => ['Ücretsiz Kargo', 'İade Garantisi', 'Güvenli Ödeme', '7/24 Müşteri Hizmetleri', 'Orijinal Ürün', 'Hızlı Teslimat'],
                'cta_title'     => 'İlk siparişe özel %10 indirim',
                'cta_button'    => 'Şimdi Alışveriş Yap',
                'primary_color' => '#059669',
            ],
            'radio' => [
                'tagline'       => '7/24 Canlı Yayın',
                'hero_title'    => '{name} — Müziğin Kalbi Burada',
                'hero_subtitle' => 'En yeni parçalar, favori DJ\'leriniz ve kaçırılmayacak programlarla 7/24 sizinleyiz.',
                'hero_cta'      => 'Canlı Dinle',
                'features_title'=> 'Yayın Akışı',
                'features'      => ['Sabah Programı', 'Öğlen Müzikleri', 'Akşam Sohbetleri', 'Gece DJ Set', 'Haftasonu Özel', 'Konuk Yayınlar'],
                'primary_color' => '#8b5cf6',
            ],
            'hosting' => [
                'tagline'       => 'Hızlı, güvenli ve uygun fiyatlı hosting',
                'hero_title'    => '{name} — İnternette Sınırsız Güç',
                'hero_subtitle' => 'NVMe SSD altyapımız, ücretsiz SSL sertifikamız ve 7/24 uzman desteğimizle işinizi bir üst seviyeye taşıyoruz.',
                'hero_cta'      => 'Paketleri İncele',
                'features_title'=> 'Neden {name}?',
                'features'      => ['NVMe SSD Disk', 'Ücretsiz SSL', 'Günlük Yedekleme', '7/24 Türkçe Destek', '%99.9 Uptime Garantisi', 'cPanel Kontrol Paneli'],
                'primary_color' => '#0ea5e9',
            ],
            'education' => [
                'tagline'       => 'Geleceğinizi bugünden şekillendirin',
                'hero_title'    => '{name} — Öğrenmenin Yeni Yolu',
                'hero_subtitle' => 'Alanında uzman eğitmenler, güncel içerikler ve interaktif dersler. İstediğiniz her yerden, istediğiniz her zaman.',
                'hero_cta'      => 'Kursları Keşfet',
                'features_title'=> 'Neden {name}?',
                'features'      => ['Uzman Eğitmen', 'İnteraktif Dersler', 'Sertifika', 'Mobil Erişim', 'Ömür Boyu Erişim', 'Uygun Fiyat'],
                'primary_color' => '#d97706',
            ],
            'portfolio' => [
                'tagline'       => 'İşlerim, hikayem, tarzım',
                'hero_title'    => '{name}',
                'hero_subtitle' => 'Yaptığım işlerden bir seçki, yaklaşımım ve süreçlerim hakkında.',
                'hero_cta'      => 'Çalışmalarımı Gör',
                'features_title'=> 'Yaptığım İşler',
                'features'      => ['Kurumsal Kimlik', 'Web Tasarım', 'Fotoğraf', 'Video', 'İllüstrasyon', 'Ambalaj'],
                'primary_color' => '#7c3aed',
            ],
            'saas' => [
                'tagline'       => 'İşinizi büyütecek modern yazılım',
                'hero_title'    => '{name} — Verimliliğinizi Katlayın',
                'hero_subtitle' => 'Modern arayüzü, güçlü entegrasyonları ve akıllı otomasyonlarıyla ekibinizin işini kolaylaştırır.',
                'hero_cta'      => 'Ücretsiz Deneyin',
                'features_title'=> 'Özellikler',
                'features'      => ['Sınırsız Kullanıcı', 'Bulut Yedekleme', 'API Erişimi', 'Mobil Uygulama', 'Otomasyon', 'Detaylı Raporlar'],
                'primary_color' => '#2563eb',
            ],
            'agency' => [
                'tagline'       => 'Markanızı bir üst seviyeye taşıyoruz',
                'hero_title'    => '{name} — Vizyoner Çözümler',
                'hero_subtitle' => 'Stratejik yaklaşımımız ve yaratıcı ekibimizle markanızı hedef kitlenizle buluşturuyoruz.',
                'hero_cta'      => 'Bize Ulaşın',
                'features_title'=> 'Hizmetlerimiz',
                'features'      => ['Marka Stratejisi', 'Dijital Pazarlama', 'Web Tasarım', 'Sosyal Medya', 'İçerik Üretimi', 'SEO'],
                'primary_color' => '#111827',
            ],
            'local' => [
                'tagline'       => 'Mahallenizin güvenilir adresi',
                'hero_title'    => '{name} — Kaliteli Hizmet, Uygun Fiyat',
                'hero_subtitle' => 'Yılların deneyimi, güler yüzlü ekibimiz ve rekabetçi fiyatlarımızla her zaman yanınızdayız.',
                'hero_cta'      => 'Bizi Arayın',
                'features_title'=> 'Neden Biz?',
                'features'      => ['Deneyimli Ekip', 'Uygun Fiyat', 'Hızlı Hizmet', 'Garanti', 'Randevu ile Çalışma', 'Müşteri Memnuniyeti'],
                'primary_color' => '#059669',
            ],
            'landing' => [
                'tagline'       => 'Yepyeni bir başlangıç',
                'hero_title'    => '{name} — Çok Yakında',
                'hero_subtitle' => 'Hazırladığımız yeniliklerden ilk siz haberdar olmak için ön kayıt yaptırın.',
                'hero_cta'      => 'Bekleme Listesine Katıl',
                'features_title'=> 'Neler Sunacağız',
                'features'      => ['Modern Arayüz', 'Hızlı Deneyim', 'Uygun Fiyat', 'Kolay Kullanım', '7/24 Destek', 'Sürekli Güncelleme'],
                'primary_color' => '#0ea5e9',
            ],
        ];
    }

    /**
     * OpenAI ile içerik iyileştirme (opsiyonel).
     * Başarısız olursa null döner; upstream heuristic sonucunu kullanır.
     */
    private static function openAiRefine(string $sector, string $name, string $prompt, array $baseContent): ?array
    {
        $provider = new OpenAiProvider();
        $system = "Sen bir Türkçe web copywriter'sın. Kullanıcının işletme türüne göre kısa, satış odaklı ve profesyonel metinler yazıyorsun. Her yanıtı SADECE geçerli JSON olarak ver — hiçbir açıklama, hiçbir markdown yok.";
        $user = "Kullanıcı prompt'u: \"$prompt\"\nSektör: $sector\nİşletme adı: $name\n\n"
              . "Şu alanları içeren JSON döndür: hero_title (max 60 char), hero_subtitle (max 140 char), tagline (max 40 char), features (6 öğelik string dizi, her biri max 30 char), cta_title (max 50 char), about (max 200 char).";

        $r = $provider->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => $user],
        ], ['max_tokens' => 600, 'temperature' => 0.7]);

        if (!empty($r['error'])) {
            Logger::warning('OpenAI hata döndü', ['err' => $r['error']]);
            return null;
        }
        $raw = trim((string) ($r['content'] ?? ''));
        // Bazı modeller ```json … ``` sarabilir
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $raw) ?? $raw;
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            Logger::warning('OpenAI JSON parse edilemedi', ['raw' => mb_substr($raw, 0, 200)]);
            return null;
        }
        return $data;
    }

    /**
     * TemplateLibrary'den gelen blok ağacını, üretilen içerikle doldurur.
     * Blok type'lara göre alanları eşleştirir.
     */
    private static function fillBlocks(array $tree, array $content): array
    {
        if (empty($tree['blocks']) || !is_array($tree['blocks'])) return $tree;

        foreach ($tree['blocks'] as &$block) {
            $type = $block['type'] ?? '';
            $block['props'] ??= [];
            switch ($type) {
                case 'hero':
                    $block['props']['title']    = $content['hero_title']    ?? $block['props']['title']    ?? '';
                    $block['props']['subtitle'] = $content['hero_subtitle'] ?? $block['props']['subtitle'] ?? '';
                    $block['props']['cta_text'] = $content['hero_cta']      ?? $block['props']['cta_text'] ?? 'Hemen Başla';
                    break;
                case 'features':
                    $block['props']['title'] = $content['features_title'] ?? $block['props']['title'] ?? 'Öne Çıkan Özellikler';
                    if (!empty($content['features']) && is_array($content['features'])) {
                        $block['props']['items'] = $content['features'];
                    }
                    break;
                case 'services':
                    $block['props']['title'] = $content['services_title'] ?? $content['features_title'] ?? 'Hizmetlerimiz';
                    if (!empty($content['services_items']) && is_array($content['services_items'])) {
                        $block['props']['items'] = $content['services_items'];
                    }
                    break;
                case 'menu':
                    $block['props']['title'] = $content['menu_title'] ?? 'Menü';
                    if (!empty($content['menu_items']) && is_array($content['menu_items'])) {
                        $block['props']['items'] = $content['menu_items'];
                    }
                    break;
                case 'cta':
                    $block['props']['title']  = $content['cta_title']  ?? $block['props']['title']  ?? '';
                    $block['props']['button'] = $content['cta_button'] ?? $block['props']['button'] ?? 'Hemen Başla';
                    break;
                case 'about':
                    if (!empty($content['about'])) {
                        $block['props']['text'] = $content['about'];
                    }
                    break;
            }
        }
        return $tree;
    }

    private static function defaultName(string $sector): string
    {
        return match ($sector) {
            'clinic'     => 'Sağlık Merkezi',
            'restaurant' => 'Lezzet Durağı',
            'ecommerce'  => 'Online Mağaza',
            'radio'      => 'FM Radyo',
            'hosting'    => 'Hosting Firmam',
            'education'  => 'Akademi',
            'portfolio'  => 'Portfolyom',
            'saas'       => 'Uygulamam',
            'agency'     => 'Ajansım',
            'local'      => 'İşletmem',
            default      => 'Yeni Sitem',
        };
    }

    private static function logGeneration(int $customerId, string $prompt, string $sector, int $projectId, bool $aiUsed): void
    {
        try {
            $tables = Connection::select("SHOW TABLES LIKE 'ai_logs'");
            if (empty($tables)) return;
            Connection::insert('ai_logs', [
                'customer_id' => $customerId,
                'admin_id'    => null,
                'context'     => 'site_generator',
                'provider'    => $aiUsed ? 'openai' : 'heuristic',
                'prompt'      => mb_substr($prompt, 0, 1000),
                'response'    => json_encode(['project_id' => $projectId, 'sector' => $sector], JSON_UNESCAPED_UNICODE),
                'tokens_used' => 0,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Log tablosu farklı şemalı olabilir; sessiz geç
            Logger::debug('SiteGenerator log yazamadı: ' . $e->getMessage());
        }
    }
}
