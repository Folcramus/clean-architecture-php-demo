<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Order\Money;
use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use App\Domain\Order\OrderRepository;
use App\Domain\Order\OrderStatus;
use DateTimeImmutable;
use PDO;

final class PdoOrderRepository implements OrderRepository
{
    public function __construct(private PDO $pdo) {}

    public function nextId(): int
    {
        $value = $this->pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM orders')->fetchColumn();
        return (int) $value;
    }

    public function save(Order $order): void
    {
        $id = $order->id();
        if ($id === null) {
            $id = $this->nextId();
            $order->assignId($id);
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO orders (id, customer_id, status, created_at, paid_at)
                 VALUES (:id, :customer_id, :status, :created_at, :paid_at)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), paid_at = VALUES(paid_at)'
            );
            $stmt->execute([
                ':id' => $id,
                ':customer_id' => $order->customerId(),
                ':status' => $order->status()->value,
                ':created_at' => $order->createdAt()->format('Y-m-d H:i:s'),
                ':paid_at' => $order->paidAt()?->format('Y-m-d H:i:s'),
            ]);

            $delete = $this->pdo->prepare('DELETE FROM order_items WHERE order_id = :order_id');
            $delete->execute([':order_id' => $id]);

            $insert = $this->pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, unit_price_minor, currency, quantity)
                 VALUES (:order_id, :product_id, :product_name, :unit_price_minor, :currency, :quantity)'
            );

            foreach ($order->items() as $item) {
                $insert->execute([
                    ':order_id' => $id,
                    ':product_id' => $item->productId(),
                    ':product_name' => $item->productName(),
                    ':unit_price_minor' => $item->unitPrice()->minor(),
                    ':currency' => $item->unitPrice()->currency(),
                    ':quantity' => $item->quantity(),
                ]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function findById(int $id): ?Order
    {
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        $itemsStmt = $this->pdo->prepare('SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id');
        $itemsStmt->execute([':order_id' => $id]);

        $items = [];
        foreach ($itemsStmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $items[] = new OrderItem(
                (int) $item['product_id'],
                (string) $item['product_name'],
                new Money((int) $item['unit_price_minor'], (string) $item['currency']),
                (int) $item['quantity'],
            );
        }

        return Order::reconstitute(
            (int) $row['id'],
            (int) $row['customer_id'],
            OrderStatus::from((string) $row['status']),
            new DateTimeImmutable((string) $row['created_at']),
            $row['paid_at'] !== null ? new DateTimeImmutable((string) $row['paid_at']) : null,
            $items,
        );
    }
}
