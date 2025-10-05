# 📦 Sprint 3-4: Inventory Service - Plano de Implementação

**Início:** Sprint 3  
**Duração:** 2 Sprints  
**Status:** 🟡 Planejamento

---

## 🎯 Objetivos

Implementar um serviço completo de gestão de produtos e estoque seguindo Clean Architecture, DDD e Event-Driven Architecture.

---

## 🏗️ Arquitetura

### Bounded Context: Inventory Management

**Agregados:**
1. **Product** - Produto do catálogo
2. **Category** - Categoria de produtos
3. **Stock** - Controle de estoque
4. **StockMovement** - Movimentações de estoque

---

## 📋 Domain Model

### 1. Product Aggregate

**Entities:**
- `Product` (Root)

**Value Objects:**
- `ProductId` (UUID)
- `ProductName` (string, 3-200 chars)
- `SKU` (string, unique, 3-50 chars)
- `Barcode` (string, opcional)
- `Price` (decimal, >= 0)
- `Description` (string, opcional)
- `ProductStatus` (enum: active, inactive, discontinued)

**Domain Events:**
- `ProductCreated`
- `ProductUpdated`
- `ProductActivated`
- `ProductDeactivated`
- `ProductDiscontinued`
- `PriceChanged`

**Business Rules:**
- SKU deve ser único
- Price deve ser >= 0
- Produtos inativos não podem ter movimentações de estoque
- Produtos descontinuados não podem ser reativados

---

### 2. Category Aggregate

**Entities:**
- `Category` (Root)

**Value Objects:**
- `CategoryId` (UUID)
- `CategoryName` (string, 3-100 chars)
- `CategorySlug` (string, unique)
- `CategoryStatus` (enum: active, inactive)

**Domain Events:**
- `CategoryCreated`
- `CategoryUpdated`
- `CategoryActivated`
- `CategoryDeactivated`

**Business Rules:**
- Slug deve ser único
- Categorias com produtos não podem ser deletadas (apenas desativadas)

---

### 3. Stock Aggregate

**Entities:**
- `Stock` (Root)
- `StockMovement` (Child)

**Value Objects:**
- `StockId` (UUID)
- `ProductId` (reference)
- `Quantity` (int, >= 0)
- `MinimumQuantity` (int, >= 0)
- `MaximumQuantity` (int, opcional)
- `MovementType` (enum: IN, OUT, ADJUSTMENT, RETURN)
- `MovementReason` (string)

**Domain Events:**
- `StockCreated`
- `StockIncreased`
- `StockDecreased`
- `StockAdjusted`
- `StockLowAlert`
- `StockDepleted`

**Business Rules:**
- Quantidade nunca pode ser negativa
- Alertas de estoque baixo quando quantity <= minimumQuantity
- Movimentações devem ter reason
- Não pode diminuir estoque de produto inativo

---

## 🔄 Use Cases

### Product Use Cases
1. **CreateProduct** - Criar novo produto
2. **UpdateProduct** - Atualizar produto
3. **GetProduct** - Buscar produto por ID
4. **ListProducts** - Listar produtos (com filtros e paginação)
5. **SearchProducts** - Buscar produtos (por nome, SKU, barcode)
6. **ActivateProduct** - Ativar produto
7. **DeactivateProduct** - Desativar produto
8. **DiscontinueProduct** - Descontinuar produto
9. **UpdatePrice** - Atualizar preço

### Category Use Cases
1. **CreateCategory** - Criar categoria
2. **UpdateCategory** - Atualizar categoria
3. **GetCategory** - Buscar categoria por ID
4. **ListCategories** - Listar categorias
5. **ActivateCategory** - Ativar categoria
6. **DeactivateCategory** - Desativar categoria

### Stock Use Cases
1. **CreateStock** - Criar controle de estoque para produto
2. **IncreaseStock** - Entrada de estoque
3. **DecreaseStock** - Saída de estoque
4. **AdjustStock** - Ajuste de estoque (inventário)
5. **GetStock** - Consultar estoque de produto
6. **ListStockMovements** - Listar movimentações
7. **GetLowStockProducts** - Produtos com estoque baixo

---

## 📡 API Endpoints

### Products

```
POST   /api/products              - Criar produto
GET    /api/products              - Listar produtos (com filtros)
GET    /api/products/{id}         - Buscar produto por ID
PUT    /api/products/{id}         - Atualizar produto
PATCH  /api/products/{id}/status  - Ativar/Desativar
DELETE /api/products/{id}         - Descontinuar produto
GET    /api/products/search       - Buscar produtos
```

### Categories

```
POST   /api/categories            - Criar categoria
GET    /api/categories            - Listar categorias
GET    /api/categories/{id}       - Buscar categoria por ID
PUT    /api/categories/{id}       - Atualizar categoria
PATCH  /api/categories/{id}/status - Ativar/Desativar
DELETE /api/categories/{id}       - Deletar categoria
```

