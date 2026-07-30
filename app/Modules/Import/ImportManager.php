<?php

declare(strict_types=1);

namespace App\Modules\Import;

use App\Modules\Import\Contracts\ImportSourceInterface;
use App\Modules\Import\Drivers\BlestaDriver;
use App\Modules\Import\Drivers\WhmcsDriver;
use App\Modules\Import\Drivers\WisecpDriver;

/**
 * Driver registry — kolayca genişletilebilir.
 */
final class ImportManager
{
    /** @var array<string, class-string<ImportSourceInterface>> */
    private const DRIVERS = [
        'whmcs'  => WhmcsDriver::class,
        'wisecp' => WisecpDriver::class,
        'blesta' => BlestaDriver::class,
        // Gelecek: 'hostbill' => HostBillDriver::class,
    ];

    public static function driver(string $id): ?ImportSourceInterface
    {
        $cls = self::DRIVERS[strtolower($id)] ?? null;
        return $cls ? new $cls() : null;
    }

    /** @return array<int, array{id:string,label:string}> */
    public static function all(): array
    {
        $out = [];
        foreach (self::DRIVERS as $id => $cls) {
            $inst = new $cls();
            $out[] = ['id' => $id, 'label' => $inst->label()];
        }
        return $out;
    }
}
