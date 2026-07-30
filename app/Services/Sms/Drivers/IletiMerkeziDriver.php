<?php

declare(strict_types=1);

namespace App\Services\Sms\Drivers;

use App\Services\Settings\SettingsManager;
use App\Services\Sms\SmsDriverInterface;

/**
 * İletiMerkezi REST API driver
 * https://www.iletimerkezi.com/api/get/send-sms
 */
final class IletiMerkeziDriver implements SmsDriverInterface
{
    private const ENDPOINT = 'https://api.iletimerkezi.com/v1/send-sms/get/';

    public function send(string $phone, string $message): array
    {
        $user   = (string) SettingsManager::get('sms.iletimerkezi_user', '');
        $pass   = (string) SettingsManager::get('sms.iletimerkezi_password', '');
        $header = (string) SettingsManager::get('sms.iletimerkezi_header', '');

        if ($user === '' || $pass === '' || $header === '') {
            return ['ok' => false, 'error' => 'İletiMerkezi ayarları eksik.'];
        }

        $params = http_build_query([
            'username' => $user,
            'password' => $pass,
            'text'     => $message,
            'receipents' => $phone,
            'sender'   => $header,
        ]);

        $ch = curl_init(self::ENDPOINT . '?' . $params);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'error' => 'curl: ' . $err];
        }

        // İletiMerkezi XML döner — kısaca kontrol
        if (str_contains((string) $response, '<code>200</code>')) {
            return ['ok' => true, 'response' => $response];
        }
        return ['ok' => false, 'error' => 'İletiMerkezi hata', 'response' => $response];
    }
}
