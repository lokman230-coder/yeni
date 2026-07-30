<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Database\Connection;
use App\Core\SessionManager;

/**
 * Kimlik doğrulama servisi — admin + customer.
 */
final class AuthService
{
    /**
     * Admin login denemesi.
     *
     * 2FA açıksa: sadece "pending_2fa" state kurulur, admin_id set edilmez.
     *   → controller 2fa ekranına yönlendirmelidir.
     *   → completeTwoFactor() başarılı olduğunda gerçek admin_id set olur.
     *
     * @return string  'ok' → tam login, '2fa' → 2FA gerekli, 'fail' → başarısız
     */
    public static function attemptAdmin(string $email, string $password): string
    {
        $row = Connection::selectOne(
            "SELECT a.*, r.slug AS role_slug FROM admins a
             LEFT JOIN admin_roles r ON r.id = a.role_id
             WHERE a.email = ? AND a.is_active = 1 LIMIT 1",
            [$email]
        );

        if ($row === null || !PasswordHasher::verify($password, $row['password_hash'])) {
            return 'fail';
        }

        // 2FA açık mı?
        if (TwoFactorService::isEnabled('admin', (int) $row['id'])) {
            SessionManager::regenerate();
            SessionManager::set('pending_2fa_admin_id', (int) $row['id']);
            SessionManager::set('pending_2fa_admin_row', $row);
            return '2fa';
        }

        self::completeAdminLogin($row);
        return 'ok';
    }

    /** 2FA doğrulaması başarılı olunca çağrılır. */
    public static function completeTwoFactorAdmin(int $adminId): bool
    {
        $row = Connection::selectOne(
            "SELECT a.*, r.slug AS role_slug FROM admins a
             LEFT JOIN admin_roles r ON r.id = a.role_id
             WHERE a.id = ? AND a.is_active = 1 LIMIT 1",
            [$adminId]
        );
        if (!$row) return false;
        self::completeAdminLogin($row);
        SessionManager::remove('pending_2fa_admin_id');
        SessionManager::remove('pending_2fa_admin_row');
        return true;
    }

    private static function completeAdminLogin(array $row): void
    {
        SessionManager::regenerate();
        SessionManager::set('admin_id', (int) $row['id']);
        SessionManager::set('admin_email', $row['email']);
        SessionManager::set('admin_name', $row['full_name'] ?? trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
        SessionManager::set('admin_role', $row['role_slug'] ?? 'admin');

        Connection::update('admins', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ], 'id = ?', [$row['id']]);
    }

    /**
     * Customer login denemesi.
     *
     * @return string  'ok' | '2fa' | 'fail'
     */
    public static function attemptCustomer(string $email, string $password): string
    {
        $row = Connection::selectOne(
            "SELECT * FROM customers WHERE email = ? AND status = 'active' LIMIT 1",
            [$email]
        );

        if ($row === null || !PasswordHasher::verify($password, $row['password_hash'])) {
            return 'fail';
        }

        if (TwoFactorService::isEnabled('customer', (int) $row['id'])) {
            SessionManager::regenerate();
            SessionManager::set('pending_2fa_customer_id', (int) $row['id']);
            return '2fa';
        }

        self::completeCustomerLogin($row);
        return 'ok';
    }

    public static function completeTwoFactorCustomer(int $customerId): bool
    {
        $row = Connection::selectOne("SELECT * FROM customers WHERE id = ? AND status='active' LIMIT 1", [$customerId]);
        if (!$row) return false;
        self::completeCustomerLogin($row);
        SessionManager::remove('pending_2fa_customer_id');
        return true;
    }

