# 🏦 Sprint 6: Financial Service - Plano de Implementação

**Início:** 07/10/2025  
**Duração Estimada:** 3-5 dias  
**Status:** 📋 Planejamento

---

## 🎯 Objetivo

Implementar um microserviço completo de gestão financeira com:
- Contas a pagar e receber
- Fluxo de caixa
- Categorização de transações
- Relatórios financeiros
- Integração com Sales Service

---

## 📊 Escopo Funcional

### Core Features

#### 1. **Contas a Pagar** (Accounts Payable)
- ✅ Cadastro de fornecedores
- ✅ Registro de contas a pagar
- ✅ Agendamento de pagamentos
- ✅ Baixa de pagamentos (total/parcial)
- ✅ Controle de vencimentos
- ✅ Multas e juros automáticos

#### 2. **Contas a Receber** (Accounts Receivable)
- ✅ Registro de contas a receber
- ✅ Integração automática com vendas (Sales Service)
- ✅ Baixa de recebimentos (total/parcial)
- ✅ Controle de inadimplência
- ✅ Descontos por antecipação

#### 3. **Fluxo de Caixa** (Cash Flow)
- ✅ Saldo atual
- ✅ Projeções futuras (30/60/90 dias)
- ✅ Entradas vs Saídas
- ✅ Gráficos e dashboards

#### 4. **Categorias Financeiras**
- ✅ Categorias de receita
- ✅ Categorias de despesa
- ✅ Centro de custos
- ✅ Hierarquia de categorias

#### 5. **Relatórios**
- ✅ DRE (Demonstração do Resultado do Exercício)
- ✅ Fluxo de caixa consolidado
- ✅ Análise por categoria
- ✅ Contas vencidas
- ✅ Projeções

---

## 🏗️ Arquitetura

### Domain Layer (Camada de Domínio)

#### Entities (Entidades)

```
Supplier (Fornecedor)
├── id: SupplierId
├── name: SupplierName
├── document: Document (CPF/CNPJ)
├── email: Email
├── phone: Phone
├── address: Address
├── status: SupplierStatus (active/inactive)
└── paymentTerms: PaymentTerms

AccountPayable (Conta a Pagar)
├── id: AccountPayableId
├── supplierId: SupplierId
├── description: string
├── category: CategoryId
├── dueDate: Date
├── amount: Money
├── paidAmount: Money
├── status: PaymentStatus (pending/partial/paid/overdue/cancelled)
├── paymentDate: Date?
├── fine: Money?
├── interest: Money?
└── notes: string?

AccountReceivable (Conta a Receber)
├── id: AccountReceivableId
├── customerId: CustomerId
├── orderId: OrderId? (integração com Sales)
├── description: string
├── category: CategoryId
├── dueDate: Date
├── amount: Money
├── receivedAmount: Money
├── status: ReceivableStatus (pending/partial/received/overdue/cancelled)
├── receivedDate: Date?
├── discount: Money?
└── notes: string?

Category (Categoria Financeira)
├── id: CategoryId
├── name: string
├── type: CategoryType (income/expense)
├── parentId: CategoryId? (hierarquia)
├── color: string
└── status: CategoryStatus

Transaction (Transação)
├── id: TransactionId
├── type: TransactionType (payable/receivable)
├── referenceId: string (AccountPayableId ou AccountReceivableId)
├── amount: Money
├── date: Date
├── categoryId: CategoryId
└── description: string
```

#### Value Objects

```
- Money (valor monetário com precisão decimal)
- SupplierId (UUID)
- AccountPayableId (UUID)
- AccountReceivableId (UUID)
- CategoryId (UUID)
- TransactionId (UUID)
- SupplierName (validação de nome)
- PaymentTerms (prazo de pagamento)
- PaymentStatus (enum)
- ReceivableStatus (enum)
- CategoryType (enum)
```

#### Domain Events

```
- SupplierCreated
- AccountPayableCreated
- AccountPayablePaid
- AccountPayableOverdue
- AccountReceivableCreated
- AccountReceivableReceived
- AccountReceivableOverdue
- TransactionCreated
- CategoryCreated
```

---

### Application Layer (Camada de Aplicação)

#### Use Cases

**Suppliers:**
- CreateSupplier
- UpdateSupplier
- GetSupplier
- ListSuppliers
- DeactivateSupplier

**Accounts Payable:**
- CreateAccountPayable
- PayAccountPayable (total/parcial)
- CancelAccountPayable
- GetAccountPayable
- ListAccountsPayable
- GetOverduePayables

**Accounts Receivable:**
- CreateAccountReceivable (manual + automático via evento)
- ReceiveAccountReceivable (total/parcial)
- CancelAccountReceivable
- GetAccountReceivable
- ListAccountsReceivable
- GetOverdueReceivables

**Categories:**
- CreateCategory
- UpdateCategory
- GetCategory
- ListCategories
- DeactivateCategory

**Reports:**
- GetCashFlow (projeção)
- GetCashFlowReport (histórico)
- GetDRE (Demonstração do Resultado)
- GetCategoryReport
- GetOverdueReport

