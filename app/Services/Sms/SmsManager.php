<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Services\Settings\SettingsManager;

/**
 * SMS gönderim yöneticisi — driver registry
 * Rapor Madde 6.1 (SMS ile giriş)
 *
 * Desteklenen driver'lar (iskelet):
 *   - netgsm (varsayılan, TR)
 *   - iletimerkezi
 *   - twilio
 *   - log (dev — sadece log dosyasına yazar)
 */
final class SmsManager
{
    /** @var array<string, class-string<SmsDriverInterface>> */
    private static array $drivers = [
        'netgsm'        => Drivers\NetGsmDriver::class,
        'iletimerkezi'  => Drivers\IletiMerkeziDriver::class,
        'twilio'        => Drivers\TwilioDriver::class,
        'log'           => Drivers\LogDriver::class,
    ];

    public static function send(string $phone, string $message): array
    {
        $driverKey = (string) SettingsManager::get('sms.driver', 'log');
        $cls = self::$drivers[$driverKey] ?? Drivers\LogDriver::class;
        /** @var SmsDriverInterface $driver */
        $driver = new $cls();
        return $driver->send($phone, $message);
    }

    public static function drivers(): array
    {
        return array_keys(self::$drivers);
    }
}
