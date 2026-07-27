<?php

declare(strict_types=1);

namespace App\Modules\Payment\Contracts;

interface PaymentGatewayInterface
{
    public function id(): string;
    public function label(): string;

    /**
     * Ödeme oturumu oluştur; iframe/redirect URL döndür.
     * @param array $order  ['id','order_number','total','currency', ...]
     * @return array{success:bool, redirect_url?:string, iframe_token?:string, error?:string}
     */
    public function createCheckout(array $order, array $customer): array;

    /**
     * Sağlayıcıdan gelen callback'i doğrula ve sonuç döndür.
     * @return array{success:bool, transaction_id?:string, message?:string}
     */
    public function handleCallback(array $payload): array;
}
