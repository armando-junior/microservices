#!/bin/bash

# ============================================================================
# 💰 Sales Service - Validação Completa de Endpoints
# ============================================================================

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

AUTH_URL="http://localhost:9001/api"
API_URL="http://localhost:9003/api/v1"
TIMESTAMP=$(date +%s)
RANDOM_EMAIL="salesman_${TIMESTAMP}@example.com"
#Para este teste, vamos usar apenas CPF válido comprovado
RANDOM_CPF="11144477735"

echo -e "${BLUE}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "💰 SALES SERVICE - VALIDAÇÃO DE ENDPOINTS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${NC}"

# ============================================================================
# Setup: Criar usuário e obter token JWT
# ============================================================================
echo -e "\n${YELLOW}🔑 Setup: Obtendo Token JWT${NC}"

HTTP_CODE=$(curl -s -o /tmp/sales-auth.json -w "%{http_code}" -X POST "$AUTH_URL/auth/register" \
  -H "Content-Type: application/json" \
  -d "{
    \"name\": \"Sales User\",
    \"email\": \"$RANDOM_EMAIL\",
    \"password\": \"SecurePass@123\"
  }")

if [ "$HTTP_CODE" = "201" ]; then
    JWT_TOKEN=$(cat /tmp/sales-auth.json | jq -r '.data.access_token')
    echo -e "${GREEN}✅ Token obtido${NC}"
    echo "Token: ${JWT_TOKEN:0:30}..."
else
    echo -e "${RED}❌ FAILED${NC} - Não foi possível obter token (HTTP $HTTP_CODE)"
    exit 1
fi

