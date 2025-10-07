# Sales Service - API Documentation

## Visão Geral

O **Sales Service** é responsável pela gestão de vendas no sistema ERP, incluindo:
- Gerenciamento de clientes (cadastro, consulta, listagem)
- Gerenciamento de pedidos de venda
- Adição de itens aos pedidos (integração com Inventory Service)
- Controle de status de pedidos e pagamentos
- Validação de documentos brasileiros (CPF/CNPJ)

---

## Informações Técnicas

- **Base URL**: `http://localhost:9003/api`
- **Porta**: `9003`
- **Autenticação**: JWT Bearer Token (obtido no Auth Service)
- **Formato**: JSON
- **Versão da API**: `v1`

---

## Autenticação

Todos os endpoints da API (exceto health check) requerem autenticação JWT.

### Como obter o token

1. Faça login no **Auth Service** (porta 9001):

```bash
curl -X POST http://localhost:9001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "seu@email.com",
    "password": "sua_senha"
  }'
```

2. Use o token retornado no header `Authorization`:

```bash
Authorization: Bearer {seu_token_jwt}
```

### Códigos de Erro de Autenticação

| Código | Mensagem | Descrição |
|--------|----------|-----------|
| `401` | `Unauthorized` | Token ausente ou inválido |
| `401` | `TokenExpired` | Token expirado |
| `401` | `InvalidToken` | Assinatura do token inválida |

---

## Endpoints

### Health Check

#### `GET /health`

Verifica o status do serviço.

**Autenticação**: Não requerida

**Resposta de Sucesso (200)**:
```json
{
  "status": "ok",
  "service": "sales-service",
  "timestamp": "2025-10-06T02:00:00+00:00"
}
```

---

## Customers (Clientes)

### 1. Listar Clientes

#### `GET /v1/customers`

Lista todos os clientes com paginação e filtros.

**Autenticação**: Requerida (JWT)

**Query Parameters**:
| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| `page` | integer | Não | Número da página (padrão: 1) |
| `per_page` | integer | Não | Itens por página (padrão: 15) |
| `status` | string | Não | Filtrar por status: `active`, `inactive` |
| `search` | string | Não | Buscar por nome, email ou documento |

**Exemplo de Requisição**:
```bash
curl -X GET "http://localhost:9003/api/v1/customers?page=1&per_page=15&status=active" \
  -H "Authorization: Bearer {token}"
```

**Resposta de Sucesso (200)**:
```json
{
  "data": [
    {
      "id": "3a25ea9d-3d54-4635-a65f-588ba03bca28",
      "name": "João Silva",
      "email": "joao.silva@example.com",
      "phone": "11987654321",
      "phone_formatted": "(11) 98765-4321",
      "document": "11144477735",
      "document_formatted": "111.444.777-35",
      "document_type": "CPF",
      "address": {
        "street": "Rua das Flores",
        "number": "123",
        "complement": null,
        "city": "São Paulo",
        "state": "SP",
        "zip_code": "01234567"
      },
      "status": "active",
      "created_at": "2025-10-06 02:00:00",
      "updated_at": null
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 25,
    "last_page": 2
  }
}
```

---

### 2. Criar Cliente

#### `POST /v1/customers`

Cria um novo cliente no sistema.

**Autenticação**: Requerida (JWT)

**Body Parameters**:
```json
{
  "name": "João Silva",
  "email": "joao.silva@example.com",
  "phone": "11987654321",
  "document": "11144477735",
  "address_street": "Rua das Flores",
  "address_number": "123",
  "address_complement": "Apto 45",
  "address_city": "São Paulo",
  "address_state": "SP",
  "address_zip_code": "01234567"
}
```

**Validações**:
- `name`: obrigatório, 2-200 caracteres, apenas letras
- `email`: obrigatório, formato válido, único
- `phone`: obrigatório, formato numérico
- `document`: obrigatório, CPF ou CNPJ válido com dígitos verificadores
- `address_state`: opcional, 2 caracteres maiúsculos (sigla do estado)

