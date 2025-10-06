# 🚀 Sprint 4 - Sales Service Implementation Plan

**Objetivo:** Implementar o Sales Service com gestão completa de pedidos de venda e integração com Inventory Service.

---

## 📋 Domain Model

### Aggregates

#### 1. **Customer** (Cliente)
- **Customer ID** (UUID)
- Name
- Email
- Phone
- Document (CPF/CNPJ)
- Address
- Status (active, inactive)
- Created At / Updated At

#### 2. **Order** (Pedido de Venda)
- **Order ID** (UUID)
- Order Number (sequencial único)
- Customer ID
- Status (draft, pending, confirmed, processing, shipped, delivered, cancelled)
- Items (List<OrderItem>)
- Subtotal
- Discount
- Total
- Payment Status (pending, paid, refunded)
- Payment Method
- Notes
- Created At / Updated At

#### 3. **Order Item** (Item do Pedido)
- **Order Item ID** (UUID)
- Order ID
- Product ID (referência ao Inventory)
- Product Name (snapshot)
- SKU (snapshot)
- Quantity
- Unit Price (snapshot do preço no momento)
- Subtotal
- Discount
- Total

---

## 🎯 Value Objects

1. **CustomerId** - UUID do cliente
2. **OrderId** - UUID do pedido
3. **OrderItemId** - UUID do item
4. **OrderNumber** - Número sequencial único (ORD-2024-0001)
5. **CustomerName** - Nome do cliente (validação)
6. **Email** - Email válido
7. **Phone** - Telefone válido
8. **Document** - CPF ou CNPJ (validação)
9. **Money** - Valor monetário (decimal com 2 casas)
10. **Quantity** - Quantidade (inteiro positivo)
11. **OrderStatus** - Status do pedido (enum)
12. **PaymentStatus** - Status do pagamento (enum)

---

## 🎭 Domain Events

1. **OrderCreated** - Pedido criado
2. **OrderConfirmed** - Pedido confirmado (reservar estoque)
3. **OrderCancelled** - Pedido cancelado (liberar estoque)
4. **OrderDelivered** - Pedido entregue
5. **CustomerCreated** - Cliente criado
6. **PaymentReceived** - Pagamento recebido

---

## 🔄 Integration Events (RabbitMQ)

### Publish (Sales → outros serviços):
- `order.created` → Notification Service
- `order.confirmed` → Inventory Service (reservar estoque)
- `order.cancelled` → Inventory Service (liberar estoque)
- `order.delivered` → Inventory Service (baixar estoque definitivo)

### Consume (outros serviços → Sales):
- `stock.reserved` ← Inventory Service
- `stock.insufficient` ← Inventory Service
- `payment.confirmed` ← Financial Service (futuro)

---

## 📊 Database Schema

### Tables

#### `customers`
```sql
- id (UUID, PK)
- name (VARCHAR 200)
- email (VARCHAR 255, UNIQUE)
- phone (VARCHAR 20)
- document (VARCHAR 20, UNIQUE)
- address_street (VARCHAR 255)
- address_number (VARCHAR 20)
- address_complement (VARCHAR 100)
- address_city (VARCHAR 100)
- address_state (VARCHAR 2)
- address_zip_code (VARCHAR 10)
- status (ENUM: active, inactive)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

INDEXES: email, document, status
```

#### `orders`
```sql
- id (UUID, PK)
- order_number (VARCHAR 20, UNIQUE)
- customer_id (UUID, FK → customers)
- status (ENUM: draft, pending, confirmed, processing, shipped, delivered, cancelled)
- subtotal (DECIMAL 10,2)
- discount (DECIMAL 10,2)
- total (DECIMAL 10,2)
- payment_status (ENUM: pending, paid, refunded)
- payment_method (VARCHAR 50)
- notes (TEXT)
- confirmed_at (TIMESTAMP)
- cancelled_at (TIMESTAMP)
- delivered_at (TIMESTAMP)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

INDEXES: order_number, customer_id, status, payment_status, created_at
```

#### `order_items`
```sql
- id (UUID, PK)
- order_id (UUID, FK → orders)
- product_id (UUID)  # Referência ao Inventory Service
- product_name (VARCHAR 200)  # Snapshot
- sku (VARCHAR 100)  # Snapshot
- quantity (INTEGER)
- unit_price (DECIMAL 10,2)
- subtotal (DECIMAL 10,2)
- discount (DECIMAL 10,2)
- total (DECIMAL 10,2)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

INDEXES: order_id, product_id
```

---

## 🔧 Use Cases

### Customer
1. **CreateCustomer** - Criar novo cliente
2. **GetCustomer** - Buscar cliente por ID
3. **ListCustomers** - Listar clientes com filtros
4. **UpdateCustomer** - Atualizar dados do cliente
5. **DeactivateCustomer** - Desativar cliente

### Order
1. **CreateOrder** - Criar pedido (draft)
2. **AddOrderItem** - Adicionar item ao pedido
3. **RemoveOrderItem** - Remover item do pedido
4. **CalculateOrderTotal** - Calcular totais
5. **ConfirmOrder** - Confirmar pedido (validar estoque)
6. **CancelOrder** - Cancelar pedido
7. **GetOrder** - Buscar pedido por ID
8. **ListOrders** - Listar pedidos com filtros
9. **UpdateOrderStatus** - Atualizar status do pedido

