# Microserviços - Visão Geral

## Lista de Microserviços

1. [Auth Service](./auth-service/README.md) - Autenticação e Autorização
2. [Inventory Service](./inventory-service/README.md) - Gestão de Estoque
3. [Sales Service](./sales-service/README.md) - Gestão de Vendas
4. [Logistics Service](./logistics-service/README.md) - Gestão de Logística
5. [Financial Service](./financial-service/README.md) - Gestão Financeira
6. [Notification Service](./notification-service/README.md) - Notificações

## Matriz de Comunicação

| Serviço | Publica Eventos | Consome Eventos |
|---------|----------------|-----------------|
| **Auth** | `user.registered`<br>`user.updated`<br>`user.deleted` | - |
| **Inventory** | `product.created`<br>`product.updated`<br>`stock.reserved`<br>`stock.released`<br>`stock.low_alert` | `order.created`<br>`order.cancelled` |
| **Sales** | `order.created`<br>`order.confirmed`<br>`order.cancelled`<br>`order.completed` | `stock.reserved`<br>`stock.reservation_failed`<br>`payment.processed`<br>`payment.failed`<br>`shipment.dispatched` |
| **Logistics** | `shipment.created`<br>`shipment.dispatched`<br>`shipment.in_transit`<br>`shipment.delivered` | `order.confirmed`<br>`payment.processed` |
| **Financial** | `payment.requested`<br>`payment.processed`<br>`payment.failed`<br>`invoice.generated` | `order.created`<br>`shipment.delivered` |
| **Notification** | - | `user.registered`<br>`order.created`<br>`order.confirmed`<br>`payment.processed`<br>`shipment.dispatched`<br>`shipment.delivered` |

## Dependências Entre Serviços

```
┌──────────────┐
│  Auth        │ ──┐
│  Service     │   │
└──────────────┘   │
                   ├──► Todos os serviços dependem
                   │    de autenticação
┌──────────────┐   │
│  Inventory   │ ◄─┤
│  Service     │   │
└──────┬───────┘   │
       │           │
       ▼           │
┌──────────────┐   │
│  Sales       │ ◄─┤
│  Service     │   │
└──────┬───────┘   │
       │           │
       ├──────────►│
       │           │
       ▼           │
┌──────────────┐   │
│  Logistics   │ ◄─┤
│  Service     │   │
└──────────────┘   │
       │           │
       ▼           │
┌──────────────┐   │
│  Financial   │ ◄─┤
│  Service     │   │
└──────────────┘   │
       │           │
       ▼           │
┌──────────────┐   │
│ Notification │ ◄─┘
│  Service     │
└──────────────┘
```

## Padrões Comuns

### Estrutura de Diretórios

Todos os microserviços seguem a mesma estrutura:

```
service-name/
├── app/
│   ├── Domain/                  # Camada de Domínio
│   │   ├── Entities/           # Entidades do domínio
│   │   ├── ValueObjects/       # Value Objects
│   │   ├── Events/             # Domain Events
│   │   ├── Exceptions/         # Domain Exceptions
│   │   └── Repositories/       # Repository Interfaces
│   │
│   ├── Application/             # Camada de Aplicação
│   │   ├── UseCases/           # Casos de uso
│   │   │   ├── Commands/       # Command handlers
│   │   │   └── Queries/        # Query handlers
│   │   ├── DTOs/               # Data Transfer Objects
│   │   ├── Services/           # Application Services
│   │   └── Validators/         # Validadores
│   │
│   ├── Infrastructure/          # Camada de Infraestrutura
│   │   ├── Persistence/        # Eloquent Models & Repositories
│   │   │   ├── Models/
│   │   │   └── Repositories/
│   │   ├── Messaging/          # RabbitMQ Integration
│   │   │   ├── Publishers/
│   │   │   └── Consumers/
│   │   ├── External/           # External APIs
│   │   └── Cache/              # Cache Implementation
│   │
│   └── Presentation/            # Camada de Apresentação
│       ├── Http/
│       │   ├── Controllers/
│       │   ├── Requests/       # Form Requests
│       │   ├── Resources/      # API Resources
│       │   └── Middleware/
│       └── Console/            # CLI Commands
│
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
│
├── routes/
│   ├── api.php
│   └── console.php
│
├── tests/
│   ├── Unit/
│   ├── Feature/
│   └── Integration/
│
├── docker/
│   ├── Dockerfile
│   └── nginx.conf
│
├── .env.example
├── composer.json
├── phpunit.xml
└── README.md
```

### Base Entity

```php
<?php

namespace App\Domain\Entities;

use DateTimeImmutable;

abstract class BaseEntity
{
    protected string $id;
    protected DateTimeImmutable $createdAt;
    protected DateTimeImmutable $updatedAt;
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
```

