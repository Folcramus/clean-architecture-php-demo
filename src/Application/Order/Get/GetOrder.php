<?php
declare(strict_types=1);

namespace App\Application\Order\Get;

use App\Domain\Order\Order;
use App\Domain\Order\OrderRepository;
use RuntimeException;

final class GetOrder
{
    public function __construct(private OrderRepository $orders) {}

    public function execute(int $id): OrderOutput
    {
        $order = $this->orders->findById($id);
        if ($order === null) {
            throw new RuntimeException('Order not found.');
        }
        return $this->map($order);
    }

    private function map(Order $order): OrderOutput
    {
        $items = [];
        foreach ($order->items() as $item) {
            $items[] = [
                'productId' => $item->productId(),
                'productName' => $item->productName(),
                'unitPriceMinor' => $item->unitPrice()->minor(),
                'quantity' => $item->quantity(),
                'subtotalMinor' => $item->subtotal()->minor(),
            ];
        }

        return new OrderOutput(
            $order->id() ?? 0,
            $order->customerId(),
            $order->status()->value,
            $order->total()->minor(),
            $order->total()->currency(),
            $order->createdAt()->format(DATE_ATOM),
            $order->paidAt()?->format(DATE_ATOM),
            $items,
        );
    }
}
