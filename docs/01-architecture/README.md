# Arquitetura Geral do Sistema

## Visão de Alto Nível

```
┌─────────────────────────────────────────────────────────────────┐
│                         API Gateway                             │
│                    (Kong/Traefik/Laravel)                       │
└────────────────────────────┬────────────────────────────────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
┌────────▼────────┐  ┌───────▼─────┐  ┌──────────▼──────┐
│  Auth Service   │  │   Inventory │  │  Sales Service  │
│                 │  │   Service   │  │                 │
└────────┬────────┘  └───────┬─────┘  └────────┬────────┘
         │                   │                 │
         └───────────────────┼─────────────────┘
                             │
                    ┌────────▼─────────┐
                    │    RabbitMQ      │
                    │  (Message Bus)   │
                    └────────┬─────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
┌────────▼────────┐  ┌──────▼──────┐  ┌────────▼────────┐
│   Logistics     │  │  Financial   │  │   Notification  │
│    Service      │  │   Service    │  │    Service      │
└─────────────────┘  └──────────────┘  └─────────────────┘
```

## Arquitetura em Camadas (Clean Architecture)

### Camadas por Microserviço

```
┌─────────────────────────────────────────────────────────┐
│                  Presentation Layer                      │
│           (Controllers, CLI, Queue Workers)              │
├─────────────────────────────────────────────────────────┤
│                  Application Layer                       │
│            (Use Cases, DTOs, Services)                   │
├─────────────────────────────────────────────────────────┤
│                    Domain Layer                          │
│    (Entities, Value Objects, Domain Events, Rules)      │
├─────────────────────────────────────────────────────────┤
│                 Infrastructure Layer                     │
│    (Database, External APIs, Message Queue, Cache)      │
└─────────────────────────────────────────────────────────┘
```

### Dependências

- **Presentation → Application → Domain**
- **Infrastructure → Domain** (implementa interfaces do Domain)
- Domain não depende de nada (núcleo independente)

## Padrões Arquiteturais

### 1. Repository Pattern

```php
// Domain Layer - Interface
interface ProductRepository
{
    public function findById(ProductId $id): ?Product;
    public function save(Product $product): void;
    public function findBySku(string $sku): ?Product;
}

// Infrastructure Layer - Implementation
class EloquentProductRepository implements ProductRepository
{
    public function findById(ProductId $id): ?Product
    {
        $model = ProductModel::find($id->value());
        return $model ? $this->toDomain($model) : null;
    }
    
    // ... outras implementações
}
```

### 2. CQRS (Command Query Responsibility Segregation)

**Commands (Write Operations):**
```php
CreateOrderCommand
UpdateInventoryCommand
ProcessPaymentCommand
```

**Queries (Read Operations):**
```php
GetOrderByIdQuery
ListProductsQuery
GetCustomerStatisticsQuery
```

### 3. Event Sourcing (Parcial)

- Eventos de domínio armazenados para auditoria
- Reconstrução de estado quando necessário
- Event Store para eventos críticos

```php
// Exemplo de Domain Event
class OrderCreatedEvent
{
    public function __construct(
        public readonly OrderId $orderId,
        public readonly CustomerId $customerId,
        public readonly Money $totalAmount,
        public readonly DateTimeImmutable $occurredAt
    ) {}
}
```

## Fluxo de Requisição

### Exemplo: Criar um Pedido

```
1. Cliente → API Gateway
   POST /api/orders
   {
     "customer_id": "123",
     "items": [...]
   }

2. API Gateway → Auth Service
   Valida token JWT

3. API Gateway → Sales Service
   Encaminha requisição autenticada

4. Sales Service (Application Layer)
   - Valida dados de entrada
   - Cria comando: CreateOrderCommand

5. Sales Service (Domain Layer)
   - Cria entidade Order
   - Aplica regras de negócio
   - Gera evento: OrderCreatedEvent

6. Sales Service (Infrastructure Layer)
   - Persiste no banco de dados
   - Publica evento no RabbitMQ

7. Outros Serviços (Assíncrono)
   - Inventory: Reserva estoque
   - Financial: Cria cobrança
   - Logistics: Cria ordem de separação
   - Notification: Envia confirmação

8. Resposta ao Cliente
   {
     "order_id": "ORD-2025-001",
     "status": "pending",
     "created_at": "2025-10-04T10:30:00Z"
   }
```

## Estratégias de Dados

### Database per Service

Cada microserviço possui seu próprio banco de dados:

```
┌──────────────────┐
│  Auth Service    │
│  ┌────────────┐  │
│  │ PostgreSQL │  │
│  │  auth_db   │  │
│  └────────────┘  │
└──────────────────┘

┌──────────────────┐
│ Inventory Service│
│  ┌────────────┐  │
│  │ PostgreSQL │  │
│  │inventory_db│  │
│  └────────────┘  │
└──────────────────┘

┌──────────────────┐
│  Sales Service   │
│  ┌────────────┐  │
│  │ PostgreSQL │  │
│  │  sales_db  │  │
│  └────────────┘  │
└──────────────────┘
```

