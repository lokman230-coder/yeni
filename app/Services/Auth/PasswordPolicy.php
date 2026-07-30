<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Services\Settings\SettingsManager;

/**
 * Şifre karmaşıklık politikası.
 * Admin > Ayarlar > Güvenlik'ten yönetilir (min length ayarlanabilir).
 *
 * Kurallar (default):
 *   - Min 8 karakter (admin panelden 12+ önerilir)
 *   - En az 1 büyük harf
 *   - En az 1 küçük harf
 *   - En az 1 rakam
 *   - Common şifre listesinde olmamalı
 */
final class PasswordPolicy
{
    // En yaygın 20 şifre — bunlar reddedilir
    private const BLACKLIST = [
        'password', '12345678', '123456789', 'qwerty', 'abc123',
        'password123', '111111', '123123', 'admin', 'admin123',
        'welcome', 'welcome1', 'letmein', 'monkey', 'dragon',
        'sunshine', 'iloveyou', 'password1', 'qwerty123', 'sifre123',
    ];

    /**
     * @return array{ok:bool, errors:array<int,string>}
     */
    public static function validate(string $password): array
    {
        $errors = [];
        $minLength = (int) SettingsManager::get('security.password_min_length', 8);
        $requireStrong = (bool) SettingsManager::get('security.password_require_strong', true);

        if (mb_strlen($password) < $minLength) {
            $errors[] = "Şifre en az $minLength karakter olmalı.";
        }
        if (mb_strlen($password) > 128) {
            $errors[] = "Şifre en fazla 128 karakter olmalı.";
        }

        if ($requireStrong) {
            if (!preg_match('/[A-ZÇĞİÖŞÜ]/u', $password)) {
                $errors[] = "En az 1 büyük harf içermeli.";
            }
            if (!preg_match('/[a-zçğıöşü]/u', $password)) {
                $errors[] = "En az 1 küçük harf içermeli.";
            }
            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = "En az 1 rakam içermeli.";
            }
        }

        // Blacklist kontrolü
        $lower = mb_strtolower($password);
        foreach (self::BLACKLIST as $bad) {
            if ($lower === $bad || str_contains($lower, $bad)) {
                $errors[] = "Bu şifre çok yaygın kullanılıyor, daha güvenli bir şifre seçin.";
                break;
            }
        }

        return ['ok' => empty($errors), 'errors' => $errors];
    }

    /** Şifre gücü skoru 0-100 */
    public static function strength(string $password): int
    {
        $score = 0;
        $len = mb_strlen($password);
        if ($len >= 8)  $score += 20;
        if ($len >= 12) $score += 20;
        if ($len >= 16) $score += 10;
        if (preg_match('/[a-z]/', $password))          $score += 10;
        if (preg_match('/[A-Z]/', $password))          $score += 10;
        if (preg_match('/[0-9]/', $password))          $score += 10;
        if (preg_match('/[^a-zA-Z0-9]/', $password))   $score += 15;
        if (preg_match('/[^a-zA-Z0-9\s]/', $password)) $score += 5;
        return min(100, $score);
    }
}
