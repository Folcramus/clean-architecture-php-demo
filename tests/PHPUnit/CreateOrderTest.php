<?php
declare(strict_types=1);

namespace Tests\PHPUnit;

use App\Application\Order\Create\CreateOrder;
use App\Application\Order\Create\CreateOrderCommand;
use App\Application\Order\Create\OrderItemInput;
use App\Infrastructure\Persistence\InMemoryOrderRepository;
use PHPUnit\Framework\TestCase;

final class CreateOrderTest extends TestCase
{
    public function testCreatesOrderWithoutRealDatabase(): void
    {
        $repository = new InMemoryOrderRepository();
        $useCase = new CreateOrder($repository);

        $result = $useCase->execute(new CreateOrderCommand(1, [
            new OrderItemInput(100, 'Clean Architecture Book', 249900, 1),
        ]));

        self::assertSame(249900, $result->totalMinor);
        self::assertNotNull($repository->findById($result->orderId));
    }
}
