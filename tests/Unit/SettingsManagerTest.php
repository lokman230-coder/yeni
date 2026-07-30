<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database\Connection;
use App\Services\Settings\SettingsManager;
use PHPUnit\Framework\TestCase;

final class SettingsManagerTest extends TestCase
{
    protected function setUp(): void
    {
        SettingsManager::reset();
        Connection::query("DELETE FROM settings WHERE `key` LIKE 'test.%'");
    }

    protected function tearDown(): void
    {
        Connection::query("DELETE FROM settings WHERE `key` LIKE 'test.%'");
        SettingsManager::reset();
    }

    public function test_set_and_get_string(): void
    {
        SettingsManager::set('test.name', 'Ali', 'string', 'test');
        SettingsManager::reset();
        $this->assertSame('Ali', SettingsManager::get('test.name'));
    }

    public function test_encrypted_value_stored_encrypted_and_decrypted_on_read(): void
    {
        SettingsManager::set('test.api_key', 'secret-123', 'encrypted', 'test');
        $raw = Connection::selectOne("SELECT value FROM settings WHERE `key` = ?", ['test.api_key']);
        $this->assertNotSame('secret-123', $raw['value'], 'Ham değer saklanmamalı');
        $this->assertGreaterThan(20, strlen((string)$raw['value']), 'Encrypted string uzun olmalı');

        SettingsManager::reset();
        $this->assertSame('secret-123', SettingsManager::get('test.api_key'));
    }

    public function test_env_fallback_when_not_in_settings(): void
    {
        putenv('TEST_MY_KEY=fromenv');
        $this->assertSame('fromenv', SettingsManager::get('test.non_existent', null, 'TEST_MY_KEY'));
    }

    public function test_settings_beats_env(): void
    {
        putenv('TEST_MY_KEY2=envval');
        SettingsManager::set('test.override', 'dbval', 'string', 'test');
        SettingsManager::reset();
        $this->assertSame('dbval', SettingsManager::get('test.override', null, 'TEST_MY_KEY2'));
    }

    public function test_bool_type_roundtrip(): void
    {
        SettingsManager::set('test.flag', '1', 'bool', 'test');
        SettingsManager::reset();
        $this->assertTrue(SettingsManager::get('test.flag'));
        SettingsManager::set('test.flag', '0', 'bool', 'test');
        SettingsManager::reset();
        $this->assertFalse(SettingsManager::get('test.flag'));
    }

    public function test_int_type_roundtrip(): void
    {
        SettingsManager::set('test.count', '42', 'int', 'test');
        SettingsManager::reset();
        $this->assertSame(42, SettingsManager::get('test.count'));
    }

    public function test_group_returns_masked_encrypted_values(): void
    {
        SettingsManager::set('test.pw',   'my-super-secret', 'encrypted', 'test');
        SettingsManager::set('test.name', 'Public Value',    'string',    'test');
        $rows = SettingsManager::group('test');
        $byKey = [];
        foreach ($rows as $r) $byKey[$r['key']] = $r;

        $this->assertTrue($byKey['test.pw']['is_secret']);
        $this->assertTrue($byKey['test.pw']['has_value']);
        $this->assertNotSame('my-super-secret', $byKey['test.pw']['value'], 'Encrypted UI\'da maskelenmeli');

        $this->assertFalse($byKey['test.name']['is_secret']);
        $this->assertSame('Public Value', $byKey['test.name']['value']);
    }

    public function test_forget_removes_value(): void
    {
        SettingsManager::set('test.temp', 'x', 'string', 'test');
        SettingsManager::forget('test.temp');
        SettingsManager::reset();
        $this->assertNull(SettingsManager::get('test.temp'));
    }
}
