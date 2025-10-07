# 📊 Sistema de Observabilidade - Microservices

Sistema completo de monitoring, alerting e observabilidade para a arquitetura de microserviços.

## 🎯 Componentes

### 1. Prometheus (`:9090`)
- Coleta de métricas time-series
- Armazenamento local (15 dias retenção)
- Query language (PromQL)
- **Acesso**: http://localhost:9090

### 2. Grafana (`:3000`)
- Visualização de métricas
- Dashboards interativos
- Alertas visuais
- **Acesso**: http://localhost:3000
- **Credenciais**: admin / admin123

### 3. Alertmanager (`:9093`)
- Gerenciamento de alertas
- Agrupamento e roteamento
- Integração Slack/Email
- **Acesso**: http://localhost:9093

### 4. Exporters
- **Node Exporter** (`:9100`): Métricas do host
- **cAdvisor** (`:8080`): Métricas de containers
- **Redis Exporter** (`:9121`): Métricas do Redis
- **Postgres Exporters**:
  - Auth DB: `:9187`
  - Inventory DB: `:9188`
  - Sales DB: `:9189`

## 📈 Métricas Disponíveis

### RED Metrics (Request, Error, Duration)
```promql
# Total de requisições
http_requests_total

# Taxa de erro
rate(http_requests_errors_total[5m])

# Latência (p95)
histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))
```

### Métricas de Negócio
```promql
# Pedidos
orders_created_total
orders_confirmed_total
orders_cancelled_total

# Produtos
products_created_total
products_low_stock_total

# Clientes
customers_created_total

# Autenticação
authentication_attempts_total
```

### Métricas de Sistema
```promql
# CPU
100 - (avg(rate(node_cpu_seconds_total{mode="idle"}[5m])) * 100)

# Memória
(node_memory_MemTotal_bytes - node_memory_MemAvailable_bytes) / node_memory_MemTotal_bytes * 100

# Disco
(node_filesystem_avail_bytes / node_filesystem_size_bytes) * 100

# Database connections
pg_stat_database_numbackends
```

## 🚨 Alertas Configurados

### Críticos (🔴)
- **ServiceDown**: Serviço fora do ar por >1min
- **DiskSpaceLow**: Espaço em disco <10%

### Warning (⚠️)
- **HighErrorRate**: Taxa de erro >5% por 5min
- **HighResponseTime**: P95 >1s por 5min
- **HighCPUUsage**: CPU >80% por 10min
- **HighMemoryUsage**: Memória >85% por 10min
- **DatabaseConnectionPoolExhausted**: >80% conexões em uso
- **RabbitMQQueueGrowing**: >1000 mensagens na fila por 10min

### Info (ℹ️)
- **LowStockProducts**: >10 produtos com estoque baixo
- **HighOrderCancellationRate**: Taxa cancelamento >20%

## 🎨 Dashboards Grafana

### Dashboard 1: Overview Geral
- Status de todos os serviços
- Request rate global
- Error rate global
- Latência P50/P95/P99
- Métricas de negócio agregadas

### Dashboard 2: Por Serviço
- Auth Service
- Inventory Service
- Sales Service

Cada dashboard inclui:
- Request rate
- Error rate
- Response time
- Métricas específicas do serviço

### Dashboard 3: Infraestrutura
- CPU/Memory/Disk usage
- Network I/O
- Container metrics
- Database connections

### Dashboard 4: RabbitMQ
- Message rate
- Queue depth
- Consumer count
- Message age

## 🚀 Como Usar

### Iniciar Monitoring Stack
```bash
# Subir apenas serviços de monitoring
docker-compose up -d prometheus grafana alertmanager

# Subir exporters
docker-compose up -d node-exporter cadvisor redis-exporter

# Subir postgres exporters
docker-compose up -d postgres-exporter-auth postgres-exporter-inventory postgres-exporter-sales
```

### Verificar Status
```bash
# Status Prometheus
curl http://localhost:9090/-/healthy

# Status Grafana
curl http://localhost:3000/api/health

# Status Alertmanager
curl http://localhost:9093/-/healthy
```

### Acessar Métricas dos Serviços
```bash
# Sales Service
curl http://localhost:8002/metrics

# Auth Service (adicionar endpoints similares)
curl http://localhost:8000/metrics

# Inventory Service (adicionar endpoints similares)
curl http://localhost:8001/metrics
```

