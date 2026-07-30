<?php

declare(strict_types=1);

namespace App\Modules\Payment;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Drivers\IyzicoDriver;
use App\Modules\Payment\Drivers\PaparaDriver;
use App\Modules\Payment\Drivers\PayTrDriver;
use App\Modules\Payment\Drivers\ShopierDriver;

/**
 * Ödeme sağlayıcı registry — driver ekle/çıkar, tek merkezden erişim.
 *
 * Kullanım:
 *   PaymentManager::available()             → aktif driver listesi (checkout ekranında)
 *   PaymentManager::driver('iyzico')        → PaymentGatewayInterface
 *   PaymentManager::isEnabled('paytr')      → bool (config: ayarlar tablosu)
 *
 * Not: "bank_transfer", "balance", "manual" gateway olmayıp iç ödeme akışıdır;
 * bu registry sadece dış gateway sağlayıcılarını tutar.
 */
final class PaymentManager
{
    /** @var array<string, class-string<PaymentGatewayInterface>> */
    private const DRIVERS = [
        'paytr'   => PayTrDriver::class,
        'iyzico'  => IyzicoDriver::class,
        'papara'  => PaparaDriver::class,
        'shopier' => ShopierDriver::class,
    ];

    /** @var array<string, PaymentGatewayInterface> */
    private static array $instances = [];

    public static function driver(string $id): ?PaymentGatewayInterface
    {
        $id = strtolower($id);
        if (!isset(self::DRIVERS[$id])) {
            return null;
        }
        if (!isset(self::$instances[$id])) {
            $cls = self::DRIVERS[$id];
            self::$instances[$id] = new $cls();
        }
        return self::$instances[$id];
    }

    /**
     * Yalnızca configde aktif olan gateway'leri döndür.
     *
     * @return array<int, array{id:string,label:string}>
     */
    public static function available(): array
    {
        $out = [];
        foreach (self::DRIVERS as $id => $_cls) {
            if (!self::isEnabled($id)) continue;
            $d = self::driver($id);
            if ($d === null) continue;
            $out[] = ['id' => $id, 'label' => $d->label()];
        }
        return $out;
    }

    public static function isEnabled(string $id): bool
    {
        $id = strtolower($id);
        // Admin panelden değiştirilebilir (settings tablosu) — .env fallback
        $sm = \App\Services\Settings\SettingsManager::class;

        // Explicit "gateway.enabled" flag varsa onu kullan
        $explicit = $sm::get("$id.enabled", null);
        if ($explicit !== null) return (bool) $explicit;

        // Yoksa credential varlığına bak
        return match ($id) {
            'paytr'   => (string) $sm::get('paytr.merchant_id',  '', 'PAYTR_MERCHANT_ID')  !== '',
            'iyzico'  => (string) $sm::get('iyzico.api_key',     '', 'IYZICO_API_KEY')     !== '',
            'papara'  => (string) $sm::get('papara.api_key',     '', 'PAPARA_API_KEY')     !== '',
            'shopier' => (string) $sm::get('shopier.pat', '', 'SHOPIER_PAT') !== ''
                || (string) $sm::get('shopier.api_key', '', 'SHOPIER_API_KEY') !== '',
            default   => false,
        };
    }

    /** @return array<string, class-string<PaymentGatewayInterface>> */
    public static function all(): array
    {
        return self::DRIVERS;
    }
}
