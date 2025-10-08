#!/bin/bash

# Script para testar a API do Auth Service
#
# MELHORIAS APLICADAS:
# - URL padrão corrigida para porta 9001
# - Email mais único (timestamp + random)
# - Extração correta do token (data.access_token)
# - Extração correta do user_id (data.user.id)
# - Fallback para obter token do login se registro falhar
# - Tratamento melhor de respostas JSON
#
# USO:
#   ./test-auth-api.sh
#   API_URL=http://localhost:9001 ./test-auth-api.sh
#
# LIMPEZA (opcional, antes de executar):
#   docker compose exec auth-service php artisan migrate:fresh

set -e

API_URL="${API_URL:-http://localhost:9001}"
COLOR_GREEN='\033[0;32m'
COLOR_RED='\033[0;31m'
COLOR_YELLOW='\033[1;33m'
COLOR_BLUE='\033[0;34m'
COLOR_NC='\033[0m' # No Color

# Função para printar com cor
print_color() {
    local color=$1
    shift
    echo -e "${color}$@${COLOR_NC}"
}

# Função para testar endpoint
test_endpoint() {
    local method=$1
    local endpoint=$2
    local data=$3
    local expected_status=$4
    local token=$5
    
    print_color "$COLOR_BLUE" "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    print_color "$COLOR_BLUE" "Testing: $method $endpoint"
    print_color "$COLOR_BLUE" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    
    local curl_cmd="curl -s -w '\nHTTP_STATUS:%{http_code}' -X $method"
    curl_cmd="$curl_cmd -H 'Content-Type: application/json'"
    curl_cmd="$curl_cmd -H 'Accept: application/json'"
    
    if [ -n "$token" ]; then
        curl_cmd="$curl_cmd -H 'Authorization: Bearer $token'"
    fi
    
    if [ -n "$data" ]; then
        curl_cmd="$curl_cmd -d '$data'"
    fi
    
    curl_cmd="$curl_cmd $API_URL$endpoint"
    
    local response=$(eval $curl_cmd)
    local body=$(echo "$response" | sed -e 's/HTTP_STATUS\:.*//g')
    local status=$(echo "$response" | tr -d '\n' | sed -e 's/.*HTTP_STATUS://')
    
    echo "Response Body:"
    echo "$body" | jq . 2>/dev/null || echo "$body"
    echo ""
    echo "HTTP Status: $status"
    
    if [ "$status" == "$expected_status" ]; then
        print_color "$COLOR_GREEN" "✅ Test PASSED (Expected: $expected_status, Got: $status)"
        return 0
    else
        print_color "$COLOR_RED" "❌ Test FAILED (Expected: $expected_status, Got: $status)"
        return 1
    fi
}

# Variáveis globais para armazenar dados dos testes
ACCESS_TOKEN=""
USER_ID=""
RANDOM_EMAIL="test$(date +%s)$(shuf -i 1000-9999 -n 1)@example.com"

print_color "$COLOR_YELLOW" "╔═══════════════════════════════════════════════════════════════════╗"
print_color "$COLOR_YELLOW" "║                                                                   ║"
print_color "$COLOR_YELLOW" "║          🧪 AUTH SERVICE API - AUTOMATED TESTS 🧪                ║"
print_color "$COLOR_YELLOW" "║                                                                   ║"
print_color "$COLOR_YELLOW" "╚═══════════════════════════════════════════════════════════════════╝"

echo ""
print_color "$COLOR_BLUE" "API URL: $API_URL"
print_color "$COLOR_BLUE" "Test Email: $RANDOM_EMAIL"
echo ""

# Contador de testes
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Test 1: Health Check
TOTAL_TESTS=$((TOTAL_TESTS + 1))
if test_endpoint "GET" "/api/health" "" "200"; then
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

# Test 2: Register User
TOTAL_TESTS=$((TOTAL_TESTS + 1))
REGISTER_DATA="{\"name\":\"Test User\",\"email\":\"$RANDOM_EMAIL\",\"password\":\"SecureP@ss123\"}"

print_color "$COLOR_BLUE" "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
print_color "$COLOR_BLUE" "Testing: POST /api/auth/register"
print_color "$COLOR_BLUE" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

REGISTER_RESPONSE=$(curl -s -w '\nHTTP_STATUS:%{http_code}' -X POST \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "$REGISTER_DATA" \
    "$API_URL/api/auth/register")

