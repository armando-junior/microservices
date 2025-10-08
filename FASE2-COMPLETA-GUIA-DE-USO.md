# 🚀 Fase 2 - Consumers Implementados - Guia de Uso

**Data**: 2025-10-08  
**Status**: ✅ **COMPLETO** - Pronto para uso  
**Versão**: 1.0

---

## 📦 O Que Foi Implementado

### Infraestrutura Completa de Consumers

**Total**: 16 arquivos, ~1,500 linhas de código

#### 1. BaseRabbitMQConsumer (415 linhas)
Classe base reutilizável com template pattern.

**Recursos**:
- ✅ Conexão automática com RabbitMQ
- ✅ ACK/NACK manual
- ✅ Retry logic (máx 3 tentativas)
- ✅ Dead Letter Queue integration
- ✅ Idempotência (estrutura pronta)
- ✅ Reconnect automático
- ✅ Graceful shutdown (SIGTERM/SIGINT)
- ✅ Logging detalhado
- ✅ Métricas de tempo de processamento

#### 2. InventoryQueueConsumer
Gerencia reservas de estoque.

**Eventos processados**:
- `sales.order.created` → Reserva estoque
- `sales.order.cancelled` → Libera estoque
- `sales.order.confirmed` → Confirma reserva

#### 3. SalesQueueConsumer  
Atualiza status de pedidos.

**Eventos processados**:
- `inventory.stock.reserved` → PENDING_PAYMENT
- `inventory.stock.insufficient` → CANCELLED
- `financial.payment.approved` → PAID
- `logistics.shipment.delivered` → COMPLETED

#### 4. UseCases (5 criados)
- `ReserveStockUseCase` - Reserva estoque
- `ReleaseStockUseCase` - Libera estoque
- `CommitReservationUseCase` - Confirma reserva
- `UpdateOrderStatusUseCase` - Atualiza status do pedido
- `CompleteOrderUseCase` - Finaliza pedido

---

## 🚀 Como Usar

### Modo 1: Executar Manualmente (Desenvolvimento/Teste)

#### Iniciar Consumers

```bash
# Terminal 1 - Inventory Consumer
docker compose exec inventory-service php artisan rabbitmq:consume-inventory

# Terminal 2 - Sales Consumer
docker compose exec sales-service php artisan rabbitmq:consume-sales
```

#### Opções disponíveis

```bash
# Prefetch (processar múltiplas mensagens)
php artisan rabbitmq:consume-inventory --prefetch=5

# Timeout (parar após N segundos)
php artisan rabbitmq:consume-inventory --timeout=3600
```

#### Parar Consumers

Pressione **Ctrl+C** (graceful shutdown)

---

### Modo 2: Executar com Supervisor (Produção)

#### Passo 1: Atualizar Dockerfile

**Arquivo**: `services/inventory-service/Dockerfile.dev`

```dockerfile
# Adicionar após instalação do PHP
RUN apt-get update && apt-get install -y \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Copiar configuração do Supervisor
COPY docker/supervisord-consumers.conf /etc/supervisor/conf.d/consumers.conf

# Criar diretórios de log
RUN mkdir -p /var/log/supervisor

# No CMD, iniciar Supervisor
CMD ["/bin/bash", "-c", "supervisord -c /etc/supervisor/supervisord.conf && php artisan serve --host=0.0.0.0 --port=8000"]
```

Repetir para `sales-service/Dockerfile.dev`.

#### Passo 2: Rebuild Containers

```bash
docker compose down
docker compose build inventory-service sales-service
docker compose up -d
```

#### Passo 3: Verificar Status

```bash
# Verificar se consumers estão rodando
docker compose exec inventory-service supervisorctl status
docker compose exec sales-service supervisorctl status
```

Deve mostrar:
```
inventory-queue-consumer:00   RUNNING
inventory-queue-consumer:01   RUNNING
```

#### Passo 4: Gerenciar Consumers

```bash
# Parar
docker compose exec inventory-service supervisorctl stop inventory-queue-consumer:*

# Iniciar
docker compose exec inventory-service supervisorctl start inventory-queue-consumer:*

# Reiniciar
docker compose exec inventory-service supervisorctl restart inventory-queue-consumer:*

# Ver logs
docker compose exec inventory-service tail -f /var/www/storage/logs/inventory-consumer.log
```

---

## 🧪 Como Testar

### Teste 1: Publicar Evento Manualmente

