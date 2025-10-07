# 📊 Grafana - Financial Service Dashboard Guide

## 🚀 Acesso Rápido

### URLs

- **Grafana:** http://localhost:3000
- **Prometheus:** http://localhost:9090
- **Alertmanager:** http://localhost:9093
- **Financial Service Metrics:** http://localhost:9004/metrics

### Credenciais Padrão

```
Usuário: admin
Senha: admin
```

---

## 📈 Dashboard: Financial Service

### Como Acessar

1. Abra http://localhost:3000
2. Login com `admin/admin`
3. Menu lateral → **Dashboards**
4. Selecione **"Financial Service - Monitoring"**

**OU** acesse diretamente:
```
http://localhost:3000/d/financial-service/financial-service-monitoring
```

---

## 🎯 Painéis do Dashboard

### 1. HTTP Request Rate
**Métrica:** `rate(financial_http_requests_total[5m])`

**O que mostra:** Número de requisições HTTP por segundo

**Valores esperados:**
- Normal: 1-50 req/s
- Baixo: < 0.1 req/s (possível problema)
- Alto: > 100 req/s (carga elevada)

---

### 2. HTTP Error Rate
**Métrica:** `rate(financial_http_errors_total[5m])`

**O que mostra:** Taxa de erros (4xx + 5xx) por segundo

**Valores esperados:**
- Saudável: < 1 erro/s (< 1% do total)
- Atenção: 1-10 erros/s
- Crítico: > 10 erros/s

---

### 3. Total Suppliers Created
**Métrica:** `financial_suppliers_created_total`

**O que mostra:** Contador total de fornecedores criados

**Uso:** Monitorar crescimento da base de fornecedores

---

### 4. Total Categories Created
**Métrica:** `financial_categories_created_total`

**O que mostra:** Contador total de categorias criadas

**Uso:** Monitorar setup e configuração do sistema

---

### 5. Memory Usage
**Métrica:** `financial_php_memory_usage_bytes / 1024 / 1024`

**O que mostra:** Uso de memória em MB

**Valores esperados:**
- Normal: < 256MB
- Atenção: 256-512MB
- Crítico: > 512MB

---

### 6. Accounts Payable - Created vs Paid
**Métricas:**
- Created: `rate(financial_accounts_payable_created_total[5m])`
- Paid: `rate(financial_accounts_payable_paid_total[5m])`

**O que mostra:** Taxa de criação vs pagamento de contas a pagar

**Análise:**
- **Created > Paid:** Acúmulo de contas a pagar
- **Paid > Created:** Redução do passivo
- **Created ≈ Paid:** Operação equilibrada

---

### 7. Accounts Receivable - Created vs Received
**Métricas:**
- Created: `rate(financial_accounts_receivable_created_total[5m])`
- Received: `rate(financial_accounts_receivable_received_total[5m])`

**O que mostra:** Taxa de criação vs recebimento de contas a receber

**Análise:**
- **Created > Received:** Acúmulo de recebíveis
- **Received > Created:** Redução de pendências
- **Created ≈ Received:** Fluxo de caixa saudável

---

### 8. Total Payable Amount (R$)
**Métrica:** `financial_accounts_payable_amount_total`

**O que mostra:** Valor total acumulado de contas a pagar

**Uso:** 
- Monitorar comprometimento financeiro
- Planejamento de caixa
- Alertas para valores elevados (> R$ 1M/hora)

---

### 9. Total Receivable Amount (R$)
**Métrica:** `financial_accounts_receivable_amount_total`

**O que mostra:** Valor total acumulado de contas a receber

**Uso:**
- Monitorar expectativa de receitas
- Gestão de fluxo de caixa
- Projeções financeiras

---

### 10. HTTP Requests by Status Code
**Métrica:** `financial_http_requests_by_status{status="XXX"}`

**O que mostra:** Distribuição de requests por código HTTP

**Códigos importantes:**
- **200/201:** Sucesso (verde)
- **400/422:** Erro de validação (amarelo)
- **404:** Recurso não encontrado (laranja)
- **500:** Erro interno (vermelho)

---

## 🔍 Queries PromQL Úteis

### Taxas e Percentuais

```promql
# Taxa de erro (%)
(rate(financial_http_errors_total[5m]) / rate(financial_http_requests_total[5m])) * 100

# Tempo médio de resposta
rate(financial_http_request_duration_seconds[5m]) / rate(financial_http_requests_total[5m])

# Taxa de pagamento de contas (%)
(financial_accounts_payable_paid_total / financial_accounts_payable_created_total) * 100
```

### Business Intelligence

```promql
# Valor médio de conta a pagar
financial_accounts_payable_amount_total / financial_accounts_payable_created_total

# Valor médio de conta a receber
financial_accounts_receivable_amount_total / financial_accounts_receivable_created_total

# Diferença entre recebíveis e pagáveis
financial_accounts_receivable_amount_total - financial_accounts_payable_amount_total
```

### Performance

```promql
# Requests por minuto
rate(financial_http_requests_total[1m]) * 60

# Percentual de uso de memória (assumindo limite de 512MB)
(financial_php_memory_usage_bytes / 536870912) * 100

# Latência P95 (aproximação)
histogram_quantile(0.95, rate(financial_http_request_duration_bucket[5m]))
```

---

## 🎨 Personalizando o Dashboard

### Adicionar Novo Painel

