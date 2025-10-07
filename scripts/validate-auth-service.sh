#!/bin/bash

# ============================================================================
# 🔐 Auth Service - Validação Completa de Endpoints
# ============================================================================

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

API_URL="http://localhost:9001/api"
RANDOM_EMAIL="user_$(date +%s)@example.com"
TEST_PASSWORD="SecurePass@123"
TEST_NAME="João Silva"

echo -e "${BLUE}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔐 AUTH SERVICE - VALIDAÇÃO DE ENDPOINTS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${NC}"

# ============================================================================
# Teste 1: Health Check
# ============================================================================
echo -e "\n${YELLOW}📊 Teste 1: Health Check${NC}"
echo "GET /health"
HTTP_CODE=$(curl -s -o /tmp/auth-health.json -w "%{http_code}" http://localhost:9001/health)

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Health check OK"
    cat /tmp/auth-health.json | jq .
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    exit 1
fi

# ============================================================================
# Teste 2: Metrics Endpoint
# ============================================================================
echo -e "\n${YELLOW}📊 Teste 2: Metrics Endpoint${NC}"
echo "GET /metrics"
METRICS=$(curl -s http://localhost:9001/metrics)

if echo "$METRICS" | grep -q "auth_login_attempts_total"; then
    echo -e "${GREEN}✅ PASSED${NC} - Metrics endpoint OK"
    echo "Métricas disponíveis:"
    echo "$METRICS" | grep "^auth_" | head -5
else
    echo -e "${RED}❌ FAILED${NC} - Métricas não encontradas"
    exit 1
fi

# ============================================================================
# Teste 3: Register (Criar novo usuário)
# ============================================================================
echo -e "\n${YELLOW}📝 Teste 3: Register User${NC}"
echo "POST /api/auth/register"
echo "Email: $RANDOM_EMAIL"

HTTP_CODE=$(curl -s -o /tmp/auth-register.json -w "%{http_code}" -X POST "$API_URL/auth/register" \
  -H "Content-Type: application/json" \
  -d "{
    \"name\": \"$TEST_NAME\",
    \"email\": \"$RANDOM_EMAIL\",
    \"password\": \"$TEST_PASSWORD\"
  }")

if [ "$HTTP_CODE" = "201" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Usuário registrado com sucesso"
    ACCESS_TOKEN=$(cat /tmp/auth-register.json | jq -r '.data.access_token')
    USER_ID=$(cat /tmp/auth-register.json | jq -r '.data.user.id')
    echo "User ID: $USER_ID"
    echo "Token: ${ACCESS_TOKEN:0:30}..."
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    cat /tmp/auth-register.json | jq .
    exit 1
fi

# ============================================================================
# Teste 4: Login (Autenticar usuário existente)
# ============================================================================
echo -e "\n${YELLOW}🔑 Teste 4: Login User${NC}"
echo "POST /api/auth/login"

HTTP_CODE=$(curl -s -o /tmp/auth-login.json -w "%{http_code}" -X POST "$API_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$RANDOM_EMAIL\",
    \"password\": \"$TEST_PASSWORD\"
  }")

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Login realizado com sucesso"
    NEW_TOKEN=$(cat /tmp/auth-login.json | jq -r '.data.access_token')
    echo "Novo Token: ${NEW_TOKEN:0:30}..."
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    cat /tmp/auth-login.json | jq .
    exit 1
fi

# ============================================================================
# Teste 5: Me (Buscar dados do usuário autenticado)
# ============================================================================
echo -e "\n${YELLOW}👤 Teste 5: Get User Profile (Me)${NC}"
echo "GET /api/auth/me"

HTTP_CODE=$(curl -s -o /tmp/auth-me.json -w "%{http_code}" -X GET "$API_URL/auth/me" \
  -H "Authorization: Bearer $ACCESS_TOKEN")

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Perfil obtido com sucesso"
    cat /tmp/auth-me.json | jq '.user'
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    cat /tmp/auth-me.json | jq .
    exit 1
fi

# ============================================================================
# Teste 6: Refresh Token
# ============================================================================
echo -e "\n${YELLOW}🔄 Teste 6: Refresh Token${NC}"
echo "POST /api/auth/refresh"

HTTP_CODE=$(curl -s -o /tmp/auth-refresh.json -w "%{http_code}" -X POST "$API_URL/auth/refresh" \
  -H "Authorization: Bearer $ACCESS_TOKEN")

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Token renovado com sucesso"
    REFRESHED_TOKEN=$(cat /tmp/auth-refresh.json | jq -r '.auth.access_token')
    echo "Token Renovado: ${REFRESHED_TOKEN:0:30}..."
    # Usar token renovado para próximos testes
    ACCESS_TOKEN=$REFRESHED_TOKEN
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    cat /tmp/auth-refresh.json | jq .
    exit 1
fi

# ============================================================================
# Teste 7: Logout
# ============================================================================
echo -e "\n${YELLOW}🚪 Teste 7: Logout${NC}"
echo "POST /api/auth/logout"

HTTP_CODE=$(curl -s -o /tmp/auth-logout.json -w "%{http_code}" -X POST "$API_URL/auth/logout" \
  -H "Authorization: Bearer $ACCESS_TOKEN")

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Logout realizado com sucesso"
    cat /tmp/auth-logout.json | jq .
else
    echo -e "${RED}❌ FAILED${NC} - HTTP $HTTP_CODE"
    cat /tmp/auth-logout.json | jq .
    exit 1
fi

# ============================================================================
# Teste 8: Verificar token invalidado (deve falhar)
# ============================================================================
echo -e "\n${YELLOW}🔒 Teste 8: Verificar Token Invalidado${NC}"
echo "GET /api/auth/me (usando token revogado)"

HTTP_CODE=$(curl -s -o /tmp/auth-me-after-logout.json -w "%{http_code}" -X GET "$API_URL/auth/me" \
  -H "Authorization: Bearer $ACCESS_TOKEN")

if [ "$HTTP_CODE" = "401" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Token invalidado corretamente"
    echo "Resposta esperada de não autorizado recebida"
else
    echo -e "${RED}❌ FAILED${NC} - Token deveria estar invalidado (HTTP $HTTP_CODE)"
    cat /tmp/auth-me-after-logout.json | jq .
    exit 1
fi

# ============================================================================
# Teste 9: Validação de erros - Email duplicado
# ============================================================================
echo -e "\n${YELLOW}⚠️  Teste 9: Validação - Email Duplicado${NC}"
echo "POST /api/auth/register (com email existente)"

HTTP_CODE=$(curl -s -o /tmp/auth-duplicate.json -w "%{http_code}" -X POST "$API_URL/auth/register" \
  -H "Content-Type: application/json" \
  -d "{
    \"name\": \"Another User\",
    \"email\": \"$RANDOM_EMAIL\",
    \"password\": \"$TEST_PASSWORD\"
  }")

if [ "$HTTP_CODE" = "422" ] || [ "$HTTP_CODE" = "409" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Validação de email duplicado funcionando (HTTP $HTTP_CODE)"
    cat /tmp/auth-duplicate.json | jq '.' 2>/dev/null || cat /tmp/auth-duplicate.json
else
    echo -e "${RED}❌ FAILED${NC} - Deveria retornar HTTP 422 ou 409 (recebeu $HTTP_CODE)"
    exit 1
fi

# ============================================================================
# Teste 10: Validação de erros - Senha fraca
# ============================================================================
echo -e "\n${YELLOW}⚠️  Teste 10: Validação - Senha Fraca${NC}"
echo "POST /api/auth/register (com senha inválida)"

HTTP_CODE=$(curl -s -o /tmp/auth-weak-password.json -w "%{http_code}" -X POST "$API_URL/auth/register" \
  -H "Content-Type: application/json" \
  -d "{
    \"name\": \"Test User\",
    \"email\": \"test_$(date +%s)@example.com\",
    \"password\": \"weak\"
  }")

if [ "$HTTP_CODE" = "422" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Validação de senha fraca funcionando"
    cat /tmp/auth-weak-password.json | jq '.errors.password'
else
    echo -e "${RED}❌ FAILED${NC} - Deveria retornar HTTP 422 (recebeu $HTTP_CODE)"
    exit 1
fi

# ============================================================================
# Teste 11: Credenciais inválidas
# ============================================================================
echo -e "\n${YELLOW}🔑 Teste 11: Login com Credenciais Inválidas${NC}"
echo "POST /api/auth/login (senha errada)"

HTTP_CODE=$(curl -s -o /tmp/auth-invalid-login.json -w "%{http_code}" -X POST "$API_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$RANDOM_EMAIL\",
    \"password\": \"WrongPassword123!\"
  }")

if [ "$HTTP_CODE" = "401" ]; then
    echo -e "${GREEN}✅ PASSED${NC} - Rejeição de credenciais inválidas funcionando"
    cat /tmp/auth-invalid-login.json | jq .
else
    echo -e "${RED}❌ FAILED${NC} - Deveria retornar HTTP 401 (recebeu $HTTP_CODE)"
    exit 1
fi

# ============================================================================
# Verificar métricas atualizadas
# ============================================================================
echo -e "\n${YELLOW}📊 Verificando Métricas Atualizadas${NC}"

METRICS_FINAL=$(curl -s http://localhost:9001/metrics)
echo "$METRICS_FINAL" | grep "^auth_" | grep "total"

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
echo -e "${GREEN}✅ Register User${NC}"
echo -e "${GREEN}✅ Login User${NC}"
echo -e "${GREEN}✅ Get User Profile (Me)${NC}"
echo -e "${GREEN}✅ Refresh Token${NC}"
echo -e "${GREEN}✅ Logout${NC}"
echo -e "${GREEN}✅ Token Invalidation${NC}"
echo -e "${GREEN}✅ Email Validation${NC}"
echo -e "${GREEN}✅ Password Validation${NC}"
echo -e "${GREEN}✅ Invalid Credentials${NC}"

echo -e "\n${BLUE}🎯 Auth Service está 100% funcional!${NC}\n"

# Cleanup
rm -f /tmp/auth-*.json