---

## 🌐 API Endpoints

### Customers
- `POST   /api/v1/customers` - Create customer
- `GET    /api/v1/customers` - List customers
- `GET    /api/v1/customers/{id}` - Get customer
- `PUT    /api/v1/customers/{id}` - Update customer
- `DELETE /api/v1/customers/{id}` - Deactivate customer

### Orders
- `POST   /api/v1/orders` - Create order (draft)
- `GET    /api/v1/orders` - List orders
- `GET    /api/v1/orders/{id}` - Get order with items
- `POST   /api/v1/orders/{id}/items` - Add item to order
- `DELETE /api/v1/orders/{id}/items/{itemId}` - Remove item
- `POST   /api/v1/orders/{id}/confirm` - Confirm order
- `POST   /api/v1/orders/{id}/cancel` - Cancel order
- `PUT    /api/v1/orders/{id}/status` - Update status

**Total:** ~13 endpoints

---

## 🧪 Testing Strategy

### Unit Tests (~60 tests)
- Value Objects (12 classes × ~5 tests) = 60 tests
- Entities (Customer, Order, OrderItem) = 20 tests
- Use Cases (principais) = 15 tests

### Feature Tests (~20 tests)
- Customer API = 7 tests
- Order API = 13 tests

**Total:** ~95 tests

---

## 🔄 Integration Flow Example

### Fluxo de Pedido Confirmado:

```
1. Cliente cria pedido (draft)
   → POST /api/v1/orders
   → Status: draft

2. Adiciona itens ao pedido
   → POST /api/v1/orders/{id}/items
   → Valida Product ID existe no Inventory

3. Confirma o pedido
   → POST /api/v1/orders/{id}/confirm
   → Valida totais
   → Publica evento: order.confirmed
   → RabbitMQ → Inventory Service

4. Inventory Service processa:
   → Reserva estoque
   → Publica: stock.reserved

5. Sales Service recebe confirmação:
   → Atualiza status: confirmed → processing
```

---

## 📦 Estrutura de Diretórios

```
services/sales-service/
├── src/
│   ├── Domain/
│   │   ├── Entities/
│   │   │   ├── Customer.php
│   │   │   ├── Order.php
│   │   │   └── OrderItem.php
│   │   ├── ValueObjects/
│   │   │   ├── CustomerId.php
│   │   │   ├── OrderId.php
│   │   │   ├── OrderItemId.php
│   │   │   ├── OrderNumber.php
│   │   │   ├── CustomerName.php
│   │   │   ├── Email.php
│   │   │   ├── Phone.php
│   │   │   ├── Document.php
│   │   │   ├── Money.php
│   │   │   ├── Quantity.php
│   │   │   ├── OrderStatus.php
│   │   │   └── PaymentStatus.php
│   │   ├── Events/
│   │   ├── Exceptions/
│   │   └── Repositories/
│   ├── Application/
│   │   ├── UseCases/
│   │   ├── DTOs/
│   │   └── Exceptions/
│   ├── Infrastructure/
│   │   ├── Persistence/
│   │   └── Messaging/
│   └── Presentation/
│       └── Controllers/
├── tests/
│   ├── Unit/
│   └── Feature/
├── database/
│   └── migrations/
├── API-DOCS.md
└── postman-collection.json
```

---

## ⏱️ Estimativa de Implementação

| Camada | Arquivos | Estimativa |
|--------|----------|------------|
| Domain Layer | ~30 arquivos | 2h |
| Application Layer | ~20 arquivos | 1.5h |
| Infrastructure Layer | ~10 arquivos | 1h |
| Presentation Layer | ~10 arquivos | 1h |
| Tests | ~20 arquivos | 1.5h |
| Docker + Docs | ~5 arquivos | 0.5h |
| **TOTAL** | **~95 arquivos** | **~8h** |

---

## 🎯 Milestones

### Fase 1: Core Domain (MVP)
- ✅ Customer entity
- ✅ Order entity
- ✅ Basic Use Cases
- ✅ CRUD APIs

### Fase 2: Business Logic
- ✅ Order confirmation flow
- ✅ Status transitions
- ✅ Calculations

### Fase 3: Integration
- ⏳ RabbitMQ publisher
- ⏳ Event handlers
- ⏳ Inventory integration

### Fase 4: Polish
- ⏳ Tests
- ⏳ Documentation
- ⏳ Docker

---

## 🚀 Quick Start

```bash
# 1. Copy structure from inventory-service
cp -r services/inventory-service services/sales-service

# 2. Update configurations
# - database.php (sales_db)
# - docker-compose.yml (port 9003)

# 3. Run migrations
docker exec sales-service php artisan migrate

# 4. Test
curl http://localhost:9003/api/health
```

---

**Status:** 📝 Planning Complete - Ready to Start Implementation  
**Next Step:** Create directory structure and implement Domain Layer
