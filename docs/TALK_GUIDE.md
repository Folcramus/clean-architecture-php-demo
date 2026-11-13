# Практическая демонстрация к рассказу о Clean Architecture

Этот файл можно открыть во время доклада и показывать код вместе со слайдами.

## 1–3. От монолитного Controller к разделению ответственности

Начните с антипримера:

```php
public function createOrder(Request $request)
{
    // validate HTTP
    // SQL INSERT
    // discount rules
    // bank request
    // email
}
```

Объясните, что один метод знает HTTP, SQL, бизнес и интеграции. Затем покажите реальную структуру `src/`.

## 4. Четыре слоя

Соотнесите слои со структурой проекта:

- Entities → `src/Domain`;
- Use Cases → `src/Application`;
- Interface Adapters → `src/Http` и интерфейсы портов;
- Frameworks & Drivers → `public`, `Infrastructure`, JSON/PDO/банк.

## 5. Entity и бизнес-инварианты

Откройте `src/Domain/Order/Order.php`.

Покажите:

```php
$order->markPaid();
$order->cancel();
```

Главный пример: оплаченный заказ нельзя отменить. Это правило находится внутри `Order`, а не в Controller и не в SQL.

Практический вопрос аудитории: что произойдёт, если вызвать `cancel()` после `markPaid()`?

## 6. Use Case

Откройте `CreateOrder.php`.

Use Case:

1. получает `CreateOrderCommand`;
2. создаёт Entity;
3. добавляет позиции;
4. получает ID через Repository;
5. сохраняет Order;
6. возвращает Result DTO.

Покажите, что там нет `Request`, `PDO`, `$_POST` и JSON.

## 7. Interface Adapter

Откройте `OrderController.php`.

Controller переводит внешний массив JSON в:

```php
CreateOrderCommand
OrderItemInput
```

То есть Controller знает HTTP-формат, а Use Case — нет.

## 8. Frameworks & Drivers

Покажите `public/index.php`.

Именно здесь существуют:

- `$_SERVER`;
- `php://input`;
- HTTP status;
- маршруты.

Domain об этих вещах ничего не знает.

## 9. Dependency Rule

Пройдитесь по `use` в `Order.php` и `CreateOrder.php`.

В `Order` нет импортов из `Infrastructure` или `Http`.

В `CreateOrder` используется:

```php
use App\Domain\Order\OrderRepository;
```

а не `JsonOrderRepository` и не `PdoOrderRepository`.

## 10. HTTP-запрос целиком

Запустите:

```bash
php -S 127.0.0.1:8080 -t public
```

Затем создайте заказ через curl.

Покажите путь:

```text
HTTP → OrderController → CreateOrder → Order → OrderRepository → JSON
```

После этого выполните ту же бизнес-операцию из CLI:

```bash
php bin/console.php create-order 42
```

Это лучший наглядный пример того, что Use Case не зависит от способа входа.

## 11–12. Repository и Dependency Inversion

Откройте:

- `OrderRepository.php`;
- `JsonOrderRepository.php`;
- `PdoOrderRepository.php`;
- `InMemoryOrderRepository.php`.

Один интерфейс имеет три разные реализации.

Application не меняется при замене хранилища.

## 13. Dependency Injection

Откройте `bootstrap/app.php`.

Здесь выполняется сборка графа объектов.

Замените мысленно:

```php
new JsonOrderRepository(...)
```

на:

```php
new PdoOrderRepository($pdo)
```

Use Cases не требуется редактировать.

## 14. Структура проекта

Покажите дерево каталогов из README.

Подчеркните: названия папок вторичны. Главное — направление зависимостей.

## 15. Полный CreateOrder

Выполните:

```bash
php bin/console.php create-order 5
php bin/console.php get-order 1
```

Затем объясните результат DTO и сумму в minor units — копейках.

## 16. DTO и границы

Покажите `CreateOrderCommand`, `OrderItemInput`, `OrderOutput`.

Почему нельзя передавать `Request` в Domain: иначе внутренний код начинает зависеть от web-фреймворка.

## 17. Тестирование

Запустите:

```bash
php tests/run.php
```

В тестах используется `InMemoryOrderRepository`.

Никакой базы данных не требуется.

Обсудите пирамиду:

- Domain tests — быстрые;
- Use Case tests — быстрые с fake/in-memory;
- Integration tests — проверяют реальные адаптеры.

## 18. MVC + Clean

Объясните:

```text
MVC Controller → Application Use Case → Domain
```

MVC отвечает за механизм доставки запроса. Clean Architecture — за внутренние границы и зависимости.

## 19. Типичные ошибки

Попросите аудиторию найти нарушения:

1. `Order` импортирует `Request`;
2. `CreateOrder` создаёт `new PDO`;
3. Controller содержит правило скидки;
4. Repository interface помещён только в Infrastructure и Application зависит от конкретного PDO класса;
5. для каждого класса создан бессмысленный Interface без архитектурной границы.

## 20. Когда применять

Используйте этот проект как пример случая, где Clean Architecture оправдана учебно: несколько точек входа, хранилища и внешний Payment Gateway.

Для простого `hello.php` такая структура была бы избыточна.

## 21. Финальная демонстрация

Выполните полный сценарий:

```bash
rm -f var/orders.json
php bin/console.php create-order 42
php bin/console.php get-order 1
php bin/console.php pay-order 1
php bin/console.php get-order 1
php bin/console.php cancel-order 1
```

Последняя команда должна завершиться ошибкой: оплаченный заказ нельзя отменить.

Это одновременно демонстрирует:

- Entity защищает бизнес-инвариант;
- Use Case управляет сценарием;
- Repository абстрагирует хранилище;
- Gateway абстрагирует внешний банк;
- CLI является лишь способом доставки команды;
- инфраструктуру можно менять независимо от бизнес-логики.
