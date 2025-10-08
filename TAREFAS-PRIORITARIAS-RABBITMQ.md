# ✅ Checklist de Tarefas Prioritárias - RabbitMQ

**Data de Início**: 2025-10-08  
**Objetivo**: Completar implementação de comunicação assíncrona  
**Meta**: Sistema de mensageria funcionando end-to-end

---

## 🔥 FASE 1: VALIDAÇÃO E CORREÇÕES (1-2 dias)

### Dia 1 - Validação de Publicação

- [ ] **1.1. Testar Inventory Service**
  ```bash
  ./scripts/test-inventory-events.sh
  ```
  - [ ] Verificar logs do serviço
  - [ ] Verificar mensagens na `inventory.queue` via RabbitMQ Management
  - [ ] Validar formato dos eventos publicados
  - [ ] Corrigir bugs se houver

- [ ] **1.2. Testar Sales Service**
  ```bash
  ./scripts/test-sales-events.sh
  ```
  - [ ] Verificar logs do serviço
  - [ ] Verificar mensagens nas filas `financial.queue`, `inventory.queue`, `notification.queue`
  - [ ] Validar formato dos eventos publicados
  - [ ] Corrigir bugs se houver

- [ ] **1.3. Testar Integração Completa**
  ```bash
  ./scripts/test-rabbitmq-messaging.sh
  ```
  - [ ] Analisar relatório de testes
  - [ ] Verificar taxa de sucesso > 95%
  - [ ] Documentar problemas encontrados

- [ ] **1.4. Verificar Financial Service**
  - [ ] Abrir `services/financial-service/app/Providers/DomainServiceProvider.php`
  - [ ] Verificar se EventPublisher está registrado
  - [ ] Abrir UseCases e verificar se estão publicando eventos
  - [ ] Testar criação de conta a pagar/receber e verificar eventos

- [ ] **1.5. Acessar RabbitMQ Management UI**
  ```
  URL: http://localhost:15672
  User: admin
  Pass: admin123
  ```
  - [ ] Verificar estado de todas as queues
  - [ ] Verificar exchanges e bindings
  - [ ] Inspecionar mensagens acumuladas
  - [ ] Tirar screenshots do estado atual

### Critério de Conclusão Fase 1
- ✅ Todos os serviços publicando eventos corretamente
- ✅ Mensagens chegando nas filas corretas
- ✅ Taxa de sucesso de publicação > 95%
- ✅ Bugs corrigidos e documentados

---

## 🚀 FASE 2: IMPLEMENTAÇÃO DE CONSUMERS (3-4 dias)

### Dia 2 - Base de Consumers

- [ ] **2.1. Criar Classe Base para Consumers**
  
  Criar: `services/inventory-service/src/Infrastructure/Messaging/RabbitMQ/BaseRabbitMQConsumer.php`
  
  ```php
  <?php
  namespace Src\Infrastructure\Messaging\RabbitMQ;
  
  use PhpAmqpLib\Connection\AMQPStreamConnection;
  use PhpAmqpLib\Message\AMQPMessage;
  use Psr\Log\LoggerInterface;
  
  abstract class BaseRabbitMQConsumer
  {
      protected AMQPStreamConnection $connection;
      protected $channel;
      
      public function __construct(
          protected readonly LoggerInterface $logger,
          private readonly string $host,
          private readonly int $port,
          private readonly string $user,
          private readonly string $password,
          private readonly string $vhost
      ) {
          $this->connect();
      }
      
      abstract protected function getQueueName(): string;
      abstract protected function handleMessage(array $data): void;
      
      private function connect(): void { /* implementar */ }
      public function consume(): void { /* implementar */ }
      public function __destruct() { /* implementar */ }
  }
  ```
  
  - [ ] Implementar lógica de conexão
  - [ ] Implementar lógica de consumo com ACK manual
  - [ ] Implementar tratamento de erros (nack → DLQ)
  - [ ] Adicionar logging detalhado

- [ ] **2.2. Criar Comando Artisan para Consumer**
  
  Criar: `services/inventory-service/app/Console/Commands/ConsumeInventoryQueue.php`
  
  ```bash
  php artisan make:command ConsumeInventoryQueue
  ```
  
  - [ ] Implementar comando que chama consumer
  - [ ] Adicionar opções: --queue, --timeout, --max-messages
  - [ ] Testar execução: `php artisan rabbitmq:consume-inventory`