# ============================================================================
# Teste 1: Health Check
# ============================================================================
echo -e "\n${YELLOW}📊 Teste 1: Health Check${NC}"
HTTP_CODE=$(curl -s -o /tmp/sales-health.json -w "%{http_code}" http://localhost:9003/health)

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Health check OK"
    cat /tmp/sales-health.json | jq .
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    exit 1
fi

# ============================================================================
# Teste 2: Metrics Endpoint
# ============================================================================
echo -e "\n${YELLOW}📊 Teste 2: Metrics Endpoint${NC}"
METRICS=$(curl -s http://localhost:9003/metrics)

if echo "$METRICS" | grep -q "sales_orders_created_total"; then
    echo -e "${GREEN}✅ PASSED${NC} - Metrics endpoint OK"
    echo "$METRICS" | grep "^sales_" | head -5
else
    echo -e "${RED}❌ FAILED${NC} - Métricas não encontradas"
    exit 1
fi

# ============================================================================
# Teste 3: Create Customer
# ============================================================================
echo -e "\n${YELLOW}👤 Teste 3: Create Customer${NC}"

TIMESTAMP=$(date +%s)
HTTP_CODE=$(curl -s -o /tmp/sales-customer.json -w "%{http_code}" -X POST "$API_URL/customers" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -d "{
    \"name\": \"João da Silva\",
    \"email\": \"joao_${TIMESTAMP}@example.com\",
    \"phone\": \"11987654321\",
    \"document\": \"$RANDOM_CPF\",
    \"address\": {
      \"street\": \"Rua das Flores\",
      \"number\": \"123\",
      \"complement\": \"Apto 45\",
      \"city\": \"São Paulo\",
      \"state\": \"SP\",
      \"zip_code\": \"01310-100\"
    }
  }")

if [ "$HTTP_CODE" = "201" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Cliente criado com sucesso"
    CUSTOMER_ID=$(cat /tmp/sales-customer.json | jq -r '.data.id')
    echo "Customer ID: $CUSTOMER_ID"
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    cat /tmp/sales-customer.json | jq .
    exit 1
fi

# ============================================================================
# Teste 4: Create Order
# ============================================================================
echo -e "\n${YELLOW}🛒 Teste 4: Create Order${NC}"

HTTP_CODE=$(curl -s -o /tmp/sales-order.json -w "%{http_code}" -X POST "$API_URL/orders" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -d "{
    \"customer_id\": \"$CUSTOMER_ID\"
  }")

if [ "$HTTP_CODE" = "201" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Pedido criado com sucesso"
    ORDER_ID=$(cat /tmp/sales-order.json | jq -r '.data.id')
    echo "Order ID: $ORDER_ID"
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    cat /tmp/sales-order.json | jq .
    exit 1
fi

# ============================================================================
# Setup: Criar produto no Inventory Service
# ============================================================================
echo -e "\n${YELLOW}🔧 Setup: Criar Produto no Inventory${NC}"

# Criar categoria primeiro
cat_response=$(curl -s -X POST "http://localhost:9002/api/v1/categories" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -d "{\"name\": \"Test Category $TIMESTAMP\", \"description\": \"Test\"}")
  
CATEGORY_ID=$(echo "$cat_response" | jq -r '.data.id')
echo "Category ID: $CATEGORY_ID"

# Criar produto
prod_response=$(curl -s -X POST "http://localhost:9002/api/v1/products" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -d "{
    \"name\": \"Test Product\",
    \"description\": \"Test Product for Sales\",
    \"sku\": \"TEST-${TIMESTAMP}\",
    \"category_id\": \"$CATEGORY_ID\",
    \"price\": 150.50,
    \"cost_price\": 100.00,
    \"stock_quantity\": 100,
    \"minimum_stock\": 10
  }")
  
PRODUCT_ID=$(echo "$prod_response" | jq -r '.data.id')
echo "Product ID: $PRODUCT_ID"

# ============================================================================
# Teste 5: Add Order Item
# ============================================================================
echo -e "\n${YELLOW}➕ Teste 5: Add Order Item${NC}"

HTTP_CODE=$(curl -s -o /tmp/sales-item.json -w "%{http_code}" -X POST "$API_URL/orders/$ORDER_ID/items" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -d "{
    \"product_id\": \"$PRODUCT_ID\",
    \"quantity\": 2,
    \"unit_price\": 150.50
  }")

if [ "$HTTP_CODE" = "201" ] || [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Item adicionado com sucesso (HTTP $HTTP_CODE)"
    cat /tmp/sales-item.json | jq '.data.items_count'
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    cat /tmp/sales-item.json | jq .
    exit 1
fi

# ============================================================================
# Teste 6: Confirm Order
# ============================================================================
echo -e "\n${YELLOW}✅ Teste 6: Confirm Order${NC}"

HTTP_CODE=$(curl -s -o /tmp/sales-confirm.json -w "%{http_code}" -X POST "$API_URL/orders/$ORDER_ID/confirm" \
  -H "Authorization: Bearer $JWT_TOKEN")

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Pedido confirmado com sucesso"
    STATUS=$(cat /tmp/sales-confirm.json | jq -r '.data.status')
    echo "Status: $STATUS"
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    cat /tmp/sales-confirm.json | jq .
    exit 1
fi

# ============================================================================
# Teste 7: List Orders
# ============================================================================
echo -e "\n${YELLOW}📋 Teste 7: List Orders${NC}"

HTTP_CODE=$(curl -s -o /tmp/sales-orders.json -w "%{http_code}" -X GET "$API_URL/orders" \
  -H "Authorization: Bearer $JWT_TOKEN")

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Pedidos listados com sucesso"
    TOTAL=$(cat /tmp/sales-orders.json | jq '.meta.total')
    echo "Total de pedidos: $TOTAL"
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    exit 1
fi

# ============================================================================
# Resumo Final
# ============================================================================
echo -e "\n${BLUE}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${GREEN}✅ TODOS OS TESTES PASSARAM!${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${NC}"

echo -e "${GREEN}✅ Health Check${NC}"
echo -e "${GREEN}✅ Metrics Endpoint${NC}"
echo -e "${GREEN}✅ Create Customer${NC}"
echo -e "${GREEN}✅ Create Order${NC}"
echo -e "${GREEN}✅ Add Order Item${NC}"
echo -e "${GREEN}✅ Confirm Order${NC}"
echo -e "${GREEN}✅ List Orders${NC}"

echo -e "\n${BLUE}🎯 Sales Service está 100% funcional!${NC}\n"

# Cleanup
rm -f /tmp/sales-*.json

