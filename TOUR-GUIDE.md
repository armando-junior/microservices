# 🎯 Tour Guiado - Infraestrutura ERP Microserviços

## 🐰 1. RabbitMQ Management UI

### 🌐 Acesso
```
URL: http://localhost:15672
Usuário: admin
Senha: admin123
```

### 📊 Dashboard Principal (Overview)

Ao fazer login, você verá:

1. **Message Rates**
   - Incoming: Taxa de mensagens recebidas
   - Outgoing: Taxa de mensagens enviadas
   - Gráficos em tempo real

2. **Totais**
   - ✅ 13 Exchanges
   - ✅ 12 Queues
   - 0 Connections (aguardando microserviços)
   - 0 Consumers (aguardando microserviços)

3. **Nodes**
   - rabbit@[container-id]
   - Status: Running
   - Memória e disco em uso

### 🔄 Exchanges Tab

Clique em **"Exchanges"** no menu superior:

```
┌─────────────────────────────────────────────────────────────┐
│ EXCHANGE NAME       TYPE     FEATURES      PURPOSE          │
├─────────────────────────────────────────────────────────────┤
│ auth.events         topic    D            Eventos Auth      │
│ inventory.events    topic    D            Eventos Estoque   │
│ sales.events        topic    D            Eventos Vendas    │
│ logistics.events    topic    D            Eventos Logística │
│ financial.events    topic    D            Eventos Financ.   │
│ dlx                 direct   D            Dead Letters      │
└─────────────────────────────────────────────────────────────┘

D = Durable (persiste após restart)
```

**O que fazer:**
1. Clique em qualquer exchange (ex: `sales.events`)
2. Veja os **bindings** (para quais queues as mensagens vão)
3. Na seção **"Publish message"** você pode testar enviando uma mensagem

**Teste Prático:**
```
Exchange: sales.events
Routing key: sales.order.created
Payload: {"order_id": "TEST-001", "customer_id": "CUST-123"}
```

### 📥 Queues Tab

Clique em **"Queues"** no menu superior:

```
┌─────────────────────────────────────────────────────────────┐
│ QUEUE NAME           READY  UNACKED  TOTAL  CONSUMERS       │
├─────────────────────────────────────────────────────────────┤
│ inventory.queue         0      0       0        0           │
│ sales.queue             0      0       0        0           │
│ logistics.queue         0      0       0        0           │
│ financial.queue         0      0       0        0           │
│ notification.queue      0      0       0        0           │
│ auth.queue              0      0       0        0           │
├─────────────────────────────────────────────────────────────┤
│ DLQs (Dead Letter Queues)                                   │
├─────────────────────────────────────────────────────────────┤
│ inventory.dlq           0      0       0        0           │
│ sales.dlq               0      0       0        0           │
│ logistics.dlq           0      0       0        0           │
│ financial.dlq           0      0       0        0           │
│ notification.dlq        0      0       0        0           │
│ auth.dlq                0      0       0        0           │
└─────────────────────────────────────────────────────────────┘
```

**O que fazer:**
1. Clique em uma queue (ex: `sales.queue`)
2. Veja detalhes: bindings, consumers, mensagens
3. Seção **"Get messages"**: permite visualizar mensagens na fila
4. Seção **"Purge"**: limpar todas as mensagens (use com cuidado!)

### 🔗 Bindings (Fluxo de Mensagens)

**Fluxo Configurado:**

```
📤 sales.events (exchange)
   ├─→ inventory.queue      (routing: sales.order.*)
   └─→ notification.queue   (routing: sales.order.*)

📤 inventory.events (exchange)
   └─→ sales.queue          (routing: inventory.stock.*)

📤 financial.events (exchange)
   ├─→ sales.queue          (routing: financial.payment.*)
   └─→ notification.queue   (routing: financial.payment.*)

📤 logistics.events (exchange)
   ├─→ sales.queue          (routing: logistics.shipment.delivered)
   └─→ notification.queue   (routing: logistics.shipment.*)

📤 auth.events (exchange)
   └─→ notification.queue   (routing: auth.user.registered)

📤 dlx (Dead Letter Exchange)
   ├─→ inventory.dlq        (routing: inventory.dlq)
   ├─→ sales.dlq            (routing: sales.dlq)
   ├─→ logistics.dlq        (routing: logistics.dlq)
   ├─→ financial.dlq        (routing: financial.dlq)
   └─→ notification.dlq     (routing: notification.dlq)
```

