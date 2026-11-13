<?php
declare(strict_types=1);

namespace App\Domain\Order;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

final class Order
{
    /** @var list<OrderItem> */
    private array $items = [];

    private function __construct(
        private ?int $id,
        private readonly int $customerId,
        private OrderStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $paidAt = null,
    ) {
        if ($customerId <= 0) {
            throw new InvalidArgumentException('Customer id must be positive.');
        }
    }

    public static function create(int $customerId): self
    {
        return new self(null, $customerId, OrderStatus::New, new DateTimeImmutable());
    }

    /** @param list<OrderItem> $items */
    public static function reconstitute(
        int $id,
        int $customerId,
        OrderStatus $status,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $paidAt,
        array $items,
    ): self {
        $order = new self($id, $customerId, $status, $createdAt, $paidAt);
        $order->items = $items;
        return $order;
    }

    public function addItem(OrderItem $item): void
    {
        if ($this->status !== OrderStatus::New) {
            throw new DomainException('Items can only be added to a new order.');
        }
        $this->items[] = $item;
    }

    public function markPaid(): void
    {
        if ($this->status === OrderStatus::Cancelled) {
            throw new DomainException('Cancelled order cannot be paid.');
        }
        if ($this->status === OrderStatus::Paid) {
            throw new DomainException('Order is already paid.');
        }
        if ($this->items === []) {
            throw new DomainException('Empty order cannot be paid.');
        }

        $this->status = OrderStatus::Paid;
        $this->paidAt = new DateTimeImmutable();
    }

    public function cancel(): void
    {
        if ($this->status === OrderStatus::Paid) {
            throw new DomainException('Paid order cannot be cancelled in this demo.');
        }
        if ($this->status === OrderStatus::Cancelled) {
            throw new DomainException('Order is already cancelled.');
        }
        $this->status = OrderStatus::Cancelled;
    }

    public function assignId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Order id must be positive.');
        }
        if ($this->id !== null && $this->id !== $id) {
            throw new DomainException('Order id is already assigned.');
        }
        $this->id = $id;
    }

    public function total(): Money
    {
        $total = new Money(0, 'RUB');
        foreach ($this->items as $item) {
            $total = $total->add($item->subtotal());
        }
        return $total;
    }

    public function id(): ?int { return $this->id; }
    public function customerId(): int { return $this->customerId; }
    public function status(): OrderStatus { return $this->status; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function paidAt(): ?DateTimeImmutable { return $this->paidAt; }
    /** @return list<OrderItem> */
    public function items(): array { return $this->items; }
}
