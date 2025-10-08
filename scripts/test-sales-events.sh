#!/bin/bash

# Script para testar eventos do Sales Service

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

AUTH_URL="http://localhost:9001"
INVENTORY_URL="http://localhost:9002"
SALES_URL="http://localhost:9003"
RABBITMQ_API="http://localhost:15672/api"
RABBITMQ_USER="admin"
RABBITMQ_PASS="admin123"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🧪 TESTANDO EVENTOS DO SALES SERVICE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# 1. Obter Token JWT
echo "1. Obtendo token JWT..."
TOKEN=$(curl -s -X POST "$AUTH_URL/api/auth/register" \
    -H "Content-Type: application/json" \
    -d "{
        \"name\": \"Test User\",
        \"email\": \"test_$(date +%s)@example.com\",
        \"password\": \"Password123!\",
        \"password_confirmation\": \"Password123!\"
    }" | jq -r '.data.access_token')

if [ -z "$TOKEN" ] || [ "$TOKEN" == "null" ]; then
    echo "❌ Falha ao obter token"
    exit 1
fi

echo -e "${GREEN}✅ Token obtido${NC}"
echo ""

# 2. Limpar banco do Sales
echo "2. Limpando banco do Sales Service..."
docker compose exec -T sales-service php artisan db:wipe --force > /dev/null 2>&1
docker compose exec -T sales-service php artisan migrate --force > /dev/null 2>&1
echo -e "${GREEN}✅ Banco limpo${NC}"
echo ""

# 3. Verificar filas ANTES
echo "3. Verificando filas ANTES..."
INV_Q_BEFORE=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
    "$RABBITMQ_API/queues/%2F/inventory.queue" | jq '.messages')
FIN_Q_BEFORE=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
    "$RABBITMQ_API/queues/%2F/financial.queue" | jq '.messages')
echo "inventory.queue: $INV_Q_BEFORE mensagens"
echo "financial.queue: $FIN_Q_BEFORE mensagens"
echo ""

# 4. Criar cliente
echo "4. Criando cliente..."
VALID_CPF="12345678909"
TIMESTAMP=$(date +%s)
CUST_RESPONSE=$(curl -s -X POST "$SALES_URL/api/v1/customers" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d "{\"name\": \"Test Customer\", \"email\": \"customer${TIMESTAMP}@example.com\", \"document\": \"$VALID_CPF\", \"phone\": \"11999999999\"}")

CUST_ID=$(echo "$CUST_RESPONSE" | jq -r '.data.id')

if [ -z "$CUST_ID" ] || [ "$CUST_ID" == "null" ]; then
    echo "❌ Falha ao criar cliente"
    echo "Response: $CUST_RESPONSE"
    exit 1
fi

echo -e "${GREEN}✅ Cliente criado: $CUST_ID${NC}"
echo ""

# 5. Criar pedido (deve publicar OrderCreated)
echo "5. Criando pedido (deve publicar OrderCreated)..."
ORDER_RESPONSE=$(curl -s -X POST "$SALES_URL/api/v1/orders" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d "{\"customer_id\": \"$CUST_ID\"}")

ORDER_ID=$(echo "$ORDER_RESPONSE" | jq -r '.data.id')

if [ -z "$ORDER_ID" ] || [ "$ORDER_ID" == "null" ]; then
    echo "❌ Falha ao criar pedido"
    echo "Response: $ORDER_RESPONSE"
    exit 1
fi

echo -e "${GREEN}✅ Pedido criado: $ORDER_ID${NC}"

# Aguardar processamento
sleep 2

# Verificar evento OrderCreated
FIN_Q_AFTER=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
    "$RABBITMQ_API/queues/%2F/financial.queue" | jq '.messages')