### 🎯 Exemplo de Saga de Venda

Quando um pedido for criado (futuramente):

```
1. Sales Service publica:
   Exchange: sales.events
   Routing: sales.order.created
   Payload: {order_id, customer_id, items, total}
   
2. Mensagem vai para:
   → inventory.queue (reservar estoque)
   → notification.queue (notificar cliente)

3. Inventory Service processa e publica:
   Exchange: inventory.events
   Routing: inventory.stock.reserved
   
4. Mensagem vai para:
   → sales.queue (confirmar reserva)

5. Sales Service publica:
   Exchange: sales.events
   Routing: sales.order.confirmed
   
... e assim por diante
```

### 🛠️ Comandos CLI Úteis

```bash
# Listar exchanges
docker compose exec rabbitmq rabbitmqctl list_exchanges

# Listar queues
docker compose exec rabbitmq rabbitmqctl list_queues name messages

# Listar bindings
docker compose exec rabbitmq rabbitmqctl list_bindings

# Ver consumers
docker compose exec rabbitmq rabbitmqctl list_consumers

# Status do RabbitMQ
docker compose exec rabbitmq rabbitmqctl status

# Limpar uma queue
docker compose exec rabbitmq rabbitmqctl purge_queue sales.queue
```

---

## 📊 2. Grafana

### 🌐 Acesso
```
URL: http://localhost:3000
Usuário: admin
Senha: admin
```

### 🏠 Home Dashboard

Ao fazer login pela primeira vez:
1. Pode pedir para alterar a senha → Você pode pular ou alterar
2. Você verá o **Welcome to Grafana**

### 📈 Criando Seu Primeiro Dashboard

**Passo 1: Verificar Data Source**
1. Clique no ícone ⚙️ (Configuration) no menu lateral
2. Clique em **"Data sources"**
3. Você deve ver **"Prometheus"** já configurado ✅
4. Clique nele e clique em **"Test"** → Deve mostrar "Data source is working"

**Passo 2: Criar Dashboard**
1. Clique no ➕ (Create) no menu lateral
2. Selecione **"Dashboard"**
3. Clique em **"Add visualization"**
4. Selecione **"Prometheus"** como data source

**Passo 3: Adicionar Painel de Exemplo**

Query de exemplo (quando microserviços estiverem rodando):
```
# CPU Usage
process_cpu_seconds_total

# Memory Usage
process_resident_memory_bytes

# HTTP Requests
http_requests_total

# RabbitMQ Queue Size
rabbitmq_queue_messages{queue="sales.queue"}
```

### 📊 Painéis Recomendados

1. **RabbitMQ Monitoring**
   - Queue sizes
   - Message rates
   - Consumer count
   - Connection count

2. **Service Health**
   - HTTP response times
   - Error rates
   - Request count
   - Success rate

3. **Database**
   - Connection pool
   - Query duration
   - Active connections

4. **System**
   - CPU usage
   - Memory usage
   - Disk I/O

### 🎨 Importar Dashboard Pronto

1. Clique em ➕ (Create) → **"Import"**
2. Use estes IDs de dashboards prontos:
   - **4279** - RabbitMQ Monitoring
   - **3662** - Prometheus Stats
   - **7362** - PostgreSQL Database
3. Clique em **"Load"** e depois **"Import"**

### 🔔 Alertas

1. No dashboard, clique em um painel
2. Clique em **"Edit"**
3. Vá na aba **"Alert"**
4. Configure alertas (ex: queue > 1000 mensagens)

---

## 🔥 3. Prometheus

### 🌐 Acesso
```
URL: http://localhost:9090
```

### 🎯 Interface Principal

**Aba: Graph**
- Execute queries PromQL
- Visualize métricas em gráficos
- Teste expressões

