<?php
declare(strict_types=1);

namespace App\Application\Order\Cancel;

use App\Domain\Order\OrderRepository;
use RuntimeException;

final class CancelOrder
{
    public function __construct(private OrderRepository $orders) {}

    public function execute(int $orderId): string
    {
        $order = $this->orders->findById($orderId);
        if ($order === null) {
            throw new RuntimeException('Order not found.');
        }

        $order->cancel();
        $this->orders->save($order);
        return $order->status()->value;
    }
}
