<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Ai\Services\SectorDetector;
use PHPUnit\Framework\TestCase;

final class SectorDetectorTest extends TestCase
{
    /** @dataProvider sectorCases */
    public function test_detects_expected_sector(string $prompt, string $expected): void
    {
        $r = SectorDetector::detect($prompt);
        $this->assertSame($expected, $r['sector'], "Prompt: '$prompt' → beklenen $expected, gelen {$r['sector']}");
        $this->assertGreaterThan(0.0, $r['confidence']);
    }

    public static function sectorCases(): array
    {
        return [
            'dental'      => ['Ali Diş Kliniği için bir site yap', 'clinic'],
            'pizza'       => ['Napoli Pizza için restoran sitesi', 'restaurant'],
            'clothing'    => ['Kadın giyim satan bir e-ticaret sitesi', 'ecommerce'],
            'radio'       => ['FM99 radyo sitesi yap', 'radio'],
            'hosting'     => ['Kendi hosting firmam için web hosting sitesi', 'hosting'],
            'course'      => ['Online İngilizce dil kursu için akademi sitesi', 'education'],
            'portfolio'   => ['Freelance grafik tasarımcı için portfolyo', 'portfolio'],
            'barber'      => ['Berber Ahmet için kuaför sitesi', 'local'],
            'agency'      => ['Dijital pazarlama ajansı için kurumsal site', 'agency'],
            'landing'     => ['Sadece bir landing page istiyorum', 'landing'],
        ];
    }

    public function test_returns_landing_for_ambiguous_input(): void
    {
        $r = SectorDetector::detect('site istiyorum');
        $this->assertSame('landing', $r['sector']);
    }

    public function test_extracts_business_name_from_quoted_prompt(): void
    {
        $r = SectorDetector::detect('"Kahve Dünyası" için restoran sitesi');
        $this->assertSame('Kahve Dünyası', $r['app_name_guess']);
    }

    public function test_extracts_business_name_from_natural_pattern(): void
    {
        $r = SectorDetector::detect('Ali Diş Kliniği için modern bir site yap');
        $this->assertSame('Ali Diş Kliniği', $r['app_name_guess']);
    }

    public function test_returns_null_for_unnamed_prompt(): void
    {
        $r = SectorDetector::detect('diş hekimi sitesi lazım');
        $this->assertNull($r['app_name_guess']);
    }

    public function test_confidence_capped_at_one(): void
    {
        $r = SectorDetector::detect('diş hekimi diş kliniği dişçi doktor hastane poliklinik klinik');
        $this->assertLessThanOrEqual(1.0, $r['confidence']);
    }

    public function test_matched_keywords_returned(): void
    {
        $r = SectorDetector::detect('Napoli Pizza için pizza restoran');
        $this->assertContains('pizza', $r['matched_keywords']);
        $this->assertContains('restoran', $r['matched_keywords']);
    }
}
