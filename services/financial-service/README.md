# 🏦 Financial Service

**Status:** 🚧 Em Desenvolvimento (Sprint 6 - Fase 1: Domain Layer)

Microserviço de gestão financeira do sistema ERP, responsável por:
- Contas a pagar e receber
- Fluxo de caixa
- Categorização financeira
- Relatórios financeiros

---

## 📊 Funcionalidades

### Em Desenvolvimento (Fase 1 - Domain Layer)
- [x] Value Objects
- [x] Entities
- [x] Domain Events
- [x] Domain Exceptions
- [ ] Unit Tests

### Planejado
- [ ] Application Layer (Use Cases, DTOs)
- [ ] Infrastructure Layer (Repositories, RabbitMQ)
- [ ] Presentation Layer (Controllers, Routes)
- [ ] Integration & Reports

---

## 🏗️ Arquitetura

### Domain Layer

**Value Objects:**
- `Money` - Valor monetário com precisão decimal
- `SupplierId` - Identificador único de fornecedor
- `AccountPayableId` - Identificador de conta a pagar
- `AccountReceivableId` - Identificador de conta a receber
- `CategoryId` - Identificador de categoria
- `SupplierName` - Nome do fornecedor com validação
- `PaymentTerms` - Prazo de pagamento
- `PaymentStatus` - Status de pagamento
- `ReceivableStatus` - Status de recebimento
- `CategoryType` - Tipo de categoria (receita/despesa)

**Entities:**
- `Supplier` - Fornecedor
- `AccountPayable` - Conta a pagar
- `AccountReceivable` - Conta a receber
- `Category` - Categoria financeira
- `Transaction` - Transação financeira

**Domain Events:**
- `SupplierCreated`
- `AccountPayableCreated`
- `AccountPayablePaid`
- `AccountPayableOverdue`
- `AccountReceivableCreated`
- `AccountReceivableReceived`
- `AccountReceivableOverdue`

---

## 🚀 Tecnologias

- PHP 8.3
- Laravel 11
- PostgreSQL 16
- Redis 7
- RabbitMQ 3.13
- Docker

---

## 📖 Documentação Completa

Ver `SPRINT6-PLAN.md` para plano completo de implementação.

---

**Criado em:** 07/10/2025  
**Versão:** 0.1.0 (Domain Layer)
