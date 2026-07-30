<?php

declare(strict_types=1);

namespace App\Modules\Domain\Services;

/**
 * Domain değerleme motoru.
 *
 * Faktörler (şartname madde 16):
 *   - TLD (com > net > .tr > diğer)
 *   - Uzunluk (kısa = değerli)
 *   - Marka gücü (heuristik: hece sayısı, tekrar eden karakter, tire/rakam yok)
 *   - Yaş (WHOIS created_date)
 *   - SEO sinyalleri (WHOIS + DNS varlığı)
 *   - Aktif SSL var mı
 *   - WHOIS gizliliği vs.
 *   - Ticari potansiyel skorlaması
 */
final class ValuationService
{
    private const TLD_WEIGHTS = [
        'com'    => 100,
        'net'    => 60,
        'org'    => 55,
        'io'     => 85,
        'ai'     => 90,
        'co'     => 70,
        'com.tr' => 80,
        'net.tr' => 45,
        'org.tr' => 40,
        'dev'    => 65,
        'app'    => 65,
        'tech'   => 50,
        'online' => 30,
        'xyz'    => 20,
        'top'    => 15,
    ];

    /**
     * @param string     $domain
     * @param array|null $whois  ManualDriver/DomainNameApiDriver::whois() sonucu
     * @param array|null $dns    dnsRecords sonucu
     * @param array|null $ssl    SslService::check sonucu
     * @return array
     */
    public static function evaluate(string $domain, ?array $whois = null, ?array $dns = null, ?array $ssl = null): array
    {
        $domain = strtolower(trim($domain));
        $parts = self::splitTld($domain);
        $sld = $parts['sld'];
        $tld = $parts['tld'];

        // TLD skoru
        $tldScore = self::TLD_WEIGHTS[$tld] ?? 25;

        // Uzunluk skoru — kısa iyi
        $len = mb_strlen($sld);
        $lengthScore = match (true) {
            $len <= 3  => 100,
            $len <= 5  => 90,
            $len <= 7  => 75,
            $len <= 10 => 55,
            $len <= 15 => 35,
            default    => 15,
        };

        // Marka gücü
        $brandScore = self::brandScore($sld);

        // Yaş
        $ageYears = null;
        $ageScore = 30;
        $created = $whois['created'] ?? null;
        if ($created && ($ts = strtotime((string) $created))) {
            $ageYears = (int) floor((time() - $ts) / 31536000);
            $ageScore = min(100, 20 + $ageYears * 6);
        }

        // SEO sinyalleri
        $seoScore = 30;
        if (!empty($dns['NS']))    $seoScore += 15;
        if (!empty($dns['MX']))    $seoScore += 15;
        if (!empty($dns['TXT']))   $seoScore += 10;
        if (!empty($ssl['active'])) $seoScore += 25;
        $seoScore = min(100, $seoScore);

        // Genel skor (ağırlıklı ortalama)
        $overall = (int) round(
            $tldScore    * 0.30 +
            $lengthScore * 0.20 +
            $brandScore  * 0.20 +
            $ageScore    * 0.15 +
            $seoScore    * 0.15
        );

        // Tahmini piyasa değeri (heuristik, USD)
        $basePrice = 50;
        $estUsd = (int) round(
            $basePrice
            * (1 + $tldScore / 100)
            * (1 + $lengthScore / 60)
            * (1 + $brandScore / 60)
            * (1 + $ageYears / 5)
        );
        if ($ageYears === null) $estUsd = (int) round($basePrice * (1 + $tldScore / 100) * (1 + $brandScore / 80));
        $estUsd = max(30, min(1_500_000, $estUsd));

        // Risk skorları
        $risks = [];
        if (str_contains($sld, '-')) $risks[] = 'Tire içeriyor — marka değeri düşer.';
        if (preg_match('/\d/', $sld)) $risks[] = 'Rakam içeriyor — telaffuz zorlaşır.';
        if ($len > 15) $risks[] = 'Uzun domain — akılda kalıcılık zayıf.';
        if (empty($dns['NS'])) $risks[] = 'DNS kayıtları yok — aktif kullanımda değil olabilir.';
        if (!empty($ssl) && empty($ssl['active'])) $risks[] = 'Aktif SSL yok.';

        // Ticari potansiyel (basit sınıflama)
        $commercial = match (true) {
            $overall >= 85 => 'Çok Yüksek',
            $overall >= 70 => 'Yüksek',
            $overall >= 55 => 'Orta',
            $overall >= 40 => 'Düşük',
            default        => 'Çok Düşük',
        };

        return [
            'domain'       => $domain,
            'sld'          => $sld,
            'tld'          => $tld,
            'length'       => $len,
            'age_years'    => $ageYears,
            'scores' => [
                'tld'     => $tldScore,
                'length'  => $lengthScore,
                'brand'   => $brandScore,
                'age'     => $ageScore,
                'seo'     => $seoScore,
                'overall' => $overall,
            ],
            'estimated_value_usd' => $estUsd,
            'estimated_value_try' => (int) round($estUsd * 32.5), // basit; gerçek runtime'da CurrencyService
            'commercial_potential'=> $commercial,
            'risks'               => $risks,
        ];
    }

    private static function brandScore(string $sld): int
    {
        $score = 50;
        // Sesli/sessiz denge
        $vowels = preg_match_all('/[aeiouıöü]/', $sld);
        $len = mb_strlen($sld);
        $balance = $len > 0 ? abs(($vowels / $len) - 0.4) : 1;
        $score -= (int) ($balance * 40);

        // Tire cezası
        if (str_contains($sld, '-')) $score -= 25;
        // Rakam cezası
        if (preg_match('/\d/', $sld)) $score -= 15;
        // Aynı harf tekrarı fazla ise
        if (preg_match('/(.)\1{2,}/', $sld)) $score -= 10;
        // Tek hece (kısa + akılda kalıcı)
        if ($len <= 5 && $vowels <= 2) $score += 25;

        return max(0, min(100, $score));
    }

    /** "example.com" -> ['sld'=>'example', 'tld'=>'com'] ; "example.com.tr" -> ['sld'=>'example', 'tld'=>'com.tr'] */
    private static function splitTld(string $domain): array
    {
        $domain = preg_replace('#^www\.#', '', $domain) ?? $domain;
        $parts = explode('.', $domain);
        if (count($parts) <= 1) return ['sld' => $parts[0] ?? $domain, 'tld' => ''];

        $doubleTlds = ['com.tr', 'net.tr', 'org.tr', 'gen.tr', 'biz.tr', 'info.tr', 'co.uk'];
        $tail2 = implode('.', array_slice($parts, -2));
        if (in_array($tail2, $doubleTlds, true)) {
            return ['sld' => $parts[count($parts) - 3] ?? '', 'tld' => $tail2];
        }
        return ['sld' => $parts[count($parts) - 2], 'tld' => end($parts)];
    }
}