**Exemplo de Requisição**:
```bash
curl -X POST http://localhost:9003/api/v1/customers \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "name": "Maria Santos",
    "email": "maria.santos@example.com",
    "phone": "11912345678",
    "document": "52998224725",
    "address_street": "Av Paulista",
    "address_number": "1000",
    "address_city": "São Paulo",
    "address_state": "SP",
    "address_zip_code": "01310100"
  }'
```

**Resposta de Sucesso (201)**:
```json
{
  "message": "Customer created successfully",
  "data": {
    "id": "70fca607-4b7b-45d8-b8e8-99eeaca2ae82",
    "name": "Maria Santos",
    "email": "maria.santos@example.com",
    "phone": "11912345678",
    "phone_formatted": "(11) 91234-5678",
    "document": "52998224725",
    "document_formatted": "529.982.247-25",
    "document_type": "CPF",
    "address": {
      "street": "Av Paulista",
      "number": "1000",
      "complement": null,
      "city": "São Paulo",
      "state": "SP",
      "zip_code": "01310100"
    },
    "status": "active",
    "created_at": "2025-10-06 02:25:00",
    "updated_at": null
  }
}
```

**Respostas de Erro**:

**409 - Email já existe**:
```json
{
  "error": "EmailAlreadyExists",
  "message": "Email already exists: maria.santos@example.com"
}
```

**409 - Documento já existe**:
```json
{
  "error": "DocumentAlreadyExists",
  "message": "Document already exists: 529.982.247-25"
}
```

**422 - CPF inválido**:
```json
{
  "error": "InvalidDocumentException",
  "message": "Invalid CPF"
}
```

---

### 3. Buscar Cliente

#### `GET /v1/customers/{id}`

Retorna os detalhes de um cliente específico.

**Autenticação**: Requerida (JWT)

**Path Parameters**:
| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | UUID | ID do cliente |

**Exemplo de Requisição**:
```bash
curl -X GET http://localhost:9003/api/v1/customers/70fca607-4b7b-45d8-b8e8-99eeaca2ae82 \
  -H "Authorization: Bearer {token}"
```

**Resposta de Sucesso (200)**:
```json
{
  "data": {
    "id": "70fca607-4b7b-45d8-b8e8-99eeaca2ae82",
    "name": "Maria Santos",
    "email": "maria.santos@example.com",
    "phone": "11912345678",
    "phone_formatted": "(11) 91234-5678",
    "document": "52998224725",
    "document_formatted": "529.982.247-25",
    "document_type": "CPF",
    "address": {
      "street": "Av Paulista",
      "number": "1000",
      "complement": null,
      "city": "São Paulo",
      "state": "SP",
      "zip_code": "01310100"
    },
    "status": "active",
    "created_at": "2025-10-06 02:25:00",
    "updated_at": null
  }
}
```

**Respostas de Erro**:

**404 - Cliente não encontrado**:
```json
{
  "error": "CustomerNotFound",
  "message": "Customer not found with ID: 70fca607-4b7b-45d8-b8e8-99eeaca2ae82"
}
```

---

## Orders (Pedidos)

### 4. Listar Pedidos

#### `GET /v1/orders`

Lista todos os pedidos com paginação e filtros.

**Autenticação**: Requerida (JWT)

**Query Parameters**:
| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| `page` | integer | Não | Número da página (padrão: 1) |
| `per_page` | integer | Não | Itens por página (padrão: 15) |
| `status` | string | Não | Filtrar por status do pedido |
| `payment_status` | string | Não | Filtrar por status de pagamento |
| `customer_id` | UUID | Não | Filtrar por cliente |

**Status de Pedido**:
- `draft`: Rascunho (em criação)
- `pending`: Pendente (aguardando processamento)
- `confirmed`: Confirmado
- `processing`: Em processamento
- `shipped`: Enviado
- `delivered`: Entregue
- `cancelled`: Cancelado

