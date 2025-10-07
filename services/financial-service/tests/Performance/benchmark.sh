#!/bin/bash

# Performance Benchmark Script for Sales Service
# Requires: apache2-utils (apt-get install apache2-utils)

BASE_URL="${BASE_URL:-http://localhost:9003/api/v1}"
JWT_TOKEN="${JWT_TOKEN:-}"
CONCURRENCY="${CONCURRENCY:-10}"
REQUESTS="${REQUESTS:-1000}"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🚀 Sales Service - Performance Benchmark"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Base URL: $BASE_URL"
echo "Concurrency: $CONCURRENCY concurrent requests"
echo "Total Requests: $REQUESTS"
echo ""

# Check if Apache Bench is installed
if ! command -v ab &> /dev/null; then
    echo "❌ Apache Bench (ab) not found!"
    echo "Install with: sudo apt-get install apache2-utils"
    exit 1
fi

# Create temp files for POST data
CUSTOMER_DATA=$(mktemp)
cat > "$CUSTOMER_DATA" << 'EOF'
{
  "name": "Performance Test Customer",
  "email": "perf.test@example.com",
  "phone": "11987654321",
  "document": "11144477735"
}
EOF

ORDER_DATA=$(mktemp)
cat > "$ORDER_DATA" << 'EOF'
{
  "customer_id": "550e8400-e29b-41d4-a716-446655440000"
}
EOF

# Cleanup on exit
trap "rm -f $CUSTOMER_DATA $ORDER_DATA" EXIT

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1️⃣ Testing: GET /health (Health Check)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
ab -n $REQUESTS -c $CONCURRENCY "$BASE_URL/../health"
echo ""

if [ -n "$JWT_TOKEN" ]; then
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "2️⃣ Testing: GET /customers (List Customers)"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    ab -n $REQUESTS -c $CONCURRENCY \
       -H "Authorization: Bearer $JWT_TOKEN" \
       -H "Accept: application/json" \
       "$BASE_URL/customers"
    echo ""

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "3️⃣ Testing: GET /orders (List Orders)"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    ab -n $REQUESTS -c $CONCURRENCY \
       -H "Authorization: Bearer $JWT_TOKEN" \
       -H "Accept: application/json" \
       "$BASE_URL/orders"
    echo ""

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "4️⃣ Testing: POST /customers (Create Customer)"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    ab -n 100 -c 5 \
       -H "Authorization: Bearer $JWT_TOKEN" \
       -H "Content-Type: application/json" \
       -H "Accept: application/json" \
       -p "$CUSTOMER_DATA" \
       "$BASE_URL/customers"
    echo ""

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "5️⃣ Testing: POST /orders (Create Order)"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    ab -n 100 -c 5 \
       -H "Authorization: Bearer $JWT_TOKEN" \
       -H "Content-Type: application/json" \
       -H "Accept: application/json" \
       -p "$ORDER_DATA" \
       "$BASE_URL/orders"
    echo ""
else
    echo "⚠️  JWT_TOKEN not provided. Skipping authenticated endpoints."
    echo "Set JWT_TOKEN environment variable to test protected endpoints."
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Benchmark completed!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

