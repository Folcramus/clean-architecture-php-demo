<?php
declare(strict_types=1);

namespace App\Domain\Order;

enum OrderStatus: string
{
    case New = 'new';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
}
