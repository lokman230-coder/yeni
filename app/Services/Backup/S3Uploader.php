<?php

declare(strict_types=1);

namespace App\Services\Backup;

use App\Services\Settings\SettingsManager;

/**
 * S3-uyumlu depolara (AWS S3, Backblaze B2, Wasabi, MinIO, DO Spaces) backup upload.
 * SDK gerektirmez — AWS S3 REST API'sini curl ile kullanır.
 */
final class S3Uploader
{
    public static function uploadFile(string $localPath, ?string $remoteKey = null): array
    {
        if (!is_file($localPath)) {
            return ['ok' => false, 'error' => 'Dosya yok: ' . $localPath];
        }

        $bucket    = (string) SettingsManager::get('backup.s3_bucket', '');
        $region    = (string) SettingsManager::get('backup.s3_region', 'eu-central-1');
        $accessKey = (string) SettingsManager::get('backup.s3_access_key', '');
        $secretKey = (string) SettingsManager::get('backup.s3_secret_key', '');
        $endpoint  = (string) SettingsManager::get('backup.s3_endpoint', ''); // opsiyonel: b2, wasabi

        if ($bucket === '' || $accessKey === '' || $secretKey === '') {
            return ['ok' => false, 'error' => 'S3 ayarları eksik (Ayarlar > Backup).'];
        }

        $key = $remoteKey ?? ('backups/' . basename($localPath));
        $host = $endpoint !== '' ? $endpoint : "s3.$region.amazonaws.com";
        $url  = "https://$host/$bucket/$key";

        $content = file_get_contents($localPath);
        $contentSha256 = hash('sha256', $content);
        $date = gmdate('Ymd\THis\Z');
        $shortDate = substr($date, 0, 8);

        // AWS Signature V4 headers
        $headers = [
            'Host'                 => parse_url($url, PHP_URL_HOST),
            'X-Amz-Content-Sha256' => $contentSha256,
            'X-Amz-Date'           => $date,
            'Content-Length'       => (string) strlen($content),
            'Content-Type'         => 'application/octet-stream',
        ];

        // Canonical request
        ksort($headers);
        $canonHeaders = '';
        $signedHeaders = [];
        foreach ($headers as $k => $v) {
            $lk = strtolower($k);
            $canonHeaders .= "$lk:$v\n";
            $signedHeaders[] = $lk;
        }
        $signedHeadersStr = implode(';', $signedHeaders);
        $canonRequest = "PUT\n/$bucket/$key\n\n$canonHeaders\n$signedHeadersStr\n$contentSha256";

        // String to sign
        $scope = "$shortDate/$region/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n$date\n$scope\n" . hash('sha256', $canonRequest);

        // Signing key
        $kDate    = hash_hmac('sha256', $shortDate, 'AWS4' . $secretKey, true);
        $kRegion  = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authHeader = "AWS4-HMAC-SHA256 Credential=$accessKey/$scope,SignedHeaders=$signedHeadersStr,Signature=$signature";

        $curlHeaders = ["Authorization: $authHeader"];
        foreach ($headers as $k => $v) {
            $curlHeaders[] = "$k: $v";
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => $content,
            CURLOPT_HTTPHEADER     => $curlHeaders,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 300,
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 200 && $code < 300) {
            return ['ok' => true, 'url' => $url, 'size' => strlen($content)];
        }
        return ['ok' => false, 'error' => "S3 HTTP $code: " . substr((string)$response, 0, 500)];
    }

    /** Backup klasöründeki tüm dosyaları S3'e yolla + 30 günden eskileri sil */
    public static function syncBackupDir(): array
    {
        $dir = __DIR__ . '/../../../storage/backups';
        if (!is_dir($dir)) {
            return ['ok' => false, 'error' => 'Backup klasörü yok'];
        }
        $sent = 0; $failed = 0; $errors = [];
        foreach (glob($dir . '/*.tar.gz') as $file) {
            // 30 günden eskiyi lokal'den sil (S3'te GLACIER_IR'da kalır)
            if (filemtime($file) < time() - 30 * 86400) {
                @unlink($file);
                continue;
            }
            $r = self::uploadFile($file);
            if ($r['ok']) $sent++;
            else { $failed++; $errors[] = basename($file) . ': ' . $r['error']; }
        }
        return ['ok' => true, 'sent' => $sent, 'failed' => $failed, 'errors' => $errors];
    }
}