if [ "$FIN_Q_AFTER" -gt "$FIN_Q_BEFORE" ]; then
    echo -e "${GREEN}✅ Evento OrderCreated publicado!${NC}"
    
    # Inspecionar última mensagem
    echo ""
    echo "📬 Evento recebido:"
    curl -s -X POST -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
        "$RABBITMQ_API/queues/%2F/financial.queue/get" \
        -H "Content-Type: application/json" \
        -d '{"count":1,"ackmode":"ack_requeue_true","encoding":"auto"}' \
        | jq '.[0] | {routing_key, event: (.payload | fromjson | .event_name)}'
else
    echo -e "${YELLOW}⚠️  Evento OrderCreated NÃO foi publicado${NC}"
fi

echo ""

# 6. Criar produto no Inventory para adicionar item
echo "6. Preparando produto no Inventory..."
CAT_ID=$(curl -s -X POST "$INVENTORY_URL/api/v1/categories" -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d "{\"name\": \"Test $(date +%s)\", \"description\": \"Test\"}" | jq -r '.data.id')
PROD_ID=$(curl -s -X POST "$INVENTORY_URL/api/v1/products" -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d "{\"name\": \"Product\", \"sku\": \"P$(date +%s)\", \"price\": 100, \"category_id\": \"$CAT_ID\", \"initial_stock\": 0}" | jq -r '.data.id')
curl -s -X POST "$INVENTORY_URL/api/v1/stock/product/$PROD_ID/increase" -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d '{"quantity": 50, "reason": "test"}' > /dev/null
echo -e "${GREEN}✅ Produto preparado: $PROD_ID${NC}"
echo ""

# 7. Adicionar item ao pedido (deve publicar OrderItemAdded)
echo "7. Adicionando item ao pedido (deve publicar OrderItemAdded)..."
INV_Q_BEFORE=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
    "$RABBITMQ_API/queues/%2F/inventory.queue" | jq '.messages')

ITEM_RESPONSE=$(curl -s -X POST "$SALES_URL/api/v1/orders/$ORDER_ID/items" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d "{\"product_id\": \"$PROD_ID\", \"quantity\": 5, \"unit_price\": 100}")

ITEM_ID=$(echo "$ITEM_RESPONSE" | jq -r '.data.id')

if [ -z "$ITEM_ID" ] || [ "$ITEM_ID" == "null" ]; then
    echo "❌ Falha ao adicionar item"
    echo "Response: $ITEM_RESPONSE"
else
    echo -e "${GREEN}✅ Item adicionado: $ITEM_ID${NC}"
    
    sleep 2
    
    INV_Q_AFTER=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
        "$RABBITMQ_API/queues/%2F/inventory.queue" | jq '.messages')
    
    if [ "$INV_Q_AFTER" -gt "$INV_Q_BEFORE" ]; then
        echo -e "${GREEN}✅ Evento OrderItemAdded publicado!${NC}"
    else
        echo -e "${YELLOW}⚠️  Evento OrderItemAdded NÃO foi publicado${NC}"
    fi
fi

echo ""

# 8. Confirmar pedido (deve publicar OrderConfirmed)
echo "8. Confirmando pedido (deve publicar OrderConfirmed)..."
INV_Q_BEFORE=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
    "$RABBITMQ_API/queues/%2F/inventory.queue" | jq '.messages')

CONFIRM_RESPONSE=$(curl -s -X PATCH "$SALES_URL/api/v1/orders/$ORDER_ID/confirm" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN")

STATUS=$(echo "$CONFIRM_RESPONSE" | jq -r '.data.status')

if [ "$STATUS" == "confirmed" ]; then
    echo -e "${GREEN}✅ Pedido confirmado${NC}"
    
    sleep 2
    
    INV_Q_AFTER=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
        "$RABBITMQ_API/queues/%2F/inventory.queue" | jq '.messages')
    
    if [ "$INV_Q_AFTER" -gt "$INV_Q_BEFORE" ]; then
        echo -e "${GREEN}✅ Evento OrderConfirmed publicado!${NC}"
    else
        echo -e "${YELLOW}⚠️  Evento OrderConfirmed NÃO foi publicado${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Falha ao confirmar pedido${NC}"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ TESTES COMPLETOS!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