---

### Infrastructure Layer

#### Repositories (PostgreSQL)
- EloquentSupplierRepository
- EloquentAccountPayableRepository
- EloquentAccountReceivableRepository
- EloquentCategoryRepository
- EloquentTransactionRepository

#### Messaging (RabbitMQ)
- **Consume:** `order.confirmed` (do Sales Service)
- **Publish:** `payment.completed`, `payment.overdue`

#### External Integrations
- Sales Service (HTTP) - Buscar dados de pedidos

---

### Presentation Layer

#### REST API Endpoints

```
# Health & Metrics
GET    /health
GET    /metrics

# Suppliers
GET    /api/v1/suppliers
POST   /api/v1/suppliers
GET    /api/v1/suppliers/{id}
PUT    /api/v1/suppliers/{id}
DELETE /api/v1/suppliers/{id}

# Accounts Payable
GET    /api/v1/accounts-payable
POST   /api/v1/accounts-payable
GET    /api/v1/accounts-payable/{id}
POST   /api/v1/accounts-payable/{id}/pay
POST   /api/v1/accounts-payable/{id}/cancel
GET    /api/v1/accounts-payable/overdue

# Accounts Receivable
GET    /api/v1/accounts-receivable
POST   /api/v1/accounts-receivable
GET    /api/v1/accounts-receivable/{id}
POST   /api/v1/accounts-receivable/{id}/receive
POST   /api/v1/accounts-receivable/{id}/cancel
GET    /api/v1/accounts-receivable/overdue

# Categories
GET    /api/v1/categories
POST   /api/v1/categories
GET    /api/v1/categories/{id}
PUT    /api/v1/categories/{id}
DELETE /api/v1/categories/{id}

# Reports
GET    /api/v1/reports/cash-flow
GET    /api/v1/reports/dre
GET    /api/v1/reports/categories
GET    /api/v1/reports/overdue
```

---

## 🗄️ Database Schema

### Tables

```sql
-- Fornecedores
CREATE TABLE suppliers (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    document VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    address_street VARCHAR(255),
    address_number VARCHAR(20),
    address_complement VARCHAR(100),
    address_city VARCHAR(100),
    address_state VARCHAR(2),
    address_zip_code VARCHAR(10),
    payment_terms INTEGER DEFAULT 30,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP
);

-- Categorias
CREATE TABLE categories (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(20) NOT NULL, -- income/expense
    parent_id UUID REFERENCES categories(id),
    color VARCHAR(7),
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP
);

-- Contas a Pagar
CREATE TABLE accounts_payable (
    id UUID PRIMARY KEY,
    supplier_id UUID REFERENCES suppliers(id),
    category_id UUID REFERENCES categories(id),
    description TEXT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    paid_amount DECIMAL(15,2) DEFAULT 0,
    due_date DATE NOT NULL,
    payment_date DATE,
    status VARCHAR(20) DEFAULT 'pending',
    fine DECIMAL(15,2) DEFAULT 0,
    interest DECIMAL(15,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP
);

-- Contas a Receber
CREATE TABLE accounts_receivable (
    id UUID PRIMARY KEY,
    customer_id UUID NOT NULL, -- referência ao Sales Service
    order_id UUID, -- referência ao Sales Service
    category_id UUID REFERENCES categories(id),
    description TEXT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    received_amount DECIMAL(15,2) DEFAULT 0,
    due_date DATE NOT NULL,
    received_date DATE,
    status VARCHAR(20) DEFAULT 'pending',
    discount DECIMAL(15,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP
);

-- Transações (histórico de movimentações)
CREATE TABLE transactions (
    id UUID PRIMARY KEY,
    type VARCHAR(20) NOT NULL, -- payable/receivable
    reference_id UUID NOT NULL,
    category_id UUID REFERENCES categories(id),
    amount DECIMAL(15,2) NOT NULL,
    transaction_date DATE NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL
);

-- Índices
CREATE INDEX idx_accounts_payable_supplier ON accounts_payable(supplier_id);
CREATE INDEX idx_accounts_payable_due_date ON accounts_payable(due_date);
CREATE INDEX idx_accounts_payable_status ON accounts_payable(status);
CREATE INDEX idx_accounts_receivable_customer ON accounts_receivable(customer_id);
CREATE INDEX idx_accounts_receivable_order ON accounts_receivable(order_id);
CREATE INDEX idx_accounts_receivable_due_date ON accounts_receivable(due_date);
CREATE INDEX idx_accounts_receivable_status ON accounts_receivable(status);
CREATE INDEX idx_transactions_date ON transactions(transaction_date);
CREATE INDEX idx_transactions_type ON transactions(type);
CREATE INDEX idx_categories_type ON categories(type);
```

---

## 🔄 Event-Driven Integration

### Consumir Eventos

**De: Sales Service**
```
Event: order.confirmed
Payload: {
  order_id: UUID,
  customer_id: UUID,
  total: Money,
  payment_method: string
}

Action:
→ Criar AccountReceivable automaticamente
→ Atualizar fluxo de caixa
```

