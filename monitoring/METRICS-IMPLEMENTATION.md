# 📊 Guia de Implementação de Métricas

Este documento explica como as métricas foram implementadas nos microserviços.

---

## 🏗️ Arquitetura

```
┌─────────────────┐
│  Microservice   │
│                 │
│  ┌───────────┐  │
│  │ Routes    │  │ ← /metrics endpoint
│  │ web.php   │  │
│  └─────┬─────┘  │
│        │        │
│  ┌─────▼──────┐ │
│  │ Metrics    │ │ ← Expõe métricas
│  │ Controller │ │
│  └─────┬──────┘ │
│        │        │
│  ┌─────▼──────┐ │
│  │ Cache/     │ │ ← Armazena contadores
│  │ Redis      │ │
│  └────────────┘ │
└─────────────────┘
         │
         │ scrape (15s)
         ▼
┌─────────────────┐
│   Prometheus    │ ← Coleta métricas
└─────────────────┘
         │
         │ query
         ▼
┌─────────────────┐
│    Grafana      │ ← Visualiza
└─────────────────┘
```

---

## 📝 Implementação Passo a Passo

### 1️⃣ Criar MetricsController

**Localização:** `app/Http/Controllers/MetricsController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class MetricsController extends Controller
{
    public function index()
    {
        $metrics = [];
        
        // Application info
        $metrics[] = '# HELP app_info Application information';
        $metrics[] = '# TYPE app_info gauge';
        $metrics[] = sprintf('app_info{service="%s",version="%s"} 1', 
            'service-name', 
            config('app.version', '1.0.0')
        );
        
        // Service UP
        $metrics[] = '# HELP up Service is up';
        $metrics[] = '# TYPE up gauge';
        $metrics[] = sprintf('up{service="%s"} 1', 'service-name');
        
        // HTTP metrics
        $requests = cache()->get('metrics:http_requests_total', 0);
        $metrics[] = '# HELP http_requests_total Total HTTP requests';
        $metrics[] = '# TYPE http_requests_total counter';
        $metrics[] = sprintf('http_requests_total{service="%s"} %d', 
            'service-name', 
            $requests
        );
        
        // Response time
        $responseTime = cache()->get('metrics:http_request_duration_seconds', 0.1);
        $metrics[] = '# HELP http_request_duration_seconds HTTP request duration';
        $metrics[] = '# TYPE http_request_duration_seconds gauge';
        $metrics[] = sprintf('http_request_duration_seconds{service="%s"} %.3f', 
            'service-name', 
            $responseTime
        );
        
        // Memory usage
        $metrics[] = '# HELP php_memory_usage_bytes PHP memory usage';
        $metrics[] = '# TYPE php_memory_usage_bytes gauge';
        $metrics[] = sprintf('php_memory_usage_bytes{service="%s"} %d', 
            'service-name', 
            memory_get_usage(true)
        );
        
        // Business metrics (exemplo para Auth Service)
        $loginAttempts = cache()->get('metrics:login_attempts_total', 0);
        $metrics[] = '# HELP auth_login_attempts_total Total login attempts';
        $metrics[] = '# TYPE auth_login_attempts_total counter';
        $metrics[] = sprintf('auth_login_attempts_total{service="auth-service"} %d', 
            $loginAttempts
        );
        
        return response(implode("\n", $metrics) . "\n", Response::HTTP_OK)
            ->header('Content-Type', 'text/plain; version=0.0.4');
    }
}
```

### 2️⃣ Registrar Rota em web.php

**⚠️ IMPORTANTE:** A rota DEVE estar em `routes/web.php`, não apenas em `api.php`!

**Localização:** `routes/web.php`

```php
<?php

use App\Http\Controllers\MetricsController;
use Illuminate\Support\Facades\Route;

// Metrics endpoint for Prometheus (without 'api' prefix)
Route::get('/metrics', [MetricsController::class, 'index']);

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'service-name',
        'timestamp' => now()->toIso8601String(),
    ]);
});
```