- [ ] **2.3. Configurar Supervisor**
  
  Criar: `services/inventory-service/docker/supervisord-consumers.conf`
  
  ```ini
  [program:inventory-queue-consumer]
  command=php /var/www/artisan rabbitmq:consume-inventory
  directory=/var/www
  autostart=true
  autorestart=true
  numprocs=2
  ```
  
  - [ ] Adicionar ao Dockerfile
  - [ ] Testar inicialização automática

### Dia 3 - Inventory Consumer

- [ ] **2.4. Implementar InventoryQueueConsumer**
  
  Criar: `services/inventory-service/src/Infrastructure/Messaging/RabbitMQ/InventoryQueueConsumer.php`
  
  **Eventos a consumir**:
  - `sales.order.created` → Reservar estoque
  - `sales.order.cancelled` → Liberar estoque
  - `sales.order.confirmed` → Confirmar reserva
  
  **Tarefas**:
  - [ ] Implementar classe que estende BaseRabbitMQConsumer
  - [ ] Implementar `handleMessage()` com match de eventos
  - [ ] Integrar com UseCases:
    - [ ] `ReserveStockUseCase` (criar se não existir)
    - [ ] `ReleaseStockUseCase` (criar se não existir)
    - [ ] `CommitStockReservationUseCase` (criar se não existir)
  - [ ] Adicionar validação de payload
  - [ ] Adicionar tratamento de erros
  - [ ] Adicionar logging

- [ ] **2.5. Criar UseCases de Estoque**
  
  **ReserveStockUseCase**:
  ```
  Criar: services/inventory-service/src/Application/UseCases/Stock/ReserveStock/
    - ReserveStockUseCase.php
    - ReserveStockDTO.php
  ```
  
  **Lógica**:
  - [ ] Receber: product_id, quantity, order_id
  - [ ] Verificar se há estoque disponível
  - [ ] Criar registro de reserva (nova tabela: stock_reservations)
  - [ ] Decrementar available_quantity (não committed_quantity ainda)
  - [ ] Publicar evento: `inventory.stock.reserved`
  
  **ReleaseStockUseCase**:
  - [ ] Receber: product_id, quantity, order_id
  - [ ] Encontrar reserva
  - [ ] Incrementar available_quantity
  - [ ] Remover registro de reserva
  - [ ] Publicar evento: `inventory.stock.released`
  
  **CommitStockReservationUseCase**:
  - [ ] Receber: order_id
  - [ ] Encontrar todas as reservas do pedido
  - [ ] Decrementar committed_quantity definitivamente
  - [ ] Remover registros de reserva
  - [ ] Publicar evento: `inventory.stock.committed`

- [ ] **2.6. Criar Migração para Reservas**
  ```bash
  php artisan make:migration create_stock_reservations_table
  ```
  
  **Campos**:
  - id (uuid)
  - stock_id (uuid) - FK para stocks
  - order_id (uuid) - ID do pedido
  - quantity (int) - Quantidade reservada
  - reserved_at (timestamp)
  - expires_at (timestamp) - Expiração da reserva (15 min)
  
  - [ ] Criar migração
  - [ ] Criar modelo StockReservation (se necessário)
  - [ ] Executar migração

### Dia 4 - Sales Consumer

- [ ] **2.7. Implementar SalesQueueConsumer**
  
  Criar: `services/sales-service/src/Infrastructure/Messaging/RabbitMQ/SalesQueueConsumer.php`
  
  **Eventos a consumir**:
  - `inventory.stock.reserved` → Atualizar status do pedido
  - `inventory.stock.depleted` → Cancelar pedido (sem estoque)
  - `financial.payment.approved` → Atualizar status de pagamento
  - `financial.payment.failed` → Cancelar pedido
  - `logistics.shipment.delivered` → Completar pedido
  
  **Tarefas**:
  - [ ] Copiar BaseRabbitMQConsumer para sales-service
  - [ ] Implementar SalesQueueConsumer
  - [ ] Criar comando Artisan `ConsumeS salesQueue`
  - [ ] Integrar com UseCases (criar se não existirem):
    - [ ] `UpdateOrderStatusUseCase`
    - [ ] `CompleteOrderUseCase`
  - [ ] Adicionar ao Supervisor

- [ ] **2.8. Criar UseCases de Pedido**
  
  **UpdateOrderStatusUseCase**:
  ```
  Criar: services/sales-service/src/Application/UseCases/Order/UpdateOrderStatus/
  ```
  - [ ] Receber: order_id, new_status, reason
  - [ ] Validar transição de status
  - [ ] Atualizar pedido
  - [ ] Publicar evento se necessário

  **CompleteOrderUseCase**:
  - [ ] Receber: order_id
  - [ ] Validar que pedido pode ser completado
  - [ ] Marcar como COMPLETED
  - [ ] Publicar evento: `sales.order.completed`

