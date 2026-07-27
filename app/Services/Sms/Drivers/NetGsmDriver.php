<?php

declare(strict_types=1);

namespace App\Services\Sms\Drivers;

use App\Services\Settings\SettingsManager;
use App\Services\Sms\SmsDriverInterface;

/**
 * NetGSM XML API driver
 * https://www.netgsm.com.tr/dokuman/#h-http-get-xml-post
 */
final class NetGsmDriver implements SmsDriverInterface
{
    private const ENDPOINT = 'https://api.netgsm.com.tr/sms/send/xml';

    public function send(string $phone, string $message): array
    {
        $user   = (string) SettingsManager::get('sms.netgsm_user', '');
        $pass   = (string) SettingsManager::get('sms.netgsm_password', '');
        $header = (string) SettingsManager::get('sms.netgsm_header', '');

        if ($user === '' || $pass === '' || $header === '') {
            return ['ok' => false, 'error' => 'NetGSM ayarları eksik (Ayarlar > SMS).'];
        }

        $xml = '<?xml version="1.0"?><mainbody>'
             . '<header><company dil="TR">Netgsm</company>'
             . '<usercode>' . htmlspecialchars($user) . '</usercode>'
             . '<password>' . htmlspecialchars($pass) . '</password>'
             . '<type>1:n</type>'
             . '<msgheader>' . htmlspecialchars($header) . '</msgheader>'
             . '</header><body><msg><![CDATA[' . $message . ']]></msg>'
             . '<no>' . htmlspecialchars($phone) . '</no></body></mainbody>';

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $xml,
            CURLOPT_HTTPHEADER     => ['Content-Type: text/xml; charset=UTF-8'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'error' => 'curl: ' . $err];
        }

        // NetGSM: "00 jobid" veya "00" başarı, tek sayı hata
        $trim = trim((string) $response);
        if (str_starts_with($trim, '00')) {
            return ['ok' => true, 'response' => $trim];
        }
        return ['ok' => false, 'error' => 'NetGSM hata: ' . $trim, 'response' => $trim];
    }
}
