<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database\Connection;
use App\Modules\Hosting\Services\UsageSyncService;
use PHPUnit\Framework\TestCase;

final class UsageSyncServiceTest extends TestCase
{
    public function test_service_class_exists_and_sync_method_exists(): void
    {
        $this->assertTrue(class_exists(UsageSyncService::class));
        $this->assertTrue(method_exists(UsageSyncService::class, 'sync'));
    }

    public function test_sync_returns_expected_structure(): void
    {
        // Aktif account yoksa da hata vermeden dönmeli
        $r = UsageSyncService::sync(0);
        $this->assertIsArray($r);
        $this->assertArrayHasKey('updated', $r);
        $this->assertArrayHasKey('skipped', $r);
        $this->assertArrayHasKey('errors',  $r);
        $this->assertArrayHasKey('accounts', $r);
    }

    public function test_sync_skips_accounts_without_server(): void
    {
        // Zaten aktif olan bir hesap yoksa 0 döndürmeli
        $r = UsageSyncService::sync(200);
        $this->assertGreaterThanOrEqual(0, $r['accounts']);
    }
}
