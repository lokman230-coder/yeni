<?php

declare(strict_types=1);

namespace App\Modules\Ai\Services;

/**
 * Kullanıcı prompt'unu okuyup Site Builder sektörüne eşler.
 *
 * Kural-tabanlı çalışır (OpenAI bağımlılığı YOK); istenirse
 * OpenAiProvider ile daha sonra "ince ayar" yapılabilir.
 *
 * Sektörler (TemplateLibrary::siteSectors ile birebir):
 *   hosting, agency, landing, radio, ecommerce, restaurant,
 *   clinic, education, portfolio, saas, local
 */
final class SectorDetector
{
    /** @return array{sector:string, confidence:float, matched_keywords:array<int,string>, app_name_guess:?string} */
    public static function detect(string $prompt): array
    {
        $p = ' ' . mb_strtolower($prompt, 'UTF-8') . ' ';
        $best = ['sector' => 'landing', 'score' => 0, 'matched' => []];

        foreach (self::sectorKeywords() as $sector => $keywords) {
            $matched = [];
            $score = 0.0;
            foreach ($keywords as $kw => $weight) {
                if (mb_strpos($p, $kw) !== false) {
                    $matched[] = $kw;
                    $score += $weight;
                }
            }
            if ($score > $best['score']) {
                $best = ['sector' => $sector, 'score' => $score, 'matched' => $matched];
            }
        }

        // "sitem/uygulamam yap" gibi kalıplardan olası isim çıkarımı
        $appName = self::guessAppName($prompt);

        // Confidence: 0..1 (skor 5 = full)
        $confidence = min(1.0, $best['score'] / 5.0);

        return [
            'sector'           => $best['sector'],
            'confidence'       => $confidence,
            'matched_keywords' => $best['matched'],
            'app_name_guess'   => $appName,
        ];
    }

