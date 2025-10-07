# 🔔 Alertas - Guia Completo

## 📋 Visão Geral

Este documento descreve todos os alertas configurados para o **Financial Service** no Prometheus/Alertmanager.

---

## 🚨 Níveis de Severidade

| Severidade | Descrição | Ação Requerida |
|------------|-----------|----------------|
| **Critical** | Serviço indisponível ou erro crítico | Ação imediata |
| **Warning** | Problema que pode escalar | Investigar em breve |
| **Info** | Notificação informativa | Monitorar |

---

## 🏦 Financial Service Alerts

### 🔴 Critical Alerts

#### 1. FinancialServiceDown
- **Condição:** Serviço offline por mais de 1 minuto
- **Threshold:** `up{job="financial-service"} == 0`
- **Ação:** 
  - Verificar logs: `docker logs financial-service`
  - Restart: `docker compose restart financial-service`
  - Verificar dependências (DB, Redis, RabbitMQ)

#### 2. FinancialServiceHighErrorRate
- **Condição:** Taxa de erro > 10 erros/segundo por 2 minutos
- **Threshold:** `rate(financial_http_errors_total[5m]) > 10`
- **Ação:**
  - Verificar logs para identificar erro recorrente
  - Verificar endpoints específicos com problemas
  - Verificar integridade do banco de dados

#### 3. FinancialDatabaseDown
- **Condição:** Database PostgreSQL offline por mais de 1 minuto
- **Threshold:** `up{job="postgres-financial"} == 0`
- **Ação:**
  - Verificar container: `docker ps | grep financial-db`
  - Verificar logs: `docker logs financial-db`
  - Restart: `docker compose restart financial-db`

---

### ⚠️ Warning Alerts

#### 4. FinancialServiceHighResponseTime
- **Condição:** Tempo médio de resposta > 2 segundos por 5 minutos
- **Threshold:** `avg_response_time > 2s`
- **Ação:**
  - Verificar queries lentas no banco
  - Verificar uso de CPU/Memória
  - Analisar performance de endpoints específicos
  - Considerar otimização de índices

#### 5. FinancialServiceHighMemoryUsage
- **Condição:** Uso de memória > 512MB por 5 minutos
- **Threshold:** `memory_usage > 536870912 bytes`
- **Ação:**
  - Verificar memory leaks
  - Analisar cache Redis
  - Considerar aumento de recursos
  - Verificar jobs/processos em background

#### 6. FinancialPayableReceivableImbalance
- **Condição:** Contas a pagar excedem receber em > R$ 500K (24h)
- **Threshold:** `(receivables - payables) < -500000`
- **Ação:**
  - Revisar fluxo de caixa
  - Verificar com equipe financeira
  - Validar lançamentos recentes
  - Considerar ações corretivas

#### 7. FinancialSupplierCreationSpike
- **Condição:** Criação > 10 fornecedores/segundo
- **Threshold:** `rate(suppliers_created) > 10`
- **Ação:**
  - Verificar se há importação em massa
  - Validar se não há loop de criação
  - Verificar logs de aplicação

---

### ℹ️ Info Alerts

#### 8. FinancialHighAccountsPayableVolume
- **Condição:** Criação > 50 contas a pagar/segundo por 5 minutos
- **Threshold:** `rate(accounts_payable_created) > 50`
- **Observação:** Pode indicar importação em massa ou erro de entrada de dados

#### 9. FinancialHighPayableAmount
- **Condição:** Aumento > R$ 1M em contas a pagar (1 hora)
- **Threshold:** `increase(payable_amount[1h]) > 1000000`
- **Observação:** Notificação para auditoria e controle financeiro

#### 10. FinancialHighReceivableAmount
- **Condição:** Aumento > R$ 1M em contas a receber (1 hora)
- **Threshold:** `increase(receivable_amount[1h]) > 1000000`
- **Observação:** Notificação para acompanhamento de recebimentos

#### 11. FinancialNoAccountsPayableActivity
- **Condição:** Nenhuma conta a pagar criada em 4 horas
- **Threshold:** `increase(accounts_payable_created[1h]) == 0`
- **Observação:** Normal fora do horário comercial

#### 12. FinancialServiceLowRequestRate
- **Condição:** Taxa de requests < 0.1/segundo por 10 minutos
- **Threshold:** `rate(http_requests_total[5m]) < 0.1`
- **Observação:** Serviço pode estar inacessível ou não estar sendo usado

#### 13. FinancialCategoryCreationSpike
- **Condição:** Criação > 5 categorias/segundo
- **Threshold:** `rate(categories_created) > 5`
- **Observação:** Possível setup ou migração em andamento

---

### 🗄️ Infrastructure Alerts

#### 14. FinancialDatabaseHighConnections
- **Condição:** > 50 conexões ativas no banco
- **Threshold:** `pg_stat_activity_count > 50`
- **Ação:**
  - Verificar connection pooling
  - Identificar queries longas
  - Verificar memory leaks em conexões

