<?php
declare(strict_types=1);

namespace App\Infrastructure\Payment;

use App\Application\Contracts\PaymentGateway;
use App\Domain\Order\Money;

final class FakePaymentGateway implements PaymentGateway
{
    public function charge(int $orderId, Money $amount): string
    {
        return sprintf('FAKE-%d-%d-%s', $orderId, $amount->minor(), $amount->currency());
    }
}
