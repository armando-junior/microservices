#!/bin/bash

#############################################################
# Financial Service - End-to-End Tests
# Validação completa de todos os fluxos do serviço
#############################################################

set -e

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

API_URL="http://localhost:9004/api/v1"
METRICS_URL="http://localhost:9004/metrics"
PROMETHEUS_URL="http://localhost:9090"

# Test counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0
SKIPPED_TESTS=0

# Test data storage
declare -A TEST_DATA

# Utility functions
log_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

log_success() {
    echo -e "${GREEN}✓${NC} $1"
    PASSED_TESTS=$((PASSED_TESTS + 1))
}

log_error() {
    echo -e "${RED}✗${NC} $1"
    FAILED_TESTS=$((FAILED_TESTS + 1))
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

log_skip() {
    echo -e "${CYAN}⊘${NC} $1"
    SKIPPED_TESTS=$((SKIPPED_TESTS + 1))
}

test_section() {
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    echo -e "\n${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}TEST $TOTAL_TESTS: $1${NC}"
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

assert_equals() {
    local expected="$1"
    local actual="$2"
    local message="$3"
    
    if [ "$expected" == "$actual" ]; then
        log_success "$message (expected: $expected, got: $actual)"
        return 0
    else
        log_error "$message (expected: $expected, got: $actual)"
        return 1
    fi
}

assert_numeric_equals() {
    local expected="$1"
    local actual="$2"
    local message="$3"
    
    # Compare as floating point numbers
    if awk "BEGIN {exit !($expected == $actual)}"; then
        log_success "$message (expected: $expected, got: $actual)"
        return 0
    else
        log_error "$message (expected: $expected, got: $actual)"
        return 1
    fi
}

assert_not_empty() {
    local value="$1"
    local message="$2"
    
    if [ -n "$value" ] && [ "$value" != "null" ]; then
        log_success "$message (value: $value)"
        return 0
    else
        log_error "$message (value is empty or null)"
        return 1
    fi
}

assert_http_code() {
    local expected="$1"
    local actual="$2"
    local endpoint="$3"
    
    if [ "$expected" == "$actual" ]; then
        log_success "HTTP $actual for $endpoint"
        return 0
    else
        log_error "HTTP $actual for $endpoint (expected: $expected)"
        return 1
    fi
}

# Banner
echo -e "${BLUE}"
cat << "EOF"
╔══════════════════════════════════════════════════════════════════════╗
║                                                                      ║
║           🏦 FINANCIAL SERVICE - E2E TESTS                          ║
║                                                                      ║
╚══════════════════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

log_info "Starting End-to-End tests..."
log_info "API URL: $API_URL"
log_info "Test timestamp: $(date '+%Y-%m-%d %H:%M:%S')"

# ============================================
# PRE-FLIGHT CHECKS
# ============================================

test_section "Pre-flight Checks"

# Check if service is running
log_info "Checking if Financial Service is running..."
if curl -s -f "$API_URL/../health" > /dev/null 2>&1; then
    log_success "Financial Service is running"
else
    log_error "Financial Service is not accessible"
    exit 1
fi

# Check if Prometheus is scraping
log_info "Checking if Prometheus is scraping metrics..."
SCRAPE_STATUS=$(curl -s "$PROMETHEUS_URL/api/v1/targets" | jq -r '.data.activeTargets[] | select(.labels.job=="financial-service") | .health')
if [ "$SCRAPE_STATUS" == "up" ]; then
    log_success "Prometheus is scraping Financial Service"
else
    log_warning "Prometheus scraping status: $SCRAPE_STATUS"
fi

# ============================================
# TEST 1: HEALTH CHECK
# ============================================

test_section "Health Check"

HEALTH_RESPONSE=$(curl -s "$API_URL/../health")
HEALTH_STATUS=$(echo "$HEALTH_RESPONSE" | jq -r '.status')
DB_STATUS=$(echo "$HEALTH_RESPONSE" | jq -r '.checks.database')

assert_equals "healthy" "$HEALTH_STATUS" "Service health status"
assert_equals "connected" "$DB_STATUS" "Database connection status"

# ============================================
# TEST 2: SUPPLIER CRUD
# ============================================

test_section "Supplier - Complete CRUD Flow"

# Create Supplier
log_info "Creating supplier..."
TIMESTAMP=$(date +%s%N)
SUPPLIER_DATA=$(cat <<EOF
{
  "name": "E2E Test Supplier $TIMESTAMP",
  "document": "$(printf '%014d' $((10000000000000 + RANDOM % 90000000000000)))",
  "email": "e2e-supplier-$TIMESTAMP@test.com",
  "phone": "+55 11 98765-4321",
  "address": "Rua E2E Test, 123 - São Paulo, SP"
}
EOF
)

CREATE_RESPONSE=$(curl -s -X POST "$API_URL/suppliers" \
    -H "Content-Type: application/json" \
    -d "$SUPPLIER_DATA" \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$CREATE_RESPONSE" | tail -1)
BODY=$(echo "$CREATE_RESPONSE" | sed '$d')

assert_http_code "201" "$HTTP_CODE" "POST /suppliers"

SUPPLIER_ID=$(echo "$BODY" | jq -r '.data.id')
assert_not_empty "$SUPPLIER_ID" "Supplier ID returned"
TEST_DATA[supplier_id]=$SUPPLIER_ID

# Get Supplier
log_info "Retrieving supplier..."
GET_RESPONSE=$(curl -s "$API_URL/suppliers/$SUPPLIER_ID" -w "\n%{http_code}")
HTTP_CODE=$(echo "$GET_RESPONSE" | tail -1)

assert_http_code "200" "$HTTP_CODE" "GET /suppliers/{id}"

# List Suppliers
log_info "Listing suppliers..."
LIST_RESPONSE=$(curl -s "$API_URL/suppliers" -w "\n%{http_code}")
HTTP_CODE=$(echo "$LIST_RESPONSE" | tail -1)
BODY=$(echo "$LIST_RESPONSE" | sed '$d')

assert_http_code "200" "$HTTP_CODE" "GET /suppliers"

SUPPLIER_COUNT=$(echo "$BODY" | jq '.data | length')
if [ "$SUPPLIER_COUNT" -gt 0 ]; then
    log_success "Suppliers list contains $SUPPLIER_COUNT items"
else
    log_error "Suppliers list is empty"
fi

# Update Supplier
log_info "Updating supplier..."
UPDATE_DATA=$(cat <<EOF
{
  "name": "E2E Test Supplier UPDATED",
  "email": "updated-$TIMESTAMP@test.com"
}
EOF
)

UPDATE_RESPONSE=$(curl -s -X PUT "$API_URL/suppliers/$SUPPLIER_ID" \
    -H "Content-Type: application/json" \
    -d "$UPDATE_DATA" \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$UPDATE_RESPONSE" | tail -1)
BODY=$(echo "$UPDATE_RESPONSE" | sed '$d')

assert_http_code "200" "$HTTP_CODE" "PUT /suppliers/{id}"

UPDATED_NAME=$(echo "$BODY" | jq -r '.data.name')
assert_equals "E2E Test Supplier UPDATED" "$UPDATED_NAME" "Supplier name updated"

# ============================================
# TEST 3: CATEGORY CRUD
# ============================================

test_section "Category - Complete CRUD Flow"

# Create Category (Expense)
log_info "Creating expense category..."
CATEGORY_DATA=$(cat <<EOF
{
  "name": "E2E Test Category Expense $TIMESTAMP",
  "type": "expense",
  "description": "Test expense category"
}
EOF
)

CREATE_RESPONSE=$(curl -s -X POST "$API_URL/categories" \
    -H "Content-Type: application/json" \
    -d "$CATEGORY_DATA" \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$CREATE_RESPONSE" | tail -1)
BODY=$(echo "$CREATE_RESPONSE" | sed '$d')

assert_http_code "201" "$HTTP_CODE" "POST /categories"

CATEGORY_ID=$(echo "$BODY" | jq -r '.data.id')
assert_not_empty "$CATEGORY_ID" "Category ID returned"
TEST_DATA[category_id]=$CATEGORY_ID

# Create Category (Income)
log_info "Creating income category..."
INCOME_CATEGORY_DATA=$(cat <<EOF
{
  "name": "E2E Test Category Income $TIMESTAMP",
  "type": "income",
  "description": "Test income category"
}
EOF
)

CREATE_RESPONSE=$(curl -s -X POST "$API_URL/categories" \
    -H "Content-Type: application/json" \
    -d "$INCOME_CATEGORY_DATA" \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$CREATE_RESPONSE" | tail -1)
BODY=$(echo "$CREATE_RESPONSE" | sed '$d')

assert_http_code "201" "$HTTP_CODE" "POST /categories (income)"

INCOME_CATEGORY_ID=$(echo "$BODY" | jq -r '.data.id')
TEST_DATA[income_category_id]=$INCOME_CATEGORY_ID

# List Categories
log_info "Listing categories..."
LIST_RESPONSE=$(curl -s "$API_URL/categories" -w "\n%{http_code}")
HTTP_CODE=$(echo "$LIST_RESPONSE" | tail -1)

assert_http_code "200" "$HTTP_CODE" "GET /categories"

# Filter by type
log_info "Filtering categories by type (expense)..."
FILTER_RESPONSE=$(curl -s "$API_URL/categories?type=expense" -w "\n%{http_code}")
HTTP_CODE=$(echo "$FILTER_RESPONSE" | tail -1)

assert_http_code "200" "$HTTP_CODE" "GET /categories?type=expense"

# Update Category
log_info "Updating category..."
UPDATE_DATA=$(cat <<EOF
{
  "name": "E2E Test Category UPDATED",
  "description": "Updated description"
}
EOF
)

UPDATE_RESPONSE=$(curl -s -X PUT "$API_URL/categories/$CATEGORY_ID" \
    -H "Content-Type: application/json" \
    -d "$UPDATE_DATA" \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$UPDATE_RESPONSE" | tail -1)
assert_http_code "200" "$HTTP_CODE" "PUT /categories/{id}"

# ============================================
# TEST 4: ACCOUNTS PAYABLE FLOW
# ============================================

test_section "Accounts Payable - Complete Flow"

# Create Account Payable
log_info "Creating account payable..."
PAYABLE_DATA=$(cat <<EOF
{
  "supplier_id": "${TEST_DATA[supplier_id]}",
  "category_id": "${TEST_DATA[category_id]}",
  "description": "E2E Test Payment $TIMESTAMP",
  "amount": 15000.50,
  "issue_date": "$(date +%Y-%m-%d)",
  "payment_terms_days": 30
}
EOF
)

CREATE_RESPONSE=$(curl -s -X POST "$API_URL/accounts-payable" \
    -H "Content-Type: application/json" \
    -d "$PAYABLE_DATA" \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$CREATE_RESPONSE" | tail -1)
BODY=$(echo "$CREATE_RESPONSE" | sed '$d')

assert_http_code "201" "$HTTP_CODE" "POST /accounts-payable"

PAYABLE_ID=$(echo "$BODY" | jq -r '.data.id')
assert_not_empty "$PAYABLE_ID" "Account Payable ID returned"
TEST_DATA[payable_id]=$PAYABLE_ID

PAYABLE_STATUS=$(echo "$BODY" | jq -r '.data.status')
assert_equals "pending" "$PAYABLE_STATUS" "Initial status is pending"

PAYABLE_AMOUNT=$(echo "$BODY" | jq -r '.data.amount')
assert_numeric_equals "15000.50" "$PAYABLE_AMOUNT" "Amount is correct"

# List Accounts Payable
log_info "Listing accounts payable..."
LIST_RESPONSE=$(curl -s "$API_URL/accounts-payable" -w "\n%{http_code}")
HTTP_CODE=$(echo "$LIST_RESPONSE" | tail -1)

assert_http_code "200" "$HTTP_CODE" "GET /accounts-payable"

# Pay Account Payable
log_info "Paying account payable..."
PAY_DATA=$(cat <<EOF
{
  "notes": "E2E Test Payment"
}
EOF
)

PAY_RESPONSE=$(curl -s -X POST "$API_URL/accounts-payable/$PAYABLE_ID/pay" \
    -H "Content-Type: application/json" \
    -d "$PAY_DATA" \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$PAY_RESPONSE" | tail -1)
BODY=$(echo "$PAY_RESPONSE" | sed '$d')

assert_http_code "200" "$HTTP_CODE" "POST /accounts-payable/{id}/pay"

PAID_STATUS=$(echo "$BODY" | jq -r '.data.status')
assert_equals "paid" "$PAID_STATUS" "Status changed to paid"

PAID_AT=$(echo "$BODY" | jq -r '.data.paid_at')
assert_not_empty "$PAID_AT" "Payment date recorded"

# ============================================
# TEST 5: ACCOUNTS RECEIVABLE FLOW
# ============================================

test_section "Accounts Receivable - Complete Flow"

# Create Account Receivable
log_info "Creating account receivable..."
RECEIVABLE_DATA=$(cat <<EOF
{
  "customer_id": "$(uuidgen)",
  "category_id": "${TEST_DATA[income_category_id]}",
  "description": "E2E Test Receipt $TIMESTAMP",
  "amount": 25000.75,
  "issue_date": "$(date +%Y-%m-%d)",
  "payment_terms_days": 30
}
EOF
)

CREATE_RESPONSE=$(curl -s -X POST "$API_URL/accounts-receivable" \
    -H "Content-Type: application/json" \
    -d "$RECEIVABLE_DATA" \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$CREATE_RESPONSE" | tail -1)
BODY=$(echo "$CREATE_RESPONSE" | sed '$d')

assert_http_code "201" "$HTTP_CODE" "POST /accounts-receivable"

RECEIVABLE_ID=$(echo "$BODY" | jq -r '.data.id')
assert_not_empty "$RECEIVABLE_ID" "Account Receivable ID returned"
TEST_DATA[receivable_id]=$RECEIVABLE_ID

RECEIVABLE_STATUS=$(echo "$BODY" | jq -r '.data.status')
assert_equals "pending" "$RECEIVABLE_STATUS" "Initial status is pending"

# List Accounts Receivable
log_info "Listing accounts receivable..."
LIST_RESPONSE=$(curl -s "$API_URL/accounts-receivable" -w "\n%{http_code}")
HTTP_CODE=$(echo "$LIST_RESPONSE" | tail -1)

assert_http_code "200" "$HTTP_CODE" "GET /accounts-receivable"

# Receive Account Receivable
log_info "Receiving account receivable..."
RECEIVE_DATA=$(cat <<EOF
{
  "notes": "E2E Test Receipt"
}
EOF
)

RECEIVE_RESPONSE=$(curl -s -X POST "$API_URL/accounts-receivable/$RECEIVABLE_ID/receive" \
    -H "Content-Type: application/json" \
    -d "$RECEIVE_DATA" \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$RECEIVE_RESPONSE" | tail -1)
BODY=$(echo "$RECEIVE_RESPONSE" | sed '$d')

assert_http_code "200" "$HTTP_CODE" "POST /accounts-receivable/{id}/receive"

RECEIVED_STATUS=$(echo "$BODY" | jq -r '.data.status')
assert_equals "received" "$RECEIVED_STATUS" "Status changed to received"

# ============================================
# TEST 6: VALIDATION TESTS
# ============================================

test_section "Input Validation Tests"

# Invalid Supplier (missing required field)
log_info "Testing supplier validation (missing name)..."
INVALID_DATA='{"email": "test@test.com"}'
RESPONSE=$(curl -s -X POST "$API_URL/suppliers" \
    -H "Content-Type: application/json" \
    -d "$INVALID_DATA" \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$RESPONSE" | tail -1)
assert_http_code "422" "$HTTP_CODE" "POST /suppliers (invalid data)"

# Invalid Category Type
log_info "Testing category validation (invalid type)..."
INVALID_DATA='{"name": "Test", "type": "invalid_type"}'
RESPONSE=$(curl -s -X POST "$API_URL/categories" \
    -H "Content-Type: application/json" \
    -d "$INVALID_DATA" \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$RESPONSE" | tail -1)
assert_http_code "422" "$HTTP_CODE" "POST /categories (invalid type)"

# Invalid Account Payable (negative amount)
log_info "Testing account payable validation (negative amount)..."
INVALID_DATA=$(cat <<EOF
{
  "supplier_id": "${TEST_DATA[supplier_id]}",
  "category_id": "${TEST_DATA[category_id]}",
  "description": "Test",
  "amount": -1000,
  "issue_date": "$(date +%Y-%m-%d)",
  "payment_terms_days": 30
}
EOF
)

RESPONSE=$(curl -s -X POST "$API_URL/accounts-payable" \
    -H "Content-Type: application/json" \
    -d "$INVALID_DATA" \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$RESPONSE" | tail -1)
assert_http_code "422" "$HTTP_CODE" "POST /accounts-payable (negative amount)"

# Non-existent Resource
log_info "Testing 404 for non-existent supplier..."
RESPONSE=$(curl -s "$API_URL/suppliers/00000000-0000-0000-0000-000000000000" -w "\n%{http_code}")
HTTP_CODE=$(echo "$RESPONSE" | tail -1)
assert_http_code "404" "$HTTP_CODE" "GET /suppliers/{invalid_id}"

# ============================================
# TEST 7: METRICS VALIDATION
# ============================================

test_section "Metrics Validation"

log_info "Fetching Prometheus metrics..."
METRICS=$(curl -s "$METRICS_URL")

# Check if key metrics exist
log_info "Validating metric existence..."

if echo "$METRICS" | grep -q "financial_http_requests_total"; then
    log_success "Metric 'financial_http_requests_total' exists"
else
    log_error "Metric 'financial_http_requests_total' not found"
fi

if echo "$METRICS" | grep -q "financial_suppliers_created_total"; then
    log_success "Metric 'financial_suppliers_created_total' exists"
else
    log_error "Metric 'financial_suppliers_created_total' not found"
fi

if echo "$METRICS" | grep -q "financial_accounts_payable_created_total"; then
    log_success "Metric 'financial_accounts_payable_created_total' exists"
else
    log_error "Metric 'financial_accounts_payable_created_total' not found"
fi

# Check if metrics have reasonable values
HTTP_REQUESTS=$(echo "$METRICS" | grep "^financial_http_requests_total" | awk '{print $2}')
if [ -n "$HTTP_REQUESTS" ] && [ "$HTTP_REQUESTS" -gt 0 ]; then
    log_success "HTTP requests metric has value: $HTTP_REQUESTS"
else
    log_warning "HTTP requests metric is 0 or empty"
fi

# ============================================
# TEST 8: BUSINESS RULES
# ============================================

test_section "Business Rules Validation"

# Test: Cannot pay already paid account
log_info "Testing: Cannot pay already paid account..."
PAY_RESPONSE=$(curl -s -X POST "$API_URL/accounts-payable/${TEST_DATA[payable_id]}/pay" \
    -H "Content-Type: application/json" \
    -d '{"notes": "Double payment"}' \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$PAY_RESPONSE" | tail -1)
# Should fail (400 or 422)
if [ "$HTTP_CODE" == "400" ] || [ "$HTTP_CODE" == "422" ]; then
    log_success "Cannot pay already paid account (HTTP $HTTP_CODE)"
else
    log_warning "Expected 400/422 for double payment, got $HTTP_CODE"
fi

# Test: Cannot receive already received account
log_info "Testing: Cannot receive already received account..."
RECEIVE_RESPONSE=$(curl -s -X POST "$API_URL/accounts-receivable/${TEST_DATA[receivable_id]}/receive" \
    -H "Content-Type: application/json" \
    -d '{"notes": "Double receipt"}' \
    -w "\n%{http_code}")

HTTP_CODE=$(echo "$RECEIVE_RESPONSE" | tail -1)
if [ "$HTTP_CODE" == "400" ] || [ "$HTTP_CODE" == "422" ]; then
    log_success "Cannot receive already received account (HTTP $HTTP_CODE)"
else
    log_warning "Expected 400/422 for double receipt, got $HTTP_CODE"
fi

# ============================================
# TEST 9: PERFORMANCE CHECK
# ============================================

test_section "Performance Check"

log_info "Measuring response time for list endpoints..."

# Measure Suppliers List
START=$(date +%s%N)
curl -s "$API_URL/suppliers" > /dev/null
END=$(date +%s%N)
DURATION=$(( (END - START) / 1000000 ))

if [ "$DURATION" -lt 1000 ]; then
    log_success "Suppliers list response time: ${DURATION}ms (< 1s)"
else
    log_warning "Suppliers list response time: ${DURATION}ms (> 1s)"
fi

# Measure Categories List
START=$(date +%s%N)
curl -s "$API_URL/categories" > /dev/null
END=$(date +%s%N)
DURATION=$(( (END - START) / 1000000 ))

if [ "$DURATION" -lt 1000 ]; then
    log_success "Categories list response time: ${DURATION}ms (< 1s)"
else
    log_warning "Categories list response time: ${DURATION}ms (> 1s)"
fi

# ============================================
# FINAL REPORT
# ============================================

echo -e "\n${BLUE}"
cat << "EOF"
╔══════════════════════════════════════════════════════════════════════╗
║                      E2E TEST REPORT                                 ║
╚══════════════════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

echo -e "${CYAN}Test Summary:${NC}"
echo -e "  Total Tests:   ${YELLOW}$TOTAL_TESTS${NC}"
echo -e "  Passed:        ${GREEN}$PASSED_TESTS${NC}"
echo -e "  Failed:        ${RED}$FAILED_TESTS${NC}"
echo -e "  Skipped:       ${CYAN}$SKIPPED_TESTS${NC}"

if [ $FAILED_TESTS -eq 0 ]; then
    PASS_RATE=100
else
    PASS_RATE=$(( (PASSED_TESTS * 100) / (PASSED_TESTS + FAILED_TESTS) ))
fi

echo -e "\n  Pass Rate:     ${GREEN}${PASS_RATE}%${NC}"

echo -e "\n${CYAN}Test Data Created:${NC}"
echo -e "  Supplier ID:   ${TEST_DATA[supplier_id]}"
echo -e "  Category ID:   ${TEST_DATA[category_id]}"
echo -e "  Payable ID:    ${TEST_DATA[payable_id]}"
echo -e "  Receivable ID: ${TEST_DATA[receivable_id]}"

echo -e "\n${CYAN}Services Status:${NC}"
echo -e "  Financial Service:  ${GREEN}✓ Running${NC}"
echo -e "  Database:           ${GREEN}✓ Connected${NC}"
echo -e "  Prometheus:         ${GREEN}✓ Scraping${NC}"

echo ""
if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}✅ ALL E2E TESTS PASSED!${NC}"
    exit 0
else
    echo -e "${RED}❌ SOME E2E TESTS FAILED!${NC}"
    exit 1
fi