### Publicar Eventos

**Para: Outros Serviços**
```
Event: payment.completed
Payload: {
  account_id: UUID,
  type: "payable" | "receivable",
  amount: Money,
  date: Date
}

Event: payment.overdue
Payload: {
  account_id: UUID,
  type: "payable" | "receivable",
  days_overdue: int
}
```

---

## 📊 Métricas de Negócio

```
financial_accounts_payable_created_total     - Contas a pagar criadas
financial_accounts_payable_paid_total        - Contas pagas
financial_accounts_payable_overdue           - Contas vencidas (gauge)
financial_accounts_receivable_created_total  - Contas a receber criadas
financial_accounts_receivable_received_total - Contas recebidas
financial_accounts_receivable_overdue        - Contas vencidas (gauge)
financial_cash_balance                       - Saldo em caixa (gauge)
financial_suppliers_created_total            - Fornecedores cadastrados
financial_categories_created_total           - Categorias criadas
```

---

## 🧪 Testing Strategy

### Unit Tests (50+ tests)
- Value Objects validation
- Entity business rules
- Use Cases logic

### Integration Tests (30+ tests)
- Repository operations
- Database transactions
- Event publishing/consuming

### Feature Tests (20+ tests)
- API endpoints
- Authentication
- Validation

### Target Coverage: 85%+

---

## 🚀 Roadmap de Implementação

### Fase 1: Setup & Domain (Dia 1)
- [ ] Criar estrutura Laravel
- [ ] Configurar Docker
- [ ] Implementar Value Objects
- [ ] Implementar Entities
- [ ] Testes unitários do Domain

### Fase 2: Application Layer (Dia 2)
- [ ] Implementar DTOs
- [ ] Implementar Use Cases (Suppliers)
- [ ] Implementar Use Cases (Accounts Payable)
- [ ] Implementar Use Cases (Accounts Receivable)
- [ ] Implementar Use Cases (Categories)
- [ ] Testes de Use Cases

### Fase 3: Infrastructure (Dia 3)
- [ ] Migrations do banco
- [ ] Implementar Repositories (Eloquent)
- [ ] Configurar RabbitMQ consumer
- [ ] Configurar RabbitMQ publisher
- [ ] Testes de integração

### Fase 4: Presentation (Dia 4)
- [ ] Controllers REST
- [ ] Form Requests (validação)
- [ ] Resources (serialização)
- [ ] Routes
- [ ] JWT Middleware
- [ ] Testes de Feature

### Fase 5: Integration & Reports (Dia 4-5)
- [ ] Integração com Sales Service
- [ ] Implementar relatórios
- [ ] Observabilidade (métricas)
- [ ] Documentação da API
- [ ] Postman Collection

### Fase 6: Testing & Validation (Dia 5)
- [ ] Script de validação automatizado
- [ ] Testes end-to-end
- [ ] Performance tests
- [ ] Code Coverage
- [ ] CI/CD

---

## 📚 Regras de Negócio

### Contas a Pagar
1. Não permitir pagamento antes da data de vencimento (opcional)
2. Calcular multa automática após vencimento (2%)
3. Calcular juros por dia de atraso (0,033% ao dia = 1% ao mês)
4. Permitir pagamento parcial
5. Gerar transação para cada pagamento

### Contas a Receber
1. Permitir desconto para pagamento antecipado
2. Marcar como vencida automaticamente
3. Permitir recebimento parcial
4. Integração automática com vendas confirmadas
5. Gerar transação para cada recebimento

### Fluxo de Caixa
1. Calcular saldo atual: (Contas Recebidas - Contas Pagas)
2. Projetar 30/60/90 dias baseado em contas pendentes
3. Considerar apenas contas com status "pending" ou "partial"

---

## 🎨 Dashboard Grafana

Criar painel específico para Financial Service:
- Saldo em caixa (tempo real)
- Contas vencidas (gauge)
- Gráfico de entradas vs saídas (7 dias)
- Projeção de fluxo de caixa
- Top categorias de despesa
- Taxa de inadimplência

---

## 🔐 Segurança

- [ ] Todos os endpoints requerem JWT
- [ ] Validação de permissões por role
- [ ] Audit log de operações financeiras
- [ ] Criptografia de dados sensíveis
- [ ] Rate limiting

---

## 📖 Documentação

- [ ] API-DOCS.md (Swagger-style)
- [ ] README.md do serviço
- [ ] Postman Collection
- [ ] Diagramas de fluxo
- [ ] Exemplos de uso

---

## ✅ Definition of Done

- [ ] Todos os Use Cases implementados
- [ ] Cobertura de testes > 85%
- [ ] Documentação completa
- [ ] API testada via Postman
- [ ] Métricas funcionando no Grafana
- [ ] Eventos RabbitMQ funcionando
- [ ] Script de validação automatizado
- [ ] Code review aprovado
- [ ] Deploy em Docker funcionando

---

**Estimativa Total:** 3-5 dias  
**Prioridade:** Alta  
**Dependências:** Sales Service (para integração de contas a receber)