### Stock

```
POST   /api/stock                 - Criar controle de estoque
GET    /api/stock/product/{id}    - Consultar estoque de produto
POST   /api/stock/increase        - Entrada de estoque
POST   /api/stock/decrease        - Saída de estoque
POST   /api/stock/adjust          - Ajuste de estoque
GET    /api/stock/movements       - Listar movimentações
GET    /api/stock/low-stock       - Produtos com estoque baixo
GET    /api/stock/movements/{id}  - Detalhes de movimentação
```

### Health

```
GET    /api/health                - Health check
```

---

## 🗄️ Database Schema

### products table
```sql
- id (uuid, pk)
- category_id (uuid, fk, nullable)
- name (varchar 200)
- sku (varchar 50, unique)
- barcode (varchar 100, nullable)
- description (text, nullable)
- price (decimal 10,2)
- status (enum: active, inactive, discontinued)
- created_at (timestamp)
- updated_at (timestamp)

indexes:
- sku
- status
- category_id
- created_at
```

### categories table
```sql
- id (uuid, pk)
- name (varchar 100)
- slug (varchar 100, unique)
- description (text, nullable)
- status (enum: active, inactive)
- created_at (timestamp)
- updated_at (timestamp)

indexes:
- slug
- status
```

### stocks table
```sql
- id (uuid, pk)
- product_id (uuid, fk, unique)
- quantity (int, default 0)
- minimum_quantity (int, default 0)
- maximum_quantity (int, nullable)
- last_movement_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)

indexes:
- product_id (unique)
- quantity
```

### stock_movements table
```sql
- id (uuid, pk)
- stock_id (uuid, fk)
- product_id (uuid, fk)
- type (enum: IN, OUT, ADJUSTMENT, RETURN)
- quantity (int)
- quantity_before (int)
- quantity_after (int)
- reason (varchar 255)
- reference_id (varchar 100, nullable) # referência para pedido, por exemplo
- performed_by (uuid, nullable) # user_id do auth-service
- performed_at (timestamp)
- created_at (timestamp)

indexes:
- stock_id
- product_id
- type
- performed_at
- reference_id
```

---

## 🔌 Integrações

### Event Publishing (RabbitMQ)

**Exchange:** `inventory.events`

**Eventos publicados:**
1. `product.created`
2. `product.updated`
3. `product.price.changed`
4. `product.discontinued`
5. `stock.increased`
6. `stock.decreased`
7. `stock.low`
8. `stock.depleted`

**Consumidores esperados:**
- Sales Service (para validar disponibilidade)
- Notification Service (para alertas)
- Financial Service (para atualizar preços)

---

## 🧪 Testes

### Unit Tests (Target: 100 testes)
- Domain Entities (Product, Category, Stock)
- Value Objects (ProductId, SKU, Price, etc)
- Use Cases (Create, Update, List, etc)

### Integration Tests (Target: 30 testes)
- Repositories (Eloquent)
- Event Publisher (RabbitMQ)

### Feature Tests (Target: 40 testes)
- API Endpoints
- Validation
- Error Handling
- Business Rules

**Total esperado: ~170 testes**

---

## 📦 Dependências

### Composer Packages
- `php-amqplib/php-amqplib` (já instalado)
- `ramsey/uuid` (já instalado via Laravel)
- Nenhuma nova dependência necessária

---

## 🎯 Milestones

### Sprint 3

**Semana 1-2:**
- ✅ Setup do projeto
- ✅ Domain Layer completa
- ✅ Application Layer completa
- ✅ Migrations e Models

**Semana 3-4:**
- ✅ Infrastructure Layer
- ✅ Presentation Layer (Products + Categories)
- ✅ Testes (Unit + Integration)

### Sprint 4

**Semana 1-2:**
- ✅ Stock Management completo
- ✅ Stock Movements
- ✅ Low Stock Alerts

**Semana 3-4:**
- ✅ Testes (Feature)
- ✅ Integração Docker
- ✅ Documentação
- ✅ Postman Collection

---

## 📝 Estrutura de Arquivos

