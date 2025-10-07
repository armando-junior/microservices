# 🏦 Financial Service - API Documentation

**Version:** 1.0.0  
**Base URL:** `http://localhost:9004`  
**API Prefix:** `/api/v1`

---

## 📋 Table of Contents

- [Overview](#overview)
- [Authentication](#authentication)
- [Health Check](#health-check)
- [Suppliers](#suppliers)
- [Categories](#categories)
- [Accounts Payable](#accounts-payable)
- [Accounts Receivable](#accounts-receivable)
- [Error Handling](#error-handling)

---

## 🎯 Overview

O **Financial Service** é responsável por gerenciar:
- **Fornecedores** (Suppliers)
- **Categorias Financeiras** (Categories)
- **Contas a Pagar** (Accounts Payable)
- **Contas a Receber** (Accounts Receivable)

### Base URLs

- **Local:** `http://localhost:9004`
- **Docker Internal:** `http://financial-service:8000`

---

## 🔐 Authentication

Atualmente o serviço **não requer autenticação JWT** para desenvolvimento local.

> **Nota:** Para produção, recomenda-se integração com o Auth Service para proteção dos endpoints.

---

## ❤️ Health Check

### Check Service Health

**Endpoint:** `GET /health`

Verifica o status do serviço e suas dependências.

#### Request

```bash
curl -X GET http://localhost:9004/health
```

#### Response (200 OK)

```json
{
  "service": "financial-service",
  "status": "healthy",
  "checks": {
    "service": "up",
    "database": "connected"
  },
  "timestamp": "2025-10-07T21:00:00+00:00"
}
```

#### Response (503 Service Unavailable)

```json
{
  "service": "financial-service",
  "status": "unhealthy",
  "checks": {
    "service": "up",
    "database": "disconnected"
  },
  "timestamp": "2025-10-07T21:00:00+00:00"
}
```

---

## 👥 Suppliers

### List Suppliers

**Endpoint:** `GET /api/v1/suppliers`

Lista todos os fornecedores com paginação.

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Número da página |
| `per_page` | integer | No | 15 | Itens por página |

#### Request

```bash
curl -X GET "http://localhost:9004/api/v1/suppliers?page=1&per_page=10"
```

#### Response (200 OK)

```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Fornecedor XYZ Ltda",
      "document": "12345678000190",
      "email": "contato@fornecedor.com",
      "phone": "+55 11 98765-4321",
      "address": "Rua Exemplo, 123 - São Paulo, SP",
      "active": true,
      "created_at": "2025-10-07T10:00:00+00:00",
      "updated_at": "2025-10-07T10:00:00+00:00"
    }
  ],
  "meta": {
    "total": 50,
    "page": 1,
    "per_page": 10
  }
}
```

---

### Get Supplier

**Endpoint:** `GET /api/v1/suppliers/{id}`

Busca um fornecedor específico por ID.

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | uuid | Yes | ID do fornecedor |

#### Request

```bash
curl -X GET http://localhost:9004/api/v1/suppliers/550e8400-e29b-41d4-a716-446655440000
```

#### Response (200 OK)

```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Fornecedor XYZ Ltda",
    "document": "12345678000190",
    "email": "contato@fornecedor.com",
    "phone": "+55 11 98765-4321",
    "address": "Rua Exemplo, 123 - São Paulo, SP",
    "active": true,
    "created_at": "2025-10-07T10:00:00+00:00",
    "updated_at": "2025-10-07T10:00:00+00:00"
  }
}
```

#### Response (404 Not Found)

```json
{
  "error": "SupplierNotFoundException",
  "message": "Supplier not found"
}
```

---

### Create Supplier

**Endpoint:** `POST /api/v1/suppliers`

Cria um novo fornecedor.

#### Request Body

```json
{
  "name": "Fornecedor ABC Ltda",
  "document": "98765432000199",
  "email": "contato@abc.com",
  "phone": "+55 11 91234-5678",
  "address": "Av. Principal, 456 - Rio de Janeiro, RJ"
}
```

#### Field Validations

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name` | string | Yes | min:3, max:150 |
| `document` | string | No | size:14, unique |
| `email` | string | No | valid email, max:100 |
| `phone` | string | No | max:20 |
| `address` | string | No | max:500 |

#### Request

```bash
curl -X POST http://localhost:9004/api/v1/suppliers \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Fornecedor ABC Ltda",
    "document": "98765432000199",
    "email": "contato@abc.com",
    "phone": "+55 11 91234-5678",
    "address": "Av. Principal, 456 - Rio de Janeiro, RJ"
  }'
```

#### Response (201 Created)

```json
{
  "data": {
    "id": "660e9511-f39c-52e5-b827-557766551111",
    "name": "Fornecedor ABC Ltda",
    "document": "98765432000199",
    "email": "contato@abc.com",
    "phone": "+55 11 91234-5678",
    "address": "Av. Principal, 456 - Rio de Janeiro, RJ",
    "active": true,
    "created_at": "2025-10-07T15:30:00+00:00",
    "updated_at": "2025-10-07T15:30:00+00:00"
  },
  "message": "Supplier created successfully"
}
```

#### Response (422 Unprocessable Entity)

```json
{
  "error": "Validation failed",
  "message": "The given data was invalid.",
  "errors": {
    "name": ["Supplier name is required"],
    "document": ["A supplier with this document already exists"]
  }
}
```

---

### Update Supplier

**Endpoint:** `PUT /api/v1/suppliers/{id}`

Atualiza um fornecedor existente.

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | uuid | Yes | ID do fornecedor |

#### Request Body

```json
{
  "name": "Fornecedor ABC Ltda - Atualizado",
  "email": "novo@abc.com",
  "phone": "+55 11 99999-9999",
  "address": "Nova Av. Principal, 789 - Rio de Janeiro, RJ"
}
```

#### Request

```bash
curl -X PUT http://localhost:9004/api/v1/suppliers/660e9511-f39c-52e5-b827-557766551111 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Fornecedor ABC Ltda - Atualizado",
    "email": "novo@abc.com"
  }'
```

#### Response (200 OK)

```json
{
  "data": {
    "id": "660e9511-f39c-52e5-b827-557766551111",
    "name": "Fornecedor ABC Ltda - Atualizado",
    "document": "98765432000199",
    "email": "novo@abc.com",
    "phone": "+55 11 91234-5678",
    "address": "Av. Principal, 456 - Rio de Janeiro, RJ",
    "active": true,
    "created_at": "2025-10-07T15:30:00+00:00",
    "updated_at": "2025-10-07T16:45:00+00:00"
  },
  "message": "Supplier updated successfully"
}
```

---

## 📂 Categories

### List Categories

**Endpoint:** `GET /api/v1/categories`

Lista todas as categorias financeiras.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `type` | string | No | Filtrar por tipo: `income` ou `expense` |

#### Request

```bash
curl -X GET "http://localhost:9004/api/v1/categories?type=expense"
```

#### Response (200 OK)

```json
{
  "data": [
    {
      "id": "770e9622-g49d-63f6-c938-668877662222",
      "name": "Fornecedores",
      "description": "Pagamentos a fornecedores",
      "type": "expense",
      "created_at": "2025-10-07T10:00:00+00:00",
      "updated_at": "2025-10-07T10:00:00+00:00"
    },
    {
      "id": "880e9733-h59e-74g7-d049-779988773333",
      "name": "Salários",
      "description": "Folha de pagamento",
      "type": "expense",
      "created_at": "2025-10-07T10:00:00+00:00",
      "updated_at": "2025-10-07T10:00:00+00:00"
    }
  ]
}
```

---

### Create Category

**Endpoint:** `POST /api/v1/categories`

Cria uma nova categoria financeira.

#### Request Body

```json
{
  "name": "Aluguel",
  "type": "expense",
  "description": "Pagamento de aluguel mensal"
}
```

#### Field Validations

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name` | string | Yes | min:3, max:100 |
| `type` | string | Yes | in:income,expense |
| `description` | string | No | max:500 |

#### Request

```bash
curl -X POST http://localhost:9004/api/v1/categories \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Aluguel",
    "type": "expense",
    "description": "Pagamento de aluguel mensal"
  }'
```

#### Response (201 Created)

```json
{
  "data": {
    "id": "990e9844-i69f-85h8-e150-880099884444",
    "name": "Aluguel",
    "description": "Pagamento de aluguel mensal",
    "type": "expense",
    "created_at": "2025-10-07T16:00:00+00:00",
    "updated_at": "2025-10-07T16:00:00+00:00"
  },
  "message": "Category created successfully"
}
```

---

### Update Category

**Endpoint:** `PUT /api/v1/categories/{id}`

Atualiza uma categoria existente.

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | uuid | Yes | ID da categoria |

#### Request Body

```json
{
  "name": "Aluguel e Condomínio",
  "description": "Pagamento de aluguel e condomínio mensal"
}
```

#### Request

```bash
curl -X PUT http://localhost:9004/api/v1/categories/990e9844-i69f-85h8-e150-880099884444 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Aluguel e Condomínio",
    "description": "Pagamento de aluguel e condomínio mensal"
  }'
```

#### Response (200 OK)

```json
{
  "data": {
    "id": "990e9844-i69f-85h8-e150-880099884444",
    "name": "Aluguel e Condomínio",
    "description": "Pagamento de aluguel e condomínio mensal",
    "type": "expense",
    "created_at": "2025-10-07T16:00:00+00:00",
    "updated_at": "2025-10-07T17:00:00+00:00"
  },
  "message": "Category updated successfully"
}
```

---

## 💳 Accounts Payable

### List Accounts Payable

**Endpoint:** `GET /api/v1/accounts-payable`

Lista contas a pagar com paginação e filtros.

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Número da página |
| `per_page` | integer | No | 15 | Itens por página |
| `status` | string | No | - | Filtrar por status: `pending`, `paid`, `overdue`, `cancelled` |
| `supplier_id` | uuid | No | - | Filtrar por fornecedor |
| `due_date_from` | date | No | - | Data de vencimento inicial (Y-m-d) |
| `due_date_to` | date | No | - | Data de vencimento final (Y-m-d) |

#### Request

```bash
curl -X GET "http://localhost:9004/api/v1/accounts-payable?status=pending&page=1&per_page=10"
```

#### Response (200 OK)

```json
{
  "data": [
    {
      "id": "aa0e9955-j79g-96i9-f261-991100995555",
      "supplier_id": "550e8400-e29b-41d4-a716-446655440000",
      "category_id": "770e9622-g49d-63f6-c938-668877662222",
      "description": "Pagamento de fornecedor - Pedido #1234",
      "amount": "15000.00",
      "issue_date": "2025-10-01",
      "due_date": "2025-10-30",
      "status": "pending",
      "paid_at": null,
      "payment_notes": null,
      "created_at": "2025-10-01T10:00:00+00:00",
      "updated_at": "2025-10-01T10:00:00+00:00"
    }
  ],
  "meta": {
    "total": 25,
    "page": 1,
    "per_page": 10
  }
}
```

---

### Create Account Payable

**Endpoint:** `POST /api/v1/accounts-payable`

Cria uma nova conta a pagar.

#### Request Body

```json
{
  "supplier_id": "550e8400-e29b-41d4-a716-446655440000",
  "category_id": "770e9622-g49d-63f6-c938-668877662222",
  "description": "Pagamento de fornecedor - Pedido #5678",
  "amount": 25000.50,
  "issue_date": "2025-10-07",
  "payment_terms_days": 30
}
```

#### Field Validations

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `supplier_id` | uuid | Yes | exists:suppliers |
| `category_id` | uuid | Yes | exists:categories |
| `description` | string | Yes | max:255 |
| `amount` | numeric | Yes | min:0.01 |
| `issue_date` | date | Yes | format:Y-m-d |
| `payment_terms_days` | integer | Yes | min:0, max:365 |

#### Request

```bash
curl -X POST http://localhost:9004/api/v1/accounts-payable \
  -H "Content-Type: application/json" \
  -d '{
    "supplier_id": "550e8400-e29b-41d4-a716-446655440000",
    "category_id": "770e9622-g49d-63f6-c938-668877662222",
    "description": "Pagamento de fornecedor - Pedido #5678",
    "amount": 25000.50,
    "issue_date": "2025-10-07",
    "payment_terms_days": 30
  }'
```

#### Response (201 Created)

```json
{
  "data": {
    "id": "bb0e9066-k89h-07j0-g372-002211006666",
    "supplier_id": "550e8400-e29b-41d4-a716-446655440000",
    "category_id": "770e9622-g49d-63f6-c938-668877662222",
    "description": "Pagamento de fornecedor - Pedido #5678",
    "amount": "25000.50",
    "issue_date": "2025-10-07",
    "due_date": "2025-11-06",
    "status": "pending",
    "paid_at": null,
    "payment_notes": null,
    "created_at": "2025-10-07T18:00:00+00:00",
    "updated_at": "2025-10-07T18:00:00+00:00"
  },
  "message": "Account payable created successfully"
}
```

---

### Pay Account Payable

**Endpoint:** `POST /api/v1/accounts-payable/{id}/pay`

Registra o pagamento de uma conta a pagar.

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | uuid | Yes | ID da conta a pagar |

#### Request Body

```json
{
  "notes": "Pagamento realizado via transferência bancária"
}
```

#### Request

```bash
curl -X POST http://localhost:9004/api/v1/accounts-payable/bb0e9066-k89h-07j0-g372-002211006666/pay \
  -H "Content-Type: application/json" \
  -d '{
    "notes": "Pagamento realizado via transferência bancária"
  }'
```

#### Response (200 OK)

```json
{
  "data": {
    "id": "bb0e9066-k89h-07j0-g372-002211006666",
    "supplier_id": "550e8400-e29b-41d4-a716-446655440000",
    "category_id": "770e9622-g49d-63f6-c938-668877662222",
    "description": "Pagamento de fornecedor - Pedido #5678",
    "amount": "25000.50",
    "issue_date": "2025-10-07",
    "due_date": "2025-11-06",
    "status": "paid",
    "paid_at": "2025-10-15T10:30:00+00:00",
    "payment_notes": "Pagamento realizado via transferência bancária",
    "created_at": "2025-10-07T18:00:00+00:00",
    "updated_at": "2025-10-15T10:30:00+00:00"
  },
  "message": "Account payable paid successfully"
}
```

---

## 💰 Accounts Receivable

### List Accounts Receivable

**Endpoint:** `GET /api/v1/accounts-receivable`

Lista contas a receber com paginação e filtros.

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Número da página |
| `per_page` | integer | No | 15 | Itens por página |
| `status` | string | No | - | Filtrar por status: `pending`, `received`, `overdue`, `cancelled` |
| `customer_id` | uuid | No | - | Filtrar por cliente |
| `due_date_from` | date | No | - | Data de vencimento inicial (Y-m-d) |
| `due_date_to` | date | No | - | Data de vencimento final (Y-m-d) |

#### Request

```bash
curl -X GET "http://localhost:9004/api/v1/accounts-receivable?status=pending"
```

#### Response (200 OK)

```json
{
  "data": [
    {
      "id": "cc0e9177-l99i-18k1-h483-113322117777",
      "customer_id": "dd0e9288-m00j-29l2-i594-224433228888",
      "category_id": "ee0e9399-n11k-30m3-j605-335544339999",
      "description": "Venda de produtos - Nota Fiscal #9876",
      "amount": "35000.00",
      "issue_date": "2025-10-05",
      "due_date": "2025-11-05",
      "status": "pending",
      "received_at": null,
      "receiving_notes": null,
      "created_at": "2025-10-05T14:00:00+00:00",
      "updated_at": "2025-10-05T14:00:00+00:00"
    }
  ],
  "meta": {
    "total": 15,
    "page": 1,
    "per_page": 15
  }
}
```

---

### Create Account Receivable

**Endpoint:** `POST /api/v1/accounts-receivable`

Cria uma nova conta a receber.

#### Request Body

```json
{
  "customer_id": "dd0e9288-m00j-29l2-i594-224433228888",
  "category_id": "ee0e9399-n11k-30m3-j605-335544339999",
  "description": "Venda de produtos - Nota Fiscal #1111",
  "amount": 18500.75,
  "issue_date": "2025-10-07",
  "payment_terms_days": 30
}
```

#### Field Validations

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `customer_id` | uuid | Yes | valid uuid |
| `category_id` | uuid | Yes | exists:categories |
| `description` | string | Yes | max:255 |
| `amount` | numeric | Yes | min:0.01 |
| `issue_date` | date | Yes | format:Y-m-d |
| `payment_terms_days` | integer | Yes | min:0, max:365 |

#### Request

```bash
curl -X POST http://localhost:9004/api/v1/accounts-receivable \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": "dd0e9288-m00j-29l2-i594-224433228888",
    "category_id": "ee0e9399-n11k-30m3-j605-335544339999",
    "description": "Venda de produtos - Nota Fiscal #1111",
    "amount": 18500.75,
    "issue_date": "2025-10-07",
    "payment_terms_days": 30
  }'
```

#### Response (201 Created)

```json
{
  "data": {
    "id": "ff0e9400-o22l-41n4-k716-446655440000",
    "customer_id": "dd0e9288-m00j-29l2-i594-224433228888",
    "category_id": "ee0e9399-n11k-30m3-j605-335544339999",
    "description": "Venda de produtos - Nota Fiscal #1111",
    "amount": "18500.75",
    "issue_date": "2025-10-07",
    "due_date": "2025-11-06",
    "status": "pending",
    "received_at": null,
    "receiving_notes": null,
    "created_at": "2025-10-07T19:00:00+00:00",
    "updated_at": "2025-10-07T19:00:00+00:00"
  },
  "message": "Account receivable created successfully"
}
```

---

### Receive Account Receivable

**Endpoint:** `POST /api/v1/accounts-receivable/{id}/receive`

Registra o recebimento de uma conta a receber.

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | uuid | Yes | ID da conta a receber |

#### Request Body

```json
{
  "notes": "Recebido via PIX"
}
```

#### Request

```bash
curl -X POST http://localhost:9004/api/v1/accounts-receivable/ff0e9400-o22l-41n4-k716-446655440000/receive \
  -H "Content-Type: application/json" \
  -d '{
    "notes": "Recebido via PIX"
  }'
```

#### Response (200 OK)

```json
{
  "data": {
    "id": "ff0e9400-o22l-41n4-k716-446655440000",
    "customer_id": "dd0e9288-m00j-29l2-i594-224433228888",
    "category_id": "ee0e9399-n11k-30m3-j605-335544339999",
    "description": "Venda de produtos - Nota Fiscal #1111",
    "amount": "18500.75",
    "issue_date": "2025-10-07",
    "due_date": "2025-11-06",
    "status": "received",
    "received_at": "2025-10-20T11:15:00+00:00",
    "receiving_notes": "Recebido via PIX",
    "created_at": "2025-10-07T19:00:00+00:00",
    "updated_at": "2025-10-20T11:15:00+00:00"
  },
  "message": "Account receivable received successfully"
}
```

---

## ❌ Error Handling

### Standard Error Response

Todos os erros seguem o formato padrão:

```json
{
  "error": "ErrorType",
  "message": "Error description"
}
```

### HTTP Status Codes

| Status Code | Description |
|-------------|-------------|
| `200` | OK - Request successful |
| `201` | Created - Resource created successfully |
| `400` | Bad Request - Invalid input data |
| `404` | Not Found - Resource not found |
| `409` | Conflict - Resource already exists |
| `422` | Unprocessable Entity - Validation failed |
| `500` | Internal Server Error |
| `503` | Service Unavailable |

### Common Error Types

#### 404 - Resource Not Found

```json
{
  "error": "SupplierNotFoundException",
  "message": "Supplier not found"
}
```

#### 409 - Conflict

```json
{
  "error": "SupplierAlreadyExistsException",
  "message": "A supplier with this identifier already exists"
}
```

#### 422 - Validation Error

```json
{
  "error": "Validation failed",
  "message": "The given data was invalid.",
  "errors": {
    "name": ["Supplier name is required"],
    "amount": ["Amount must be greater than zero"]
  }
}
```

#### 400 - Bad Request

```json
{
  "error": "InvalidSupplierException",
  "message": "Supplier data is invalid"
}
```

---

## 📊 Status Enums

### Payment Status (Accounts Payable)

| Status | Description |
|--------|-------------|
| `pending` | Aguardando pagamento |
| `paid` | Pago |
| `overdue` | Vencido |
| `cancelled` | Cancelado |

### Receivable Status (Accounts Receivable)

| Status | Description |
|--------|-------------|
| `pending` | Aguardando recebimento |
| `received` | Recebido |
| `overdue` | Vencido |
| `cancelled` | Cancelado |

### Category Type

| Type | Description |
|------|-------------|
| `income` | Receita |
| `expense` | Despesa |

---

## 🔗 Related Services

- **Auth Service** (Port 9001) - Autenticação e autorização
- **Inventory Service** (Port 9002) - Gestão de produtos
- **Sales Service** (Port 9003) - Gestão de pedidos e vendas

---

## 📝 Notes

- Todos os valores monetários são retornados como strings para precisão decimal
- Todas as datas seguem o formato ISO 8601
- IDs são UUIDs v4
- O serviço publica eventos no RabbitMQ para cada operação importante

---

**Version:** 1.0.0  
**Last Updated:** 2025-10-07  
**Maintainer:** Development Team
