# Sales Service - Serviço de Gestão de Vendas

## Visão Geral

O Sales Service é responsável por gerenciar todo o processo de vendas, desde a criação do pedido até sua conclusão.

## Bounded Context

**Domínio:** Gestão de Vendas e Pedidos

### Responsabilidades

- Criação e gerenciamento de pedidos
- Gestão de clientes
- Carrinho de compras
- Histórico de compras
- Status de pedidos
- Orquestração da saga de venda

### O que NÃO é responsabilidade

- Gestão de estoque físico
- Processamento de pagamentos
- Logística de entrega
- Emissão de notas fiscais

## Modelo de Domínio

### Entidades

#### Order (Aggregate Root)

```php
<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\OrderId;
use App\Domain\ValueObjects\CustomerId;
use App\Domain\ValueObjects\Money;

class Order extends BaseEntity
{
    private OrderId $orderId;
    private CustomerId $customerId;
    private string $status; // pending, confirmed, cancelled, completed
    private array $items;
    private Money $subtotal;
    private Money $discount;
    private Money $shipping;
    private Money $total;
    private ?string $paymentId;
    private ?string $shipmentId;

    public function __construct(
        OrderId $orderId,
        CustomerId $customerId,
        array $items
    ) {
        $this->orderId = $orderId;
        $this->customerId = $customerId;
        $this->status = 'pending';
        $this->items = $items;
        $this->calculateTotals();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addItem(OrderItem $item): void
    {
        $this->items[] = $item;
        $this->calculateTotals();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function confirm(): void
    {
        if ($this->status !== 'pending') {
            throw new \DomainException('Only pending orders can be confirmed');
        }

        $this->status = 'confirmed';
        $this->updatedAt = new DateTimeImmutable();
    }

    public function cancel(): void
    {
        if (!in_array($this->status, ['pending', 'confirmed'])) {
            throw new \DomainException('Cannot cancel order in current status');
        }

        $this->status = 'cancelled';
        $this->updatedAt = new DateTimeImmutable();
    }

    public function complete(): void
    {
        if ($this->status !== 'confirmed') {
            throw new \DomainException('Only confirmed orders can be completed');
        }

        $this->status = 'completed';
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setPaymentId(string $paymentId): void
    {
        $this->paymentId = $paymentId;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setShipmentId(string $shipmentId): void
    {
        $this->shipmentId = $shipmentId;
        $this->updatedAt = new DateTimeImmutable();
    }

    private function calculateTotals(): void
    {
        $subtotal = 0;
        foreach ($this->items as $item) {
            $subtotal += $item->getTotal()->getAmount();
        }

        $this->subtotal = new Money($subtotal);
        $this->discount = $this->discount ?? new Money(0);
        $this->shipping = $this->shipping ?? new Money(0);

        $total = $subtotal - $this->discount->getAmount() + $this->shipping->getAmount();
        $this->total = new Money($total);
    }

    // Getters...
}
```

#### OrderItem

```php
<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\ProductId;
use App\Domain\ValueObjects\Money;

class OrderItem extends BaseEntity
{
    private ProductId $productId;
    private string $productName;
    private int $quantity;
    private Money $unitPrice;
    private Money $total;

    public function __construct(
        ProductId $productId,
        string $productName,
        int $quantity,
        Money $unitPrice
    ) {
        $this->id = uniqid('item_', true);
        $this->productId = $productId;
        $this->productName = $productName;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->total = $unitPrice->multiply($quantity);
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function changeQuantity(int $newQuantity): void
    {
        if ($newQuantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }

        $this->quantity = $newQuantity;
        $this->total = $this->unitPrice->multiply($newQuantity);
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters...
}
```

#### Customer

```php
<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\CustomerId;
use App\Domain\ValueObjects\Email;

class Customer extends BaseEntity
{
    private CustomerId $customerId;
    private string $name;
    private Email $email;
    private ?string $phone;
    private ?string $document; // CPF/CNPJ
    private array $addresses;
    private bool $isActive;

    public function __construct(
        CustomerId $customerId,
        string $name,
        Email $email,
        ?string $phone = null,
        ?string $document = null
    ) {
        $this->customerId = $customerId;
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
        $this->document = $document;
        $this->addresses = [];
        $this->isActive = true;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addAddress(Address $address): void
    {
        $this->addresses[] = $address;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters...
}
```

#### Address

```php
<?php

namespace App\Domain\Entities;

class Address extends BaseEntity
{
    private string $street;
    private string $number;
    private ?string $complement;
    private string $neighborhood;
    private string $city;
    private string $state;
    private string $zipCode;
    private string $country;
    private bool $isDefault;

    // Constructor and getters...
}
```