### Dia 5 - Financial & Notification Consumers

- [ ] **2.9. Implementar FinancialQueueConsumer**
  
  Criar: `services/financial-service/src/Infrastructure/Messaging/RabbitMQ/FinancialQueueConsumer.php`
  
  **Eventos a consumir**:
  - `sales.order.created` → Criar conta a receber
  - `logistics.shipment.dispatched` → Atualizar data prevista de recebimento
  
  **Tarefas**:
  - [ ] Copiar BaseRabbitMQConsumer
  - [ ] Implementar FinancialQueueConsumer
  - [ ] Criar comando Artisan
  - [ ] Integrar com UseCases:
    - [ ] `CreateAccountReceivableFromOrderUseCase` (criar)
    - [ ] `UpdateAccountReceivableUseCase`
  - [ ] Adicionar ao Supervisor

- [ ] **2.10. Implementar NotificationQueueConsumer (Standalone)**
  
  Criar: `scripts/notification-consumer.php`
  
  **Abordagem Simplificada** (sem microserviço completo):
  ```php
  <?php
  // Script standalone que consome notification.queue
  // e envia emails usando Mailgun/SendGrid
  ```
  
  **Eventos a consumir**:
  - `auth.user.registered` → Email de boas-vindas
  - `sales.order.created` → Email de confirmação de pedido
  - `sales.order.confirmed` → Email de pedido confirmado
  - `financial.payment.approved` → Email de pagamento aprovado
  
  **Tarefas**:
  - [ ] Criar script PHP standalone
  - [ ] Implementar lógica de conexão RabbitMQ
  - [ ] Implementar templates simples de email
  - [ ] Integrar com provedor de email (Mailgun)
  - [ ] Adicionar ao Supervisor
  - [ ] Testar envio de emails

### Critério de Conclusão Fase 2
- ✅ 4 consumers implementados e rodando
- ✅ Consumers configurados no Supervisor
- ✅ Mensagens sendo processadas automaticamente
- ✅ Filas esvaziando após processamento
- ✅ Logs indicando consumo bem-sucedido

---

## 🧪 FASE 3: TESTES E2E (2 dias)

### Dia 6 - Scripts de Teste E2E

- [ ] **3.1. Criar Script de Teste E2E Completo**
  
  Criar: `scripts/e2e-rabbitmq-full-flow.sh`
  
  **Fluxo a testar**:
  1. Criar usuário (Auth) → Verificar evento na notification.queue
  2. Criar produto (Inventory) → Verificar evento publicado
  3. Adicionar estoque (Inventory) → Verificar evento publicado
  4. Criar pedido (Sales) → Verificar eventos em financial.queue, inventory.queue, notification.queue
  5. Aguardar processamento (consumers processam)
  6. Verificar estoque reservado (Inventory)
  7. Verificar conta a receber criada (Financial)
  8. Verificar email enviado (Notification)
  9. Confirmar pedido (Sales) → Verificar eventos
  10. Verificar estoque committed (Inventory)
  
  - [ ] Implementar script
  - [ ] Adicionar validações em cada etapa
  - [ ] Adicionar relatório de sucesso/falha
  - [ ] Executar e documentar resultados

- [ ] **3.2. Testar Cenários de Erro**
  
  **Cenário 1: Estoque Insuficiente**
  - [ ] Criar pedido com quantidade > estoque disponível
  - [ ] Verificar evento `inventory.stock.depleted`
  - [ ] Verificar pedido cancelado automaticamente
  
  **Cenário 2: Mensagem Inválida**
  - [ ] Publicar mensagem com payload inválido
  - [ ] Verificar que vai para Dead Letter Queue
  - [ ] Verificar logs de erro
  
  **Cenário 3: Consumer Parado**
  - [ ] Parar consumer do Inventory
  - [ ] Criar pedido
  - [ ] Verificar mensagens acumulando na fila
  - [ ] Reiniciar consumer
  - [ ] Verificar processamento das mensagens acumuladas

- [ ] **3.3. Testar Idempotência**
  - [ ] Publicar mesmo evento 2x (mesmo event_id)
  - [ ] Verificar que processa apenas 1x
  - [ ] Documentar estratégia de idempotência

### Dia 7 - Validação e Documentação

- [ ] **3.4. Executar Bateria Completa de Testes**
  ```bash
  ./scripts/test-rabbitmq-messaging.sh
  ./scripts/test-inventory-events.sh
  ./scripts/test-sales-events.sh
  ./scripts/e2e-rabbitmq-full-flow.sh
  ```
  
  - [ ] Executar todos os scripts
  - [ ] Coletar métricas:
    - Taxa de sucesso de publicação
    - Taxa de sucesso de consumo
    - Latência média de processamento
    - Mensagens em DLQ
  - [ ] Documentar resultados