**Aba: Alerts**
- Ver alertas configurados
- Status: Firing, Pending, Inactive

**Aba: Status**
- Targets: Serviços sendo monitorados
- Configuration: Config do Prometheus
- Rules: Regras de alerta

### 📊 Queries PromQL de Exemplo

```promql
# Ver todos os targets
up

# RabbitMQ queue size
rabbitmq_queue_messages

# RabbitMQ message rate
rate(rabbitmq_queue_messages_published_total[5m])

# HTTP requests (quando serviços estiverem rodando)
rate(http_requests_total[5m])

# Memory usage
process_resident_memory_bytes

# CPU usage
rate(process_cpu_seconds_total[5m])
```

### 🎯 Targets (Status → Targets)

Veja quais serviços estão sendo monitorados:
- ✅ prometheus (self)
- ✅ rabbitmq
- ⏳ auth-service (aguardando implementação)
- ⏳ inventory-service (aguardando)
- ⏳ sales-service (aguardando)
- ⏳ logistics-service (aguardando)
- ⏳ financial-service (aguardando)

---

## 🔍 4. Jaeger (Distributed Tracing)

### 🌐 Acesso
```
URL: http://localhost:16686
```

### 📍 Interface Principal

**O que é Distributed Tracing?**
- Rastreia uma requisição através de múltiplos serviços
- Mostra o tempo gasto em cada serviço
- Identifica gargalos
- Debug de problemas em microserviços

### 🔎 Como Usar (quando microserviços estiverem rodando)

1. **Service**: Selecione o serviço (ex: sales-service)
2. **Operation**: Selecione a operação (ex: POST /api/orders)
3. **Lookback**: Período de tempo (ex: last hour)
4. Clique em **"Find Traces"**

### 📊 O que você verá:

```
Trace Timeline:

api-gateway          [====]  20ms
  └─ sales-service   [=======]  50ms
      ├─ inventory-service [===]  30ms
      │   └─ PostgreSQL   [==]  15ms
      └─ RabbitMQ        [=]   5ms

Total: 105ms
```

**Cada span mostra:**
- Nome do serviço
- Operação executada
- Duração
- Tags (metadata)
- Logs

### 🎯 Use Cases

1. **Performance Debugging**: Encontrar serviços lentos
2. **Error Tracking**: Ver onde erros ocorrem na cadeia
3. **Dependency Mapping**: Visualizar dependências entre serviços
4. **Latency Analysis**: Identificar gargalos

---

## 🚪 5. Kong API Gateway

### 🌐 Acesso Admin API
```
URL: http://localhost:8001
```

### 🌐 Proxy (onde requests vão passar)
```
URL: http://localhost:8000
```

### 📋 Endpoints Principais

```bash
# Status
curl http://localhost:8001/status

# Listar Services
curl http://localhost:8001/services

# Listar Routes
curl http://localhost:8001/routes

# Listar Plugins
curl http://localhost:8001/plugins

# Health Check
curl http://localhost:8001/
```

### ➕ Adicionar um Service (Exemplo)

```bash
# Criar service para auth
curl -i -X POST http://localhost:8001/services \
  --data name=auth-service \
  --data url='http://auth-service:8000'

# Criar route para o service
curl -i -X POST http://localhost:8001/services/auth-service/routes \
  --data 'paths[]=/auth' \
  --data name=auth-route

# Agora http://localhost:8000/auth/* vai para auth-service
```

### 🔌 Plugins Úteis

```bash
# Rate Limiting
curl -X POST http://localhost:8001/services/auth-service/plugins \
  --data "name=rate-limiting" \
  --data "config.second=5" \
  --data "config.hour=10000"

# CORS
curl -X POST http://localhost:8001/services/auth-service/plugins \
  --data "name=cors" \
  --data "config.origins=*"

# Request Logging
curl -X POST http://localhost:8001/services/auth-service/plugins \
  --data "name=file-log" \
  --data "config.path=/tmp/requests.log"

# JWT Authentication
curl -X POST http://localhost:8001/services/auth-service/plugins \
  --data "name=jwt"
```