**Por que web.php e não api.php?**
- Prometheus acessa `/metrics` diretamente
- `api.php` adiciona prefixo `/api/metrics`
- Resultado: 404 Not Found

### 3️⃣ Criar MetricsMiddleware (Opcional)

Para coletar métricas automaticamente de todas as requisições.

**Localização:** `app/Http/Middleware/MetricsMiddleware.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MetricsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $duration = microtime(true) - $startTime;
        
        // Increment total requests
        cache()->increment('metrics:http_requests_total');
        
        // Increment by status code
        $status = $response->getStatusCode();
        cache()->increment("metrics:http_requests_status_{$status}");
        
        // Store last request duration
        cache()->put('metrics:http_request_duration_seconds', $duration);
        
        // Store memory usage
        cache()->put('metrics:php_memory_usage_bytes', memory_get_usage(true));
        
        return $response;
    }
}
```

**Registrar no Kernel:** `bootstrap/app.php` (Laravel 11+)

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \App\Http\Middleware\MetricsMiddleware::class,
    ]);
})
```

### 4️⃣ Incrementar Métricas de Negócio

Nos seus Use Cases ou Controllers, incremente as métricas relevantes:

```php
// Exemplo: LoginUseCase
public function execute(LoginUserDTO $dto): LoginUserOutputDTO
{
    // Incrementar tentativas de login
    cache()->increment('metrics:login_attempts_total');
    
    try {
        // Lógica de autenticação
        $user = $this->userRepository->findByEmail($dto->email);
        
        if (!$user || !Hash::check($dto->password, $user->password)) {
            cache()->increment('metrics:login_failed_total');
            throw new InvalidCredentialsException();
        }
        
        cache()->increment('metrics:login_success_total');
        cache()->increment('metrics:tokens_generated_total');
        
        // Retornar resultado
        return new LoginUserOutputDTO(/* ... */);
    } catch (\Exception $e) {
        cache()->increment('metrics:login_failed_total');
        throw $e;
    }
}
```

---

## 🎯 Convenções de Nomenclatura

### Prefixos por Serviço

**IMPORTANTE:** Todas as métricas de negócio devem ter o prefixo do serviço!

| Serviço | Prefixo | Exemplos |
|---------|---------|----------|
| Auth | `auth_` | `auth_login_attempts_total`<br>`auth_users_registered_total` |
| Inventory | `inventory_` | `inventory_products_created_total`<br>`inventory_stock_adjustments_total` |
| Sales | `sales_` | `sales_orders_created_total`<br>`sales_customers_created_total` |

### Métricas Comuns (sem prefixo)

Estas métricas são comuns a todos os serviços:

- `http_requests_total`
- `http_request_duration_seconds`
- `php_memory_usage_bytes`
- `up`
- `app_info`

### Tipos de Métricas

**Counter** (sempre incrementa):
- `*_total` - Total de eventos
- Exemplo: `auth_login_attempts_total`

**Gauge** (pode subir e descer):
- `*_bytes` - Tamanhos
- `*_seconds` - Durações
- `*_count` - Contagens atuais
- Exemplo: `php_memory_usage_bytes`

**Histogram/Summary** (para percentis):
- `*_bucket` - Buckets de histograma
- `*_count` - Número de observações
- `*_sum` - Soma das observações

---

## ⚙️ Configuração do Prometheus

**Localização:** `monitoring/prometheus/prometheus.yml`

```yaml
scrape_configs:
  # Auth Service
  - job_name: 'auth-service'
    metrics_path: '/metrics'
    static_configs:
      - targets: ['auth-service:8000']
    relabel_configs:
      - source_labels: [__address__]
        target_label: instance
        replacement: 'auth-service'
      - target_label: service
        replacement: 'auth-service'
  
  # Inventory Service
  - job_name: 'inventory-service'
    metrics_path: '/metrics'
    static_configs:
      - targets: ['inventory-service:8000']
    relabel_configs:
      - source_labels: [__address__]
        target_label: instance
        replacement: 'inventory-service'
      - target_label: service
        replacement: 'inventory-service'
  
  # Sales Service
  - job_name: 'sales-service'
    metrics_path: '/metrics'
    static_configs:
      - targets: ['sales-service:8000']
    relabel_configs:
      - source_labels: [__address__]
        target_label: instance
        replacement: 'sales-service'
      - target_label: service
        replacement: 'sales-service'
