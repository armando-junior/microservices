#!/bin/bash

# cross-service-integration-test.sh
#
# Script para testar a integração e comunicação entre os microserviços.
# Valida: Auth, Inventory, Sales, Financial e RabbitMQ
#
# Uso: ./scripts/cross-service-integration-test.sh

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# URLs dos serviços
AUTH_URL="http://localhost:9001/api/v1"
INVENTORY_URL="http://localhost:9002/api/v1"
SALES_URL="http://localhost:9003/api/v1"
FINANCIAL_URL="http://localhost:9004/api/v1"

# Contadores
PASSED=0
FAILED=0
TOTAL=0

# Função de log
log_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

log_success() {
    echo -e "${GREEN}✓${NC} $1"
    ((PASSED++))
}

log_error() {
    echo -e "${RED}✗${NC} $1"
    ((FAILED++))
}

log_test() {
    ((TOTAL++))
    echo -e "\n${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}TEST $TOTAL: $1${NC}"
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

# Cabeçalho
echo -e "${BLUE}\n╔══════════════════════════════════════════════════════════════════════╗"
echo -e "║                                                                      ║"
echo -e "║           🔗 CROSS-SERVICE INTEGRATION TESTS                        ║"
echo -e "║                                                                      ║"
echo -e "╚══════════════════════════════════════════════════════════════════════╝${NC}"

log_info "Iniciando testes de integração entre serviços..."
log_info "Timestamp: $(date +"%Y-%m-%d %H:%M:%S")"
echo ""

# ==========================================
# TEST 1: Verificar Saúde dos Serviços
# ==========================================
log_test "Verificar Saúde de Todos os Serviços"

services=("auth:9001" "inventory:9002" "sales:9003" "financial:9004")
for service in "${services[@]}"; do
    IFS=':' read -r name port <<< "$service"
    HEALTH=$(curl -s "http://localhost:$port/health" | jq -r '.status' 2>/dev/null)
    if [ "$HEALTH" == "healthy" ] || [ "$HEALTH" == "ok" ]; then
        log_success "$name Service está saudável"
    else
        log_error "$name Service não está respondendo corretamente (status: $HEALTH)"
    fi
done

# ==========================================
# TEST 2: Auth Service - Registro e Login
# ==========================================
log_test "Auth Service - Registro e Login"

# Limpar database do auth
docker compose exec -T auth-service php artisan db:wipe --force > /dev/null 2>&1
docker compose exec -T auth-service php artisan migrate --force > /dev/null 2>&1

# Registrar usuário
TIMESTAMP=$(date +%s)
REGISTER_RESPONSE=$(curl -s -X POST "$AUTH_URL/auth/register" \
    -H "Content-Type: application/json" \
    -d "{
        \"name\": \"Test User\",
        \"email\": \"test${TIMESTAMP}@example.com\",
        \"password\": \"Password123!\",
        \"password_confirmation\": \"Password123!\"
    }" -w "\n%{http_code}")

HTTP_CODE=$(echo "$REGISTER_RESPONSE" | tail -1)
BODY=$(echo "$REGISTER_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" == "201" ]; then
    log_success "Usuário registrado com sucesso"
    USER_EMAIL="test${TIMESTAMP}@example.com"
else
    log_error "Falha ao registrar usuário (HTTP $HTTP_CODE)"
    echo "Response: $BODY"
fi

# Login
LOGIN_RESPONSE=$(curl -s -X POST "$AUTH_URL/auth/login" \
    -H "Content-Type: application/json" \
    -d "{
        \"email\": \"$USER_EMAIL\",
        \"password\": \"Password123!\"
    }" -w "\n%{http_code}")

HTTP_CODE=$(echo "$LOGIN_RESPONSE" | tail -1)
BODY=$(echo "$LOGIN_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" == "200" ]; then
    JWT_TOKEN=$(echo "$BODY" | jq -r '.access_token')
    if [ -n "$JWT_TOKEN" ] && [ "$JWT_TOKEN" != "null" ]; then
        log_success "Login realizado e JWT gerado"
        log_info "Token JWT: ${JWT_TOKEN:0:30}..."
    else
        log_error "JWT não foi gerado"
    fi
else
    log_error "Falha no login (HTTP $HTTP_CODE)"
fi

# ==========================================
# TEST 3: Inventory Service - Criar Categoria e Produto
# ==========================================
log_test "Inventory Service - Criar Categoria e Produto"

# Criar categoria
CAT_RESPONSE=$(curl -s -X POST "$INVENTORY_URL/categories" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -d "{
        \"name\": \"Categoria Teste $(date +%s)\",
        \"description\": \"Descrição de teste\"
    }" -w "\n%{http_code}")

HTTP_CODE=$(echo "$CAT_RESPONSE" | tail -1)
BODY=$(echo "$CAT_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" == "201" ]; then
    CATEGORY_ID=$(echo "$BODY" | jq -r '.data.id')
    log_success "Categoria criada: $CATEGORY_ID"
else
    log_error "Falha ao criar categoria (HTTP $HTTP_CODE)"
    echo "Response: $BODY"
fi

# Criar produto
PRODUCT_RESPONSE=$(curl -s -X POST "$INVENTORY_URL/products" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -d "{
        \"name\": \"Produto Teste $(date +%s)\",
        \"description\": \"Descrição de teste\",
        \"sku\": \"SKU-$(date +%s)\",
        \"price\": 99.90,
        \"category_id\": \"$CATEGORY_ID\",
        \"initial_stock\": 100
    }" -w "\n%{http_code}")

HTTP_CODE=$(echo "$PRODUCT_RESPONSE" | tail -1)
BODY=$(echo "$PRODUCT_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" == "201" ]; then
    PRODUCT_ID=$(echo "$BODY" | jq -r '.data.id')
    log_success "Produto criado: $PRODUCT_ID"
else
    log_error "Falha ao criar produto (HTTP $HTTP_CODE)"
    echo "Response: $BODY"
fi

# ==========================================
# TEST 4: Sales Service - Criar Cliente e Pedido
# ==========================================
log_test "Sales Service - Criar Cliente e Pedido"

# Lista de CPFs válidos
VALID_CPFS=("12345678909" "98765432100" "11122233344" "55566677788" "99988877766")
# Selecionar um CPF aleatório
CUSTOMER_CPF="${VALID_CPFS[$RANDOM % ${#VALID_CPFS[@]}]}"

# Criar cliente
CUSTOMER_RESPONSE=$(curl -s -X POST "$SALES_URL/customers" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -d "{
        \"name\": \"Cliente Teste $(date +%s)\",
        \"email\": \"cliente${TIMESTAMP}@example.com\",
        \"document\": \"$CUSTOMER_CPF\",
        \"phone\": \"+5511987654321\",
        \"address\": \"Rua Teste, 123\"
    }" -w "\n%{http_code}")

HTTP_CODE=$(echo "$CUSTOMER_RESPONSE" | tail -1)
BODY=$(echo "$CUSTOMER_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" == "201" ]; then
    CUSTOMER_ID=$(echo "$BODY" | jq -r '.data.id')
    log_success "Cliente criado: $CUSTOMER_ID"
else
    log_error "Falha ao criar cliente (HTTP $HTTP_CODE)"
    echo "Response: $BODY"
fi

# Criar pedido
ORDER_RESPONSE=$(curl -s -X POST "$SALES_URL/orders" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -d "{
        \"customer_id\": \"$CUSTOMER_ID\"
    }" -w "\n%{http_code}")

HTTP_CODE=$(echo "$ORDER_RESPONSE" | tail -1)
BODY=$(echo "$ORDER_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" == "201" ]; then
    ORDER_ID=$(echo "$BODY" | jq -r '.data.id')
    log_success "Pedido criado: $ORDER_ID"
else
    log_error "Falha ao criar pedido (HTTP $HTTP_CODE)"
    echo "Response: $BODY"
fi

# Adicionar item ao pedido
ORDER_ITEM_RESPONSE=$(curl -s -X POST "$SALES_URL/orders/$ORDER_ID/items" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -d "{
        \"product_id\": \"$PRODUCT_ID\",
        \"quantity\": 5,
        \"unit_price\": 99.90
    }" -w "\n%{http_code}")

HTTP_CODE=$(echo "$ORDER_ITEM_RESPONSE" | tail -1)
BODY=$(echo "$ORDER_ITEM_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" == "201" ]; then
    log_success "Item adicionado ao pedido"
else
    log_error "Falha ao adicionar item ao pedido (HTTP $HTTP_CODE)"
    echo "Response: $BODY"
fi

# Confirmar pedido
CONFIRM_RESPONSE=$(curl -s -X POST "$SALES_URL/orders/$ORDER_ID/confirm" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$CONFIRM_RESPONSE" | tail -1)
BODY=$(echo "$CONFIRM_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" == "200" ]; then
    log_success "Pedido confirmado com sucesso"
else
    log_error "Falha ao confirmar pedido (HTTP $HTTP_CODE)"
    echo "Response: $BODY"
fi

# ==========================================
# TEST 5: Financial Service - Criar Fornecedor e Conta a Pagar
# ==========================================
log_test "Financial Service - Criar Fornecedor e Conta a Pagar"

# Criar fornecedor
SUPPLIER_RESPONSE=$(curl -s -X POST "$FINANCIAL_URL/suppliers" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -d "{
        \"name\": \"Fornecedor Teste $(date +%s)\",
        \"document\": \"12345678000100\",
        \"email\": \"fornecedor${TIMESTAMP}@example.com\",
        \"phone\": \"+5511987654321\",
        \"address\": \"Rua Fornecedor, 456\"
    }" -w "\n%{http_code}")

HTTP_CODE=$(echo "$SUPPLIER_RESPONSE" | tail -1)
BODY=$(echo "$SUPPLIER_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" == "201" ]; then
    SUPPLIER_ID=$(echo "$BODY" | jq -r '.data.id')
    log_success "Fornecedor criado: $SUPPLIER_ID"
else
    log_error "Falha ao criar fornecedor (HTTP $HTTP_CODE)"
    echo "Response: $BODY"
fi

# Criar categoria financeira
FIN_CAT_RESPONSE=$(curl -s -X POST "$FINANCIAL_URL/categories" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -d "{
        \"name\": \"Categoria Financeira $(date +%s)\",
        \"type\": \"expense\",
        \"description\": \"Categoria de teste\"
    }" -w "\n%{http_code}")

HTTP_CODE=$(echo "$FIN_CAT_RESPONSE" | tail -1)
BODY=$(echo "$FIN_CAT_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" == "201" ]; then
    FIN_CATEGORY_ID=$(echo "$BODY" | jq -r '.data.id')
    log_success "Categoria financeira criada: $FIN_CATEGORY_ID"
else
    log_error "Falha ao criar categoria financeira (HTTP $HTTP_CODE)"
    echo "Response: $BODY"
fi

# Criar conta a pagar
PAYABLE_RESPONSE=$(curl -s -X POST "$FINANCIAL_URL/accounts-payable" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -d "{
        \"supplier_id\": \"$SUPPLIER_ID\",
        \"category_id\": \"$FIN_CATEGORY_ID\",
        \"description\": \"Conta de teste\",
        \"amount\": 1500.00,
        \"issue_date\": \"2025-10-07\",
        \"due_date\": \"2025-12-31\",
        \"payment_terms_days\": 30
    }" -w "\n%{http_code}")

HTTP_CODE=$(echo "$PAYABLE_RESPONSE" | tail -1)
BODY=$(echo "$PAYABLE_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" == "201" ]; then
    PAYABLE_ID=$(echo "$BODY" | jq -r '.data.id')
    log_success "Conta a pagar criada: $PAYABLE_ID"
else
    log_error "Falha ao criar conta a pagar (HTTP $HTTP_CODE)"
    echo "Response: $BODY"
fi

# ==========================================
# TEST 6: RabbitMQ - Verificar Eventos Publicados
# ==========================================
log_test "RabbitMQ - Verificar Filas e Mensagens"

RABBITMQ_QUEUES=$(curl -s -u admin:admin123 "http://localhost:15672/api/queues/%2F" | jq -r '.[] | select(.messages > 0) | .name' 2>/dev/null)

if [ -n "$RABBITMQ_QUEUES" ]; then
    log_success "RabbitMQ possui filas com mensagens:"
    echo "$RABBITMQ_QUEUES" | while read -r queue; do
        MESSAGE_COUNT=$(curl -s -u admin:admin123 "http://localhost:15672/api/queues/%2F/$queue" | jq '.messages')
        log_info "  → Fila: $queue (${MESSAGE_COUNT} mensagens)"
    done
else
    log_info "RabbitMQ não possui mensagens pendentes (esperado em ambiente limpo)"
fi

# ==========================================
# RELATÓRIO FINAL
# ==========================================
echo -e "\n${BLUE}╔══════════════════════════════════════════════════════════════════════╗"
echo -e "║                  RELATÓRIO DE TESTES DE INTEGRAÇÃO                  ║"
echo -e "╚══════════════════════════════════════════════════════════════════════╝${NC}\n"

echo -e "${CYAN}Estatísticas:${NC}"
echo "  Total de Testes: $TOTAL"
echo -e "  ${GREEN}Passou: $PASSED${NC}"
echo -e "  ${RED}Falhou: $FAILED${NC}"
echo ""

if [ "$FAILED" -eq 0 ]; then
    PASS_RATE=100
    echo -e "  Taxa de Sucesso: ${GREEN}${PASS_RATE}%${NC}"
    echo -e "\n${GREEN}✅ TODOS OS TESTES DE INTEGRAÇÃO PASSARAM!${NC}\n"
    exit 0
else
    PASS_RATE=$(awk "BEGIN { printf \"%.0f\", ($PASSED * 100) / $TOTAL }")
    echo -e "  Taxa de Sucesso: ${YELLOW}${PASS_RATE}%${NC}"
    echo -e "\n${RED}❌ ALGUNS TESTES FALHARAM! Verifique os logs acima.${NC}\n"
    exit 1
fi