### 🎯 Kong Manager (GUI) - Não configurado ainda

Kong também tem uma GUI, mas requer Kong Enterprise ou configuração adicional.
Por enquanto, use a Admin API.

---

## 📝 6. Kibana (Logs)

### 🌐 Acesso
```
URL: http://localhost:5601
Usuário: elastic
Senha: jr120777
```

### 🚀 First Time Setup

**Passo 1: Skip Welcome**
- Clique em **"Explore on my own"**

**Passo 2: Criar Index Pattern** (quando logs chegarem)
1. Menu ☰ → Management → Stack Management
2. Kibana → Index Patterns
3. Clique em **"Create index pattern"**
4. Pattern name: `microservices-*`
5. Time field: `@timestamp`
6. Clique em **"Create index pattern"**

**Passo 3: Ver Logs**
1. Menu ☰ → Analytics → Discover
2. Selecione o index pattern `microservices-*`
3. Veja os logs em tempo real!

### 🔍 Queries KQL (Kibana Query Language)

```
# Logs de um serviço específico
service: "auth-service"

# Logs de erro
level: "error"

# Logs de um endpoint
message: "/api/orders"

# Combinações
service: "sales-service" AND level: "error"

# Range de tempo
@timestamp >= "2025-10-04" AND @timestamp < "2025-10-05"
```

### 📊 Criar Visualização

1. Menu ☰ → Analytics → Visualize Library
2. Clique em **"Create visualization"**
3. Escolha tipo (Line, Bar, Pie, etc)
4. Configure agregações e filtros

**Exemplos:**
- **Error Rate**: Count de logs com `level: error` ao longo do tempo
- **Service Activity**: Count de logs por serviço
- **Response Time**: Average de `duration` por endpoint

---

## 🎯 Comandos Rápidos de Validação

### Verificar Todos os Serviços

```bash
# Status de containers
docker compose ps

# Health checks
echo "Kong:" && curl -s http://localhost:8001/status | grep -q database && echo "✅ OK" || echo "❌ FAIL"
echo "RabbitMQ:" && curl -s -u admin:admin123 http://localhost:15672/api/overview > /dev/null && echo "✅ OK" || echo "❌ FAIL"
echo "Grafana:" && curl -s http://localhost:3000 > /dev/null && echo "✅ OK" || echo "❌ FAIL"
echo "Prometheus:" && curl -s http://localhost:9090 > /dev/null && echo "✅ OK" || echo "❌ FAIL"
echo "Jaeger:" && curl -s http://localhost:16686 > /dev/null && echo "✅ OK" || echo "❌ FAIL"
echo "Elasticsearch:" && curl -s http://localhost:9200 > /dev/null && echo "✅ OK" || echo "❌ FAIL"
```

### Ver Logs de um Serviço

```bash
# RabbitMQ
docker compose logs -f rabbitmq

# Grafana
docker compose logs -f grafana

# Kong
docker compose logs -f api-gateway

# Todos
docker compose logs -f
```

---

## 🎓 Próximos Passos

### Quando Microserviços Estiverem Rodando:

1. **RabbitMQ**: Ver mensagens fluindo entre serviços
2. **Grafana**: Criar dashboards com métricas reais
3. **Prometheus**: Monitorar performance dos serviços
4. **Jaeger**: Rastrear requisições end-to-end
5. **Kibana**: Analisar logs e debugar problemas
6. **Kong**: Configurar rotas e plugins

### Recursos de Aprendizado:

- **RabbitMQ**: https://www.rabbitmq.com/tutorials/tutorial-one-php.html
- **Grafana**: https://grafana.com/docs/grafana/latest/getting-started/
- **Prometheus**: https://prometheus.io/docs/prometheus/latest/querying/basics/
- **Jaeger**: https://www.jaegertracing.io/docs/
- **Kong**: https://docs.konghq.com/gateway/latest/get-started/

---

**🎉 Parabéns! Você conheceu todas as ferramentas de infraestrutura!**

Agora está pronto para começar a desenvolver os microserviços! 🚀