- [ ] **3.5. Verificar RabbitMQ Management**
  - [ ] Verificar que todas as queues têm consumers
  - [ ] Verificar taxa de processamento (messages/sec)
  - [ ] Verificar que DLQs estão vazias
  - [ ] Tirar screenshots do estado final

- [ ] **3.6. Documentar Fluxos**
  - [ ] Criar diagrama de fluxo de eventos (Mermaid ou Draw.io)
  - [ ] Documentar cada evento e seus consumers
  - [ ] Documentar formato de payload de cada evento
  - [ ] Criar guia de troubleshooting

### Critério de Conclusão Fase 3
- ✅ Todos os testes E2E passando
- ✅ Taxa de sucesso > 95%
- ✅ Cenários de erro testados
- ✅ Documentação completa

---

## 📊 MÉTRICAS DE ACOMPANHAMENTO

### Diariamente Verificar:

**RabbitMQ Management (http://localhost:15672)**:
- [ ] Número de mensagens em cada queue
- [ ] Número de consumers ativos
- [ ] Taxa de processamento (msg/s)
- [ ] Mensagens em Dead Letter Queues

**Logs dos Serviços**:
```bash
docker-compose logs -f inventory-service | grep "RabbitMQ"
docker-compose logs -f sales-service | grep "RabbitMQ"
docker-compose logs -f financial-service | grep "RabbitMQ"
```

**Supervisor Status** (dentro dos containers):
```bash
docker-compose exec inventory-service supervisorctl status
docker-compose exec sales-service supervisorctl status
docker-compose exec financial-service supervisorctl status
```

---

## 🎯 DEFINIÇÃO DE PRONTO (DONE)

### Uma tarefa está PRONTA quando:

- ✅ Código implementado e testado
- ✅ Testes unitários criados (se aplicável)
- ✅ Logs adequados adicionados
- ✅ Documentação atualizada
- ✅ Testado manualmente
- ✅ Sem erros de linting
- ✅ Code review feito (se aplicável)
- ✅ Commit feito com mensagem descritiva

### O projeto está COMPLETO quando:

- ✅ Todas as tarefas das Fases 1-3 concluídas
- ✅ Todos os 4 consumers rodando
- ✅ Taxa de sucesso de testes E2E > 95%
- ✅ Documentação completa
- ✅ Demo funcionando end-to-end

---

## 📞 PONTOS DE ATENÇÃO

### ⚠️ Cuidados Importantes:

1. **Idempotência**: Sempre verificar se evento já foi processado (usar event_id)
2. **ACK Manual**: Só fazer ACK após processar com sucesso
3. **Dead Letter Queue**: Monitorar DLQs diariamente
4. **Logs**: Adicionar logs em TODAS as etapas (publish, consume, process)
5. **Timeouts**: Configurar timeouts adequados (prefetch_count=1)
6. **Transações**: Usar transações de banco de dados onde necessário
7. **Retry**: Não fazer retry infinito (máximo 3x)
8. **Monitoring**: Verificar RabbitMQ Management diariamente

### 🐛 Troubleshooting Comum:

| Problema | Solução |
|----------|---------|
| Consumer não inicia | Verificar Supervisor: `supervisorctl status` |
| Mensagens acumulando | Verificar logs do consumer por erros |
| Mensagens na DLQ | Inspecionar payload e corrigir consumer |
| Evento não publicado | Verificar logs do publisher e conexão RabbitMQ |
| Estoque não reserva | Verificar logs do InventoryQueueConsumer |

---

## 🎉 CHECKPOINT DE PROGRESSO

Marque aqui conforme avançar:

- [ ] **Fase 1 Completa** (Data: ______)
  - [ ] Todos os publishers validados
  - [ ] Financial Service verificado
  - [ ] Bugs corrigidos

- [ ] **Fase 2 Completa** (Data: ______)
  - [ ] BaseRabbitMQConsumer criada
  - [ ] 4 consumers implementados
  - [ ] Supervisor configurado
  - [ ] Consumers rodando

- [ ] **Fase 3 Completa** (Data: ______)
  - [ ] Testes E2E passando
  - [ ] Documentação finalizada
  - [ ] MVP pronto para uso

---

**Última Atualização**: 2025-10-08  
**Próxima Revisão**: Após Fase 1  
**Responsável**: Armando N Junior

