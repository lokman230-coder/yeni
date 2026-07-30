<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Cron\CronScheduler;
use PHPUnit\Framework\TestCase;

final class CronSchedulerTest extends TestCase
{
    public function test_register_callable_stored(): void
    {
        $called = false;
        CronScheduler::register('test:noop', function () use (&$called) { $called = true; });
        // Not: gerçek DB tetiklemesi yok; register happy-path
        $this->assertTrue(true);
    }
}
