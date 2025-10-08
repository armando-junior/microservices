#!/bin/bash

# Script para testar eventos do Inventory Service

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

AUTH_URL="http://localhost:9001"
INVENTORY_URL="http://localhost:9002"
RABBITMQ_API="http://localhost:15672/api"
RABBITMQ_USER="admin"
RABBITMQ_PASS="admin123"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🧪 TESTANDO EVENTOS DO INVENTORY SERVICE"
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

# 2. Criar categoria
echo "2. Criando categoria..."
CAT_RESPONSE=$(curl -s -X POST "$INVENTORY_URL/api/v1/categories" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d "{\"name\": \"Test Category $(date +%s)\", \"description\": \"For event testing\"}")

CAT_ID=$(echo "$CAT_RESPONSE" | jq -r '.data.id')

if [ -z "$CAT_ID" ] || [ "$CAT_ID" == "null" ]; then
    echo "❌ Falha ao criar categoria"
    echo "Response: $CAT_RESPONSE"
    exit 1
fi

echo -e "${GREEN}✅ Categoria criada: $CAT_ID${NC}"
echo ""

# 3. Verificar filas ANTES
echo "3. Verificando filas ANTES..."
SALES_Q_BEFORE=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
    "$RABBITMQ_API/queues/%2F/sales.queue" | jq '.messages')
echo "sales.queue: $SALES_Q_BEFORE mensagens"
echo ""

# 4. Criar produto (deve publicar ProductCreated)
echo "4. Criando produto (deve publicar ProductCreated)..."
PROD_RESPONSE=$(curl -s -X POST "$INVENTORY_URL/api/v1/products" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d "{
        \"name\": \"Test Product for Events\",
        \"sku\": \"EVT-$(date +%s)\",
        \"price\": 100,
        \"category_id\": \"$CAT_ID\",
        \"initial_stock\": 0
    }")

PROD_ID=$(echo "$PROD_RESPONSE" | jq -r '.data.id')

if [ -z "$PROD_ID" ] || [ "$PROD_ID" == "null" ]; then
    echo "❌ Falha ao criar produto"
    echo "Response: $PROD_RESPONSE"
    exit 1
fi

echo -e "${GREEN}✅ Produto criado: $PROD_ID${NC}"
echo ""

# Aguardar processamento
sleep 2

# 5. Verificar ProductCreated event
echo "5. Verificando evento ProductCreated..."
SALES_Q_AFTER=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
    "$RABBITMQ_API/queues/%2F/sales.queue" | jq '.messages')
echo "sales.queue: $SALES_Q_AFTER mensagens"

if [ "$SALES_Q_AFTER" -gt "$SALES_Q_BEFORE" ]; then
    echo -e "${GREEN}✅ Evento ProductCreated publicado!${NC}"
    
    # Inspecionar última mensagem
    echo ""
    echo "📬 Evento recebido:"
    curl -s -X POST -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
        "$RABBITMQ_API/queues/%2F/sales.queue/get" \
        -H "Content-Type: application/json" \
        -d '{"count":1,"ackmode":"ack_requeue_true","encoding":"auto"}' \
        | jq '.[0] | {routing_key, event: (.payload | fromjson | .event_name)}'
else
    echo -e "${YELLOW}⚠️  Evento ProductCreated NÃO foi publicado${NC}"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "6. Testando eventos de estoque (StockLowAlert / StockDepleted)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# 6. Aumentar estoque
echo "6.1. Aumentando estoque para 15 unidades..."
curl -s -X POST "$INVENTORY_URL/api/v1/stock/product/$PROD_ID/increase" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d '{"quantity": 15, "reason": "initial stock"}' > /dev/null

echo -e "${GREEN}✅ Estoque aumentado${NC}"
echo ""

# 7. Diminuir estoque para disparar StockLowAlert
echo "6.2. Diminuindo estoque para 5 unidades (deve disparar StockLowAlert)..."
SALES_Q_BEFORE=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
    "$RABBITMQ_API/queues/%2F/sales.queue" | jq '.messages')

DECREASE_RESPONSE=$(curl -s -X POST "$INVENTORY_URL/api/v1/stock/product/$PROD_ID/decrease" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d '{"quantity": 10, "reason": "test decrease"}')

CURRENT_STOCK=$(echo "$DECREASE_RESPONSE" | jq -r '.data.current_quantity')

if [ -z "$CURRENT_STOCK" ] || [ "$CURRENT_STOCK" == "null" ]; then
    echo "❌ Falha ao diminuir estoque"
    echo "Response: $DECREASE_RESPONSE"
else
    echo -e "${GREEN}✅ Estoque reduzido para: $CURRENT_STOCK${NC}"
    
    sleep 2
    
    SALES_Q_AFTER=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
        "$RABBITMQ_API/queues/%2F/sales.queue" | jq '.messages')
    
    if [ "$SALES_Q_AFTER" -gt "$SALES_Q_BEFORE" ]; then
        echo -e "${GREEN}✅ Evento StockLowAlert publicado!${NC}"
    else
        echo -e "${YELLOW}⚠️  Evento StockLowAlert NÃO foi publicado${NC}"
    fi
fi

echo ""

# 8. Diminuir estoque para zero (deve disparar StockDepleted)
echo "6.3. Diminuindo estoque para 0 (deve disparar StockDepleted)..."
SALES_Q_BEFORE=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
    "$RABBITMQ_API/queues/%2F/sales.queue" | jq '.messages')

DECREASE_RESPONSE=$(curl -s -X POST "$INVENTORY_URL/api/v1/stock/product/$PROD_ID/decrease" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d "{\"quantity\": $CURRENT_STOCK, \"reason\": \"test deplete\"}")

FINAL_STOCK=$(echo "$DECREASE_RESPONSE" | jq -r '.data.current_quantity')

if [ "$FINAL_STOCK" == "0" ]; then
    echo -e "${GREEN}✅ Estoque zerado${NC}"
    
    sleep 2
    
    SALES_Q_AFTER=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
        "$RABBITMQ_API/queues/%2F/sales.queue" | jq '.messages')
    
    if [ "$SALES_Q_AFTER" -gt "$SALES_Q_BEFORE" ]; then
        echo -e "${GREEN}✅ Evento StockDepleted publicado!${NC}"
    else
        echo -e "${YELLOW}⚠️  Evento StockDepleted NÃO foi publicado${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Estoque não foi zerado (atual: $FINAL_STOCK)${NC}"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ TESTES COMPLETOS!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
