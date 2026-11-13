<?php
declare(strict_types=1);

use App\Http\JsonResponse;
use App\Http\OrderController;

$container = require dirname(__DIR__) . '/bootstrap/app.php';

$controller = new OrderController(
    $container['createOrder'],
    $container['getOrder'],
    $container['payOrder'],
    $container['cancelOrder'],
);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

try {
    if ($method === 'GET' && $path === '/health') {
        JsonResponse::send(['status' => 'ok']);
    }

    if ($method === 'POST' && $path === '/orders') {
        $raw = file_get_contents('php://input') ?: '{}';
        $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        JsonResponse::send($controller->create(is_array($payload) ? $payload : []), 201);
    }

    if (preg_match('#^/orders/(\d+)$#', $path, $m) === 1 && $method === 'GET') {
        JsonResponse::send($controller->get((int) $m[1]));
    }

    if (preg_match('#^/orders/(\d+)/pay$#', $path, $m) === 1 && $method === 'POST') {
        JsonResponse::send($controller->pay((int) $m[1]));
    }

    if (preg_match('#^/orders/(\d+)/cancel$#', $path, $m) === 1 && $method === 'POST') {
        JsonResponse::send($controller->cancel((int) $m[1]));
    }

    JsonResponse::error('Route not found.', 404);
} catch (\DomainException|\InvalidArgumentException $e) {
    JsonResponse::error($e->getMessage(), 422);
} catch (\RuntimeException $e) {
    JsonResponse::error($e->getMessage(), 404);
} catch (\JsonException $e) {
    JsonResponse::error('Invalid JSON.', 400);
} catch (\Throwable $e) {
    JsonResponse::error('Internal error: ' . $e->getMessage(), 500);
}