**Status de Pagamento**:
- `pending`: Pendente
- `paid`: Pago
- `refunded`: Reembolsado
- `failed`: Falhou

**Exemplo de Requisição**:
```bash
curl -X GET "http://localhost:9003/api/v1/orders?status=confirmed&page=1" \
  -H "Authorization: Bearer {token}"
```

**Resposta de Sucesso (200)**:
```json
{
  "data": [
    {
      "id": "526d715b-13a4-4328-8935-b78d63cfc9ef",
      "order_number": "ORD-2025-0002",
      "customer_id": "70fca607-4b7b-45d8-b8e8-99eeaca2ae82",
      "status": "confirmed",
      "subtotal": 3999.98,
      "discount": 5.00,
      "total": 3994.98,
      "payment_status": "pending",
      "payment_method": null,
      "notes": "Pedido via API",
      "items_count": 1,
      "items": [
        {
          "id": "abc123...",
          "product_id": "0eb5e387-d850-442e-8c8f-80f4fcec287f",
          "product_name": "Notebook Dell Inspiron",
          "sku": "NOTE-DELL-001",
          "quantity": 2,
          "unit_price": 1999.99,
          "subtotal": 3999.98,
          "discount": 5.00,
          "total": 3994.98,
          "created_at": "2025-10-06 02:30:00",
          "updated_at": null
        }
      ],
      "confirmed_at": "2025-10-06 02:34:21",
      "cancelled_at": null,
      "delivered_at": null,
      "created_at": "2025-10-06 02:25:03",
      "updated_at": "2025-10-06 02:34:21"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 10,
    "last_page": 1
  }
}
```

---

### 5. Criar Pedido

#### `POST /v1/orders`

Cria um novo pedido em status `draft` (rascunho).

**Autenticação**: Requerida (JWT)

**Body Parameters**:
```json
{
  "customer_id": "70fca607-4b7b-45d8-b8e8-99eeaca2ae82",
  "notes": "Pedido urgente"
}
```

**Validações**:
- `customer_id`: obrigatório, UUID válido, cliente deve existir
- `notes`: opcional, texto livre (máx. 1000 caracteres)

**Exemplo de Requisição**:
```bash
curl -X POST http://localhost:9003/api/v1/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "customer_id": "70fca607-4b7b-45d8-b8e8-99eeaca2ae82",
    "notes": "Pedido via API"
  }'
```

**Resposta de Sucesso (201)**:
```json
{
  "message": "Order created successfully",
  "data": {
    "id": "526d715b-13a4-4328-8935-b78d63cfc9ef",
    "order_number": "ORD-2025-0002",
    "customer_id": "70fca607-4b7b-45d8-b8e8-99eeaca2ae82",
    "status": "draft",
    "subtotal": 0,
    "discount": 0,
    "total": 0,
    "payment_status": "pending",
    "payment_method": null,
    "notes": "Pedido via API",
    "items_count": 0,
    "items": [],
    "confirmed_at": null,
    "cancelled_at": null,
    "delivered_at": null,
    "created_at": "2025-10-06 02:25:03",
    "updated_at": null
  }
}
```

**Respostas de Erro**:

**404 - Cliente não encontrado**:
```json
{
  "error": "CustomerNotFound",
  "message": "Customer not found with ID: 70fca607-4b7b-45d8-b8e8-99eeaca2ae82"
}
```

---

### 6. Buscar Pedido

#### `GET /v1/orders/{id}`

Retorna os detalhes de um pedido específico, incluindo todos os itens.

**Autenticação**: Requerida (JWT)

**Path Parameters**:
| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | UUID | ID do pedido |

**Exemplo de Requisição**:
```bash
curl -X GET http://localhost:9003/api/v1/orders/526d715b-13a4-4328-8935-b78d63cfc9ef \
  -H "Authorization: Bearer {token}"
```