### Domain Events

```php
<?php

namespace App\Domain\Events;

class OrderCreatedEvent extends DomainEvent
{
    public function __construct(
        private string $orderId,
        private string $customerId,
        private array $items,
        private float $total
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'sales.order.created';
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt->format('c'),
            'payload' => [
                'order_id' => $this->orderId,
                'customer_id' => $this->customerId,
                'items' => $this->items,
                'total' => $this->total,
            ],
        ];
    }
}

class OrderConfirmedEvent extends DomainEvent
{
    public function __construct(
        private string $orderId,
        private string $customerId,
        private string $paymentId
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'sales.order.confirmed';
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt->format('c'),
            'payload' => [
                'order_id' => $this->orderId,
                'customer_id' => $this->customerId,
                'payment_id' => $this->paymentId,
            ],
        ];
    }
}

class OrderCancelledEvent extends DomainEvent
{
    public function __construct(
        private string $orderId,
        private array $items,
        private string $reason
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'sales.order.cancelled';
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt->format('c'),
            'payload' => [
                'order_id' => $this->orderId,
                'items' => $this->items,
                'reason' => $this->reason,
            ],
        ];
    }
}
```

## Casos de Uso

### 1. Create Order (Saga Orchestrator)

```php
<?php

namespace App\Application\UseCases\Commands;

use App\Domain\Entities\Order;
use App\Domain\Events\OrderCreatedEvent;

class CreateOrderUseCase implements UseCaseInterface
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private CustomerRepositoryInterface $customerRepository,
        private EventPublisher $eventPublisher
    ) {}

    public function execute($input): array
    {
        // Validar cliente
        $customer = $this->customerRepository->findById(
            new CustomerId($input['customer_id'])
        );

        if (!$customer) {
            throw new \DomainException('Customer not found');
        }

        // Criar items do pedido
        $items = [];
        foreach ($input['items'] as $itemData) {
            $items[] = new OrderItem(
                new ProductId($itemData['product_id']),
                $itemData['product_name'],
                $itemData['quantity'],
                new Money($itemData['unit_price'])
            );
        }

        // Criar order
        $order = new Order(
            new OrderId(),
            $customer->getCustomerId(),
            $items
        );

        // Persistir
        $this->orderRepository->save($order);

        // Publicar evento para iniciar saga
        $event = new OrderCreatedEvent(
            $order->getOrderId()->value(),
            $order->getCustomerId()->value(),
            array_map(fn($item) => [
                'product_id' => $item->getProductId()->value(),
                'quantity' => $item->getQuantity(),
                'unit_price' => $item->getUnitPrice()->getAmount(),
            ], $items),
            $order->getTotal()->getAmount()
        );

        $this->eventPublisher->publish($event);

        return [
            'order_id' => $order->getOrderId()->value(),
            'status' => $order->getStatus(),
            'total' => $order->getTotal()->getAmount(),
        ];
    }
}
```

### 2. Cancel Order

```php
<?php

namespace App\Application\UseCases\Commands;

class CancelOrderUseCase implements UseCaseInterface
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private EventPublisher $eventPublisher
    ) {}

    public function execute($input): void
    {
        $order = $this->orderRepository->findById(
            new OrderId($input['order_id'])
        );

        if (!$order) {
            throw new \DomainException('Order not found');
        }

        $order->cancel();
        $this->orderRepository->save($order);

        // Publicar evento para compensar saga
        $event = new OrderCancelledEvent(
            $order->getOrderId()->value(),
            array_map(fn($item) => [
                'product_id' => $item->getProductId()->value(),
                'quantity' => $item->getQuantity(),
            ], $order->getItems()),
            $input['reason'] ?? 'Customer request'
        );

        $this->eventPublisher->publish($event);
    }
}
```

## API Endpoints

### Orders
```
GET    /api/v1/orders                - Listar pedidos
POST   /api/v1/orders                - Criar pedido
GET    /api/v1/orders/{id}           - Obter pedido
PUT    /api/v1/orders/{id}           - Atualizar pedido
DELETE /api/v1/orders/{id}           - Cancelar pedido
GET    /api/v1/orders/{id}/status    - Obter status do pedido
POST   /api/v1/orders/{id}/confirm   - Confirmar pedido
POST   /api/v1/orders/{id}/cancel    - Cancelar pedido
GET    /api/v1/orders/customer/{id}  - Pedidos de um cliente
```

