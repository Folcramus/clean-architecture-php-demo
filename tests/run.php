<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/autoload.php';

use App\Application\Order\Cancel\CancelOrder;
use App\Application\Order\Create\CreateOrder;
use App\Application\Order\Create\CreateOrderCommand;
use App\Application\Order\Create\OrderItemInput;
use App\Application\Order\Get\GetOrder;
use App\Application\Order\Pay\PayOrder;
use App\Domain\Order\Money;
use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use App\Domain\Order\OrderStatus;
use App\Infrastructure\Payment\FakePaymentGateway;
use App\Infrastructure\Persistence\InMemoryOrderRepository;

final class TestFailure extends RuntimeException {}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new TestFailure($message);
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new TestFailure($message . sprintf(' Expected %s, got %s.', var_export($expected, true), var_export($actual, true)));
    }
}

function assertThrows(callable $fn, string $exceptionClass, string $message): void
{
    try {
        $fn();
    } catch (Throwable $e) {
        if ($e instanceof $exceptionClass) {
            return;
        }
        throw new TestFailure($message . ' Wrong exception: ' . $e::class);
    }
    throw new TestFailure($message . ' No exception thrown.');
}

$tests = [];

$tests['Money adds and multiplies'] = static function (): void {
    $money = new Money(10000, 'RUB');
    assertSameValue(30000, $money->multiply(3)->minor(), 'Money multiplication failed.');
    assertSameValue(15000, $money->add(new Money(5000, 'RUB'))->minor(), 'Money addition failed.');
};

$tests['Order protects domain invariants'] = static function (): void {
    $order = Order::create(1);
    $order->addItem(new OrderItem(10, 'Book', new Money(10000), 2));
    assertSameValue(20000, $order->total()->minor(), 'Order total is wrong.');
    $order->markPaid();
    assertSameValue(OrderStatus::Paid, $order->status(), 'Order should be paid.');
    assertThrows(static fn() => $order->cancel(), DomainException::class, 'Paid order must not be cancelled.');
};

$tests['CreateOrder use case has no database dependency'] = static function (): void {
    $repository = new InMemoryOrderRepository();
    $useCase = new CreateOrder($repository);

    $result = $useCase->execute(new CreateOrderCommand(42, [
        new OrderItemInput(1, 'Keyboard', 500000, 1),
        new OrderItemInput(2, 'Mouse', 250000, 2),
    ]));

    assertSameValue(1, $result->orderId, 'Unexpected order id.');
    assertSameValue(1000000, $result->totalMinor, 'Unexpected total.');
    assertTrue($repository->findById($result->orderId) !== null, 'Order was not saved.');
};

$tests['PayOrder uses PaymentGateway abstraction'] = static function (): void {
    $repository = new InMemoryOrderRepository();
    $create = new CreateOrder($repository);
    $created = $create->execute(new CreateOrderCommand(7, [
        new OrderItemInput(1, 'Course', 150000, 1),
    ]));

    $pay = new PayOrder($repository, new FakePaymentGateway());
    $result = $pay->execute($created->orderId);

    assertSameValue('paid', $result->status, 'Order should become paid.');
    assertTrue(str_starts_with($result->paymentReference, 'FAKE-'), 'Fake payment reference expected.');
};

$tests['CancelOrder and GetOrder work through application layer'] = static function (): void {
    $repository = new InMemoryOrderRepository();
    $create = new CreateOrder($repository);
    $created = $create->execute(new CreateOrderCommand(9, [
        new OrderItemInput(5, 'Notebook', 10000, 3),
    ]));

    $cancel = new CancelOrder($repository);
    assertSameValue('cancelled', $cancel->execute($created->orderId), 'Order should be cancelled.');

    $get = new GetOrder($repository);
    $output = $get->execute($created->orderId);
    assertSameValue('cancelled', $output->status, 'Read model should reflect cancellation.');
    assertSameValue(30000, $output->totalMinor, 'Read model total is wrong.');
};

$passed = 0;
foreach ($tests as $name => $test) {
    try {
        $test();
        $passed++;
        echo "[OK]   {$name}\n";
    } catch (Throwable $e) {
        echo "[FAIL] {$name}\n       {$e->getMessage()}\n";
    }
}

echo "\n{$passed}/" . count($tests) . " tests passed.\n";
exit($passed === count($tests) ? 0 : 1);
