#!/bin/bash

# Script de teste rápido simplificado

API_URL="http://localhost:8000"
EMAIL="quicktest$(date +%s)@example.com"

echo "=== Quick API Test ==="
echo "Email: $EMAIL"
echo ""

# Test 1: Health Check
echo "1️⃣  Health Check:"
curl -s "$API_URL/api/health" | jq .
echo ""

# Test 2: Register User
echo "2️⃣  Register User:"
REGISTER_RESPONSE=$(curl -s -X POST "$API_URL/api/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"name\":\"Quick Test\",\"email\":\"$EMAIL\",\"password\":\"SecureP@ss123\",\"password_confirmation\":\"SecureP@ss123\"}")
echo "$REGISTER_RESPONSE" | jq .

# Extract token (from .data.access_token because of Laravel Resource wrapping)
TOKEN=$(echo "$REGISTER_RESPONSE" | jq -r '.data.access_token // .access_token // empty')

if [ -z "$TOKEN" ]; then
    echo "❌ Failed to get access token"
    exit 1
fi

echo ""
echo "✅ Got access token: ${TOKEN:0:20}..."
echo ""

# Test 3: Get /me
echo "3️⃣  Get /me (authenticated):"
curl -s "$API_URL/api/auth/me" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq .
echo ""

# Test 4: Logout
echo "4️⃣  Logout:"
curl -s -X POST "$API_URL/api/auth/logout" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq .
echo ""

# Test 5: Login
echo "5️⃣  Login:"
LOGIN_RESPONSE=$(curl -s -X POST "$API_URL/api/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"SecureP@ss123\"}")
echo "$LOGIN_RESPONSE" | jq .

# Extract new token (from .data.access_token because of Laravel Resource wrapping)
NEW_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.access_token // .access_token // empty')

if [ -z "$NEW_TOKEN" ]; then
    echo "❌ Failed to login"
    exit 1
fi

echo ""
echo "✅ Logged in successfully"
echo ""

echo "=== ✅ All tests passed! ==="

