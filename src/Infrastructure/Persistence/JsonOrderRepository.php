<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Order\Money;
use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use App\Domain\Order\OrderRepository;
use App\Domain\Order\OrderStatus;
use DateTimeImmutable;
use RuntimeException;

final class JsonOrderRepository implements OrderRepository
{
    public function __construct(private readonly string $file)
    {
        $dir = dirname($file);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create storage directory.');
        }
    }

    public function nextId(): int
    {
        $data = $this->readAll();
        $ids = array_map('intval', array_keys($data));
        return $ids === [] ? 1 : max($ids) + 1;
    }

    public function save(Order $order): void
    {
        $id = $order->id();
        if ($id === null) {
            $id = $this->nextId();
            $order->assignId($id);
        }

        $data = $this->readAll();
        $data[$id] = $this->serialize($order);
        $this->writeAll($data);
    }

    public function findById(int $id): ?Order
    {
        $data = $this->readAll();
        if (!isset($data[$id])) {
            return null;
        }
        return $this->hydrate($data[$id]);
    }

    /** @return array<int|string, array<string, mixed>> */
    private function readAll(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $json = file_get_contents($this->file);
        if ($json === false || trim($json) === '') {
            return [];
        }
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return is_array($data) ? $data : [];
    }

    /** @param array<int|string, array<string, mixed>> $data */
    private function writeAll(array $data): void
    {
        $tmp = $this->file . '.tmp';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (file_put_contents($tmp, $json, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write storage.');
        }
        if (!rename($tmp, $this->file)) {
            throw new RuntimeException('Unable to replace storage.');
        }
    }

    /** @return array<string, mixed> */
    private function serialize(Order $order): array
    {
        $items = [];
        foreach ($order->items() as $item) {
            $items[] = [
                'productId' => $item->productId(),
                'productName' => $item->productName(),
                'unitPriceMinor' => $item->unitPrice()->minor(),
                'currency' => $item->unitPrice()->currency(),
                'quantity' => $item->quantity(),
            ];
        }

        return [
            'id' => $order->id(),
            'customerId' => $order->customerId(),
            'status' => $order->status()->value,
            'createdAt' => $order->createdAt()->format(DATE_ATOM),
            'paidAt' => $order->paidAt()?->format(DATE_ATOM),
            'items' => $items,
        ];
    }

    /** @param array<string, mixed> $data */
    private function hydrate(array $data): Order
    {
        $items = [];
        foreach ($data['items'] ?? [] as $item) {
            $items[] = new OrderItem(
                (int) $item['productId'],
                (string) $item['productName'],
                new Money((int) $item['unitPriceMinor'], (string) ($item['currency'] ?? 'RUB')),
                (int) $item['quantity'],
            );
        }

        return Order::reconstitute(
            (int) $data['id'],
            (int) $data['customerId'],
            OrderStatus::from((string) $data['status']),
            new DateTimeImmutable((string) $data['createdAt']),
            isset($data['paidAt']) && $data['paidAt'] !== null ? new DateTimeImmutable((string) $data['paidAt']) : null,
            $items,
        );
    }
}
