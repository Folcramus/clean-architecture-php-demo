<?php
declare(strict_types=1);

namespace App\Domain\Order;

interface OrderRepository
{
    public function nextId(): int;
    public function save(Order $order): void;
    public function findById(int $id): ?Order;
}