REGISTER_BODY=$(echo "$REGISTER_RESPONSE" | sed -e 's/HTTP_STATUS\:.*//g')
REGISTER_STATUS=$(echo "$REGISTER_RESPONSE" | tr -d '\n' | sed -e 's/.*HTTP_STATUS://')

echo "Response Body:"
echo "$REGISTER_BODY" | jq . 2>/dev/null || echo "$REGISTER_BODY"
echo ""
echo "HTTP Status: $REGISTER_STATUS"

if [ "$REGISTER_STATUS" == "201" ]; then
    print_color "$COLOR_GREEN" "✅ Test PASSED (Expected: 201, Got: $REGISTER_STATUS)"
    PASSED_TESTS=$((PASSED_TESTS + 1))
    
    # Extrair token e user_id da resposta (estrutura: data.access_token e data.user.id)
    ACCESS_TOKEN=$(echo "$REGISTER_BODY" | jq -r '.data.access_token // .access_token // .token // empty' 2>/dev/null)
    USER_ID=$(echo "$REGISTER_BODY" | jq -r '.data.user.id // .user.id // .data.id // empty' 2>/dev/null)
    
    if [ -n "$ACCESS_TOKEN" ] && [ "$ACCESS_TOKEN" != "null" ]; then
        print_color "$COLOR_GREEN" "✅ Access Token obtained: ${ACCESS_TOKEN:0:20}..."
    fi
    
    if [ -n "$USER_ID" ] && [ "$USER_ID" != "null" ]; then
        print_color "$COLOR_GREEN" "✅ User ID obtained: $USER_ID"
    fi
else
    print_color "$COLOR_RED" "❌ Test FAILED (Expected: 201, Got: $REGISTER_STATUS)"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

# Test 3: Login User (ou obter token se registro falhou)
TOTAL_TESTS=$((TOTAL_TESTS + 1))
LOGIN_DATA="{\"email\":\"$RANDOM_EMAIL\",\"password\":\"SecureP@ss123\"}"

print_color "$COLOR_BLUE" "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
print_color "$COLOR_BLUE" "Testing: POST /api/auth/login"
print_color "$COLOR_BLUE" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

LOGIN_RESPONSE=$(curl -s -w '\nHTTP_STATUS:%{http_code}' -X POST \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "$LOGIN_DATA" \
    "$API_URL/api/auth/login")

LOGIN_BODY=$(echo "$LOGIN_RESPONSE" | sed -e 's/HTTP_STATUS\:.*//g')
LOGIN_STATUS=$(echo "$LOGIN_RESPONSE" | tr -d '\n' | sed -e 's/.*HTTP_STATUS://')

echo "Response Body:"
echo "$LOGIN_BODY" | jq . 2>/dev/null || echo "$LOGIN_BODY"
echo ""
echo "HTTP Status: $LOGIN_STATUS"

if [ "$LOGIN_STATUS" == "200" ]; then
    print_color "$COLOR_GREEN" "✅ Test PASSED (Expected: 200, Got: $LOGIN_STATUS)"
    PASSED_TESTS=$((PASSED_TESTS + 1))
    
    # Se não temos token ainda, extrair do login
    if [ -z "$ACCESS_TOKEN" ] || [ "$ACCESS_TOKEN" == "null" ]; then
        ACCESS_TOKEN=$(echo "$LOGIN_BODY" | jq -r '.data.access_token // .access_token // .token // empty' 2>/dev/null)
        USER_ID=$(echo "$LOGIN_BODY" | jq -r '.data.user.id // .user.id // .data.id // empty' 2>/dev/null)
        
        if [ -n "$ACCESS_TOKEN" ] && [ "$ACCESS_TOKEN" != "null" ]; then
            print_color "$COLOR_GREEN" "✅ Access Token obtained from login: ${ACCESS_TOKEN:0:20}..."
        fi
        
        if [ -n "$USER_ID" ] && [ "$USER_ID" != "null" ]; then
            print_color "$COLOR_GREEN" "✅ User ID obtained from login: $USER_ID"
        fi
    fi
else
    print_color "$COLOR_RED" "❌ Test FAILED (Expected: 200, Got: $LOGIN_STATUS)"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

