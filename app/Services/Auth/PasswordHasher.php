<?php

declare(strict_types=1);

namespace App\Services\Auth;

final class PasswordHasher
{
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verify(string $password, string $hash): bool
    {
        $hash = trim($hash);
        if ($hash === '') {
            return false;
        }

        if (password_verify($password, $hash)) {
            return true;
        }

        if (str_starts_with($hash, '$P$') || str_starts_with($hash, '$H$')) {
            return hash_equals($hash, self::phpassHash($password, $hash));
        }

        if (preg_match('/^[a-f0-9]{32}:[^:]+$/i', $hash)) {
            [$md5, $salt] = explode(':', $hash, 2);
            return hash_equals(strtolower($md5), md5($salt . $password));
        }

        if (preg_match('/^[a-f0-9]{32}$/i', $hash)) {
            return hash_equals(strtolower($hash), md5($password));
        }

        return false;
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    private static function phpassHash(string $password, string $setting): string
    {
        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $output = '*0';
        if (substr($setting, 0, 2) === $output) {
            $output = '*1';
        }
        if (strlen($setting) < 12 || ($setting[0] !== '$' || ($setting[1] !== 'P' && $setting[1] !== 'H') || $setting[2] !== '$')) {
            return $output;
        }

        $countLog2 = strpos($itoa64, $setting[3]);
        if ($countLog2 === false || $countLog2 < 7 || $countLog2 > 30) {
            return $output;
        }

        $salt = substr($setting, 4, 8);
        if (strlen($salt) !== 8) {
            return $output;
        }

        $count = 1 << $countLog2;
        $hash = md5($salt . $password, true);
        do {
            $hash = md5($hash . $password, true);
        } while (--$count);

        return substr($setting, 0, 12) . self::phpassEncode64($hash, 16);
    }

    private static function phpassEncode64(string $input, int $count): string
    {
        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $output = '';
        $i = 0;
        do {
            $value = ord($input[$i++]);
            $output .= $itoa64[$value & 0x3f];
            if ($i < $count) {
                $value |= ord($input[$i]) << 8;
            }
            $output .= $itoa64[($value >> 6) & 0x3f];
            if ($i++ >= $count) {
                break;
            }
            if ($i < $count) {
                $value |= ord($input[$i]) << 16;
            }
            $output .= $itoa64[($value >> 12) & 0x3f];
            if ($i++ >= $count) {
                break;
            }
            $output .= $itoa64[($value >> 18) & 0x3f];
        } while ($i < $count);

        return $output;
    }
}
