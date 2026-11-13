<?php
declare(strict_types=1);

namespace App\Application\Order\Create;

final readonly class CreateOrderCommand
{
    /** @param list<OrderItemInput> $items */
    public function __construct(
        public int $customerId,
        public array $items,
    ) {}
}
