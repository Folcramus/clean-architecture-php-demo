<?php
declare(strict_types=1);

namespace App\Application\Contracts;

use App\Domain\Order\Money;

interface PaymentGateway
{
    public function charge(int $orderId, Money $amount): string;
}
