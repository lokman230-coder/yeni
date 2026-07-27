<?php

declare(strict_types=1);

namespace App\Services\Sms\Drivers;

use App\Services\Settings\SettingsManager;
use App\Services\Sms\SmsDriverInterface;

/**
 * Twilio REST API driver
 * https://www.twilio.com/docs/sms/send-messages
 */
final class TwilioDriver implements SmsDriverInterface
{
    public function send(string $phone, string $message): array
    {
        $sid    = (string) SettingsManager::get('sms.twilio_sid', '');
        $token  = (string) SettingsManager::get('sms.twilio_token', '');
        $from   = (string) SettingsManager::get('sms.twilio_from', '');

        if ($sid === '' || $token === '' || $from === '') {
            return ['ok' => false, 'error' => 'Twilio ayarları eksik.'];
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
        $data = http_build_query([
            'To'   => '+' . ltrim($phone, '+'),
            'From' => $from,
            'Body' => $message,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_USERPWD        => "$sid:$token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 200 && $code < 300) {
            return ['ok' => true, 'response' => $response];
        }
        return ['ok' => false, 'error' => "Twilio HTTP $code", 'response' => $response];
    }
}
