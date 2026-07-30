<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database\Connection;
use App\Modules\Ai\Services\SiteGenerator;
use PHPUnit\Framework\TestCase;

/**
 * SiteGenerator entegrasyon testi.
 * Gerçek DB'ye yazar → tearDown ile temizler.
 */
final class SiteGeneratorTest extends TestCase
{
    private array $createdProjectIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdProjectIds as $id) {
            try {
                Connection::delete('builder_pages',    'project_id = ?', [$id]);
                Connection::delete('builder_projects', 'id = ?',         [$id]);
            } catch (\Throwable) {}
        }
    }

    public function test_generates_clinic_site_from_dental_prompt(): void
    {
        $r = SiteGenerator::generate(1, 'Ali Diş Kliniği için modern bir diş hekimi sitesi', ['use_ai' => false]);
        $this->registerProject($r);

        $this->assertTrue($r['ok']);
        $this->assertSame('clinic', $r['sector']);
        $this->assertSame('Ali Diş Kliniği', $r['name']);
        $this->assertGreaterThan(0, $r['project_id']);
        $this->assertGreaterThan(0, $r['page_id']);
        $this->assertFalse($r['ai_used']); // heuristic
    }

    public function test_hero_block_contains_generated_title(): void
    {
        $r = SiteGenerator::generate(1, 'Napoli Pizza için restoran sitesi', ['use_ai' => false]);
        $this->registerProject($r);

        $this->assertTrue($r['ok']);
        $hero = null;
        foreach ($r['tree']['blocks'] as $b) {
            if (($b['type'] ?? '') === 'hero') { $hero = $b; break; }
        }
        $this->assertNotNull($hero, 'Hero blok bulunamadı');
        $this->assertStringContainsString('Napoli Pizza', (string) $hero['props']['title']);
    }

    public function test_features_block_has_sector_specific_items(): void
    {
        $r = SiteGenerator::generate(1, 'Kendi hosting firmam için hosting sitesi', ['use_ai' => false]);
        $this->registerProject($r);

        $this->assertTrue($r['ok']);
        $features = null;
        foreach ($r['tree']['blocks'] as $b) {
            if (($b['type'] ?? '') === 'features') { $features = $b; break; }
        }
        if ($features !== null) {
            $items = $features['props']['items'] ?? [];
            // Hosting sektörü SSD/SSL/Yedekleme gibi öğeler içermeli
            $joined = strtolower(implode(' ', $items));
            $this->assertMatchesRegularExpression('/ssl|ssd|yedek|destek|uptime/', $joined);
        } else {
            $this->markTestSkipped('Hosting sektörü şu an features bloğunu içermiyor');
        }
    }

    public function test_manual_sector_override(): void
    {
        $r = SiteGenerator::generate(1, 'sadece bir site istiyorum', ['sector' => 'agency', 'name' => 'Test Ajans', 'use_ai' => false]);
        $this->registerProject($r);

        $this->assertTrue($r['ok']);
        $this->assertSame('agency', $r['sector']);
        $this->assertSame('Test Ajans', $r['name']);
    }

    public function test_empty_prompt_returns_error(): void
    {
        $r = SiteGenerator::generate(1, '   ');
        $this->assertFalse($r['ok']);
        $this->assertArrayHasKey('error', $r);
    }

    public function test_project_persisted_in_database(): void
    {
        $r = SiteGenerator::generate(1, 'Freelancer portfolyo sitesi', ['use_ai' => false]);
        $this->registerProject($r);

        $this->assertTrue($r['ok']);
        $row = Connection::selectOne("SELECT * FROM builder_projects WHERE id = ?", [$r['project_id']]);
        $this->assertNotNull($row);
        $this->assertSame('portfolio', $row['sector']);
        $this->assertSame('site', $row['kind']);
    }

    private function registerProject(array $r): void
    {
        if (!empty($r['project_id'])) {
            $this->createdProjectIds[] = (int) $r['project_id'];
        }
    }
}
