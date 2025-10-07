#!/bin/bash

#############################################################
# Financial Service - Validation Script
# Tests all API endpoints and generates metrics
#############################################################

set -e

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

API_URL="http://localhost:9004/api/v1"
TEST_COUNT=0
PASSED_COUNT=0
FAILED_COUNT=0

# Test counter
test() {
    TEST_COUNT=$((TEST_COUNT + 1))
    echo -e "\n${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}TEST $TEST_COUNT: $1${NC}"
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

# Success message
success() {
    PASSED_COUNT=$((PASSED_COUNT + 1))
    echo -e "${GREEN}✅ PASSED${NC}: $1"
}

# Failure message
fail() {
    FAILED_COUNT=$((FAILED_COUNT + 1))
    echo -e "${RED}❌ FAILED${NC}: $1"
}

echo -e "${YELLOW}"
cat << "EOF"
╔══════════════════════════════════════════════════════════════════════╗
║                                                                      ║
║           🏦 FINANCIAL SERVICE VALIDATION                           ║
║                                                                      ║
╚══════════════════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

# Test 1: Health Check
test "Health Check"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:9004/health)
if [ "$HTTP_CODE" = "200" ]; then
    success "Health check returned 200 OK"
    curl -s http://localhost:9004/health | jq '.'
else
    fail "Health check returned $HTTP_CODE (expected 200)"
fi

# Test 2: Metrics Endpoint
test "Metrics Endpoint"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:9004/metrics)
if [ "$HTTP_CODE" = "200" ]; then
    success "Metrics endpoint returned 200 OK"
    echo "Sample metrics:"
    curl -s http://localhost:9004/metrics | grep "financial_" | head -5
else
    fail "Metrics endpoint returned $HTTP_CODE (expected 200)"
fi

# Test 3: Create Supplier
test "Create Supplier"
TIMESTAMP=$(date +%s)
SUPPLIER_DATA=$(cat <<EOF
{
  "name": "Fornecedor Test $TIMESTAMP",
  "document": "$(printf '%014d' $((10000000000000 + RANDOM % 90000000000000)))",
  "email": "fornecedor$TIMESTAMP@test.com",
  "phone": "+55 11 98765-4321",
  "address": "Rua Teste, $TIMESTAMP - São Paulo, SP"
}
EOF
)