**Resposta de Sucesso (200)**:
```json
{
  "data": {
    "id": "526d715b-13a4-4328-8935-b78d63cfc9ef",
    "order_number": "ORD-2025-0002",
    "customer_id": "70fca607-4b7b-45d8-b8e8-99eeaca2ae82",
    "status": "confirmed",
    "subtotal": 3999.98,
    "discount": 5.00,
    "total": 3994.98,
    "payment_status": "pending",
    "payment_method": null,
    "notes": "Pedido via API",
    "items_count": 1,
    "items": [
      {
        "id": "abc123...",
        "product_id": "0eb5e387-d850-442e-8c8f-80f4fcec287f",
        "product_name": "Notebook Dell Inspiron",
        "sku": "NOTE-DELL-001",
        "quantity": 2,
        "unit_price": 1999.99,
        "subtotal": 3999.98,
        "discount": 5.00,
        "total": 3994.98,
        "created_at": "2025-10-06 02:30:00",
        "updated_at": null
      }
    ],
    "confirmed_at": "2025-10-06 02:34:21",
    "cancelled_at": null,
    "delivered_at": null,
    "created_at": "2025-10-06 02:25:03",
    "updated_at": "2025-10-06 02:34:21"
  }
}
```

**Respostas de Erro**:

**404 - Pedido não encontrado**:
```json
{
  "error": "OrderNotFound",
  "message": "Order not found with ID: 526d715b-13a4-4328-8935-b78d63cfc9ef"
}
```

---

### 7. Adicionar Item ao Pedido

#### `POST /v1/orders/{id}/items`

Adiciona um item ao pedido. O produto é buscado automaticamente no **Inventory Service**.

**Autenticação**: Requerida (JWT)

**⚠️ Importante**: O pedido deve estar em status `draft` para adicionar itens.

**Path Parameters**:
| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | UUID | ID do pedido |

**Body Parameters**:
```json
{
  "product_id": "0eb5e387-d850-442e-8c8f-80f4fcec287f",
  "quantity": 2,
  "discount": 5.00
}
```

**Validações**:
- `product_id`: obrigatório, UUID válido, produto deve existir no Inventory
- `quantity`: obrigatório, inteiro >= 1
- `discount`: opcional, decimal >= 0

**Exemplo de Requisição**:
```bash
curl -X POST http://localhost:9003/api/v1/orders/526d715b-13a4-4328-8935-b78d63cfc9ef/items \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "product_id": "0eb5e387-d850-442e-8c8f-80f4fcec287f",
    "quantity": 2,
    "discount": 5.00
  }'
```

**Resposta de Sucesso (200)**:
```json
{
  "message": "Item added to order successfully",
  "data": {
    "id": "526d715b-13a4-4328-8935-b78d63cfc9ef",
    "order_number": "ORD-2025-0002",
    "customer_id": "70fca607-4b7b-45d8-b8e8-99eeaca2ae82",
    "status": "draft",
    "subtotal": 3999.98,
    "discount": 5.00,
    "total": 3994.98,
    "payment_status": "pending",
    "payment_method": null,
    "notes": "Pedido via API",
    "items_count": 1,
    "items": [
      {
        "id": "abc123...",
        "product_id": "0eb5e387-d850-442e-8c8f-80f4fcec287f",
        "product_name": "Notebook Dell Inspiron",
        "sku": "NOTE-DELL-001",
        "quantity": 2,
        "unit_price": 1999.99,
        "subtotal": 3999.98,
        "discount": 5.00,
        "total": 3994.98,
        "created_at": "2025-10-06 02:30:00",
        "updated_at": null
      }
    ],
    "confirmed_at": null,
    "cancelled_at": null,
    "delivered_at": null,
    "created_at": "2025-10-06 02:25:03",
    "updated_at": "2025-10-06 02:30:00"
  }
}
```

**Respostas de Erro**:

