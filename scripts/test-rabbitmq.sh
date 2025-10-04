#!/bin/bash

# Script para testar RabbitMQ enviando uma mensagem de exemplo

echo "🐰 Testando RabbitMQ - Enviando Mensagem de Teste"
echo "=================================================="
echo ""

# Verificar se RabbitMQ está rodando
if ! docker compose ps rabbitmq | grep -q "Up"; then
    echo "❌ RabbitMQ não está rodando!"
    echo "Execute: docker compose up -d rabbitmq"
    exit 1
fi

echo "✅ RabbitMQ está rodando"
echo ""

# Criar mensagem de teste
MESSAGE='{
  "event_id": "evt_test_'$(date +%s)'",
  "event_name": "sales.order.created",
  "occurred_at": "'$(date -u +"%Y-%m-%dT%H:%M:%SZ")'",
  "payload": {
    "order_id": "ORD-TEST-001",
    "customer_id": "CUST-123",
    "total": 150.00,
    "items": [
      {
        "product_id": "PROD-001",
        "quantity": 2,
        "unit_price": 75.00
      }
    ]
  }
}'

echo "📤 Publicando mensagem no exchange 'sales.events'..."
echo ""
echo "Mensagem:"
echo "$MESSAGE" | python3 -m json.tool
echo ""

# Publicar mensagem usando API do RabbitMQ
PAYLOAD_STR=$(echo "$MESSAGE" | jq -c . | jq -R .)
RESPONSE=$(curl -s -u admin:admin123 \
  -X POST http://localhost:15672/api/exchanges/%2F/sales.events/publish \
  -H "Content-Type: application/json" \
  -d "{
    \"properties\":{},
    \"routing_key\":\"sales.order.created\",
    \"payload\":$PAYLOAD_STR,
    \"payload_encoding\":\"string\"
  }")

if echo "$RESPONSE" | grep -q '"routed":true'; then
    echo "✅ Mensagem publicada com sucesso!"
    echo ""
    echo "🔍 A mensagem foi roteada para as seguintes queues:"
    echo "   - inventory.queue (para reservar estoque)"
    echo "   - notification.queue (para notificar cliente)"
    echo ""
    echo "📊 Verifique no RabbitMQ Management:"
    echo "   http://localhost:15672"
    echo ""
    echo "💡 Para ver as mensagens nas queues:"
    echo "   1. Vá em 'Queues'"
    echo "   2. Clique em 'inventory.queue'"
    echo "   3. Na seção 'Get messages' clique em 'Get Message(s)'"
    echo ""
else
    echo "❌ Erro ao publicar mensagem!"
    echo "Resposta: $RESPONSE"
    exit 1
fi

# Verificar quantas mensagens tem nas queues
echo "📥 Status das Queues:"
echo ""
docker compose exec rabbitmq rabbitmqctl list_queues name messages 2>/dev/null | grep -E "(inventory|notification|sales).queue" || echo "Nenhuma mensagem encontrada"

echo ""
echo "✅ Teste concluído!"
echo ""
echo "🎯 Próximos passos:"
echo "   1. Acesse http://localhost:15672 (admin/admin123)"
echo "   2. Vá em 'Queues' e veja as mensagens"
echo "   3. Quando os microserviços estiverem rodando, eles irão consumir essas mensagens"

