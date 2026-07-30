<?php

declare(strict_types=1);

namespace App\Services\Sms;

interface SmsDriverInterface
{
    /**
     * @return array{ok:bool, error?:string, response?:mixed}
     */
    public function send(string $phone, string $message): array;
}