**404 - Produto não encontrado**:
```json
{
  "error": "ProductNotFound",
  "message": "Product not found in Inventory Service: 0eb5e387-d850-442e-8c8f-80f4fcec287f"
}
```

**422 - Pedido não está em draft**:
```json
{
  "error": "DomainError",
  "message": "Cannot add items to a non-draft order"
}
```

**422 - Produto já existe no pedido**:
```json
{
  "error": "DomainError",
  "message": "Product already exists in the order. Update quantity instead."
}
```

---

### 8. Confirmar Pedido

#### `POST /v1/orders/{id}/confirm`

Confirma o pedido, mudando seu status de `draft` para `pending`.

**Autenticação**: Requerida (JWT)

**⚠️ Importante**: 
- O pedido deve estar em status `draft`
- O pedido deve ter pelo menos 1 item

**Path Parameters**:
| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | UUID | ID do pedido |

**Exemplo de Requisição**:
```bash
curl -X POST http://localhost:9003/api/v1/orders/526d715b-13a4-4328-8935-b78d63cfc9ef/confirm \
  -H "Authorization: Bearer {token}"
```

**Resposta de Sucesso (200)**:
```json
{
  "message": "Order confirmed successfully",
  "data": {
    "id": "526d715b-13a4-4328-8935-b78d63cfc9ef",
    "order_number": "ORD-2025-0002",
    "customer_id": "70fca607-4b7b-45d8-b8e8-99eeaca2ae82",
    "status": "confirmed",
    "subtotal": 3999.98,
    "discount": 5.00,
    "total": 3994.98,
    "payment_status": "pending",
    "payment_method": null,
    "notes": "Pedido via API",
    "items_count": 1,
    "items": [...],
    "confirmed_at": "2025-10-06 02:34:21",
    "cancelled_at": null,
    "delivered_at": null,
    "created_at": "2025-10-06 02:25:03",
    "updated_at": "2025-10-06 02:34:21"
  }
}
```

**Respostas de Erro**:

**422 - Pedido não está em draft**:
```json
{
  "error": "DomainError",
  "message": "Only draft orders can be confirmed."
}
```

**422 - Pedido vazio**:
```json
{
  "error": "DomainError",
  "message": "Cannot confirm an empty order."
}
```

---

### 9. Cancelar Pedido

#### `POST /v1/orders/{id}/cancel`

Cancela um pedido, mudando seu status para `cancelled`.

**Autenticação**: Requerida (JWT)

**⚠️ Importante**: Não é possível cancelar pedidos já cancelados ou entregues.

**Path Parameters**:
| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | UUID | ID do pedido |

**Body Parameters**:
```json
{
  "reason": "Cliente desistiu da compra"
}
```

**Validações**:
- `reason`: opcional, texto livre (máx. 500 caracteres)

**Exemplo de Requisição**:
```bash
curl -X POST http://localhost:9003/api/v1/orders/352e52e8-851a-44c9-bdcc-37b03ab72931/cancel \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "reason": "Cliente desistiu da compra"
  }'
```

**Resposta de Sucesso (200)**:
```json
{
  "message": "Order cancelled successfully",
  "data": {
    "id": "352e52e8-851a-44c9-bdcc-37b03ab72931",
    "order_number": "ORD-2025-0003",
    "customer_id": "70fca607-4b7b-45d8-b8e8-99eeaca2ae82",
    "status": "cancelled",
    "subtotal": 0,
    "discount": 0,
    "total": 0,
    "payment_status": "pending",
    "payment_method": null,
    "notes": "Pedido para cancelar\nCancellation Reason: Cliente desistiu da compra",
    "items_count": 0,
    "items": [],
    "confirmed_at": null,
    "cancelled_at": "2025-10-06 02:34:21",
    "delivered_at": null,
    "created_at": "2025-10-06 02:34:20",
    "updated_at": "2025-10-06 02:34:21"
  }
}
```

**Respostas de Erro**:

