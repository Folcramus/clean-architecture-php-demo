<?php
declare(strict_types=1);

use App\Application\Order\Create\CreateOrderCommand;
use App\Application\Order\Create\OrderItemInput;

$container = require dirname(__DIR__) . '/bootstrap/app.php';

$command = $argv[1] ?? 'help';

try {
    switch ($command) {
        case 'create-order':
            $customerId = (int) ($argv[2] ?? 1);
            $result = $container['createOrder']->execute(new CreateOrderCommand($customerId, [
                new OrderItemInput(101, 'Clean Architecture Book', 249900, 1),
                new OrderItemInput(202, 'PHP Course', 590000, 1),
            ]));
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
            break;

        case 'get-order':
            $id = (int) ($argv[2] ?? 0);
            echo json_encode($container['getOrder']->execute($id), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
            break;

        case 'pay-order':
            $id = (int) ($argv[2] ?? 0);
            echo json_encode($container['payOrder']->execute($id), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
            break;

        case 'cancel-order':
            $id = (int) ($argv[2] ?? 0);
            echo $container['cancelOrder']->execute($id) . PHP_EOL;
            break;

        default:
            echo "Commands:\n";
            echo "  php bin/console.php create-order [customerId]\n";
            echo "  php bin/console.php get-order <id>\n";
            echo "  php bin/console.php pay-order <id>\n";
            echo "  php bin/console.php cancel-order <id>\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
