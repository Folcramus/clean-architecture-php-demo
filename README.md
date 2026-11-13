# Clean Architecture in PHP — учебное приложение

Небольшое, но полноценное приложение оформления заказов, созданное специально для демонстрации **Clean Architecture** на PHP 8.2+.

Проект показывает не только структуру папок, а главное правило: **зависимости исходного кода направлены к бизнес-логике, а инфраструктура подключается снаружи**.

## Что умеет приложение

- создать заказ;
- получить заказ;
- оплатить заказ через абстракцию `PaymentGateway`;
- отменить неоплаченный заказ;
- хранить данные в JSON без дополнительных расширений;
- показывать альтернативные Repository: InMemory и PDO/MySQL;
- работать через HTTP и CLI с одними и теми же Use Cases;
- запускать тесты без Composer;
- при наличии Composer запускать PHPUnit.

## Архитектура

```text
HTTP / CLI
    ↓
Interface Adapters
    ↓
Application / Use Cases
    ↓
Domain
    ↑
Repository / Gateway interfaces
    ↑
Infrastructure: JSON, PDO, Fake bank
```

Направление runtime-вызовов может идти наружу, но исходные зависимости внутреннего кода не направлены на конкретную инфраструктуру.

## Структура проекта

```text
src/
  Domain/
    Order/
      Money.php
      Order.php
      OrderItem.php
      OrderRepository.php
      OrderStatus.php

  Application/
    Contracts/
      PaymentGateway.php
    Order/
      Create/
      Get/
      Pay/
      Cancel/

  Infrastructure/
    Persistence/
      InMemoryOrderRepository.php
      JsonOrderRepository.php
      PdoOrderRepository.php
    Payment/
      FakePaymentGateway.php

  Http/
    JsonResponse.php
    OrderController.php

bootstrap/
  autoload.php
  app.php

public/
  index.php

bin/
  console.php

tests/
  run.php
  PHPUnit/

database/
  schema.sql
```

## Быстрый запуск

Нужен PHP 8.2 или новее.

### 1. Тесты без зависимостей

```bash
php tests/run.php
```

### 2. CLI

Создать заказ:

```bash
php bin/console.php create-order 42
```

Получить заказ:

```bash
php bin/console.php get-order 1
```

Оплатить:

```bash
php bin/console.php pay-order 1
```

Отменить неоплаченный заказ:

```bash
php bin/console.php cancel-order 1
```

### 3. HTTP API

Запустить встроенный сервер PHP:

```bash
php -S 127.0.0.1:8080 -t public
```

Проверка:

```bash
curl http://127.0.0.1:8080/health
```

Создание заказа:

```bash
curl -X POST http://127.0.0.1:8080/orders \
  -H 'Content-Type: application/json' \
  -d '{
    "customerId": 42,
    "items": [
      {
        "productId": 101,
        "productName": "Clean Architecture Book",
        "unitPriceMinor": 249900,
        "quantity": 1
      },
      {
        "productId": 202,
        "productName": "PHP Course",
        "unitPriceMinor": 590000,
        "quantity": 1
      }
    ]
  }'
```

Получить заказ:

```bash
curl http://127.0.0.1:8080/orders/1
```

Оплатить:

```bash
curl -X POST http://127.0.0.1:8080/orders/1/pay
```

Отменить:

```bash
curl -X POST http://127.0.0.1:8080/orders/1/cancel
```

## Где здесь Clean Architecture

### Domain

`src/Domain` содержит бизнес-объекты и инварианты.

Пример: `Order::cancel()` сам запрещает отменять оплаченный заказ. Это правило не находится в HTTP Controller и не зависит от базы.

### Application / Use Cases

`CreateOrder`, `PayOrder`, `CancelOrder`, `GetOrder` описывают цели пользователя.

`CreateOrder` не знает, пришли данные из HTTP, CLI или очереди.

### Interface Adapters

`OrderController` превращает HTTP JSON в `CreateOrderCommand` и результат Use Case обратно в массив для ответа.

### Infrastructure

`JsonOrderRepository`, `PdoOrderRepository` и `FakePaymentGateway` — конкретные технические реализации.

### Dependency Rule

`CreateOrder` зависит от `OrderRepository`, но не от `JsonOrderRepository` или `PdoOrderRepository`.

`PayOrder` зависит от `PaymentGateway`, но не от конкретного банка.

## Repository и Dependency Inversion

Внутри Domain:

```php
interface OrderRepository
{
    public function nextId(): int;
    public function save(Order $order): void;
    public function findById(int $id): ?Order;
}
```

Снаружи:

```php
final class PdoOrderRepository implements OrderRepository
{
    // SQL и PDO находятся здесь.
}
```

Поэтому Application Layer работает с абстракцией.

## Dependency Injection

В `bootstrap/app.php` находится composition root:

```php
$repository = new JsonOrderRepository(...);
$paymentGateway = new FakePaymentGateway();

$createOrder = new CreateOrder($repository);
$payOrder = new PayOrder($repository, $paymentGateway);
```

Именно внешний слой решает, какие конкретные адаптеры использовать.

## PDO/MySQL

Для демонстрации реальной БД есть `PdoOrderRepository` и `database/schema.sql`.

Создайте таблицы из `database/schema.sql`, создайте `PDO` и замените `JsonOrderRepository` в `bootstrap/app.php` на `PdoOrderRepository`.

## PHPUnit

Если установлен Composer:

```bash
composer install
vendor/bin/phpunit
```

Но для занятия Composer не требуется: `php tests/run.php` запускает основные тесты на чистом PHP.

## Связь с презентацией

| Тема | Где смотреть |
|---|---|
| Entities | `src/Domain/Order/Order.php` |
| Use Cases | `src/Application/Order/*` |
| Interface Adapters | `src/Http/OrderController.php` |
| Frameworks & Drivers | `public/index.php`, `Infrastructure/*` |
| Dependency Rule | зависимости Application от интерфейсов |
| Repository | `OrderRepository` + реализации |
| Dependency Inversion | `OrderRepository`, `PaymentGateway` |
| Dependency Injection | `bootstrap/app.php` |
| DTO | `CreateOrderCommand`, `OrderOutput` |
| Testing | `tests/run.php`, `tests/PHPUnit` |
| MVC + Clean | HTTP Controller поверх Use Cases |

Подробный сценарий демонстрации для рассказа находится в [`docs/TALK_GUIDE.md`](docs/TALK_GUIDE.md).