#### 15. FinancialDatabaseSlowQueries
- **Condição:** Menos de 100 rows fetched/segundo por 10 minutos
- **Threshold:** `rate(pg_stat_database_tup_fetched) < 100`
- **Ação:**
  - Analisar queries lentas
  - Verificar índices
  - Considerar otimização de queries

---

## 🔧 Como Verificar Alertas

### Via Prometheus UI

```bash
# Abrir Prometheus
http://localhost:9090/alerts

# Ver regras ativas
http://localhost:9090/rules
```

### Via Alertmanager UI

```bash
# Abrir Alertmanager
http://localhost:9093

# Ver alertas ativos
http://localhost:9093/#/alerts
```

### Via API

```bash
# Listar todos os alertas ativos
curl http://localhost:9090/api/v1/alerts | jq

# Listar alertas do Financial Service
curl http://localhost:9090/api/v1/alerts | jq '.data.alerts[] | select(.labels.service=="financial")'

# Ver regras carregadas
curl http://localhost:9090/api/v1/rules | jq '.data.groups[] | select(.name=="financial_service_alerts")'
```

---

## 📧 Configurando Notificações

### Slack

Edite `monitoring/alertmanager/alertmanager.yml`:

```yaml
receivers:
  - name: 'slack-notifications'
    slack_configs:
      - api_url: 'YOUR_SLACK_WEBHOOK_URL'
        channel: '#financial-alerts'
        title: '{{ .GroupLabels.alertname }}'
        text: '{{ range .Alerts }}{{ .Annotations.description }}{{ end }}'
```

### Email

```yaml
receivers:
  - name: 'email-notifications'
    email_configs:
      - to: 'financial-team@company.com'
        from: 'alertmanager@company.com'
        smarthost: 'smtp.company.com:587'
        auth_username: 'alertmanager'
        auth_password: 'password'
```

### PagerDuty

```yaml
receivers:
  - name: 'pagerduty-critical'
    pagerduty_configs:
      - service_key: 'YOUR_PAGERDUTY_KEY'
        severity: '{{ .CommonLabels.severity }}'
```

---

## 🧪 Testando Alertas

### Simular Serviço Down

```bash
# Parar o Financial Service
docker compose stop financial-service

# Aguardar 1 minuto
# Verificar alerta em http://localhost:9090/alerts

# Restaurar serviço
docker compose start financial-service
```

### Simular High Error Rate

```bash
# Gerar requests inválidos
for i in {1..100}; do
  curl -X POST http://localhost:9004/api/v1/suppliers \
    -H "Content-Type: application/json" \
    -d '{"invalid": "data"}'
  sleep 0.1
done
```

### Simular High Memory Usage

```bash
# Gerar carga alta no serviço
./scripts/generate-financial-metrics.sh 1000 0.01
```

---

## 📊 Métricas de Referência

### Normal Operating Ranges

| Métrica | Range Normal | Threshold Warning | Threshold Critical |
|---------|--------------|-------------------|-------------------|
| Request Rate | 1-50 req/s | < 0.1 req/s | - |
| Error Rate | < 1% | > 5% | > 10% |
| Response Time | < 500ms | > 1s | > 2s |
| Memory Usage | < 256MB | > 512MB | > 1GB |
| CPU Usage | < 50% | > 70% | > 90% |
| DB Connections | < 20 | > 50 | > 80 |

---

## 🔍 Troubleshooting

### Alerta não está disparando

1. Verificar se as métricas estão sendo coletadas:
   ```bash
   curl http://localhost:9004/metrics | grep financial_
   ```

2. Verificar se o Prometheus está scrapando:
   ```bash
   curl http://localhost:9090/api/v1/targets | jq '.data.activeTargets[] | select(.labels.job=="financial-service")'
   ```

3. Verificar regras carregadas:
   ```bash
   curl http://localhost:9090/api/v1/rules | jq
   ```

### Alerta está disparando incorretamente

1. Ajustar threshold na regra
2. Aumentar o tempo de `for:` para evitar falsos positivos
3. Refinar a query PromQL

### Alertmanager não está enviando notificações

1. Verificar configuração do receiver
2. Verificar logs: `docker logs alertmanager`
3. Testar webhook/email manualmente

---

## 📚 Recursos Adicionais

- [Prometheus Alerting Rules](https://prometheus.io/docs/prometheus/latest/configuration/alerting_rules/)
- [Alertmanager Configuration](https://prometheus.io/docs/alerting/latest/configuration/)
- [PromQL Basics](https://prometheus.io/docs/prometheus/latest/querying/basics/)

---

**Última Atualização:** 2025-10-07  
**Versão:** 1.0.0  
**Mantenedor:** DevOps Team