```
services/inventory-service/
├── src/
│   ├── Domain/
│   │   ├── Entities/
│   │   │   ├── Product.php
│   │   │   ├── Category.php
│   │   │   ├── Stock.php
│   │   │   └── StockMovement.php
│   │   ├── ValueObjects/
│   │   │   ├── ProductId.php
│   │   │   ├── CategoryId.php
│   │   │   ├── StockId.php
│   │   │   ├── ProductName.php
│   │   │   ├── SKU.php
│   │   │   ├── Price.php
│   │   │   ├── Quantity.php
│   │   │   └── MovementType.php
│   │   ├── Events/
│   │   │   ├── ProductCreated.php
│   │   │   ├── StockIncreased.php
│   │   │   └── StockLowAlert.php
│   │   ├── Exceptions/
│   │   │   ├── InvalidSKUException.php
│   │   │   ├── InsufficientStockException.php
│   │   │   └── ProductInactiveException.php
│   │   └── Repositories/
│   │       ├── ProductRepositoryInterface.php
│   │       ├── CategoryRepositoryInterface.php
│   │       └── StockRepositoryInterface.php
│   │
│   ├── Application/
│   │   ├── UseCases/
│   │   │   ├── Product/
│   │   │   │   ├── CreateProduct/
│   │   │   │   ├── UpdateProduct/
│   │   │   │   ├── ListProducts/
│   │   │   │   └── SearchProducts/
│   │   │   ├── Category/
│   │   │   │   ├── CreateCategory/
│   │   │   │   └── ListCategories/
│   │   │   └── Stock/
│   │   │       ├── IncreaseStock/
│   │   │       ├── DecreaseStock/
│   │   │       └── GetLowStockProducts/
│   │   ├── DTOs/
│   │   │   ├── ProductDTO.php
│   │   │   ├── CategoryDTO.php
│   │   │   ├── StockDTO.php
│   │   │   └── StockMovementDTO.php
│   │   └── Contracts/
│   │       └── EventPublisherInterface.php
│   │
│   ├── Infrastructure/
│   │   ├── Persistence/
│   │   │   └── Eloquent/
│   │   │       ├── Models/
│   │   │       │   ├── ProductModel.php
│   │   │       │   ├── CategoryModel.php
│   │   │       │   ├── StockModel.php
│   │   │       │   └── StockMovementModel.php
│   │   │       └── Repositories/
│   │   │           ├── EloquentProductRepository.php
│   │   │           ├── EloquentCategoryRepository.php
│   │   │           └── EloquentStockRepository.php
│   │   └── Messaging/
│   │       └── RabbitMQ/
│   │           └── RabbitMQEventPublisher.php
│   │
│   └── Presentation/
│       ├── Controllers/
│       │   ├── ProductController.php
│       │   ├── CategoryController.php
│       │   └── StockController.php
│       ├── Requests/
│       │   ├── CreateProductRequest.php
│       │   ├── UpdateProductRequest.php
│       │   ├── CreateCategoryRequest.php
│       │   └── StockMovementRequest.php
│       └── Resources/
│           ├── ProductResource.php
│           ├── CategoryResource.php
│           └── StockResource.php
│
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Feature/
│
├── database/
│   └── migrations/
│       ├── create_categories_table.php
│       ├── create_products_table.php
│       ├── create_stocks_table.php
│       └── create_stock_movements_table.php
│
├── config/
│   ├── database.php
│   ├── rabbitmq.php
│   └── inventory.php
│
├── routes/
│   └── api.php
│
├── Dockerfile
├── Dockerfile.dev
├── composer.json
└── README.md
```

---

## 🔒 Autenticação

Todos os endpoints (exceto health check) devem exigir autenticação JWT do Auth Service.

**Middleware:** `jwt.auth` (reusar do Auth Service)

**Autorização:** Por enquanto, qualquer usuário autenticado pode acessar. RBAC será implementado depois.

---

## 🎨 Padrões e Convenções

- **PSR-12** - Coding Standards
- **SOLID** - Princípios de design
- **Clean Architecture** - Separação de camadas
- **DDD** - Domain-Driven Design
- **Event-Driven** - Comunicação via eventos
- **RESTful** - API design
- **UUID** - Identificadores
- **Immutable VOs** - Value Objects imutáveis
- **Factory Methods** - Para Value Objects

---

## 📊 Métricas de Sucesso

- ✅ ~170 testes passando (100% success rate)
- ✅ Cobertura de testes > 80%
- ✅ Todas as APIs documentadas
- ✅ Postman Collection completa
- ✅ Eventos publicados corretamente
- ✅ Performance < 100ms por request
- ✅ Zero bugs críticos

---

## 📚 Documentação a Criar

1. **API-DOCS.md** - Documentação completa da API
2. **ARCHITECTURE.md** - Arquitetura e padrões
3. **README.md** - Overview e quick start
4. **postman-collection.json** - Collection para testes
5. **SPRINT3-4-SUMMARY.md** - Resumo ao final

---

## 🚀 Ordem de Implementação

1. **Setup** - Criar estrutura de diretórios e configurações
2. **Domain** - Entities, VOs, Events, Exceptions
3. **Application** - Use Cases e DTOs
4. **Infrastructure** - Repositories e Event Publisher
5. **Presentation** - Controllers, Requests, Resources
6. **Tests** - Unit, Integration, Feature
7. **Documentation** - Docs e Postman
8. **Integration** - Docker e testes finais

---

**Pronto para começar!** 🚀

Vamos começar pela estrutura base e Domain Layer?

