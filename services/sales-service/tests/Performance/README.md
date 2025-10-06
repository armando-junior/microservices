# Performance Tests - Sales Service

Este diretório contém scripts e ferramentas para testar a performance do Sales Service.

## 🛠️ Ferramentas

### 1. Apache Bench (ab)

**Instalação:**
```bash
# Ubuntu/Debian
sudo apt-get install apache2-utils

# macOS
brew install httpd
```

**Uso:**
```bash
# Teste básico
./benchmark.sh

# Com JWT token
JWT_TOKEN="your-token-here" ./benchmark.sh

# Customizar parâmetros
CONCURRENCY=20 REQUESTS=2000 JWT_TOKEN="token" ./benchmark.sh
```

**Parâmetros:**
- `BASE_URL`: URL base do serviço (padrão: http://localhost:9003/api/v1)
- `JWT_TOKEN`: Token JWT para endpoints protegidos
- `CONCURRENCY`: Número de requisições concorrentes (padrão: 10)
- `REQUESTS`: Total de requisições (padrão: 1000)

---

### 2. Locust (Python)

**Instalação:**
```bash
pip install locust
```

**Uso:**
```bash
# Modo web (interface gráfica)
JWT_TOKEN="your-token" locust -f load-test.py --host=http://localhost:9003

# Modo headless (sem interface)
JWT_TOKEN="your-token" locust -f load-test.py \
    --host=http://localhost:9003 \
    --users 100 \
    --spawn-rate 10 \
    --run-time 60s \
    --headless

# Salvar resultados
locust -f load-test.py \
    --host=http://localhost:9003 \
    --users 50 \
    --spawn-rate 5 \
    --run-time 120s \
    --html report.html \
    --headless
```

**Parâmetros:**
- `--users`: Número de usuários simultâneos
- `--spawn-rate`: Taxa de criação de usuários por segundo
- `--run-time`: Duração do teste (ex: 60s, 5m, 1h)
- `--html`: Gerar relatório HTML
- `--csv`: Salvar resultados em CSV

**Web UI:**
1. Inicie o Locust: `locust -f load-test.py --host=http://localhost:9003`
2. Abra http://localhost:8089
3. Configure número de usuários e spawn rate
4. Clique em "Start Swarming"

---

## 📊 Métricas Importantes

### Apache Bench

- **Requests per second (req/s)**: Quantas requisições por segundo o serviço suporta
- **Time per request (ms)**: Tempo médio de resposta
- **Transfer rate (KB/s)**: Taxa de transferência de dados
- **Connection Times**: min/mean/median/max
- **Percentage served**: Percentis (50%, 66%, 75%, 80%, 90%, 95%, 98%, 99%, 100%)

### Locust

- **RPS (Requests Per Second)**: Taxa de requisições
- **Response Time**: Percentis (50%, 95%, 99%)
- **Failures**: Taxa de erros
- **Users**: Usuários simulados ativos

---

## 🎯 Benchmarks Esperados

### Health Check Endpoint
- **Target RPS**: > 1000 req/s
- **Response Time (p95)**: < 10ms
- **Error Rate**: < 0.1%

### List Endpoints (GET)
- **Target RPS**: > 500 req/s
- **Response Time (p95)**: < 50ms
- **Error Rate**: < 0.5%

### Create Endpoints (POST)
- **Target RPS**: > 200 req/s
- **Response Time (p95)**: < 100ms
- **Error Rate**: < 1%

---

## 🚀 Scenarios de Teste

### 1. Smoke Test
```bash
CONCURRENCY=1 REQUESTS=10 ./benchmark.sh
```
Verificação básica de funcionamento.

### 2. Load Test
```bash
CONCURRENCY=10 REQUESTS=1000 ./benchmark.sh
```
Teste com carga normal esperada.

### 3. Stress Test
```bash
CONCURRENCY=50 REQUESTS=5000 ./benchmark.sh
```
Teste com carga alta para identificar limites.

### 4. Spike Test
```bash
locust -f load-test.py --users 200 --spawn-rate 50 --run-time 60s --headless
```
Teste com pico súbito de usuários.

### 5. Endurance Test
```bash
locust -f load-test.py --users 50 --spawn-rate 5 --run-time 30m --headless
```
Teste de longa duração para detectar memory leaks.

---

## 📝 Dicas

1. **Ambiente de Produção**: Rode testes em ambiente similar à produção
2. **Warm-up**: Faça um aquecimento antes dos testes principais
3. **Monitoramento**: Use Prometheus/Grafana para monitorar métricas
4. **Database**: Considere o impacto no banco de dados
5. **Cache**: Teste com cache frio e quente
6. **Limpe Dados**: Reset o banco entre testes para resultados consistentes

---

## 🔧 Troubleshooting

### "Too many open files"
```bash
ulimit -n 10000
```

### "Connection refused"
Verifique se o serviço está rodando:
```bash
docker compose ps sales-service
curl http://localhost:9003/api/health
```

### "Unauthorized (401)"
Gere um JWT token válido:
```bash
# Login no auth service primeiro
TOKEN=$(curl -X POST http://localhost:9001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}' | jq -r '.data.token')

# Use o token nos testes
JWT_TOKEN="$TOKEN" ./benchmark.sh
```