### Customers
```
GET    /api/v1/customers             - Listar clientes
POST   /api/v1/customers             - Criar cliente
GET    /api/v1/customers/{id}        - Obter cliente
PUT    /api/v1/customers/{id}        - Atualizar cliente
DELETE /api/v1/customers/{id}        - Deletar cliente
POST   /api/v1/customers/{id}/addresses - Adicionar endereço
GET    /api/v1/customers/{id}/orders    - Pedidos do cliente
```

## Schema do Banco de Dados

```sql
-- Customers Table
CREATE TABLE customers (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    document VARCHAR(20) UNIQUE,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP
);

CREATE INDEX idx_customers_email ON customers(email);
CREATE INDEX idx_customers_document ON customers(document);

-- Addresses Table
CREATE TABLE addresses (
    id UUID PRIMARY KEY,
    customer_id UUID REFERENCES customers(id) ON DELETE CASCADE,
    street VARCHAR(255) NOT NULL,
    number VARCHAR(20) NOT NULL,
    complement VARCHAR(100),
    neighborhood VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(2) NOT NULL,
    zip_code VARCHAR(10) NOT NULL,
    country VARCHAR(2) DEFAULT 'BR',
    is_default BOOLEAN DEFAULT false,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_addresses_customer_id ON addresses(customer_id);

-- Orders Table
CREATE TABLE orders (
    id UUID PRIMARY KEY,
    customer_id UUID REFERENCES customers(id),
    status VARCHAR(20) NOT NULL, -- pending, confirmed, cancelled, completed
    subtotal DECIMAL(10, 2) NOT NULL,
    discount DECIMAL(10, 2) DEFAULT 0,
    shipping DECIMAL(10, 2) DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL,
    payment_id VARCHAR(255),
    shipment_id VARCHAR(255),
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP
);

CREATE INDEX idx_orders_customer_id ON orders(customer_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created_at ON orders(created_at);

-- Order Items Table
CREATE TABLE order_items (
    id UUID PRIMARY KEY,
    order_id UUID REFERENCES orders(id) ON DELETE CASCADE,
    product_id UUID NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INTEGER NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_order_items_product_id ON order_items(product_id);
```

## Saga de Venda

### Fluxo Normal (Happy Path)

```
1. Sales Service: Cria Order (status: pending)
   ↓ Event: order.created

2. Inventory Service: Reserva Estoque
   ↓ Event: stock.reserved

3. Financial Service: Cria cobrança e processa pagamento
   ↓ Event: payment.processed

4. Sales Service: Atualiza Order (status: confirmed)
   ↓ Event: order.confirmed

5. Logistics Service: Cria pedido de envio
   ↓ Event: shipment.created

6. Logistics Service: Despacha pedido
   ↓ Event: shipment.dispatched

7. Logistics Service: Pedido entregue
   ↓ Event: shipment.delivered

8. Sales Service: Atualiza Order (status: completed)
   ↓ Event: order.completed
```

### Fluxo de Compensação (Falha)

```
Se payment.failed:
  → Inventory Service: Libera estoque (stock.released)
  → Sales Service: Cancela ordem (order.cancelled)
  → Notification Service: Notifica cliente

Se stock.reservation_failed:
  → Sales Service: Cancela ordem (order.cancelled)
  → Notification Service: Notifica cliente
```

## Eventos Publicados

- sales.order.created
- sales.order.confirmed
- sales.order.cancelled
- sales.order.completed

## Eventos Consumidos

### inventory.stock.reserved
```php
public function handle(StockReservedEvent $event)
{
    // Aguardar processamento de pagamento
    // Caso pagamento seja confirmado, seguir fluxo
}
```

### financial.payment.processed
```php
public function handle(PaymentProcessedEvent $event)
{
    $order = $this->orderRepository->findById(new OrderId($event->orderId));
    $order->confirm();
    $order->setPaymentId($event->paymentId);
    $this->orderRepository->save($order);
    
    $this->eventPublisher->publish(new OrderConfirmedEvent(/*...*/));
}
```

### logistics.shipment.delivered
```php
public function handle(ShipmentDeliveredEvent $event)
{
    $order = $this->orderRepository->findByShipmentId($event->shipmentId);
    $order->complete();
    $this->orderRepository->save($order);
    
    $this->eventPublisher->publish(new OrderCompletedEvent(/*...*/));
}
```

## Resiliência

### Circuit Breaker
- Comunicação com Inventory Service
- Comunicação com Financial Service

### Timeout
- 30s para reserva de estoque
- 60s para processamento de pagamento

### Retry
- 3 tentativas para eventos críticos
- Backoff exponencial (1s, 2s, 4s)

---

**Próximo:** [Logistics Service](../logistics-service/README.md)