1. No Dashboard, clique em **"Add"** → **"Visualization"**
2. Selecione **"Prometheus"** como data source
3. Digite sua query PromQL
4. Ajuste visualização (Graph, Stat, Gauge, etc.)
5. Clique em **"Apply"**

### Exemplo: Criar Painel "Fluxo de Caixa"

```promql
# Query
financial_accounts_receivable_amount_total - financial_accounts_payable_amount_total

# Tipo: Stat
# Unit: Currency (BRL)
# Thresholds:
#   - Red: < 0 (negativo)
#   - Yellow: 0 - 100000
#   - Green: > 100000
```

---

## 📊 Interpretando Métricas

### Cenário 1: Sistema Saudável

```
✅ HTTP Request Rate: 10-30 req/s
✅ Error Rate: < 0.5%
✅ Response Time: < 500ms
✅ Memory Usage: 150-250MB
✅ Payable/Receivable: Balanceado
```

### Cenário 2: Alta Carga

```
⚠️  HTTP Request Rate: > 100 req/s
⚠️  Response Time: 1-2s
⚠️  Memory Usage: 400-500MB
✅ Error Rate: < 2%
```

**Ação:** Considerar escalonamento horizontal

### Cenário 3: Problema Crítico

```
🚨 Error Rate: > 10%
🚨 Response Time: > 3s
🚨 Memory Usage: > 600MB
🚨 HTTP 500 errors: Aumentando
```

**Ação Imediata:**
1. Verificar logs: `docker logs financial-service`
2. Verificar database
3. Restart se necessário
4. Investigar causa raiz

---

## 🔔 Alertas Integrados

O dashboard está integrado com **Alertmanager**. Alertas ativos aparecem:

1. **Ícone de sino** no topo do dashboard
2. **Annotations** nas linhas do tempo
3. **Alert state** nos painéis

### Ver Alertas Ativos

```bash
# Via Browser
http://localhost:9093/#/alerts

# Via cURL
curl http://localhost:9090/api/v1/alerts | jq '.data.alerts[] | select(.labels.service=="financial")'
```

---

## 🧪 Testando Visualizações

### Gerar Métricas de Teste

```bash
# Gerar carga leve (30 iterações)
./scripts/generate-financial-metrics.sh 30 0.3

# Gerar carga média (100 iterações)
./scripts/generate-financial-metrics.sh 100 0.2

# Gerar carga pesada (500 iterações)
./scripts/generate-financial-metrics.sh 500 0.1
```

### Simular Cenários

```bash
# Cenário: Alto volume de contas a pagar
for i in {1..50}; do
  curl -s -X POST http://localhost:9004/api/v1/accounts-payable \
    -H "Content-Type: application/json" \
    -d "{\"supplier_id\":\"SUPPLIER_ID\",\"category_id\":\"CATEGORY_ID\",\"description\":\"Test\",\"amount\":$((RANDOM%10000+1000)),\"issue_date\":\"2025-10-07\",\"payment_terms_days\":30}"
done

# Cenário: Erros 500
for i in {1..20}; do
  curl -s -X POST http://localhost:9004/api/v1/suppliers \
    -H "Content-Type: application/json" \
    -d '{"invalid": "data"}'
done
```

---

## 💡 Dicas e Truques

### 1. Time Range Shortcuts

- **Last 5 minutes:** Ideal para debugging em tempo real
- **Last 1 hour:** Visualizar tendências recentes
- **Last 24 hours:** Análise diária
- **Last 7 days:** Padrões semanais

### 2. Auto-Refresh

Configure auto-refresh para monitoramento contínuo:
- **5s:** Debugging ativo
- **1m:** Monitoramento regular
- **5m:** Observação casual

### 3. Variáveis de Dashboard

Adicione filtros dinâmicos:
```
Service: financial-service (fixo)
Environment: development, staging, production
Time Window: 5m, 15m, 1h, 6h, 24h
```

### 4. Exportar Dashboard

```bash
# Via API
curl -u admin:admin http://localhost:3000/api/dashboards/uid/financial-service | jq '.dashboard' > dashboard-backup.json

# Via UI
Dashboard Settings → JSON Model → Copy to clipboard
```

---

## 🐛 Troubleshooting

### Dashboard não mostra dados

1. **Verificar Prometheus scraping:**
   ```bash
   curl http://localhost:9090/api/v1/targets | jq '.data.activeTargets[] | select(.labels.job=="financial-service")'
   ```

2. **Verificar métricas disponíveis:**
   ```bash
   curl http://localhost:9004/metrics | grep financial_
   ```

3. **Verificar datasource no Grafana:**
   - Settings → Data Sources → Prometheus
   - Clique em "Test" - deve retornar "Data source is working"

### Painéis mostram "No data"

- Verificar time range (últimos 5 minutos pode não ter dados)
- Gerar métricas: `./scripts/generate-financial-metrics.sh 10 0.5`
- Verificar query PromQL está correta

### Alertas não aparecem

- Alertas precisam estar "firing" por tempo configurado
- Ver: http://localhost:9090/alerts
- Simular alerta parando o serviço: `docker compose stop financial-service`

---

## 📚 Recursos Adicionais

- [Grafana Dashboards](https://grafana.com/docs/grafana/latest/dashboards/)
- [PromQL Query Examples](https://prometheus.io/docs/prometheus/latest/querying/examples/)
- [Grafana Alerting](https://grafana.com/docs/grafana/latest/alerting/)

---

**Última Atualização:** 2025-10-07  
**Dashboard Version:** 1.0.0  
**Compatível com:** Financial Service v1.0.0

