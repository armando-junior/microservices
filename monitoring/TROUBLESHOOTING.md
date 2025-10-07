# 🔧 Troubleshooting - Monitoramento

Este guia contém soluções para problemas comuns no sistema de observabilidade.

---

## 📊 Dashboard Grafana mostra "No data"

### Problema
O dashboard "Microservices Overview" mostra "No data" em todos os painéis.

### Soluções

#### 1️⃣ Verifique se os serviços estão UP

```bash
# Verificar status dos serviços
docker compose ps

# Verificar targets do Prometheus
curl -s "http://localhost:9090/api/v1/targets" | jq -r '.data.activeTargets[] | select(.labels.job | contains("service")) | "\(.labels.job): \(.health)"'
```

**Resultado esperado:**
```
auth-service: up
inventory-service: up
sales-service: up
```

#### 2️⃣ Gere métricas

Se os serviços estão UP mas sem dados, gere carga:

```bash
# Gerar métricas por 60 segundos
./scripts/generate-metrics.sh 60

# Ou stress test
./scripts/stress-test.sh 30
```

#### 3️⃣ Verifique o Datasource

```bash
# Verificar UID do datasource
curl -s -u admin:admin123 'http://localhost:3000/api/datasources' | jq -r '.[] | select(.type=="prometheus") | "UID: \(.uid)"'
```

**Resultado esperado:** `UID: prometheus`

**Se estiver diferente:** Reinicie o Grafana:
```bash
docker compose restart grafana
```

#### 4️⃣ Hard Refresh no Navegador

- **Windows/Linux:** `Ctrl + Shift + R`
- **Mac:** `Cmd + Shift + R`

#### 5️⃣ Verifique se as métricas estão sendo expostas

```bash
# Testar endpoints /metrics
curl http://localhost:9001/metrics | head -20
curl http://localhost:9002/metrics | head -20
curl http://localhost:9003/metrics | head -20
```

**Se retornar 404:** As rotas não estão configuradas. Verifique se existe `routes/web.php` com:

```php
Route::get('/metrics', [MetricsController::class, 'index']);
```

---

## 🔴 Serviços aparecem como DOWN no Prometheus