    /**
     * SMS/OTP ile giriş — telefon üzerinden müşteriyi bulur, session'a alır.
     * @return array{ok:bool, customer_id?:int, error?:string}
     */
    public static function loginCustomerByPhone(string $phone): array
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (strlen($digits) < 10) {
            return ['ok' => false, 'error' => 'Geçersiz telefon numarası.'];
        }
        // Normalize varyantları
        $variants = array_unique([
            $digits,
            '9' . $digits,
            '90' . ltrim($digits, '0'),
            ltrim($digits, '9'),
            ltrim($digits, '90'),
            '0' . substr($digits, -10),
        ]);
        $ph = implode(',', array_fill(0, count($variants), '?'));
        $row = Connection::selectOne(
            "SELECT * FROM customers WHERE REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '(', ''), ')', ''), '-', '') IN ($ph) AND status = 'active' LIMIT 1",
            array_values($variants)
        );
        if (!$row) {
            return ['ok' => false, 'error' => 'Bu telefon numarasıyla kayıtlı müşteri bulunamadı.'];
        }
        self::completeCustomerLogin($row);
        return ['ok' => true, 'customer_id' => (int) $row['id']];
    }

    private static function completeCustomerLogin(array $row): void
    {
        SessionManager::regenerate();
        SessionManager::set('customer_id', (int) $row['id']);
        SessionManager::set('customer_email', $row['email']);
        SessionManager::set('customer_name', trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));

        Connection::update('customers', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ], 'id = ?', [$row['id']]);
    }

    /**
     * Yeni müşteri kaydı. Başarılıysa otomatik login + customer_id döner.
     *
     * @param array $data ['email','password','first_name','last_name','phone'?,'company'?]
     * @return array{ok:bool, customer_id?:int, error?:string, errors?:array<string,string>}
     */
    public static function registerCustomer(array $data): array
    {
        $errors = [];
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $passwordConfirm = (string) ($data['password_confirm'] ?? $password);
        $first = trim((string) ($data['first_name'] ?? ''));
        $last  = trim((string) ($data['last_name']  ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Geçerli bir e-posta girin.';
        }
        // Şifre karmaşıklık politikası
        $policyCheck = PasswordPolicy::validate($password);
        if (!$policyCheck['ok']) {
            $errors['password'] = implode(' ', $policyCheck['errors']);
        }
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Şifreler eşleşmiyor.';
        }
        if ($first === '') {
            $errors['first_name'] = 'Ad zorunlu.';
        }
        if ($last === '') {
            $errors['last_name'] = 'Soyad zorunlu.';
        }
        if (empty($data['kvkk'])) {
            $errors['kvkk'] = 'Kayıt için sözleşmeleri onaylamalısınız.';
        }

        if ($errors === []) {
            $exists = Connection::selectOne("SELECT id FROM customers WHERE email = ?", [$email]);
            if ($exists) {
                $errors['email'] = 'Bu e-posta ile daha önce kayıt oluşturulmuş. Giriş yapın.';
            }
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        try {
            $customerId = Connection::insert('customers', [
                'email'         => $email,
                'password_hash' => PasswordHasher::hash($password),
                'first_name'    => $first,
                'last_name'     => $last,
                'phone'         => trim((string) ($data['phone']   ?? '')) ?: null,
                'company'       => trim((string) ($data['company'] ?? '')) ?: null,
                'country'       => 'TR',
                'preferred_language' => 'tr',
                'preferred_currency' => 'TRY',
                'status'        => 'active',
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Kayıt oluşturulamadı: ' . $e->getMessage()];
        }

        // Otomatik login
        SessionManager::regenerate();
        SessionManager::set('customer_id', $customerId);
        SessionManager::set('customer_email', $email);
        SessionManager::set('customer_name', trim("$first $last"));

        // Referral programı: aktif kod varsa attach et
        if (class_exists(\App\Modules\Referral\Services\ReferralService::class)) {
            try {
                \App\Modules\Referral\Services\ReferralService::attachOnSignup($customerId);
            } catch (\Throwable) {}
        }

        // Hoş geldin e-postası (Mailer varsa)
        if (class_exists(\App\Services\Mail\Mailer::class)) {
            try {
                \App\Services\Mail\Mailer::send(
                    'welcome',
                    $email,
                    [
                        'first_name' => $first,
                        'site_name'  => (string) env('APP_NAME', 'Ahost Bilişim'),
                        'panel_url'  => rtrim((string) env('APP_URL', ''), '/') . '/panel',
                    ],
                    trim("$first $last") ?: null
                );
            } catch (\Throwable) {
                // Template yoksa sessiz geç
            }
        }

        // E-posta doğrulama linki gönder (Faz 6e)
        try {
            EmailVerificationService::sendVerification('customer', $customerId, $email);
        } catch (\Throwable) {}

        return ['ok' => true, 'customer_id' => $customerId];
    }

    public static function admin(): ?array
    {
        $id = SessionManager::get('admin_id');
        if (!$id) return null;
        return Connection::selectOne("SELECT * FROM admins WHERE id = ?", [$id]);
    }

    public static function customer(): ?array
    {
        $id = SessionManager::get('customer_id');
        if (!$id) return null;
        return Connection::selectOne("SELECT * FROM customers WHERE id = ?", [$id]);
    }

    public static function isAdmin(): bool
    {
        return SessionManager::has('admin_id');
    }

    public static function isCustomer(): bool
    {
        return SessionManager::has('customer_id');
    }

    public static function logoutAdmin(): void
    {
        SessionManager::forget('admin_id');
        SessionManager::forget('admin_email');
        SessionManager::forget('admin_name');
        SessionManager::forget('admin_role');
    }

    public static function logoutCustomer(): void
    {
        SessionManager::forget('customer_id');
        SessionManager::forget('customer_email');
        SessionManager::forget('customer_name');
    }
}
