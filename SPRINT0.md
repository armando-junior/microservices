# Sprint 0: Configuração de Infraestrutura ✅

## Status: Em Andamento

Esta sprint configura toda a infraestrutura base necessária para o desenvolvimento dos microserviços.

## 📋 Checklist de Tarefas

### ✅ Semana 1: Setup Inicial (COMPLETO)

- [x] Criar repositório Git
- [x] Configurar estrutura de diretórios
- [x] Definir .gitignore
- [x] Criar README principal
- [x] Criar docker-compose.yml principal
- [x] Configurar rede Docker
- [x] Configurar volumes persistentes
- [x] Configurar PostgreSQL containers (todos os serviços)
- [x] Criar databases para cada serviço
- [x] Configurar usuários e permissões

### ⏳ Semana 2: Infraestrutura Compartilhada (EM ANDAMENTO)

- [x] Configurar RabbitMQ container
- [x] Criar exchanges
- [x] Criar queues
- [x] Configurar bindings
- [x] Configurar Redis container
- [x] Configurar Prometheus
- [x] Configurar Grafana
- [x] Configurar Jaeger para tracing
- [x] Configurar ELK Stack (Elasticsearch, Logstash, Kibana)
- [x] Configurar API Gateway (Kong)
- [x] Criar scripts de inicialização
- [ ] Testar toda a infraestrutura
- [ ] Documentar configurações

## 🚀 Como Usar

### 1. Pré-requisitos

- Docker 24+
- Docker Compose 2.x
- Git
- 16GB RAM (recomendado)
- 50GB de espaço em disco

### 2. Iniciar Infraestrutura

```bash
# Tornar scripts executáveis
chmod +x scripts/*.sh

# Iniciar tudo
./scripts/start.sh
```

### 3. Verificar Status

```bash
# Ver status de todos os serviços
./scripts/status.sh

# Ver logs
./scripts/logs.sh

# Ver logs de um serviço específico
./scripts/logs.sh rabbitmq
```

### 4. Acessar Serviços

**API Gateway (Kong):**
- Proxy: http://localhost:8000
- Admin API: http://localhost:8001
- Admin GUI: http://localhost:8002

**RabbitMQ:**
- Management: http://localhost:15672
- User: `admin` / Password: `admin123`

**Grafana:**
- Dashboard: http://localhost:3000
- User: `admin` / Password: `admin`

**Prometheus:**
- UI: http://localhost:9090

**Jaeger (Tracing):**
- UI: http://localhost:16686

**Kibana (Logs):**
- UI: http://localhost:5601

**Elasticsearch:**
- API: http://localhost:9200

**Redis:**
- Port: 6379
- Password: `redis123`

**PostgreSQL Databases:**
- Auth DB: `localhost:5432` (via auth-db container)
- Inventory DB: `localhost:5432` (via inventory-db container)
- Sales DB: `localhost:5432` (via sales-db container)
- Logistics DB: `localhost:5432` (via logistics-db container)
- Financial DB: `localhost:5432` (via financial-db container)

### 5. Parar Infraestrutura

```bash
# Parar todos os serviços
./scripts/stop.sh

# Limpar completamente (remove volumes - PERDE DADOS)
./scripts/clean.sh
```

## 📊 Arquitetura Implementada

```
┌─────────────────────────────────────────────────────────┐
│                    API Gateway (Kong)                    │
│                    Port: 8000, 8001                      │
└────────────────────────┬────────────────────────────────┘
                         │
         ┌───────────────┼───────────────┐
         │               │               │
┌────────▼────────┐ ┌───▼────┐ ┌───────▼────────┐
│   PostgreSQL    │ │ Redis  │ │   RabbitMQ     │
│   (6 databases) │ │        │ │   + Exchanges  │
└─────────────────┘ └────────┘ └────────────────┘

┌─────────────────────────────────────────────────────────┐
│              Monitoring & Observability                  │
├─────────────────┬─────────────┬──────────────────────────┤
│   Prometheus    │   Grafana   │   Jaeger (Tracing)      │
│   (Metrics)     │  (Dashboards│                          │
├─────────────────┴─────────────┴──────────────────────────┤
│                  ELK Stack (Logging)                     │
│        Elasticsearch + Logstash + Kibana                 │
└─────────────────────────────────────────────────────────┘
```

## 🔧 Configurações Criadas