```

**Notas importantes:**
- Use a porta **INTERNA** (8000), não a porta externa do host
- `metrics_path` deve ser `/metrics`
- `relabel_configs` adiciona labels úteis

---

## 📊 Queries PromQL Úteis

### Métricas de Infraestrutura

```promql
# Taxa de requisições por serviço
rate(http_requests_total[5m])

# Latência média
avg(http_request_duration_seconds) by (service)

# Taxa de erro (status 5xx)
rate(http_requests_total{status=~"5.."}[5m]) / rate(http_requests_total[5m]) * 100

# Uso de memória
php_memory_usage_bytes
```

### Métricas de Negócio

```promql
# Logins bem-sucedidos por hora
increase(auth_login_success_total[1h])

# Taxa de falha de login
rate(auth_login_failed_total[5m]) / rate(auth_login_attempts_total[5m]) * 100

# Pedidos criados hoje
increase(sales_orders_created_total[24h])

# Taxa de cancelamento de pedidos
increase(sales_orders_cancelled_total[1h]) / increase(sales_orders_created_total[1h]) * 100

# Produtos com estoque baixo
inventory_low_stock_products
```

---

## ✅ Checklist de Implementação

Para adicionar métricas em um novo serviço:

- [ ] Criar `MetricsController.php`
- [ ] Registrar rota em `routes/web.php` (não api.php!)
- [ ] Adicionar métricas básicas (up, app_info, http_requests_total)
- [ ] Adicionar métricas de infraestrutura (response_time, memory)
- [ ] Identificar métricas de negócio relevantes
- [ ] Adicionar prefixo correto nas métricas (ex: `auth_`, `sales_`)
- [ ] Incrementar métricas nos Use Cases relevantes
- [ ] Configurar scrape no Prometheus
- [ ] Testar endpoint `/metrics`
- [ ] Criar painel no Grafana

---

## 🧪 Testando Métricas

```bash
# 1. Verificar se o endpoint está acessível
curl http://localhost:9001/metrics

# 2. Verificar se as métricas têm os prefixos corretos
curl http://localhost:9001/metrics | grep "^auth_"

# 3. Gerar carga para incrementar métricas
./scripts/stress-test.sh 30

# 4. Verificar no Prometheus
curl -s "http://localhost:9090/api/v1/query?query=auth_login_attempts_total"

# 5. Visualizar no Grafana
# http://localhost:3000/d/microservices-overview
```

---

## 📚 Referências

- **Prometheus Best Practices:** https://prometheus.io/docs/practices/naming/
- **Metric Types:** https://prometheus.io/docs/concepts/metric_types/
- **PromQL:** https://prometheus.io/docs/prometheus/latest/querying/basics/
- **Grafana Dashboards:** https://grafana.com/docs/grafana/latest/dashboards/

---

## 🚨 Problemas Comuns

### Métricas não aparecem no Prometheus

1. Verifique se a rota está em `web.php`
2. Teste o endpoint diretamente: `curl http://localhost:9001/metrics`
3. Verifique os targets: http://localhost:9090/targets
4. Verifique os logs do Prometheus

### Dashboard Grafana sem dados

1. Verifique o UID do datasource: deve ser `prometheus`
2. Verifique se as métricas têm os prefixos corretos
3. Gere carga: `./scripts/stress-test.sh 60`
4. Faça hard refresh: `Ctrl+Shift+R`

### Métricas sempre em 0

1. Verifique se o cache/Redis está funcionando
2. Verifique se as métricas estão sendo incrementadas no código
3. Execute os Use Cases que incrementam as métricas

Consulte [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) para mais detalhes.