### Consultar Métricas (PromQL)
```bash
# Via API
curl 'http://localhost:9090/api/v1/query?query=up'

# Taxa de requisições nos últimos 5 minutos
curl 'http://localhost:9090/api/v1/query?query=rate(http_requests_total[5m])'
```

## 📊 Queries Úteis

### Performance
```promql
# Request rate por serviço
sum(rate(http_requests_total[5m])) by (service)

# Error rate
sum(rate(http_requests_errors_total[5m])) / sum(rate(http_requests_total[5m]))

# Latência média
avg(http_request_duration_seconds) by (service)
```

### Negócio
```promql
# Pedidos criados por hora
increase(orders_created_total[1h])

# Taxa de conversão (confirmados/criados)
rate(orders_confirmed_total[1h]) / rate(orders_created_total[1h])

# Taxa de cancelamento
rate(orders_cancelled_total[1h]) / rate(orders_created_total[1h])
```

### Infraestrutura
```promql
# Top 5 containers por CPU
topk(5, rate(container_cpu_usage_seconds_total[5m]))

# Top 5 containers por memória
topk(5, container_memory_usage_bytes)

# Queries lentas no PostgreSQL
pg_stat_statements_mean_time_seconds > 1
```

## 🔔 Configurar Alertas (Slack/Email)

### Slack
1. Criar Incoming Webhook no Slack
2. Editar `monitoring/alertmanager/alertmanager.yml`:
```yaml
slack_configs:
  - api_url: 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL'
    channel: '#alerts'
```

### Email
1. Configurar SMTP em `alertmanager.yml`:
```yaml
global:
  smtp_smarthost: 'smtp.gmail.com:587'
  smtp_from: 'alerts@example.com'
  smtp_auth_username: 'your-email@gmail.com'
  smtp_auth_password: 'your-app-password'
```

## 🧪 Testar Alertas

### Simular Alta Taxa de Erro
```bash
# Gerar erros propositalmente
for i in {1..100}; do
  curl -X POST http://localhost:8002/api/invalid-endpoint
done
```

### Simular Alta Latência
```bash
# Fazer muitas requisições simultâneas
ab -n 1000 -c 100 http://localhost:8002/api/orders
```

## 📝 Melhores Práticas

### 1. Naming Conventions
- Use sufixos descritivos: `_total`, `_seconds`, `_bytes`
- Prefixo por tipo: `http_`, `db_`, `queue_`
- Labels consistentes: `service`, `method`, `status`

### 2. Cardinality
- Evite labels com alta cardinalidade (user_id, timestamp)
- Prefira labels com valores limitados (status, method)

### 3. Alertas
- Defina SLOs/SLIs claros
- Use períodos de avaliação adequados
- Configure rotas de escalonamento

### 4. Retenção
- Prometheus: 15 dias (local)
- Considere Thanos/Cortex para long-term storage

## 🔧 Troubleshooting

### Prometheus não coleta métricas
```bash
# Verificar targets
curl http://localhost:9090/api/v1/targets

# Ver logs
docker logs prometheus
```

### Grafana não conecta ao Prometheus
```bash
# Testar conectividade
docker exec grafana wget -O- http://prometheus:9090/api/v1/query?query=up

# Verificar datasource
curl -u admin:admin123 http://localhost:3000/api/datasources
```

### Alertas não disparam
```bash
# Ver regras ativas
curl http://localhost:9090/api/v1/rules

# Ver alertas pendentes
curl http://localhost:9090/api/v1/alerts

# Logs do Alertmanager
docker logs alertmanager
```

## 📚 Recursos

- [Prometheus Documentation](https://prometheus.io/docs/)
- [Grafana Documentation](https://grafana.com/docs/)
- [PromQL Cheat Sheet](https://promlabs.com/promql-cheat-sheet/)
- [Alerting Best Practices](https://prometheus.io/docs/practices/alerting/)

## 🎓 Próximos Passos

1. Adicionar Tracing (Jaeger)
2. Implementar Log Aggregation (ELK/Loki)
3. Service Mesh observability (Istio)
4. Custom business dashboards
5. SLO/SLI tracking
6. Cost monitoring