RESPONSE=$(curl -s -X POST "$API_URL/suppliers" \
    -H "Content-Type: application/json" \
    -d "$SUPPLIER_DATA" \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [ "$HTTP_CODE" = "201" ] || [ "$HTTP_CODE" = "200" ]; then
    success "Supplier created successfully (HTTP $HTTP_CODE)"
    SUPPLIER_ID=$(echo "$BODY" | jq -r '.data.id')
    echo "Supplier ID: $SUPPLIER_ID"
    echo "$BODY" | jq '.'
else
    fail "Create supplier returned $HTTP_CODE (expected 201)"
    echo "$BODY" | jq '.'
fi

# Test 4: List Suppliers
test "List Suppliers"
HTTP_CODE=$(curl -s -o /tmp/financial-suppliers.json -w "%{http_code}" "$API_URL/suppliers")
if [ "$HTTP_CODE" = "200" ]; then
    success "List suppliers returned 200 OK"
    cat /tmp/financial-suppliers.json | jq '.data | length' | xargs echo "Total suppliers:"
else
    fail "List suppliers returned $HTTP_CODE (expected 200)"
fi

# Test 5: Get Supplier
if [ -n "$SUPPLIER_ID" ]; then
    test "Get Supplier by ID"
    HTTP_CODE=$(curl -s -o /tmp/financial-supplier.json -w "%{http_code}" "$API_URL/suppliers/$SUPPLIER_ID")
    if [ "$HTTP_CODE" = "200" ]; then
        success "Get supplier returned 200 OK"
        cat /tmp/financial-supplier.json | jq '.data'
    else
        fail "Get supplier returned $HTTP_CODE (expected 200)"
    fi
fi

# Test 6: Create Category
test "Create Category (Expense)"
CATEGORY_DATA=$(cat <<EOF
{
  "name": "Fornecedores Test $TIMESTAMP",
  "type": "expense",
  "description": "Pagamentos a fornecedores"
}
EOF
)

RESPONSE=$(curl -s -X POST "$API_URL/categories" \
    -H "Content-Type: application/json" \
    -d "$CATEGORY_DATA" \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [ "$HTTP_CODE" = "201" ] || [ "$HTTP_CODE" = "200" ]; then
    success "Category created successfully (HTTP $HTTP_CODE)"
    CATEGORY_ID=$(echo "$BODY" | jq -r '.data.id')
    echo "Category ID: $CATEGORY_ID"
    echo "$BODY" | jq '.'
else
    fail "Create category returned $HTTP_CODE (expected 201)"
    echo "$BODY" | jq '.'
fi

# Test 7: List Categories
test "List Categories"
HTTP_CODE=$(curl -s -o /tmp/financial-categories.json -w "%{http_code}" "$API_URL/categories")
if [ "$HTTP_CODE" = "200" ]; then
    success "List categories returned 200 OK"
    cat /tmp/financial-categories.json | jq '.data | length' | xargs echo "Total categories:"
else
    fail "List categories returned $HTTP_CODE (expected 200)"
fi

# Test 8: Create Account Payable
if [ -n "$SUPPLIER_ID" ] && [ -n "$CATEGORY_ID" ]; then
    test "Create Account Payable"
    PAYABLE_DATA=$(cat <<EOF
{
  "supplier_id": "$SUPPLIER_ID",
  "category_id": "$CATEGORY_ID",
  "description": "Teste de conta a pagar $TIMESTAMP",
  "amount": 15000.50,
  "issue_date": "$(date +%Y-%m-%d)",
  "payment_terms_days": 30
}
EOF
)

    RESPONSE=$(curl -s -X POST "$API_URL/accounts-payable" \
        -H "Content-Type: application/json" \
        -d "$PAYABLE_DATA" \
        -w "\n%{http_code}")

    HTTP_CODE=$(echo "$RESPONSE" | tail -1)
    BODY=$(echo "$RESPONSE" | sed '$d')

    if [ "$HTTP_CODE" = "201" ] || [ "$HTTP_CODE" = "200" ]; then
        success "Account payable created successfully (HTTP $HTTP_CODE)"
        PAYABLE_ID=$(echo "$BODY" | jq -r '.data.id')
        echo "Account Payable ID: $PAYABLE_ID"
        echo "$BODY" | jq '.'
    else
        fail "Create account payable returned $HTTP_CODE (expected 201)"
        echo "$BODY" | jq '.'
    fi
fi

# Test 9: List Accounts Payable
test "List Accounts Payable"
HTTP_CODE=$(curl -s -o /tmp/financial-payables.json -w "%{http_code}" "$API_URL/accounts-payable")
if [ "$HTTP_CODE" = "200" ]; then
    success "List accounts payable returned 200 OK"
    cat /tmp/financial-payables.json | jq '.data | length' | xargs echo "Total accounts payable:"
else
    fail "List accounts payable returned $HTTP_CODE (expected 200)"
fi

# Test 10: Create Account Receivable
if [ -n "$CATEGORY_ID" ]; then
    test "Create Account Receivable"
    RECEIVABLE_DATA=$(cat <<EOF
{
  "customer_id": "$(uuidgen)",
  "category_id": "$CATEGORY_ID",
  "description": "Teste de conta a receber $TIMESTAMP",
  "amount": 25000.75,
  "issue_date": "$(date +%Y-%m-%d)",
  "payment_terms_days": 30
}
EOF
)

    RESPONSE=$(curl -s -X POST "$API_URL/accounts-receivable" \
        -H "Content-Type: application/json" \
        -d "$RECEIVABLE_DATA" \
        -w "\n%{http_code}")

    HTTP_CODE=$(echo "$RESPONSE" | tail -1)
    BODY=$(echo "$RESPONSE" | sed '$d')

    if [ "$HTTP_CODE" = "201" ] || [ "$HTTP_CODE" = "200" ]; then
        success "Account receivable created successfully (HTTP $HTTP_CODE)"
        RECEIVABLE_ID=$(echo "$BODY" | jq -r '.data.id')
        echo "Account Receivable ID: $RECEIVABLE_ID"
        echo "$BODY" | jq '.'
    else
        fail "Create account receivable returned $HTTP_CODE (expected 201)"
        echo "$BODY" | jq '.'
    fi
fi

# Test 11: List Accounts Receivable
test "List Accounts Receivable"
HTTP_CODE=$(curl -s -o /tmp/financial-receivables.json -w "%{http_code}" "$API_URL/accounts-receivable")
if [ "$HTTP_CODE" = "200" ]; then
    success "List accounts receivable returned 200 OK"
    cat /tmp/financial-receivables.json | jq '.data | length' | xargs echo "Total accounts receivable:"
else
    fail "List accounts receivable returned $HTTP_CODE (expected 200)"
fi

# Final Report
echo -e "\n${YELLOW}"
cat << "EOF"
╔══════════════════════════════════════════════════════════════════════╗
║                   VALIDATION REPORT                                  ║
╚══════════════════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

echo -e "Total Tests: ${YELLOW}$TEST_COUNT${NC}"
echo -e "Passed:      ${GREEN}$PASSED_COUNT${NC}"
echo -e "Failed:      ${RED}$FAILED_COUNT${NC}"

if [ $FAILED_COUNT -eq 0 ]; then
    echo -e "\n${GREEN}✅ ALL TESTS PASSED!${NC}\n"
    exit 0
else
    echo -e "\n${RED}❌ SOME TESTS FAILED!${NC}\n"
    exit 1
fi

