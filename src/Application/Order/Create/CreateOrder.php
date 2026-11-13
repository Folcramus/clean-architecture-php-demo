<?php
declare(strict_types=1);

namespace App\Application\Order\Create;

use App\Domain\Order\Money;
use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use App\Domain\Order\OrderRepository;
use InvalidArgumentException;

final class CreateOrder
{
    public function __construct(private OrderRepository $orders) {}

    public function execute(CreateOrderCommand $command): CreateOrderResult
    {
        if ($command->items === []) {
            throw new InvalidArgumentException('Order must contain at least one item.');
        }

        $order = Order::create($command->customerId);
        foreach ($command->items as $item) {
            $order->addItem(new OrderItem(
                $item->productId,
                $item->productName,
                new Money($item->unitPriceMinor, 'RUB'),
                $item->quantity,
            ));
        }

        $order->assignId($this->orders->nextId());
        $this->orders->save($order);

        $total = $order->total();
        return new CreateOrderResult(
            $order->id(),
            $order->status()->value,
            $total->minor(),
            $total->currency(),
        );
    }
}
