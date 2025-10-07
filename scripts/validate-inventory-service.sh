#!/bin/bash

# ============================================================================
# 📦 Inventory Service - Validação Completa de Endpoints
# ============================================================================

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

AUTH_URL="http://localhost:9001/api"
API_URL="http://localhost:9002/api/v1"
RANDOM_EMAIL="admin_$(date +%s)@example.com"

echo -e "${BLUE}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📦 INVENTORY SERVICE - VALIDAÇÃO DE ENDPOINTS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${NC}"

# ============================================================================
# Setup: Criar usuário e obter token JWT
# ============================================================================
echo -e "\n${YELLOW}🔑 Setup: Obtendo Token JWT${NC}"

HTTP_CODE=$(curl -s -o /tmp/inv-auth.json -w "%{http_code}" -X POST "$AUTH_URL/auth/register" \
  -H "Content-Type: application/json" \
  -d "{
    \"name\": \"Admin User\",
    \"email\": \"$RANDOM_EMAIL\",
    \"password\": \"SecurePass@123\"
  }")

if [ "$HTTP_CODE" = "201" ]; then
    JWT_TOKEN=$(cat /tmp/inv-auth.json | jq -r '.data.access_token')
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
HTTP_CODE=$(curl -s -o /tmp/inv-health.json -w "%{http_code}" http://localhost:9002/health)

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Health check OK"
    cat /tmp/inv-health.json | jq .
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    exit 1
fi

# ============================================================================
# Teste 2: Metrics Endpoint
# ============================================================================
echo -e "\n${YELLOW}📊 Teste 2: Metrics Endpoint${NC}"
METRICS=$(curl -s http://localhost:9002/metrics)

if echo "$METRICS" | grep -q "inventory_products_created_total"; then
    echo -e "${GREEN}✅ PASSED${NC} - Metrics endpoint OK"
    echo "$METRICS" | grep "^inventory_" | head -5
else
    echo -e "${RED}❌ FAILED${NC} - Métricas não encontradas"
    exit 1
fi

# ============================================================================
# Teste 3: Create Category
# ============================================================================
echo -e "\n${YELLOW}📁 Teste 3: Create Category${NC}"

TIMESTAMP=$(date +%s)
HTTP_CODE=$(curl -s -o /tmp/inv-category.json -w "%{http_code}" -X POST "$API_URL/categories" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -d "{
    \"name\": \"Eletrônicos Test $TIMESTAMP\",
    \"description\": \"Produtos eletrônicos em geral\"
  }")

if [ "$HTTP_CODE" = "201" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Categoria criada com sucesso"
    CATEGORY_ID=$(cat /tmp/inv-category.json | jq -r '.data.id')
    echo "Category ID: $CATEGORY_ID"
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    cat /tmp/inv-category.json | jq .
    exit 1
fi

# ============================================================================
# Teste 4: Create Product
# ============================================================================
echo -e "\n${YELLOW}📦 Teste 4: Create Product${NC}"

HTTP_CODE=$(curl -s -o /tmp/inv-product.json -w "%{http_code}" -X POST "$API_URL/products" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -d "{
    \"name\": \"Notebook Dell\",
    \"description\": \"Notebook Dell Inspiron 15\",
    \"sku\": \"DELL-NB-$(date +%s)\",
    \"category_id\": \"$CATEGORY_ID\",
    \"price\": 3500.00,
    \"cost_price\": 2800.00,
    \"stock_quantity\": 10,
    \"minimum_stock\": 2
  }")

if [ "$HTTP_CODE" = "201" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Produto criado com sucesso"
    PRODUCT_ID=$(cat /tmp/inv-product.json | jq -r '.data.id')
    echo "Product ID: $PRODUCT_ID"
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    cat /tmp/inv-product.json | jq .
    exit 1
fi

# ============================================================================
# Teste 5: List Products
# ============================================================================
echo -e "\n${YELLOW}📋 Teste 5: List Products${NC}"

HTTP_CODE=$(curl -s -o /tmp/inv-products.json -w "%{http_code}" -X GET "$API_URL/products" \
  -H "Authorization: Bearer $JWT_TOKEN")

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Produtos listados com sucesso"
    TOTAL=$(cat /tmp/inv-products.json | jq '.meta.total')
    echo "Total de produtos: $TOTAL"
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    exit 1
fi

# ============================================================================
# Teste 6: Get Product
# ============================================================================
echo -e "\n${YELLOW}🔍 Teste 6: Get Product${NC}"

HTTP_CODE=$(curl -s -o /tmp/inv-get-product.json -w "%{http_code}" -X GET "$API_URL/products/$PRODUCT_ID" \
  -H "Authorization: Bearer $JWT_TOKEN")

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Produto obtido com sucesso"
    cat /tmp/inv-get-product.json | jq '.data.name'
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    exit 1
fi

# ============================================================================
# Teste 7: Increase Stock
# ============================================================================
echo -e "\n${YELLOW}📊 Teste 7: Increase Stock${NC}"

HTTP_CODE=$(curl -s -o /tmp/inv-increase.json -w "%{http_code}" -X POST "$API_URL/stock/product/$PRODUCT_ID/increase" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -d "{
    \"quantity\": 5,
    \"reason\": \"Reabastecimento\"
  }")

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Estoque incrementado com sucesso"
    NEW_STOCK=$(cat /tmp/inv-increase.json | jq '.data.current_stock')
    echo "Novo estoque: $NEW_STOCK"
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    cat /tmp/inv-increase.json | jq .
    exit 1
fi

# ============================================================================
# Teste 8: Decrease Stock
# ============================================================================
echo -e "\n${YELLOW}📉 Teste 8: Decrease Stock${NC}"

HTTP_CODE=$(curl -s -o /tmp/inv-decrease.json -w "%{http_code}" -X POST "$API_URL/stock/product/$PRODUCT_ID/decrease" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -d "{
    \"quantity\": 2,
    \"reason\": \"Venda\"
  }")

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Estoque decrementado com sucesso"
    NEW_STOCK=$(cat /tmp/inv-decrease.json | jq '.data.current_stock')
    echo "Novo estoque: $NEW_STOCK"
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    cat /tmp/inv-decrease.json | jq .
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
echo -e "${GREEN}✅ Create Category${NC}"
echo -e "${GREEN}✅ Create Product${NC}"
echo -e "${GREEN}✅ List Products${NC}"
echo -e "${GREEN}✅ Get Product${NC}"
echo -e "${GREEN}✅ Increase Stock${NC}"
echo -e "${GREEN}✅ Decrease Stock${NC}"

echo -e "\n${BLUE}🎯 Inventory Service está 100% funcional!${NC}\n"

# Cleanup
rm -f /tmp/inv-*.json