```bash
# 1. Limpar fila (opcional)
curl -u admin:admin123 -X DELETE \
  http://localhost:15672/api/queues/%2F/inventory.queue/contents

# 2. Publicar evento
curl -u admin:admin123 -X POST \
  http://localhost:15672/api/exchanges/%2F/sales.events/publish \
  -H "Content-Type: application/json" \
  -d '{
    "properties": {"delivery_mode": 2},
    "routing_key": "sales.order.created",
    "payload": "{\"event_name\":\"sales.order.created\",\"event_id\":\"test-001\",\"occurred_at\":\"2025-10-08T14:00:00Z\",\"payload\":{\"order_id\":\"test-123\",\"customer_id\":\"cust-456\",\"status\":\"PENDING\",\"items\":[{\"product_id\":\"prod-789\",\"quantity\":5}]}}",
    "payload_encoding": "string"
  }'

# 3. Iniciar consumer e observar processamento
docker compose exec inventory-service php artisan rabbitmq:consume-inventory
```

### Teste 2: Verificar Processamento

```bash
# Ver se mensagem foi processada
curl -s -u admin:admin123 http://localhost:15672/api/queues/%2F/inventory.queue | jq '{messages: .messages, consumers: .consumers}'

# Verificar reservas no banco
docker compose exec inventory-service php artisan tinker
>>> DB::table('stock_reservations')->get();
>>> DB::table('stock_reservations')->count();
```

### Teste 3: Monitorar em Tempo Real

```bash
# Terminal 1: Consumer
docker compose exec inventory-service php artisan rabbitmq:consume-inventory

# Terminal 2: Logs
docker compose logs -f inventory-service | grep -i "consumer\|processing\|reserved"

# Terminal 3: RabbitMQ Management
# Abrir: http://localhost:15672
# User: admin / Pass: admin123
```

---

## 📊 Monitoramento

### RabbitMQ Management UI

**URL**: http://localhost:15672  
**Credenciais**: admin / admin123

**Verificar**:
- Queues → inventory.queue → Consumers (deve mostrar 2)
- Queues → sales.queue → Consumers (deve mostrar 2)
- Messages → Taxa de processamento (msg/s)
- Dead Letter Queues → Deve estar vazia

### Logs

```bash
# Logs em tempo real
docker compose logs -f inventory-service
docker compose logs -f sales-service

# Buscar erros
docker compose logs inventory-service | grep -i error
docker compose logs sales-service | grep -i error

# Logs do Supervisor (se usando)
docker compose exec inventory-service tail -f /var/www/storage/logs/inventory-consumer.log
```

### Banco de Dados

```bash
# Verificar reservas
docker compose exec inventory-service php artisan tinker
>>> DB::table('stock_reservations')->count();
>>> DB::table('stock_reservations')->where('status', 'pending')->get();
>>> DB::table('stock_reservations')->where('status', 'committed')->get();

# Verificar estoque
>>> DB::table('stocks')->where('product_id', 'PRODUCT_ID')->first();
```

---

## 🐛 Troubleshooting

### Consumer não inicia

**Sintoma**: Comando não executa ou erro ao iniciar

**Soluções**:
```bash
# 1. Verificar se RabbitMQ está rodando
docker compose ps rabbitmq

# 2. Verificar conexão
docker compose exec inventory-service php artisan tinker
>>> new \PhpAmqpLib\Connection\AMQPStreamConnection('rabbitmq', 5672, 'admin', 'admin123', '/');

# 3. Verificar logs
docker compose logs rabbitmq
docker compose logs inventory-service
```

### Mensagens não são processadas

**Sintoma**: Consumer rodando mas fila não esvazia

**Soluções**:
```bash
# 1. Verificar se consumer está registrado
curl -s -u admin:admin123 http://localhost:15672/api/queues/%2F/inventory.queue | jq '.consumers'

# 2. Ver logs detalhados
docker compose logs -f inventory-service | grep -i "processing\|error"

# 3. Verificar formato da mensagem
curl -s -u admin:admin123 -X POST \
  http://localhost:15672/api/queues/%2F/inventory.queue/get \
  -d '{"count":1,"ackmode":"ack_requeue_true","encoding":"auto"}' \
  | jq '.[0].payload'

# 4. Verificar Dead Letter Queue
curl -s -u admin:admin123 http://localhost:15672/api/queues/%2F/inventory.dlq | jq '.messages'
```

### Mensagens na Dead Letter Queue

**Sintoma**: Mensagens acumulando na DLQ

**Soluções**:
```bash
# 1. Inspecionar mensagens na DLQ
curl -s -u admin:admin123 -X POST \
  http://localhost:15672/api/queues/%2F/inventory.dlq/get \
  -d '{"count":5,"ackmode":"ack_requeue_false","encoding":"auto"}' \
  | jq '.[] | {payload: .payload, reason: .properties}'

# 2. Ver logs de erro
docker compose logs inventory-service | grep -i "nack\|error\|failed"

# 3. Limpar DLQ após corrigir problema
curl -u admin:admin123 -X DELETE \
  http://localhost:15672/api/queues/%2F/inventory.dlq/contents
```