### RabbitMQ

**Exchanges:**
- `auth.events` (topic)
- `inventory.events` (topic)
- `sales.events` (topic)
- `logistics.events` (topic)
- `financial.events` (topic)
- `dlx` (direct - Dead Letter Exchange)

**Queues:**
- `auth.queue` + `auth.dlq`
- `inventory.queue` + `inventory.dlq`
- `sales.queue` + `sales.dlq`
- `logistics.queue` + `logistics.dlq`
- `financial.queue` + `financial.dlq`
- `notification.queue` + `notification.dlq`

**Bindings:**
- Todas as conexões entre serviços conforme documentado

### PostgreSQL

Databases criados:
- `auth_db` (user: auth_user)
- `inventory_db` (user: inventory_user)
- `sales_db` (user: sales_user)
- `logistics_db` (user: logistics_user)
- `financial_db` (user: financial_user)
- `kong` (gateway database)

### Redis

- Cache distribuído
- Session storage
- Rate limiting data

### Prometheus

- Configurado para scraping de:
  - RabbitMQ
  - Kong Gateway
  - Todos os microserviços (quando implementados)

### Grafana

- Data source Prometheus configurado
- Ready para criação de dashboards

## 📝 Scripts Disponíveis

- `scripts/start.sh` - Inicia toda a infraestrutura
- `scripts/stop.sh` - Para toda a infraestrutura
- `scripts/status.sh` - Verifica status de todos os serviços
- `scripts/logs.sh` - Visualiza logs dos serviços
- `scripts/clean.sh` - Limpa completamente (remove volumes)

## 🧪 Testes de Validação

### 1. Verificar Kong

```bash
curl http://localhost:8001/status
```

### 2. Verificar RabbitMQ

```bash
# Login: admin/admin123
open http://localhost:15672
```

### 3. Verificar Redis

```bash
docker exec -it redis redis-cli -a redis123 ping
# Deve retornar: PONG
```

### 4. Verificar PostgreSQL

```bash
# Conectar no auth-db
docker exec -it auth-db psql -U auth_user -d auth_db -c "\l"
```

### 5. Verificar Prometheus

```bash
curl http://localhost:9090/-/healthy
# Deve retornar: Prometheus is Healthy.
```

### 6. Verificar Elasticsearch

```bash
curl http://localhost:9200/_cluster/health
```

## 🐛 Troubleshooting

### Porta já em uso

```bash
# Verificar qual processo está usando a porta
sudo lsof -i :8000

# Parar o processo ou mudar a porta no docker-compose.yml
```

### Container não inicia

```bash
# Ver logs detalhados
docker-compose logs nome-do-servico

# Rebuild do container
docker-compose build --no-cache nome-do-servico
docker-compose up -d nome-do-servico
```

### Erro de memória

```bash
# Aumentar memória do Docker Desktop (mínimo 8GB, recomendado 16GB)
# Settings > Resources > Memory
```

### Limpar e recomeçar

```bash
# Para tudo e limpa volumes
./scripts/clean.sh

# Inicia novamente
./scripts/start.sh
```

## 📚 Próximos Passos

Após confirmar que toda a infraestrutura está funcionando:

1. ✅ Sprint 0 completa
2. ➡️ Iniciar **Sprint 1: Auth Service - Base**
3. Criar projeto Laravel para Auth Service
4. Implementar Domain Layer
5. Configurar conexão com PostgreSQL

## 📖 Documentação Relacionada

- [Documentação Completa](./docs/README.md)
- [Arquitetura](./docs/01-architecture/README.md)
- [Infraestrutura Detalhada](./docs/02-infrastructure/README.md)
- [Guia de Início Rápido](./docs/QUICKSTART.md)
- [Planejamento de Sprints](./docs/06-sprints/README.md)

## ✅ Critérios de Conclusão da Sprint 0

- [ ] Todos os containers iniciam sem erros
- [ ] Todos os health checks passam
- [ ] RabbitMQ Management acessível
- [ ] Grafana acessível com Prometheus configurado
- [ ] Kong Gateway respondendo
- [ ] Todos os databases criados
- [ ] Scripts funcionando corretamente
- [ ] Documentação atualizada

---

**Status:** 🟡 Em Progresso  
**Data Início:** Outubro 2025  
**Responsável:** Time de DevOps/Backend

