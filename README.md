# Sistema ERP - Microserviços

Sistema ERP completo baseado em arquitetura de microserviços, desenvolvido com Laravel 11.x, implementando Clean Architecture, Event-Driven Architecture e Domain-Driven Design.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-purple.svg)](https://php.net)
[![Docker](https://img.shields.io/badge/Docker-24+-blue.svg)](https://docker.com)

## 📊 Status do Projeto

🎉 **Sprint 1 Concluída com Sucesso!**

- ✅ **Auth Service** - 100% Funcional e Testado
- ✅ **139 testes passando** (100% success rate)
- ✅ **6 endpoints** de autenticação implementados
- ✅ **JWT Authentication** com Redis blacklist
- ✅ **Clean Architecture** completa
- ✅ **Documentação** completa (API-DOCS.md, Postman Collection)
- ✅ **Production Ready** 🚀

**Próximo:** Sprint 2 - RBAC & Email Verification

---

## 🎯 Visão Geral

Este projeto implementa um sistema ERP completo para gerenciamento de vendas, estoque, logística e financeiro, utilizando arquitetura de microserviços com as melhores práticas do mercado.

### 🏗️ Arquitetura

- **Clean Architecture** - Separação clara de responsabilidades
- **Event-Driven Architecture** - Comunicação assíncrona via eventos
- **Domain-Driven Design** - Bounded contexts bem definidos
- **CQRS** - Segregação de comandos e consultas
- **Saga Pattern** - Transações distribuídas

## 🚀 Microserviços

### 1. ✅ **Auth Service** - Autenticação e Autorização

**Status:** 🟢 Sprint 1 Completo (Production Ready)

**Endpoints:**
- `POST /api/auth/register` - Registro de usuários
- `POST /api/auth/login` - Autenticação JWT
- `POST /api/auth/logout` - Logout (token blacklist)
- `POST /api/auth/refresh` - Refresh token
- `GET /api/auth/me` - Dados do usuário autenticado
- `GET /api/health` - Health check

**Features:**
- JWT Authentication (HS256)
- Password Hashing (BCrypt, cost 12)
- Token Blacklist (Redis)
- Input Validation (FormRequests)
- Clean Architecture
- Event Publishing (RabbitMQ)
- 139 testes automatizados

**Documentação:**
- [API Documentation](services/auth-service/API-DOCS.md)
- [Architecture](services/auth-service/ARCHITECTURE.md)
- [Sprint 1 Summary](SPRINT1-COMPLETO.md)
- [Postman Collection](services/auth-service/postman-collection.json)

**Base URL:** http://localhost:9001/api

---

### 2. ⏳ **Inventory Service** - Gestão de Produtos e Estoque

**Status:** 🔴 Não iniciado (Sprint 3-4)

---

### 3. ⏳ **Sales Service** - Gestão de Vendas e Pedidos

**Status:** 🔴 Não iniciado (Sprint 5-6)

---

### 4. ⏳ **Logistics Service** - Gestão de Logística e Entregas

**Status:** 🔴 Não iniciado (Sprint 9-10)

---

### 5. ⏳ **Financial Service** - Gestão Financeira e Pagamentos

**Status:** 🔴 Não iniciado (Sprint 7-8)

---

### 6. ⏳ **Notification Service** - Notificações (Email, SMS, Push)

**Status:** 🔴 Não iniciado (Sprint 11)

## 🛠️ Stack Tecnológico

### Backend
- **Laravel 11.x** - Framework PHP
- **PHP 8.3+** - Linguagem
- **PostgreSQL 16** - Database (um por serviço)
- **Redis 7.2** - Cache e Sessions
- **RabbitMQ 3.13** - Message Broker

### Infraestrutura
- **Docker & Docker Compose** - Containerização
- **Kong** - API Gateway
- **Prometheus & Grafana** - Monitoramento
- **Jaeger** - Distributed Tracing
- **ELK Stack** - Logging (Elasticsearch, Logstash, Kibana)

## 📋 Pré-requisitos

- Docker 24+
- Docker Compose 2.x
- Git
- 16GB RAM (recomendado)
- 50GB de espaço em disco

## 🚀 Quick Start

### 1. Clone o Repositório

```bash
git clone <repository-url>
cd microservices
```

### 2. Configure Variáveis de Ambiente

```bash
# Copiar arquivo de exemplo
cp env.example .env

# Editar conforme necessário
nano .env
```

### 3. Iniciar Infraestrutura

```bash
# Tornar scripts executáveis
chmod +x scripts/*.sh

# Iniciar toda a infraestrutura
./scripts/start.sh
```

### 4. Verificar Status

```bash
# Ver status de todos os serviços
./scripts/status.sh
```

### 5. Acessar Serviços

**Microserviços:**
- **Auth Service API:** http://localhost:9001/api (✅ Sprint 1 Completo)
- **Auth Service Docs:** [API-DOCS.md](services/auth-service/API-DOCS.md)

**Infraestrutura:**
- **API Gateway (Kong):** http://localhost:8000
- **Kong Admin:** http://localhost:8001
- **RabbitMQ Management:** http://localhost:15672 (admin/admin123)
- **Grafana:** http://localhost:3000 (admin/admin)
- **Prometheus:** http://localhost:9090
- **Jaeger:** http://localhost:16686
- **Kibana:** http://localhost:5601 (elastic/jr120777)

## 📖 Documentação Completa

Toda a documentação detalhada está disponível em [`/docs`](./docs):

- [📘 Visão Geral](./docs/README.md)
- [🏗️ Arquitetura](./docs/01-architecture/README.md)
- [🔧 Infraestrutura](./docs/02-infrastructure/README.md)
- [🔌 Microserviços](./docs/03-microservices/README.md)
- [📡 Comunicação](./docs/04-communication/README.md)
- [🛡️ Resiliência e Segurança](./docs/05-resilience/README.md)
- [📅 Planejamento de Sprints](./docs/06-sprints/README.md)
- [📚 Glossário](./docs/GLOSSARY.md)
- [⚡ Guia de Início Rápido](./docs/QUICKSTART.md)

## 🏃 Desenvolvimento

### Estrutura de Diretórios

```
microservices/
├── docs/                       # Documentação completa
├── infrastructure/             # Configurações de infraestrutura
│   ├── rabbitmq/              # RabbitMQ configs
│   ├── prometheus/            # Prometheus configs
│   ├── grafana/               # Grafana configs
│   └── logstash/              # Logstash configs
├── scripts/                    # Scripts utilitários
│   ├── start.sh               # Iniciar infraestrutura
│   ├── stop.sh                # Parar infraestrutura
│   ├── status.sh              # Status dos serviços
│   ├── logs.sh                # Ver logs
│   └── clean.sh               # Limpar tudo
├── auth-service/              # (Sprint 1-2)
├── inventory-service/         # (Sprint 3-4)
├── sales-service/             # (Sprint 5-6)
├── financial-service/         # (Sprint 7-8)
├── logistics-service/         # (Sprint 9-10)
├── notification-service/      # (Sprint 11)
├── docker-compose.yml         # Compose principal
├── SPRINT0.md                 # Status da Sprint 0
└── README.md                  # Este arquivo
```

### Scripts Disponíveis

```bash
# Iniciar toda a infraestrutura
./scripts/start.sh

# Parar toda a infraestrutura
./scripts/stop.sh

# Ver status de todos os serviços
./scripts/status.sh

# Ver logs (todos ou de um serviço específico)
./scripts/logs.sh [service-name]

# Limpar completamente (remove volumes)
./scripts/clean.sh
```

## 📊 Status do Projeto

### Sprint 0: Configuração de Infraestrutura ✅

**Status:** 🟡 Em Andamento

Ver detalhes em: [SPRINT0.md](./SPRINT0.md)

- [x] Docker Compose configurado
- [x] RabbitMQ configurado
- [x] PostgreSQL configurado
- [x] Redis configurado
- [x] Kong Gateway configurado
- [x] Monitoring Stack configurado
- [ ] Testes completos
- [ ] Documentação finalizada

### Próximas Sprints

- **Sprint 1-2:** Auth Service (4 semanas)
- **Sprint 3-4:** Inventory Service (4 semanas)
- **Sprint 5-6:** Sales Service (4 semanas)
- **Sprint 7-8:** Financial Service (4 semanas)
- **Sprint 9-10:** Logistics Service (4 semanas)
- **Sprint 11:** Notification Service (2 semanas)
- **Sprint 12:** API Gateway (2 semanas)
- **Sprint 13-14:** Integração e Deploy (4 semanas)

**Total:** ~28 semanas (7 meses)

## 🧪 Testes

```bash
# Executar testes de um serviço
docker-compose exec auth-service php artisan test

# Executar testes com coverage
docker-compose exec auth-service php artisan test --coverage
```

## 🔒 Segurança

- JWT Authentication em todos os serviços
- RBAC (Role-Based Access Control)
- Rate Limiting
- Circuit Breaker Pattern
- Request validation em todas as camadas
- Criptografia de dados sensíveis

## 🔄 Resiliência

- **Circuit Breaker** - Previne cascata de falhas
- **Retry Pattern** - Tentativas automáticas com backoff
- **Timeout** - Limites de tempo para operações
- **Bulkhead** - Isolamento de recursos
- **Health Checks** - Monitoramento de saúde dos serviços

## 📡 Comunicação

### Síncrona (REST)
- Requisições client-server via API Gateway
- HTTP/HTTPS com JSON

### Assíncrona (RabbitMQ)
- Eventos entre microserviços
- Event-Driven Architecture
- Dead Letter Queues para falhas

## 📈 Monitoramento

- **Prometheus** - Coleta de métricas
- **Grafana** - Visualização e dashboards
- **Jaeger** - Distributed tracing
- **ELK Stack** - Logging centralizado

## 🤝 Contribuindo

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

### Padrões de Commit

- `feat:` Nova funcionalidade
- `fix:` Correção de bug
- `docs:` Mudanças na documentação
- `style:` Formatação, ponto e vírgula, etc
- `refactor:` Refatoração de código
- `test:` Adição de testes
- `chore:` Manutenção

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 👥 Time

- **Tech Lead:** -
- **Backend Developers:** -
- **DevOps Engineer:** -
- **QA Engineer:** -
- **Product Owner:** -

## 📞 Suporte

- 📧 Email: support@erp-microservices.local
- 📖 Documentação: [/docs](./docs)
- 🐛 Issues: [GitHub Issues](#)

## 🎯 Roadmap

### Fase 1 - MVP (7 meses)
- [x] Sprint 0: Infraestrutura ✅ (Completo)
- [x] Sprint 1: Auth Service ✅ (Completo - 139 testes passando)
- [ ] Sprint 2: Auth Service - RBAC & Email Verification
- [ ] Sprint 3-4: Inventory Service
- [ ] Sprint 5-6: Sales Service
- [ ] Sprint 7-8: Financial Service
- [ ] Sprint 9-10: Logistics Service
- [ ] Sprint 11: Notification Service
- [ ] Sprint 12: API Gateway Integration
- [ ] Sprint 13-14: Integração e Deploy

### Fase 2 - Melhorias (Futuro)
- Event Sourcing completo
- CQRS avançado
- Machine Learning para previsão de estoque
- BI e Analytics
- Kubernetes deployment
- Service Mesh (Istio)
- Multi-region deployment

## 🌟 Features Principais

- ✅ Arquitetura de Microserviços
- ✅ Event-Driven Architecture
- ✅ Clean Architecture
- ✅ Domain-Driven Design
- ✅ CQRS Pattern
- ✅ Saga Pattern
- ✅ Circuit Breaker
- ✅ Distributed Tracing
- ✅ Centralized Logging
- ✅ API Gateway
- ✅ Service Discovery
- ✅ Health Checks
- ✅ Auto-scaling ready
- ✅ Docker & Docker Compose
- ✅ Comprehensive Documentation

---

**Desenvolvido com ❤️ usando Laravel e Docker**

**Versão:** 1.0.0-alpha  
**Status:** 🚧 Em Desenvolvimento  
**Última Atualização:** Outubro 2025

