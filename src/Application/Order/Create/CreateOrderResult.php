<?php
declare(strict_types=1);

namespace App\Application\Order\Create;

final readonly class CreateOrderResult
{
    public function __construct(
        public int $orderId,
        public string $status,
        public int $totalMinor,
        public string $currency,
    ) {}
}
