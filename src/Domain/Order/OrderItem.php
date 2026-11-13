<?php
declare(strict_types=1);

namespace App\Domain\Order;

use InvalidArgumentException;

final readonly class OrderItem
{
    public function __construct(
        private int $productId,
        private string $productName,
        private Money $unitPrice,
        private int $quantity,
    ) {
        if ($productId <= 0) {
            throw new InvalidArgumentException('Product id must be positive.');
        }
        if (trim($productName) === '') {
            throw new InvalidArgumentException('Product name is required.');
        }
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be positive.');
        }
    }

    public function productId(): int { return $this->productId; }
    public function productName(): string { return $this->productName; }
    public function unitPrice(): Money { return $this->unitPrice; }
    public function quantity(): int { return $this->quantity; }
    public function subtotal(): Money { return $this->unitPrice->multiply($this->quantity); }
}
