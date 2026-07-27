<?php

declare(strict_types=1);

namespace App\Modules\Builder\Services;

use App\Core\Database\Connection;

/**
 * Mobile Builder Export — APK/AAB/kaynak kod build queue.
 *
 * Build gerçek sunucuda cron ile işlenir (`builder:build-mobile` komutu).
 * Bu servis job kaydeder + tetikleyici webhook (opsiyonel) çağırır.
 *
 * Fiyatlandırma admin > ayarlar > builder'dan:
 *   builder.mobile.apk_price
 *   builder.mobile.aab_price
 *   builder.mobile.source_price
 */
final class MobileExportService
{
    public static function queueBuild(int $projectId, int $customerId, string $exportType, ?int $invoiceId = null): array
    {
        $prices = [
            'mobile_apk'          => (float) \App\Services\Settings\SettingsManager::get('builder.mobile.apk_price', '299'),
            'mobile_aab'          => (float) \App\Services\Settings\SettingsManager::get('builder.mobile.aab_price', '499'),
            'flutter_source'      => (float) \App\Services\Settings\SettingsManager::get('builder.mobile.flutter_source_price', '999'),
            'react_native_source' => (float) \App\Services\Settings\SettingsManager::get('builder.mobile.rn_source_price', '999'),
            'android_source'      => (float) \App\Services\Settings\SettingsManager::get('builder.mobile.android_source_price', '1499'),
            'site_zip'            => 0.0,
        ];

        $price = $prices[$exportType] ?? 0.0;

        $jobId = Connection::insert('builder_export_jobs', [
            'project_id'  => $projectId,
            'customer_id' => $customerId,
            'invoice_id'  => $invoiceId,
            'export_type' => $exportType,
            'status'      => 'pending',
            'price'       => $price,
            'currency'    => 'TRY',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        // Build hook (opsiyonel): CI/CD sistemine tetikle
        $hookUrl = (string) \App\Services\Settings\SettingsManager::get('builder.mobile.build_webhook', '');
        if ($hookUrl !== '') {
            @self::triggerBuildHook($hookUrl, $jobId, $exportType);
        }

        return ['job_id' => $jobId, 'price' => $price, 'status' => 'pending'];
    }

    /** Basit build simülasyonu — gerçekte Flutter/Gradle çalıştırılır */
    public static function processJob(int $jobId): array
    {
        $job = Connection::selectOne("SELECT * FROM builder_export_jobs WHERE id = ?", [$jobId]);
        if (!$job) return ['ok' => false, 'error' => 'job not found'];
        if ($job['status'] !== 'pending') return ['ok' => false, 'error' => 'not pending'];

        Connection::update('builder_export_jobs',
            ['status' => 'building', 'started_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            'id = ?', [$jobId]
        );

        $exportDir = AHO_ROOT . '/storage/builder-exports';
        if (!is_dir($exportDir)) @mkdir($exportDir, 0775, true);

        $project = Connection::selectOne("SELECT * FROM builder_projects WHERE id = ?", [$job['project_id']]);
        $pages   = Connection::select("SELECT * FROM builder_pages WHERE project_id = ? ORDER BY sort_order", [$job['project_id']]);

        $fileName = 'export-' . $job['export_type'] . '-' . $jobId . '-' . date('YmdHis');
        $outputPath = null;

        try {
            switch ($job['export_type']) {
                case 'site_zip':
                    $outputPath = "$exportDir/$fileName.zip";
                    ExportService::toZip($project, $pages, $outputPath);
                    break;

                case 'flutter_source':
                    $outputPath = "$exportDir/$fileName.zip";
                    self::generateFlutterSource($project, $pages, $outputPath);
                    break;

                case 'react_native_source':
                    $outputPath = "$exportDir/$fileName.zip";
                    self::generateReactNativeSource($project, $pages, $outputPath);
                    break;

                case 'android_source':
                    $outputPath = "$exportDir/$fileName.zip";
                    self::generateAndroidSource($project, $pages, $outputPath);
                    break;

                case 'mobile_apk':
                case 'mobile_aab':
                    // Gerçek build ortamında Flutter/Gradle çalıştırılır. Şimdilik placeholder.
                    $outputPath = "$exportDir/$fileName." . ($job['export_type'] === 'mobile_apk' ? 'apk' : 'aab');
                    self::buildMobileBinary($project, $pages, $outputPath, $job['export_type']);
                    break;

                default:
                    throw new \RuntimeException('Bilinmeyen export tipi');
            }

            $downloadToken = bin2hex(random_bytes(16));
            $downloadUrl = "/panel/export/" . $jobId . "/indir/" . $downloadToken;

            Connection::update('builder_export_jobs', [
                'status'       => 'ready',
                'output_path'  => $outputPath,
                'output_url'   => $downloadUrl,
                'completed_at' => date('Y-m-d H:i:s'),
                'expires_at'   => date('Y-m-d H:i:s', time() + 30 * 86400),
                'updated_at'   => date('Y-m-d H:i:s'),
            ], 'id = ?', [$jobId]);

            return ['ok' => true, 'path' => $outputPath, 'url' => $downloadUrl];
        } catch (\Throwable $e) {
            Connection::update('builder_export_jobs', [
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ], 'id = ?', [$jobId]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────
    // Generator'lar (kaynak kod üretim — templated)
    // Gerçek üretim için CI/CD ile Flutter/Gradle build sunucusuna push edilmeli
    // ─────────────────────────────────────

    private static function generateFlutterSource(array $project, array $pages, string $outputPath): void
    {
        $zip = new \ZipArchive();
        $zip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $appName = $project['name'] ?? 'AhostApp';
        $packageId = 'com.ahost.' . preg_replace('/[^a-z0-9]/', '', strtolower($appName));

        // pubspec.yaml
        $zip->addFromString('pubspec.yaml', "name: " . strtolower(preg_replace('/[^a-z0-9]/', '', $appName)) . "
description: $appName - Ahost Builder ile üretildi.
version: 1.0.0+1
environment:
  sdk: '>=3.0.0'
dependencies:
  flutter: {sdk: flutter}
  http: ^1.1.0
  shared_preferences: ^2.2.0
");

        // main.dart — sayfaları render eden template
        $tree = json_decode(($pages[0]['tree_json'] ?? '{}'), true);
        $mainDart = self::flutterMainDart($appName, $tree);
        $zip->addFromString('lib/main.dart', $mainDart);

        // Android manifest
        $zip->addFromString('android/app/src/main/AndroidManifest.xml',
            "<?xml version='1.0' encoding='utf-8'?>
<manifest xmlns:android='http://schemas.android.com/apk/res/android' package='$packageId'>
    <application android:label='$appName' android:icon='@mipmap/ic_launcher'>
        <activity android:name='.MainActivity' android:exported='true'>
            <intent-filter>
                <action android:name='android.intent.action.MAIN'/>
                <category android:name='android.intent.category.LAUNCHER'/>
            </intent-filter>
        </activity>
    </application>
</manifest>");

        // README
        $zip->addFromString('README.md', "# $appName

Ahost Bilişim Site Builder tarafından üretilen Flutter kaynak kod.

## Kurulum
```bash
flutter pub get
flutter run
```

## APK Üretimi
```bash
flutter build apk --release
```

## AAB (Play Store) Üretimi
```bash
flutter build appbundle --release
```

## Lisans
Bu kod Ahost Bilişim'den satın alınmıştır. Lisans anahtarınız ile korunmaktadır.
");

        $zip->close();
    }

    private static function generateReactNativeSource(array $project, array $pages, string $outputPath): void
    {
        $zip = new \ZipArchive();
        $zip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $appName = $project['name'] ?? 'AhostApp';

        $zip->addFromString('package.json', json_encode([
            'name'         => strtolower(preg_replace('/[^a-z0-9]/', '', $appName)),
            'version'      => '1.0.0',
            'main'         => 'index.js',
            'dependencies' => [
                'react'        => '18.2.0',
                'react-native' => '0.73.0',
            ],
        ], JSON_PRETTY_PRINT));

        $zip->addFromString('App.js', "import React from 'react';
import {View, Text, ScrollView} from 'react-native';

export default function App() {
    return (
        <ScrollView>
            <View style={{padding: 20}}>
                <Text style={{fontSize: 24, fontWeight: 'bold'}}>$appName</Text>
                <Text>Ahost Bilişim Mobile Builder ile üretildi.</Text>
            </View>
        </ScrollView>
    );
}");

        $zip->addFromString('README.md', "# $appName (React Native)\n\n## Kurulum\n```\nnpm install\nnpx react-native run-android\n```");
        $zip->close();
    }

    private static function generateAndroidSource(array $project, array $pages, string $outputPath): void
    {
        // Native Android Kotlin projesi (placeholder)
        $zip = new \ZipArchive();
        $zip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('README.md', "# " . ($project['name'] ?? 'AhostApp') . " (Native Android)\n\nGradle 8.x + Kotlin + Jetpack Compose. Detaylar için docs klasörüne bakın.");
        $zip->addFromString('build.gradle.kts', "// Ahost Bilişim tarafından üretildi\nplugins {\n    id(\"com.android.application\")\n    id(\"org.jetbrains.kotlin.android\")\n}");
        $zip->close();
    }

    private static function buildMobileBinary(array $project, array $pages, string $outputPath, string $type): void
    {
        // Gerçek build: Flutter/Gradle çağrısı gerekir. Şimdilik placeholder tar oluştur.
        $note = "Ahost Bilişim - " . strtoupper($type) . " build\n\nProject: " . ($project['name'] ?? '?') . "\n\nGerçek build için build sunucusu (Flutter/Gradle) gerekli.\nAdmin > Ayarlar > Builder > Build Webhook URL'sini yapılandırın.";
        @file_put_contents($outputPath, $note);
    }

    private static function flutterMainDart(string $appName, ?array $tree): string
    {
        $blocks = $tree['blocks'] ?? [];
        $widgets = '';
        foreach ($blocks as $b) {
            $t = $b['type'] ?? 'text';
            $props = $b['props'] ?? [];
            $title = addslashes((string)($props['title'] ?? ''));
            $subtitle = addslashes((string)($props['subtitle'] ?? ''));
            switch ($t) {
                case 'hero':
                    $widgets .= "        Container(padding: EdgeInsets.all(40), color: Colors.blue, child: Column(children: [Text('$title', style: TextStyle(color: Colors.white, fontSize: 32)), Text('$subtitle', style: TextStyle(color: Colors.white70))])),\n";
                    break;
                case 'text':
                case 'about':
                    $content = addslashes((string)($props['content'] ?? $props['title'] ?? ''));
                    $widgets .= "        Padding(padding: EdgeInsets.all(16), child: Text('$content')),\n";
                    break;
                default:
                    $widgets .= "        Padding(padding: EdgeInsets.all(16), child: Text('$title')),\n";
            }
        }

        return "import 'package:flutter/material.dart';

void main() => runApp(MyApp());

class MyApp extends StatelessWidget {
    @override
    Widget build(BuildContext context) {
        return MaterialApp(
            title: '$appName',
            home: Scaffold(
                appBar: AppBar(title: Text('$appName')),
                body: SingleChildScrollView(child: Column(children: [
$widgets
                ])),
            ),
        );
    }
}
";
    }

    private static function triggerBuildHook(string $url, int $jobId, string $type): void
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['job_id' => $jobId, 'type' => $type]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 5,
        ]);
        @curl_exec($ch);
        curl_close($ch);
    }
}
