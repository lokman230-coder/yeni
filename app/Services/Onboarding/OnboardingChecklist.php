<?php

declare(strict_types=1);

namespace App\Services\Onboarding;

use App\Core\Database\Connection;
use App\Services\Settings\SettingsManager;

/**
 * Yeni admin için kurulum sonrası "yapılacaklar" checklist'i.
 * Admin dashboard'da sağda gösterilir. Tüm maddeler tamamlanınca gizlenir.
 */
final class OnboardingChecklist
{
    /** @return array<int, array{key:string, label:string, done:bool, url:string, hint:string}> */
    public static function items(): array
    {
        return [
            [
                'key'   => 'company_info',
                'label' => 'Firma bilgilerini gir',
                'done'  => self::hasCompanyInfo(),
                'url'   => '/admin/ayarlar?group=company',
                'hint'  => 'Faturalarda ve müşteri bilgilendirmelerinde kullanılacak.',
            ],
            [
                'key'   => 'smtp_configured',
                'label' => 'SMTP ayarlarını yap',
                'done'  => self::hasSmtp(),
                'url'   => '/admin/ayarlar?group=mail',
                'hint'  => 'Kayıt maili, fatura, hoş geldin mesajı için gerekli.',
            ],
            [
                'key'   => 'payment_provider',
                'label' => 'En az bir ödeme sağlayıcı bağla',
                'done'  => self::hasPaymentProvider(),
                'url'   => '/admin/ayarlar?group=payment',
                'hint'  => 'PayTR, iyzico, Papara veya Shopier — biri yeterli.',
            ],
            [
                'key'   => 'first_product',
                'label' => 'İlk ürününü ekle',
                'done'  => self::hasProducts(),
                'url'   => '/admin/urun-merkezi/yeni',
                'hint'  => 'Hosting paketi, VPS, domain fiyatı veya özel hizmet.',
            ],
            [
                'key'   => 'hosting_server',
                'label' => 'Hosting sunucusu tanımla',
                'done'  => self::hasHostingServer(),
                'url'   => '/admin/hosting-sunucu/yeni',
                'hint'  => 'Sipariş sonrası otomatik hesap açılışı için gerekli. Yoksa manuel açarsın.',
            ],
            [
                'key'   => 'admin_2fa',
                'label' => 'Kendi hesabına 2FA kur',
                'done'  => self::hasAdmin2fa(),
                'url'   => '/admin/guvenlik',
                'hint'  => 'Admin hesap güvenliği için şiddetle önerilir.',
            ],
            [
                'key'   => 'currency_updated',
                'label' => 'Kur güncellemesini test et',
                'done'  => self::hasFreshRates(),
                'url'   => '/admin/kur-yonetimi',
                'hint'  => 'TCMB\'den anlık kur çekildiğini doğrula.',
            ],
        ];
    }

    public static function completedCount(): int
    {
        return count(array_filter(self::items(), fn($i) => $i['done']));
    }

    public static function totalCount(): int
    {
        return count(self::items());
    }

    public static function isFullyDone(): bool
    {
        return self::completedCount() === self::totalCount();
    }

    // --- Kontrol metodları ---

    private static function hasCompanyInfo(): bool
    {
        return SettingsManager::get('company.name', '') !== ''
            && SettingsManager::get('company.address', '') !== '';
    }

    private static function hasSmtp(): bool
    {
        return SettingsManager::get('mail.host', '', 'MAIL_HOST') !== ''
            && SettingsManager::get('mail.from_address', '', 'MAIL_FROM_ADDRESS') !== '';
    }

    private static function hasPaymentProvider(): bool
    {
        // Herhangi bir sağlayıcı credential girilmiş mi
        foreach (['paytr.merchant_id', 'iyzico.api_key', 'papara.api_key', 'shopier.pat', 'shopier.api_key'] as $k) {
            if (SettingsManager::get($k, '') !== '') return true;
        }
        return false;
    }

    private static function hasProducts(): bool
    {
        try {
            $r = Connection::selectOne("SELECT COUNT(*) c FROM products WHERE status = 'active'");
            return (int) ($r['c'] ?? 0) > 0;
        } catch (\Throwable) { return false; }
    }

    private static function hasHostingServer(): bool
    {
        try {
            $r = Connection::selectOne("SELECT COUNT(*) c FROM hosting_servers WHERE is_active = 1");
            return (int) ($r['c'] ?? 0) > 0;
        } catch (\Throwable) { return false; }
    }

    private static function hasAdmin2fa(): bool
    {
        try {
            $r = Connection::selectOne("SELECT COUNT(*) c FROM admins WHERE two_factor_enabled = 1 AND two_factor_confirmed_at IS NOT NULL");
            return (int) ($r['c'] ?? 0) > 0;
        } catch (\Throwable) { return false; }
    }

    private static function hasFreshRates(): bool
    {
        try {
            $r = Connection::selectOne("SELECT COUNT(*) c FROM currency_rates WHERE source = 'tcmb' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
            return (int) ($r['c'] ?? 0) > 0;
        } catch (\Throwable) { return false; }
    }
}
