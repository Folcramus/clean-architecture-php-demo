<?php
declare(strict_types=1);

namespace App\Http;

use App\Application\Order\Cancel\CancelOrder;
use App\Application\Order\Create\CreateOrder;
use App\Application\Order\Create\CreateOrderCommand;
use App\Application\Order\Create\OrderItemInput;
use App\Application\Order\Get\GetOrder;
use App\Application\Order\Pay\PayOrder;
use InvalidArgumentException;

final class OrderController
{
    public function __construct(
        private CreateOrder $createOrder,
        private GetOrder $getOrder,
        private PayOrder $payOrder,
        private CancelOrder $cancelOrder,
    ) {}

    public function create(array $payload): array
    {
        $items = [];
        foreach ($payload['items'] ?? [] as $item) {
            $items[] = new OrderItemInput(
                (int) ($item['productId'] ?? 0),
                (string) ($item['productName'] ?? ''),
                (int) ($item['unitPriceMinor'] ?? -1),
                (int) ($item['quantity'] ?? 0),
            );
        }

        $command = new CreateOrderCommand((int) ($payload['customerId'] ?? 0), $items);
        return (array) $this->createOrder->execute($command);
    }

    public function get(int $id): array
    {
        return (array) $this->getOrder->execute($id);
    }

    public function pay(int $id): array
    {
        return (array) $this->payOrder->execute($id);
    }

    public function cancel(int $id): array
    {
        return ['orderId' => $id, 'status' => $this->cancelOrder->execute($id)];
    }
}
