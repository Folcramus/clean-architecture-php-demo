<?php
declare(strict_types=1);

namespace App\Application\Order\Create;

final readonly class OrderItemInput
{
    public function __construct(
        public int $productId,
        public string $productName,
        public int $unitPriceMinor,
        public int $quantity,
    ) {}
}
