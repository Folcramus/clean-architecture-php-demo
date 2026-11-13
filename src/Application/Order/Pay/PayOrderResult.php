<?php
declare(strict_types=1);

namespace App\Application\Order\Pay;

final readonly class PayOrderResult
{
    public function __construct(
        public int $orderId,
        public string $status,
        public string $paymentReference,
    ) {}
}
