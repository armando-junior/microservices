# Inventory Service - Serviço de Gestão de Estoque

## Visão Geral

O Inventory Service é responsável por gerenciar o catálogo de produtos e o controle de estoque do sistema ERP.

## Bounded Context

**Domínio:** Gestão de Estoque e Produtos

### Responsabilidades

- Cadastro e gerenciamento de produtos
- Controle de estoque (entradas, saídas, reservas)
- Gestão de categorias de produtos
- Variações de produtos (SKU, tamanhos, cores)
- Alertas de estoque baixo
- Histórico de movimentações
- Inventário físico

### O que NÃO é responsabilidade

- Processamento de vendas
- Precificação dinâmica
- Gestão de fornecedores
- Logística de entrega

## Modelo de Domínio

### Entidades

#### Product (Aggregate Root)

```php
<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\ProductId;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\SKU;

class Product extends BaseEntity
{
    private ProductId $productId;
    private string $name;
    private string $description;
    private SKU $sku;
    private Money $price;
    private ?CategoryId $categoryId;
    private bool $isActive;
    private array $variations;

    public function __construct(
        ProductId $productId,
        string $name,
        string $description,
        SKU $sku,
        Money $price,
        ?CategoryId $categoryId = null
    ) {
        $this->productId = $productId;
        $this->name = $name;
        $this->description = $description;
        $this->sku = $sku;
        $this->price = $price;
        $this->categoryId = $categoryId;
        $this->isActive = true;
        $this->variations = [];
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updatePrice(Money $newPrice): void
    {
        $this->price = $newPrice;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    // Getters...
}
```

#### Stock

```php
<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\ProductId;

class Stock extends BaseEntity
{
    private ProductId $productId;
    private int $quantity;
    private int $reserved;
    private int $minQuantity;
    private string $location;

    public function __construct(
        ProductId $productId,
        int $quantity = 0,
        int $minQuantity = 10,
        string $location = 'default'
    ) {
        $this->id = uniqid('stock_', true);
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->reserved = 0;
        $this->minQuantity = $minQuantity;
        $this->location = $location;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function reserve(int $quantity): void
    {
        if ($this->getAvailableQuantity() < $quantity) {
            throw new \DomainException('Insufficient stock');
        }

        $this->reserved += $quantity;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function release(int $quantity): void
    {
        if ($this->reserved < $quantity) {
            throw new \DomainException('Cannot release more than reserved');
        }

        $this->reserved -= $quantity;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function confirmReservation(int $quantity): void
    {
        if ($this->reserved < $quantity) {
            throw new \DomainException('Cannot confirm more than reserved');
        }

        $this->reserved -= $quantity;
        $this->quantity -= $quantity;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addStock(int $quantity): void
    {
        $this->quantity += $quantity;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getAvailableQuantity(): int
    {
        return $this->quantity - $this->reserved;
    }

    public function isLowStock(): bool
    {
        return $this->getAvailableQuantity() <= $this->minQuantity;
    }

    // Getters...
}
```

#### StockMovement

```php
<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\ProductId;

class StockMovement extends BaseEntity
{
    private ProductId $productId;
    private string $type; // in, out, adjustment, reservation, release
    private int $quantity;
    private int $previousQuantity;
    private int $newQuantity;
    private string $reason;
    private ?string $referenceType;
    private ?string $referenceId;
    private ?string $userId;

    public function __construct(
        ProductId $productId,
        string $type,
        int $quantity,
        int $previousQuantity,
        int $newQuantity,
        string $reason,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $userId = null
    ) {
        $this->id = uniqid('mov_', true);
        $this->productId = $productId;
        $this->type = $type;
        $this->quantity = $quantity;
        $this->previousQuantity = $previousQuantity;
        $this->newQuantity = $newQuantity;
        $this->reason = $reason;
        $this->referenceType = $referenceType;
        $this->referenceId = $referenceId;
        $this->userId = $userId;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters...
}
```

#### Category

```php
<?php

namespace App\Domain\Entities;

class Category extends BaseEntity
{
    private string $name;
    private string $slug;
    private ?string $parentId;
    private bool $isActive;

    public function __construct(
        string $name,
        string $slug,
        ?string $parentId = null
    ) {
        $this->id = uniqid('cat_', true);
        $this->name = $name;
        $this->slug = $slug;
        $this->parentId = $parentId;
        $this->isActive = true;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters...
}
```

### Value Objects

#### SKU

```php
<?php

namespace App\Domain\ValueObjects;

class SKU
{
    private string $value;

    public function __construct(string $value)
    {
        if (strlen($value) < 3) {
            throw new \InvalidArgumentException('SKU must have at least 3 characters');
        }

        $this->value = strtoupper($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(SKU $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

#### Money

```php
<?php

namespace App\Domain\ValueObjects;

class Money
{
    private float $amount;
    private string $currency;

