<?php
declare(strict_types=1);

use App\Application\Order\Cancel\CancelOrder;
use App\Application\Order\Create\CreateOrder;
use App\Application\Order\Get\GetOrder;
use App\Application\Order\Pay\PayOrder;
use App\Infrastructure\Payment\FakePaymentGateway;
use App\Infrastructure\Persistence\JsonOrderRepository;

require __DIR__ . '/autoload.php';

$repository = new JsonOrderRepository(dirname(__DIR__) . '/var/orders.json');
$paymentGateway = new FakePaymentGateway();

return [
    'repository' => $repository,
    'createOrder' => new CreateOrder($repository),
    'getOrder' => new GetOrder($repository),
    'payOrder' => new PayOrder($repository, $paymentGateway),
    'cancelOrder' => new CancelOrder($repository),
];
