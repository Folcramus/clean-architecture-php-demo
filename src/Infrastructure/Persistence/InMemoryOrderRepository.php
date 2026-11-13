<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;

final class InMemoryOrderRepository implements OrderRepository
{
    /** @var array<int, Order> */
    private array $orders = [];
    private int $sequence = 1;

    public function nextId(): int
    {
        return $this->sequence++;
    }

    public function save(Order $order): void
    {
        $id = $order->id();
        if ($id === null) {
            $id = $this->nextId();
            $order->assignId($id);
        }
        $this->orders[$id] = clone $order;
    }

    public function findById(int $id): ?Order
    {
        return isset($this->orders[$id]) ? clone $this->orders[$id] : null;
    }
}