    public function __construct(float $amount, string $currency = 'BRL')
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }

        $this->amount = round($amount, 2);
        $this->currency = strtoupper($currency);
    }

    public function add(Money $other): Money
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add different currencies');
        }

        return new Money($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): Money
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot subtract different currencies');
        }

        return new Money($this->amount - $other->amount, $this->currency);
    }

    public function multiply(float $multiplier): Money
    {
        return new Money($this->amount * $multiplier, $this->currency);
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    // Getters...
}
```

### Domain Events

```php
<?php

namespace App\Domain\Events;

class ProductCreatedEvent extends DomainEvent
{
    public function __construct(
        private string $productId,
        private string $name,
        private string $sku,
        private float $price
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'inventory.product.created';
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt->format('c'),
            'payload' => [
                'product_id' => $this->productId,
                'name' => $this->name,
                'sku' => $this->sku,
                'price' => $this->price,
            ],
        ];
    }
}

class StockReservedEvent extends DomainEvent
{
    public function __construct(
        private string $productId,
        private int $quantity,
        private string $referenceId,
        private string $referenceType = 'order'
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'inventory.stock.reserved';
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt->format('c'),
            'payload' => [
                'product_id' => $this->productId,
                'quantity' => $this->quantity,
                'reference_id' => $this->referenceId,
                'reference_type' => $this->referenceType,
            ],
        ];
    }
}

class StockLowAlertEvent extends DomainEvent
{
    public function __construct(
        private string $productId,
        private string $productName,
        private int $currentStock,
        private int $minStock
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'inventory.stock.low_alert';
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt->format('c'),
            'payload' => [
                'product_id' => $this->productId,
                'product_name' => $this->productName,
                'current_stock' => $this->currentStock,
                'min_stock' => $this->minStock,
            ],
        ];
    }
}
```

## Casos de Uso

### 1. Create Product

```php
<?php

namespace App\Application\UseCases\Commands;

use App\Domain\Entities\Product;
use App\Domain\Events\ProductCreatedEvent;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Domain\ValueObjects\ProductId;
use App\Domain\ValueObjects\SKU;
use App\Domain\ValueObjects\Money;

class CreateProductUseCase implements UseCaseInterface
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private EventPublisher $eventPublisher
    ) {}

    public function execute($input): array
    {
        // Verificar se SKU já existe
        if ($this->productRepository->findBySku($input['sku'])) {
            throw new \DomainException('SKU already exists');
        }

        $product = new Product(
            new ProductId(),
            $input['name'],
            $input['description'],
            new SKU($input['sku']),
            new Money($input['price'], $input['currency'] ?? 'BRL'),
            isset($input['category_id']) ? new CategoryId($input['category_id']) : null
        );

        $this->productRepository->save($product);

        // Publicar evento
        $event = new ProductCreatedEvent(
            $product->getProductId()->value(),
            $product->getName(),
            $product->getSku()->value(),
            $product->getPrice()->getAmount()
        );

        $this->eventPublisher->publish($event);

        return [
            'product_id' => $product->getProductId()->value(),
            'name' => $product->getName(),
            'sku' => $product->getSku()->value(),
            'price' => $product->getPrice()->getAmount(),
        ];
    }
}
```

### 2. Reserve Stock

```php
<?php

namespace App\Application\UseCases\Commands;

use App\Domain\Entities\StockMovement;
use App\Domain\Events\StockReservedEvent;
use App\Domain\Repositories\StockRepositoryInterface;
use App\Domain\ValueObjects\ProductId;

class ReserveStockUseCase implements UseCaseInterface
{
    public function __construct(
        private StockRepositoryInterface $stockRepository,
        private EventPublisher $eventPublisher
    ) {}