### Base Repository Interface

```php
<?php

namespace App\Domain\Repositories;

interface BaseRepositoryInterface
{
    public function findById(string $id): ?object;
    public function save(object $entity): void;
    public function delete(string $id): void;
    public function findAll(int $page = 1, int $perPage = 15): array;
}
```

### Domain Event Base

```php
<?php

namespace App\Domain\Events;

use DateTimeImmutable;

abstract class DomainEvent
{
    protected string $eventId;
    protected DateTimeImmutable $occurredAt;
    
    public function __construct()
    {
        $this->eventId = uniqid('evt_', true);
        $this->occurredAt = new DateTimeImmutable();
    }
    
    abstract public function getEventName(): string;
    abstract public function toArray(): array;
    
    public function getEventId(): string
    {
        return $this->eventId;
    }
    
    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
```

### Use Case Interface

```php
<?php

namespace App\Application\UseCases;

interface UseCaseInterface
{
    public function execute($input);
}
```

### API Response Format

```json
{
  "success": true,
  "data": {
    "id": "123",
    "name": "Product Name",
    "price": 99.99
  },
  "message": "Operation successful",
  "meta": {
    "timestamp": "2025-10-04T10:30:00Z",
    "version": "1.0"
  }
}
```

### Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": [
      {
        "field": "email",
        "message": "The email field is required"
      }
    ]
  },
  "meta": {
    "timestamp": "2025-10-04T10:30:00Z",
    "version": "1.0"
  }
}
```

## Configurações Comuns

### .env Template

```env
APP_NAME="Service Name"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=service_db
DB_USERNAME=service_user
DB_PASSWORD=service_pass

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=admin
RABBITMQ_PASSWORD=admin123
RABBITMQ_VHOST=/

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=rabbitmq

JWT_SECRET=your-secret-key
JWT_TTL=60

LOG_CHANNEL=stack
LOG_LEVEL=debug

AUTH_SERVICE_URL=http://auth-service:8000
INVENTORY_SERVICE_URL=http://inventory-service:8000
SALES_SERVICE_URL=http://sales-service:8000
LOGISTICS_SERVICE_URL=http://logistics-service:8000
FINANCIAL_SERVICE_URL=http://financial-service:8000
```

### Composer.json Base

```json
{
    "name": "erp/service-name",
    "type": "project",
    "description": "Service Description",
    "require": {
        "php": "^8.3",
        "laravel/framework": "^11.0",
        "laravel/sanctum": "^4.0",
        "laravel/horizon": "^5.0",
        "predis/predis": "^2.2",
        "php-amqplib/php-amqplib": "^3.5",
        "ramsey/uuid": "^4.7"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/telescope": "^5.0",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.0",
        "phpunit/phpunit": "^11.0",
        "larastan/larastan": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    }
}
```

## Testes

### Estrutura de Testes

```
tests/
├── Unit/                   # Testes unitários de domínio
│   ├── Domain/
│   │   ├── Entities/
│   │   └── ValueObjects/
│   └── Application/
│       └── UseCases/
│
├── Feature/                # Testes de integração
│   └── Http/
│       └── Controllers/
│
└── Integration/            # Testes end-to-end
    └── Workflows/
```

### Exemplo de Teste Unitário

```php
<?php

namespace Tests\Unit\Domain\Entities;

use Tests\TestCase;
use App\Domain\Entities\Product;
use App\Domain\ValueObjects\Money;

class ProductTest extends TestCase
{
    public function test_can_create_product()
    {
        $product = new Product(
            'Test Product',
            new Money(99.99, 'BRL'),
            'SKU-001'
        );
        
        $this->assertEquals('Test Product', $product->getName());
        $this->assertEquals(99.99, $product->getPrice()->getAmount());
    }
}
```

## Documentação API (OpenAPI)

Cada serviço deve ter documentação OpenAPI 3.0:

```yaml
openapi: 3.0.0
info:
  title: Service Name API
  version: 1.0.0
  description: Service description

servers:
  - url: http://localhost:8000/api/v1

paths:
  /resource:
    get:
      summary: List resources
      responses:
        '200':
          description: Success
```

## Migrations Pattern

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_name', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

---

**Documentação Detalhada de Cada Serviço:**

1. [Auth Service](./auth-service/README.md)
2. [Inventory Service](./inventory-service/README.md)
3. [Sales Service](./sales-service/README.md)
4. [Logistics Service](./logistics-service/README.md)
5. [Financial Service](./financial-service/README.md)
6. [Notification Service](./notification-service/README.md)

