<?php

declare(strict_types=1);

namespace App\Modules\EInvoice;

use App\Modules\EInvoice\Contracts\EInvoiceProviderInterface;
use App\Modules\EInvoice\Drivers\NoopDriver;
use App\Modules\EInvoice\Drivers\UyumsoftDriver;
use App\Services\Settings\SettingsManager;

/**
 * E-fatura sağlayıcı seçici — settings tablosundan credential okur.
 */
final class EInvoiceManager
{
    public static function driver(): EInvoiceProviderInterface
    {
        $provider = (string) SettingsManager::get('einvoice.provider', 'noop', 'EINVOICE_PROVIDER');
        return match ($provider) {
            'uyumsoft' => new UyumsoftDriver(
                (string) SettingsManager::get('einvoice.uyumsoft_username', '', 'UYUMSOFT_USERNAME'),
                (string) SettingsManager::get('einvoice.uyumsoft_password', '', 'UYUMSOFT_PASSWORD'),
                (bool)   SettingsManager::get('einvoice.uyumsoft_test_mode', true, 'UYUMSOFT_TEST_MODE'),
            ),
            default    => new NoopDriver(),
        };
    }
}