**422 - Pedido já cancelado ou entregue**:
```json
{
  "error": "DomainError",
  "message": "Cannot cancel an already cancelled or delivered order."
}
```

---

## Fluxo Completo de Venda

### Exemplo: Criando uma venda do início ao fim

```bash
# 1. Login no Auth Service
TOKEN=$(curl -s -X POST http://localhost:9001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"senha123"}' | jq -r '.data.access_token')

# 2. Criar Cliente
CUSTOMER=$(curl -s -X POST http://localhost:9003/api/v1/customers \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "name": "João Silva",
    "email": "joao@example.com",
    "phone": "11987654321",
    "document": "11144477735",
    "address_street": "Rua ABC",
    "address_number": "100",
    "address_city": "São Paulo",
    "address_state": "SP",
    "address_zip_code": "01234567"
  }')

CUSTOMER_ID=$(echo $CUSTOMER | jq -r '.data.id')

# 3. Criar Pedido (draft)
ORDER=$(curl -s -X POST http://localhost:9003/api/v1/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d "{\"customer_id\": \"$CUSTOMER_ID\", \"notes\": \"Pedido urgente\"}")

ORDER_ID=$(echo $ORDER | jq -r '.data.id')

# 4. Buscar produto no Inventory
PRODUCT_ID=$(curl -s http://localhost:9002/api/v1/products | jq -r '.data[0].id')

# 5. Adicionar item ao pedido
curl -s -X POST "http://localhost:9003/api/v1/orders/$ORDER_ID/items" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d "{\"product_id\": \"$PRODUCT_ID\", \"quantity\": 2, \"discount\": 10.00}"

# 6. Confirmar pedido
curl -s -X POST "http://localhost:9003/api/v1/orders/$ORDER_ID/confirm" \
  -H "Authorization: Bearer $TOKEN"

# 7. Consultar pedido final
curl -s -X GET "http://localhost:9003/api/v1/orders/$ORDER_ID" \
  -H "Authorization: Bearer $TOKEN" | jq
```

---

## Códigos de Status HTTP

| Código | Descrição |
|--------|-----------|
| `200` | Sucesso |
| `201` | Recurso criado com sucesso |
| `400` | Requisição inválida |
| `401` | Não autenticado |
| `404` | Recurso não encontrado |
| `409` | Conflito (email/documento duplicado) |
| `422` | Erro de validação ou domínio |
| `500` | Erro interno do servidor |

---

## Validações de Domínio

### CPF e CNPJ

O sistema valida automaticamente CPF e CNPJ brasileiros:

**CPF**:
- 11 dígitos numéricos
- Dígitos verificadores válidos
- Não aceita sequências (111.111.111-11, etc)

**CNPJ**:
- 14 dígitos numéricos
- Dígitos verificadores válidos

**Formatos aceitos**:
- Apenas números: `11144477735` ou `12345678000195`
- Com formatação: `111.444.777-35` ou `12.345.678/0001-95`

---

## Notas Importantes

### Integração com Inventory Service

- Ao adicionar um item ao pedido via `POST /v1/orders/{id}/items`, o sistema busca automaticamente as informações do produto no **Inventory Service**
- São copiados: nome, SKU e preço (snapshot no momento da venda)
- Se o produto não existir no Inventory, retorna erro 404

### OrderNumber (Número do Pedido)

- Gerado automaticamente no formato `ORD-YYYY-NNNN`
- `YYYY`: Ano atual
- `NNNN`: Sequencial incremental (reinicia a cada ano)
- Exemplo: `ORD-2025-0001`, `ORD-2025-0002`, etc.

### Cálculo de Totais

- `subtotal` = soma de (unit_price × quantity) de todos os itens
- `total` = subtotal - discount total
- Recalculado automaticamente ao adicionar/remover itens

---

## Suporte

Para mais informações ou suporte:
- **Documentação do Projeto**: `/docs`
- **Health Check**: `GET /health`
- **Postman Collection**: Disponível em `/postman-collection.json`