    /**
     * Her sektör için ağırlıklı anahtar kelimeler.
     * Yüksek ağırlık = güçlü sinyal (ör: "diş hekimi" = 5, "sağlık" = 1)
     * @return array<string, array<string, float>>
     */
    private static function sectorKeywords(): array
    {
        return [
            'clinic' => [
                'diş hekimi' => 5, 'diş kliniği' => 5, 'diş' => 3, 'dişçi' => 5,
                'doktor' => 4, 'hastane' => 4, 'poliklinik' => 4, 'klinik' => 4,
                'sağlık' => 2, 'randevu' => 2, 'tedavi' => 2, 'muayene' => 2,
                'estetik' => 3, 'psikolog' => 4, 'diyetisyen' => 4, 'fizyoterapi' => 4,
                'pediatri' => 4, 'kadın doğum' => 4, 'göz doktoru' => 4, 'kbb' => 3,
                'dermatolog' => 4, 'veteriner' => 4,
            ],
            'restaurant' => [
                'restoran' => 5, 'restaurant' => 5, 'lokanta' => 5, 'menü' => 3,
                'yemek' => 3, 'cafe' => 4, 'kafe' => 4, 'kahvaltı' => 3,
                'pizzeria' => 5, 'pizza' => 3, 'kebap' => 4, 'burger' => 4,
                'sushi' => 4, 'rezervasyon' => 3, 'mutfak' => 2, 'şef' => 3,
                'bar' => 3, 'pub' => 4, 'pastane' => 4, 'kebapçı' => 5,
            ],
            'ecommerce' => [
                'e-ticaret' => 5, 'eticaret' => 5, 'online satış' => 4, 'mağaza' => 4,
                'shop' => 4, 'satış sitesi' => 5, 'ürün satmak' => 5,
                'butik' => 4, 'takı' => 3, 'kozmetik satış' => 4, 'giyim' => 3,
                'ayakkabı satış' => 4, 'aksesuar' => 3, 'sepet' => 2,
            ],
            'radio' => [
                'radyo' => 5, 'radio' => 5, 'dj' => 3, 'canlı yayın' => 4,
                'stream' => 3, 'yayın akışı' => 4, 'müzik yayın' => 4,
            ],
            'hosting' => [
                'hosting' => 5, 'sunucu satış' => 5, 'domain satış' => 4,
                'web hosting' => 5, 'vps satış' => 4, 'server' => 2,
                'reseller' => 4, 'cpanel' => 3,
            ],
            'education' => [
                'eğitim' => 4, 'kurs' => 4, 'akademi' => 5, 'okul' => 4,
                'ders' => 3, 'öğretmen' => 3, 'öğrenci' => 2, 'sertifika' => 3,
                'online kurs' => 5, 'e-öğrenme' => 5, 'lms' => 4, 'dershane' => 5,
                'dil kursu' => 5, 'yks' => 4, 'sınav hazırlık' => 4,
            ],
            'portfolio' => [
                'portfolyo' => 5, 'portfolio' => 5, 'fotoğrafçı' => 5,
                'grafiker' => 4, 'tasarımcı portfolyo' => 5, 'sanatçı' => 3,
                'ressam' => 4, 'illüstratör' => 4, 'yazar sitesi' => 4,
                'kişisel site' => 4, 'cv site' => 4, 'freelancer' => 3,
            ],
            'saas' => [
                'saas' => 5, 'yazılım' => 3, 'uygulama' => 2, 'abonelik' => 3,
                'crm' => 4, 'erp' => 4, 'aracı yazılım' => 4, 'platform' => 2,
                'api' => 2, 'entegrasyon' => 2,
            ],
            'agency' => [
                'ajans' => 5, 'agency' => 5, 'dijital ajans' => 5,
                'reklam ajansı' => 5, 'kurumsal' => 3, 'danışmanlık' => 4,
                'hukuk bürosu' => 5, 'muhasebe' => 4, 'mali müşavir' => 5,
                'mimarlık' => 4, 'inşaat' => 3, 'mühendislik' => 4,
            ],
            'local' => [
                'kuaför' => 5, 'berber' => 5, 'güzellik salonu' => 5,
                'oto yıkama' => 5, 'oto servis' => 5, 'tamirci' => 4,
                'temizlik firması' => 5, 'nakliyat' => 4, 'kargo' => 3,
                'çiçekçi' => 5, 'lastik' => 4, 'oto tamir' => 5,
            ],
            'landing' => [
                'landing' => 5, 'tek sayfa' => 4, 'lansman' => 4,
                'ön kayıt' => 4, 'bekleme listesi' => 4, 'coming soon' => 4,
                'tanıtım sayfası' => 4,
            ],
        ];
    }

    /**
     * "Ali Diş Kliniği sitem yap", "Kahve Dünyası için restoran sitesi" gibi
     * cümlelerden olası marka/isim çıkarımı. Bulamazsa null döner.
     */
    private static function guessAppName(string $prompt): ?string
    {
        $prompt = trim($prompt);
        // Kalıplar: "X için", "X adlı", "X isimli", "X sitem"
        $patterns = [
            '/"([^"]{2,40})"/u',                              // tırnak içinde
            '/\b([A-ZÇĞİÖŞÜ][\wÇĞİÖŞÜçğıöşü]+(?:\s+[A-ZÇĞİÖŞÜ][\wÇĞİÖŞÜçğıöşü]+){1,4})\s+(?:için|adlı|isimli|sitesi|sitem|adında)\b/u',
            '/(?:için|firmam|markam|işletmem)\s+([A-ZÇĞİÖŞÜ][\wÇĞİÖŞÜçğıöşü\s]{2,30}?)(?:\.|,|$)/u',
        ];
        foreach ($patterns as $rx) {
            if (preg_match($rx, $prompt, $m)) {
                $cand = trim($m[1]);
                if (mb_strlen($cand, 'UTF-8') >= 2 && mb_strlen($cand, 'UTF-8') <= 40) {
                    return $cand;
                }
            }
        }
        return null;
    }
}