    public function execute($input): void
    {
        $productId = new ProductId($input['product_id']);
        $stock = $this->stockRepository->findByProductId($productId);

        if (!$stock) {
            throw new \DomainException('Product not found in stock');
        }

        $previousQuantity = $stock->getAvailableQuantity();

        // Reservar estoque
        $stock->reserve($input['quantity']);

        // Criar movimento
        $movement = new StockMovement(
            $productId,
            'reservation',
            $input['quantity'],
            $previousQuantity,
            $stock->getAvailableQuantity(),
            'Stock reserved for order',
            'order',
            $input['order_id'],
            $input['user_id'] ?? null
        );

        $this->stockRepository->save($stock);
        $this->stockRepository->saveMovement($movement);

        // Publicar evento
        $event = new StockReservedEvent(
            $input['product_id'],
            $input['quantity'],
            $input['order_id']
        );

        $this->eventPublisher->publish($event);

        // Verificar se estoque está baixo
        if ($stock->isLowStock()) {
            $this->eventPublisher->publish(new StockLowAlertEvent(
                $input['product_id'],
                $stock->getProduct()->getName(),
                $stock->getAvailableQuantity(),
                $stock->getMinQuantity()
            ));
        }
    }
}
```

## API Endpoints

### Products

```
GET    /api/v1/products              - Listar produtos
POST   /api/v1/products              - Criar produto
GET    /api/v1/products/{id}         - Obter produto
PUT    /api/v1/products/{id}         - Atualizar produto
DELETE /api/v1/products/{id}         - Deletar produto
GET    /api/v1/products/sku/{sku}    - Buscar por SKU
POST   /api/v1/products/bulk-import  - Importar em massa
```

### Stock

```
GET    /api/v1/stock                     - Listar estoque
GET    /api/v1/stock/product/{id}        - Obter estoque do produto
POST   /api/v1/stock/add                 - Adicionar estoque
POST   /api/v1/stock/reserve             - Reservar estoque
POST   /api/v1/stock/release             - Liberar reserva
POST   /api/v1/stock/confirm-reservation - Confirmar reserva
GET    /api/v1/stock/movements           - Histórico de movimentações
GET    /api/v1/stock/low-stock           - Produtos com estoque baixo
POST   /api/v1/stock/adjustment          - Ajuste de estoque
```

### Categories

```
GET    /api/v1/categories        - Listar categorias
POST   /api/v1/categories        - Criar categoria
GET    /api/v1/categories/{id}   - Obter categoria
PUT    /api/v1/categories/{id}   - Atualizar categoria
DELETE /api/v1/categories/{id}   - Deletar categoria
```

## Schema do Banco de Dados

```sql
-- Products Table
CREATE TABLE products (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    sku VARCHAR(100) UNIQUE NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'BRL',
    category_id UUID REFERENCES categories(id),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP
);

CREATE INDEX idx_products_sku ON products(sku);
CREATE INDEX idx_products_category_id ON products(category_id);
CREATE INDEX idx_products_is_active ON products(is_active);

-- Categories Table
CREATE TABLE categories (
    id UUID PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    parent_id UUID REFERENCES categories(id),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_categories_slug ON categories(slug);
CREATE INDEX idx_categories_parent_id ON categories(parent_id);

-- Stock Table
CREATE TABLE stock (
    id UUID PRIMARY KEY,
    product_id UUID REFERENCES products(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL DEFAULT 0,
    reserved INTEGER NOT NULL DEFAULT 0,
    min_quantity INTEGER NOT NULL DEFAULT 10,
    location VARCHAR(100) DEFAULT 'default',
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    CONSTRAINT check_quantity CHECK (quantity >= 0),
    CONSTRAINT check_reserved CHECK (reserved >= 0)
);

CREATE UNIQUE INDEX idx_stock_product_location ON stock(product_id, location);

-- Stock Movements Table
CREATE TABLE stock_movements (
    id UUID PRIMARY KEY,
    product_id UUID REFERENCES products(id) ON DELETE CASCADE,
    type VARCHAR(20) NOT NULL, -- in, out, adjustment, reservation, release
    quantity INTEGER NOT NULL,
    previous_quantity INTEGER NOT NULL,
    new_quantity INTEGER NOT NULL,
    reason TEXT,
    reference_type VARCHAR(50),
    reference_id VARCHAR(255),
    user_id UUID,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_stock_movements_product_id ON stock_movements(product_id);
CREATE INDEX idx_stock_movements_type ON stock_movements(type);
CREATE INDEX idx_stock_movements_reference ON stock_movements(reference_type, reference_id);
CREATE INDEX idx_stock_movements_created_at ON stock_movements(created_at);

-- Product Variations Table
CREATE TABLE product_variations (
    id UUID PRIMARY KEY,
    product_id UUID REFERENCES products(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    sku VARCHAR(100) UNIQUE NOT NULL,
    attributes JSONB,
    price_adjustment DECIMAL(10, 2) DEFAULT 0,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_product_variations_product_id ON product_variations(product_id);
CREATE INDEX idx_product_variations_sku ON product_variations(sku);
```

## Eventos Publicados

### inventory.product.created
### inventory.product.updated
### inventory.stock.reserved
### inventory.stock.released
### inventory.stock.low_alert

## Eventos Consumidos

### sales.order.created
```php
// Reserva estoque quando ordem é criada
public function handle(OrderCreatedEvent $event)
{
    foreach ($event->items as $item) {
        $this->reserveStockUseCase->execute([
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'order_id' => $event->orderId,
        ]);
    }
}
```

### sales.order.cancelled
```php
// Libera estoque quando ordem é cancelada
public function handle(OrderCancelledEvent $event)
{
    foreach ($event->items as $item) {
        $this->releaseStockUseCase->execute([
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'order_id' => $event->orderId,
        ]);
    }
}
```

## Resiliência

### Circuit Breaker
- Implementar para comunicação com serviços externos

### Caching
- Cache de lista de produtos (TTL: 5 minutos)
- Cache de categorias (TTL: 1 hora)
- Cache de estoque disponível (TTL: 1 minuto)

### Retry Logic
- 3 tentativas para reserva de estoque
- Backoff exponencial

---

**Próximo:** [Sales Service](../sales-service/README.md)