### Problema
No Prometheus (http://localhost:9090/targets), os serviços aparecem como "DOWN".

### Soluções

#### 1️⃣ Verifique as rotas /metrics

As rotas `/metrics` devem estar em `routes/web.php` (não apenas em `api.php`):

```bash
# Verificar se a rota existe
docker compose exec auth-service php artisan route:list | grep metrics
```

#### 2️⃣ Verifique a configuração do Prometheus

O arquivo `monitoring/prometheus/prometheus.yml` deve ter:

```yaml
- job_name: 'auth-service'
  static_configs:
    - targets: ['auth-service:8000']
  
- job_name: 'inventory-service'
  static_configs:
    - targets: ['inventory-service:8000']
  
- job_name: 'sales-service'
  static_configs:
    - targets: ['sales-service:8000']
```

**Nota:** Os targets usam a porta INTERNA (8000), não as portas externas (9001, 9002, 9003).

#### 3️⃣ Reinicie o Prometheus

```bash
docker compose restart prometheus
```

---

## 📈 Métricas de negócio não aparecem

### Problema
Métricas como `sales_orders_created_total`, `auth_login_attempts_total` não aparecem.

### Solução

As métricas devem ter **prefixos corretos**:

**Auth Service:**
- `auth_login_attempts_total`
- `auth_login_success_total`
- `auth_login_failed_total`
- `auth_users_registered_total`
- `auth_tokens_generated_total`

**Inventory Service:**
- `inventory_products_created_total`
- `inventory_products_updated_total`
- `inventory_stock_adjustments_total`
- `inventory_low_stock_products`
- `inventory_categories_created_total`

**Sales Service:**
- `sales_orders_created_total`
- `sales_orders_confirmed_total`
- `sales_orders_cancelled_total`
- `sales_customers_created_total`

**Verifique no MetricsController:**

```php
// ❌ ERRADO
$metrics[] = sprintf('orders_created_total{service="sales-service"} %d', $count);

// ✅ CORRETO
$metrics[] = sprintf('sales_orders_created_total{service="sales-service"} %d', $count);
```

---

## 🔐 Erro de autenticação no Grafana

### Problema
Não consegue fazer login no Grafana.

### Solução

**Credenciais padrão:**
- Usuário: `admin`
- Senha: `admin123`

**Se esqueceu a senha:**

```bash
# Resetar senha do Grafana
docker compose exec grafana grafana-cli admin reset-admin-password admin123
```

---

## ⚠️ Alertas não estão funcionando

### Problema
Alertas configurados no Prometheus não estão sendo disparados.

### Soluções

#### 1️⃣ Verifique as regras de alerta

```bash
# Verificar se as regras foram carregadas
curl -s http://localhost:9090/api/v1/rules | jq -r '.data.groups[].rules[] | .name'
```

#### 2️⃣ Verifique o Alertmanager

```bash
# Verificar status
curl -s http://localhost:9093/-/healthy
```

#### 3️⃣ Ver alertas ativos

```bash
# No Prometheus
curl -s http://localhost:9090/api/v1/alerts | jq '.data.alerts[] | {name: .labels.alertname, state: .state}'
```

---

## 🐳 Container do Grafana não inicia

### Problema
`docker compose ps` mostra Grafana com status "Restarting" ou "Exited".

### Soluções

#### 1️⃣ Verificar logs

```bash
docker compose logs grafana | tail -50
```

#### 2️⃣ Verificar permissões

```bash
# Verificar permissões dos volumes
ls -la monitoring/grafana/
```

#### 3️⃣ Recriar container

```bash
docker compose down grafana
docker volume rm microservices_grafana-data
docker compose up -d grafana
```

---

## 📊 Dashboard não atualiza automaticamente

### Problema
Mesmo com refresh automático configurado, o dashboard não atualiza.

### Soluções

#### 1️⃣ Verifique a configuração de refresh

- Clique no dropdown de refresh (canto superior direito)
- Selecione um intervalo (5s, 10s, 30s)

#### 2️⃣ Verifique o Time Range

- Selecione "Last 15 minutes" ou "Last 30 minutes"
- Evite usar "Last 6 hours" para dados recentes

#### 3️⃣ Gere carga contínua

```bash
# Manter gerando métricas
./scripts/stress-test.sh 300 &
```

---

## 🔍 Como verificar se tudo está funcionando

Execute este checklist completo:

```bash
# 1. Serviços rodando
docker compose ps | grep -E "auth-service|inventory-service|sales-service|prometheus|grafana"

# 2. Endpoints /metrics respondendo
curl -s http://localhost:9001/metrics | grep "^auth_" | head -5
curl -s http://localhost:9002/metrics | grep "^inventory_" | head -5
curl -s http://localhost:9003/metrics | grep "^sales_" | head -5

# 3. Prometheus coletando métricas
curl -s "http://localhost:9090/api/v1/targets" | jq -r '.data.activeTargets[] | select(.labels.job | contains("service")) | "\(.labels.job): \(.health)"'

# 4. Grafana datasource OK
curl -s -u admin:admin123 'http://localhost:3000/api/datasources' | jq -r '.[] | select(.type=="prometheus") | "✅ \(.name) - UID: \(.uid)"'

# 5. Dashboard existe
curl -s -u admin:admin123 'http://localhost:3000/api/search?type=dash-db' | jq -r '.[] | "✅ \(.title)"'
```

**Resultado esperado:**
```
✅ Todos os serviços UP
✅ Métricas com prefixos corretos
✅ Prometheus targets UP
✅ Prometheus - UID: prometheus
✅ Microservices Overview
```

---

## 📚 Links Úteis

- **Prometheus Query Language:** https://prometheus.io/docs/prometheus/latest/querying/basics/
- **Grafana Documentation:** https://grafana.com/docs/
- **Dashboard JSON Reference:** https://grafana.com/docs/grafana/latest/dashboards/json-model/

---

## 🆘 Ainda com problemas?

1. Execute o script de status:
   ```bash
   ./scripts/status.sh
   ```

2. Verifique os logs:
   ```bash
   ./scripts/logs.sh grafana
   ./scripts/logs.sh prometheus
   ```

3. Reinicie tudo:
   ```bash
   docker compose restart
   ```

4. Em último caso, recrie tudo:
   ```bash
   ./scripts/clean.sh
   ./scripts/start.sh
   ```

