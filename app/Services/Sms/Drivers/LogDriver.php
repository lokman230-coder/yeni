<?php

declare(strict_types=1);

namespace App\Services\Sms\Drivers;

use App\Services\Sms\SmsDriverInterface;

/** DEV / test driver — SMS yollamaz, sadece log yazar. */
final class LogDriver implements SmsDriverInterface
{
    public function send(string $phone, string $message): array
    {
        $logDir = __DIR__ . '/../../../../storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $line = '[' . date('Y-m-d H:i:s') . "] SMS to $phone: $message" . PHP_EOL;
        @file_put_contents($logDir . '/sms.log', $line, FILE_APPEND);
        return ['ok' => true, 'response' => 'logged'];
    }
}
