# 🚀 Guia Rápido de Observabilidade

## ⚡ Start Rápido (5 minutos)

### 1. Subir Stack de Monitoring
```bash
# Subir todos os serviços de observabilidade
docker-compose up -d prometheus grafana alertmanager node-exporter cadvisor redis-exporter

# Verificar se subiram
docker ps | grep -E "prometheus|grafana|alert"
```

### 2. Acessar Interfaces

| Serviço | URL | Credenciais |
|---------|-----|-------------|
| **Grafana** | http://localhost:3000 | admin / admin123 |
| **Prometheus** | http://localhost:9090 | - |
| **Alertmanager** | http://localhost:9093 | - |

### 3. Verificar Coleta de Métricas

```bash
# Ver targets sendo coletados
curl http://localhost:9090/api/v1/targets | jq '.data.activeTargets[] | {job: .labels.job, health: .health}'

# Ver métricas do Sales Service
curl http://localhost:8002/metrics
```

### 4. Ver Dashboards no Grafana

1. Abra http://localhost:3000
2. Login: admin / admin123
3. Menu > Dashboards
4. Explore os dashboards pré-configurados

## 📊 Métricas Principais

### Verificar Health dos Serviços
```promql
# No Prometheus (http://localhost:9090)
up
```

### Ver Taxa de Requisições
```promql
rate(http_requests_total[5m])
```

### Ver Taxa de Erro
```promql
rate(http_requests_errors_total[5m]) / rate(http_requests_total[5m])
```

## 🚨 Testar Alertas

### Gerar Carga (simular tráfego)
```bash
# Instalar apache bench (se necessário)
sudo apt-get install apache2-utils

# Gerar 1000 requisições
ab -n 1000 -c 10 http://localhost:8002/api/health
```

### Verificar Alertas Ativos
```bash
curl http://localhost:9090/api/v1/alerts | jq
```

## 🎨 Queries Úteis

### No Prometheus

**Taxa de pedidos criados por hora:**
```promql
increase(orders_created_total[1h])
```

**Memória usada pelos containers:**
```promql
container_memory_usage_bytes{name=~".*-service"}
```

**CPU dos serviços:**
```promql
rate(container_cpu_usage_seconds_total{name=~".*-service"}[5m]) * 100
```

## 🔧 Troubleshooting

### Grafana não mostra dados
```bash
# 1. Verificar se Prometheus está rodando
curl http://localhost:9090/-/healthy

# 2. Verificar datasource no Grafana
curl -u admin:admin123 http://localhost:3000/api/datasources
```

### Métricas não aparecem
```bash
# 1. Verificar se endpoint /metrics responde
curl http://localhost:8002/metrics

# 2. Ver targets no Prometheus
curl http://localhost:9090/api/v1/targets
```

### Alertas não disparam
```bash
# Ver regras configuradas
curl http://localhost:9090/api/v1/rules

# Logs do Alertmanager
docker logs alertmanager
```

## 📈 Dashboards Recomendados

1. **Overview**: Status geral do sistema
2. **Sales Service**: Métricas específicas de vendas
3. **Infrastructure**: CPU, Memory, Disk
4. **RabbitMQ**: Filas e mensagens

## 🎯 Próximos Passos

1. Configurar Slack para alertas
2. Adicionar mais métricas de negócio
3. Criar dashboards customizados
4. Configurar retenção de longo prazo

## 📚 Documentação Completa

Ver `monitoring/README.md` para documentação completa.
