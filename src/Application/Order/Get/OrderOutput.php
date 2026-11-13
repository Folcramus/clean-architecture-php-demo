<?php
declare(strict_types=1);

namespace App\Application\Order\Get;

final readonly class OrderOutput
{
    /** @param list<array<string, int|string>> $items */
    public function __construct(
        public int $id,
        public int $customerId,
        public string $status,
        public int $totalMinor,
        public string $currency,
        public string $createdAt,
        public ?string $paidAt,
        public array $items,
    ) {}
}