### Compartilhamento de Dados

- **Evitar joins entre serviços**
- **Duplicação estratégica:** Cada serviço mantém cópia dos dados necessários
- **Eventual Consistency:** Sincronização via eventos
- **API Composition:** Agregação de dados no API Gateway quando necessário

## Comunicação Entre Serviços

### Síncrona (REST)

**Quando usar:**
- Operações que requerem resposta imediata
- Queries simples
- Validações em tempo real

**Exemplo:**
```http
GET /api/products/{id}
Authorization: Bearer {token}
```

### Assíncrona (Events via RabbitMQ)

**Quando usar:**
- Operações de longa duração
- Notificações entre serviços
- Processamento em background
- Desacoplamento de serviços

**Exemplo de Evento:**
```json
{
  "event_type": "order.created",
  "event_id": "evt_123456",
  "occurred_at": "2025-10-04T10:30:00Z",
  "payload": {
    "order_id": "ORD-2025-001",
    "customer_id": "CUST-456",
    "items": [...],
    "total_amount": 150.00
  }
}
```

## Transações Distribuídas

### Saga Pattern

Implementação de transações de longa duração através de eventos:

**Exemplo: Processo de Venda**

```
1. Sales Service: Cria Order (status: pending)
   ↓ Event: OrderCreated
   
2. Inventory Service: Reserva Estoque
   ✓ Success → Event: StockReserved
   ✗ Failure → Event: StockReservationFailed
   
3. Financial Service: Processa Pagamento
   ✓ Success → Event: PaymentProcessed
   ✗ Failure → Event: PaymentFailed
   
4. Logistics Service: Cria Pedido de Envio
   ✓ Success → Event: ShippingOrderCreated
   ✗ Failure → Event: ShippingOrderFailed

5. Sales Service: Atualiza Order (status: confirmed)
```

**Compensação em caso de falha:**
```
Se PaymentFailed:
  → Inventory Service: Libera estoque reservado
  → Sales Service: Cancela ordem
  → Notification Service: Notifica cliente
```

## API Gateway

### Responsabilidades

1. **Roteamento:** Direciona requisições para os serviços corretos
2. **Autenticação:** Valida tokens JWT
3. **Rate Limiting:** Controla taxa de requisições
4. **Caching:** Cache de respostas frequentes
5. **Agregação:** Combina respostas de múltiplos serviços
6. **Transformação:** Adapta requests/responses
7. **Logging:** Centraliza logs de acesso

### Rotas

```
/api/auth/*           → Auth Service
/api/products/*       → Inventory Service
/api/inventory/*      → Inventory Service
/api/orders/*         → Sales Service
/api/customers/*      → Sales Service
/api/shipments/*      → Logistics Service
/api/deliveries/*     → Logistics Service
/api/payments/*       → Financial Service
/api/invoices/*       → Financial Service
```

## Service Discovery

### Opções

1. **Client-Side Discovery:** Cada serviço conhece os outros
2. **Server-Side Discovery:** API Gateway consulta service registry
3. **DNS-Based:** Usando Docker/Kubernetes DNS

### Implementação com Docker Compose

```yaml
services:
  auth-service:
    networks:
      - microservices-net
  
  inventory-service:
    networks:
      - microservices-net
    
networks:
  microservices-net:
    driver: bridge
```

## Versionamento de API

### Estratégia

- **URL Versioning:** `/api/v1/products`
- **Header Versioning:** `Accept: application/vnd.api.v1+json`

### Compatibilidade

- Manter versões antigas por período de transição
- Deprecation notices
- Breaking changes apenas em major versions

## Considerações de Performance

### Caching

```
┌─────────────┐
│   Redis     │ ← Cache distribuído
│   Cache     │
└─────────────┘
     ↑
     │ Cache de:
     ├─ Produtos frequentes
     ├─ Dados de usuário
     ├─ Configurações
     └─ Rate limiting data
```

### Otimizações

1. **Database Indexing:** Índices em colunas frequentes
2. **Connection Pooling:** Reuso de conexões
3. **Lazy Loading:** Carregamento sob demanda
4. **Pagination:** Sempre paginar grandes listas
5. **Eager Loading:** Evitar N+1 queries

## Escalabilidade

### Horizontal Scaling

- Múltiplas instâncias de cada serviço
- Load balancer distribui carga
- Stateless services

### Vertical Scaling

- Aumentar recursos de containers específicos
- Priorizar serviços críticos

---

**Próximo:** [Infraestrutura e Ferramentas](../02-infrastructure/README.md)