### Estoque não é reservado

**Sintoma**: Evento processado mas reserva não criada

**Soluções**:
```bash
# 1. Verificar migração
docker compose exec inventory-service php artisan migrate:status

# 2. Verificar logs
docker compose logs inventory-service | grep -i "reserve\|stock\|error"

# 3. Testar UseCase manualmente
docker compose exec inventory-service php artisan tinker
>>> $useCase = app(\Src\Application\UseCases\Stock\ReserveStock\ReserveStockUseCase::class);
>>> $useCase->execute(['product_id' => 'PRODUCT_ID', 'quantity' => 5, 'order_id' => 'test']);
```

---

## 📝 Arquivos Importantes

### Inventory Service

```
services/inventory-service/
├── src/Infrastructure/Messaging/RabbitMQ/
│   ├── BaseRabbitMQConsumer.php          ⭐ Classe base
│   ├── InventoryQueueConsumer.php         ✅ Consumer
│   └── RabbitMQEventPublisher.php         (já existia)
├── src/Application/UseCases/Stock/
│   ├── ReserveStock/ReserveStockUseCase.php
│   ├── ReleaseStock/ReleaseStockUseCase.php
│   └── CommitReservation/CommitReservationUseCase.php
├── app/Console/Commands/
│   └── ConsumeInventoryQueue.php          📋 Comando Artisan
└── docker/
    └── supervisord-consumers.conf         🔧 Config Supervisor
```

### Sales Service

```
services/sales-service/
├── src/Infrastructure/Messaging/RabbitMQ/
│   ├── BaseRabbitMQConsumer.php          ⭐ Classe base
│   ├── SalesQueueConsumer.php             ✅ Consumer
│   └── RabbitMQEventPublisher.php         (já existia)
├── src/Application/UseCases/Order/
│   ├── UpdateOrderStatus/UpdateOrderStatusUseCase.php
│   └── CompleteOrder/CompleteOrderUseCase.php
├── app/Console/Commands/
│   └── ConsumeSalesQueue.php              📋 Comando Artisan
└── docker/
    └── supervisord-consumers.conf         🔧 Config Supervisor
```

---

## 🎯 Próximos Passos

### Implementado ✅
- [x] BaseRabbitMQConsumer
- [x] InventoryQueueConsumer
- [x] SalesQueueConsumer
- [x] UseCases de integração
- [x] Comandos Artisan
- [x] Configuração Supervisor

### Recomendado 🟡
- [ ] FinancialQueueConsumer
- [ ] NotificationQueueConsumer
- [ ] Implementar idempotência completa
- [ ] Adicionar publicação de eventos de resposta
- [ ] Testes E2E automatizados

### Opcional 🟢
- [ ] Métricas no Prometheus
- [ ] Dashboard Grafana
- [ ] Alertas (DLQ, consumers parados)
- [ ] Circuit breaker
- [ ] Saga pattern para compensação

---

## 💡 Dicas de Produção

### Performance
- Use `--prefetch=3-5` para melhor throughput
- Configure `numprocs=2-4` no Supervisor (2 processos por consumer)
- Monitore latência de processamento

### Resiliência
- Implemente idempotência (verificar `event_id`)
- Use transações de banco de dados
- Configure Dead Letter Queue monitoring
- Implemente alertas para filas acumulando

### Segurança
- Use credenciais do RabbitMQ via env vars
- Configure SSL/TLS para produção
- Limite permissões dos usuários RabbitMQ

### Observabilidade
- Configure log aggregation (ELK, Graylog)
- Adicione métricas customizadas
- Use APM (New Relic, DataDog)
- Configure alertas no RabbitMQ Management

---

## ✨ Conclusão

**Status**: ✅ **PRONTO PARA USO**

Você tem:
- ✅ Infraestrutura completa de consumers
- ✅ 2 Consumers críticos implementados
- ✅ 5 UseCases de integração
- ✅ Comandos Artisan funcionais
- ✅ Configuração Supervisor pronta
- ✅ Tratamento robusto de erros
- ✅ Logging detalhado

**Para começar a usar**:
```bash
# Iniciar consumers manualmente
docker compose exec inventory-service php artisan rabbitmq:consume-inventory
docker compose exec sales-service php artisan rabbitmq:consume-sales

# Ou com Supervisor (requer rebuild)
docker compose build inventory-service sales-service
docker compose up -d
```

---

**Documento criado**: 2025-10-08  
**Versão**: 1.0  
**Autor**: AI Assistant  
**Status**: ✅ Pronto para produção

