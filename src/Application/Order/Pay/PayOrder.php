<?php
declare(strict_types=1);

namespace App\Application\Order\Pay;

use App\Application\Contracts\PaymentGateway;
use App\Domain\Order\OrderRepository;
use RuntimeException;

final class PayOrder
{
    public function __construct(
        private OrderRepository $orders,
        private PaymentGateway $payments,
    ) {}

    public function execute(int $orderId): PayOrderResult
    {
        $order = $this->orders->findById($orderId);
        if ($order === null) {
            throw new RuntimeException('Order not found.');
        }

        $reference = $this->payments->charge($orderId, $order->total());
        $order->markPaid();
        $this->orders->save($order);

        return new PayOrderResult($orderId, $order->status()->value, $reference);
    }
}