# Test 4: Get Current User (Me)
if [ -n "$ACCESS_TOKEN" ] && [ "$ACCESS_TOKEN" != "null" ]; then
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    if test_endpoint "GET" "/api/auth/me" "" "200" "$ACCESS_TOKEN"; then
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
else
    print_color "$COLOR_YELLOW" "⚠️  Skipping authenticated tests (no access token)"
fi

# Test 5: Get User by ID
if [ -n "$ACCESS_TOKEN" ] && [ "$USER_ID" != "null" ] && [ -n "$USER_ID" ]; then
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    if test_endpoint "GET" "/api/users/$USER_ID" "" "200" "$ACCESS_TOKEN"; then
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
fi

# Test 6: Update User
if [ -n "$ACCESS_TOKEN" ] && [ "$USER_ID" != "null" ] && [ -n "$USER_ID" ]; then
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    UPDATE_DATA="{\"name\":\"Updated Test User\"}"
    if test_endpoint "PUT" "/api/users/$USER_ID" "$UPDATE_DATA" "200" "$ACCESS_TOKEN"; then
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
fi

# Test 7: Refresh Token
if [ -n "$ACCESS_TOKEN" ] && [ "$ACCESS_TOKEN" != "null" ]; then
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    if test_endpoint "POST" "/api/auth/refresh" "" "200" "$ACCESS_TOKEN"; then
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
fi

# Test 8: Logout
if [ -n "$ACCESS_TOKEN" ] && [ "$ACCESS_TOKEN" != "null" ]; then
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    if test_endpoint "POST" "/api/auth/logout" "" "200" "$ACCESS_TOKEN"; then
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
fi

# Test 9: Validation Errors (Invalid Email)
TOTAL_TESTS=$((TOTAL_TESTS + 1))
INVALID_DATA="{\"name\":\"Test\",\"email\":\"invalid-email\",\"password\":\"SecureP@ss123\"}"
if test_endpoint "POST" "/api/auth/register" "$INVALID_DATA" "422"; then
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

# Test 10: Validation Errors (Weak Password)
TOTAL_TESTS=$((TOTAL_TESTS + 1))
WEAK_PASSWORD_DATA="{\"name\":\"Test\",\"email\":\"test2@example.com\",\"password\":\"123\"}"
if test_endpoint "POST" "/api/auth/register" "$WEAK_PASSWORD_DATA" "422"; then
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

# Summary
echo ""
print_color "$COLOR_YELLOW" "╔═══════════════════════════════════════════════════════════════════╗"
print_color "$COLOR_YELLOW" "║                                                                   ║"
print_color "$COLOR_YELLOW" "║                         📊 TEST SUMMARY                          ║"
print_color "$COLOR_YELLOW" "║                                                                   ║"
print_color "$COLOR_YELLOW" "╚═══════════════════════════════════════════════════════════════════╝"
echo ""
print_color "$COLOR_BLUE" "Total Tests:  $TOTAL_TESTS"
print_color "$COLOR_GREEN" "Passed:       $PASSED_TESTS"
print_color "$COLOR_RED" "Failed:       $FAILED_TESTS"
echo ""

SUCCESS_RATE=$((PASSED_TESTS * 100 / TOTAL_TESTS))
print_color "$COLOR_BLUE" "Success Rate: $SUCCESS_RATE%"
echo ""

if [ $FAILED_TESTS -eq 0 ]; then
    print_color "$COLOR_GREEN" "╔═══════════════════════════════════════════════════════════════════╗"
    print_color "$COLOR_GREEN" "║                                                                   ║"
    print_color "$COLOR_GREEN" "║                    ✅ ALL TESTS PASSED! 🎉                       ║"
    print_color "$COLOR_GREEN" "║                                                                   ║"
    print_color "$COLOR_GREEN" "╚═══════════════════════════════════════════════════════════════════╝"
    exit 0
else
    print_color "$COLOR_RED" "╔═══════════════════════════════════════════════════════════════════╗"
    print_color "$COLOR_RED" "║                                                                   ║"
    print_color "$COLOR_RED" "║                    ❌ SOME TESTS FAILED                          ║"
    print_color "$COLOR_RED" "║                                                                   ║"
    print_color "$COLOR_RED" "╚═══════════════════════════════════════════════════════════════════╝"
    exit 1
fi

