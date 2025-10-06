# 🔗 Integration Examples - Auth ↔ Inventory

This document provides complete examples of how to integrate the Auth Service with the Inventory Service using JWT authentication.

---

## 📋 Prerequisites

1. Both services must be running:
   - Auth Service: `http://localhost:9001`
   - Inventory Service: `http://localhost:9002`
2. Both services must share the same `JWT_SECRET` environment variable
3. PostgreSQL databases for both services must be up and migrated

---

## 🚀 Complete Workflow Example

### Step 1: Register a User (Auth Service)

```bash
curl -X POST http://localhost:9001/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecurePass@123"
  }'
```

**Response:**
```json
{
  "message": "User registered successfully",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": {
      "id": "123e4567-e89b-12d3-a456-426614174000",
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

### Step 2: Save the Token

```bash
# Save token to environment variable
export JWT_TOKEN="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."

# Or save to file
echo "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." > token.txt
```

### Step 3: Create a Category (Inventory Service - Protected)

```bash
curl -X POST http://localhost:9002/api/v1/categories \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -d '{
    "name": "Electronics",
    "description": "Electronic products and gadgets"
  }'
```

**Response:**
```json
{
  "message": "Category created successfully",
  "data": {
    "id": "ba6297d3-92cd-452c-9453-49ff6da2e131",
    "name": "Electronics",
    "slug": "electronics",
    "description": "Electronic products and gadgets",
    "status": "active",
    "created_at": "2025-10-06 12:00:00",
    "updated_at": null
  }
}
```

### Step 4: Create a Product (Protected)

```bash
curl -X POST http://localhost:9002/api/v1/products \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -d '{
    "name": "Laptop Pro",
    "sku": "LAPTOP-001",
    "price": 1999.99,
    "category_id": "ba6297d3-92cd-452c-9453-49ff6da2e131",
    "description": "High-performance laptop",
    "barcode": "7891234567890"
  }'
```

**Response:**
```json
{
  "message": "Product created successfully",
  "data": {
    "id": "0eb5e387-d850-442e-8c8f-80f4fcec287f",
    "name": "Laptop Pro",
    "sku": "LAPTOP-001",
    "price": 1999.99,
    "category_id": "ba6297d3-92cd-452c-9453-49ff6da2e131",
    "barcode": "7891234567890",
    "description": "High-performance laptop",
    "status": "active",
    "created_at": "2025-10-06 12:05:00",
    "updated_at": null
  }
}
```

**Note:** Stock is automatically created with initial quantity = 0

### Step 5: Increase Stock (Protected)

```bash
curl -X POST http://localhost:9002/api/v1/stock/product/0eb5e387-d850-442e-8c8f-80f4fcec287f/increase \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -d '{
    "quantity": 100,
    "reason": "Initial stock from supplier",
    "reference_id": "PO-2024-001"
  }'
```

**Response:**
```json
{
  "message": "Stock increased successfully",
  "data": {
    "id": "f8c1d123-4567-89ab-cdef-0123456789ab",
    "product_id": "0eb5e387-d850-442e-8c8f-80f4fcec287f",
    "quantity": 100,
    "minimum_quantity": 10,
    "maximum_quantity": null,
    "last_movement_at": "2025-10-06 12:10:00",
    "created_at": "2025-10-06 12:05:00",
    "updated_at": "2025-10-06 12:10:00"
  }
}
```

### Step 6: View Stock (Public - No Auth Required)

```bash
curl -X GET http://localhost:9002/api/v1/stock/product/0eb5e387-d850-442e-8c8f-80f4fcec287f
```

**Response:**
```json
{
  "data": {
    "id": "f8c1d123-4567-89ab-cdef-0123456789ab",
    "product_id": "0eb5e387-d850-442e-8c8f-80f4fcec287f",
    "quantity": 100,
    "minimum_quantity": 10,
    "maximum_quantity": null,
    "last_movement_at": "2025-10-06 12:10:00",
    "created_at": "2025-10-06 12:05:00",
    "updated_at": "2025-10-06 12:10:00"
  }
}
```

### Step 7: Decrease Stock (Protected)

```bash
curl -X POST http://localhost:9002/api/v1/stock/product/0eb5e387-d850-442e-8c8f-80f4fcec287f/decrease \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -d '{
    "quantity": 20,
    "reason": "Customer sale",
    "reference_id": "SALE-2024-042"
  }'
```

**Response:**
```json
{
  "message": "Stock decreased successfully",
  "data": {
    "id": "f8c1d123-4567-89ab-cdef-0123456789ab",
    "product_id": "0eb5e387-d850-442e-8c8f-80f4fcec287f",
    "quantity": 80,
    "minimum_quantity": 10,
    "maximum_quantity": null,
    "last_movement_at": "2025-10-06 12:15:00",
    "created_at": "2025-10-06 12:05:00",
    "updated_at": "2025-10-06 12:15:00"
  }
}
```

---

## 🔄 Token Refresh Flow

When your token expires (default: 60 minutes), you need to refresh it:

```bash
curl -X POST http://localhost:9001/api/auth/refresh \
  -H "Authorization: Bearer $JWT_TOKEN"
```

**Response:**
```json
{
  "message": "Token refreshed successfully",
  "auth": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

Then update your token:
```bash
export JWT_TOKEN="<new_token_here>"
```

---

## ❌ Error Handling Examples

### 1. Unauthorized - No Token

```bash
curl -X POST http://localhost:9002/api/v1/categories \
  -H "Content-Type: application/json" \
  -d '{"name":"Test"}'
```

**Response (401):**
```json
{
  "error": "Unauthorized",
  "message": "Token not provided"
}
```

### 2. Token Expired

**Response (401):**
```json
{
  "error": "TokenExpired",
  "message": "Token has expired"
}
```

**Solution:** Refresh your token using `/api/auth/refresh`

### 3. Invalid Token

**Response (401):**
```json
{
  "error": "InvalidToken",
  "message": "Token signature is invalid"
}
```

**Solution:** Login again to get a new token

---

## 🧪 Complete Test Script

Save this as `test-integration.sh`:

```bash
#!/bin/bash

BASE_AUTH="http://localhost:9001/api"
BASE_INVENTORY="http://localhost:9002/api"

echo "=== 1. Registering user ==="
REGISTER_RESPONSE=$(curl -s -X POST "$BASE_AUTH/auth/register" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"Test@123"}')
echo "$REGISTER_RESPONSE" | jq .

echo -e "\n=== 2. Getting JWT token ==="
TOKEN=$(echo "$REGISTER_RESPONSE" | jq -r '.data.access_token')
echo "Token: ${TOKEN:0:50}..."

echo -e "\n=== 3. Creating category (with JWT) ==="
CATEGORY=$(curl -s -X POST "$BASE_INVENTORY/v1/categories" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"Electronics","description":"Electronic products"}')
echo "$CATEGORY" | jq .
CATEGORY_ID=$(echo "$CATEGORY" | jq -r '.data.id')

echo -e "\n=== 4. Creating product (with JWT) ==="
PRODUCT=$(curl -s -X POST "$BASE_INVENTORY/v1/products" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d "{\"name\":\"Laptop\",\"sku\":\"LAPTOP-001\",\"price\":999.99,\"category_id\":\"$CATEGORY_ID\"}")
echo "$PRODUCT" | jq .
PRODUCT_ID=$(echo "$PRODUCT" | jq -r '.data.id')

echo -e "\n=== 5. Increasing stock (with JWT) ==="
curl -s -X POST "$BASE_INVENTORY/v1/stock/product/$PRODUCT_ID/increase" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"quantity":50,"reason":"Initial stock"}' | jq .

echo -e "\n=== 6. Viewing stock (no JWT - public) ==="
curl -s -X GET "$BASE_INVENTORY/v1/stock/product/$PRODUCT_ID" | jq .

echo -e "\n=== 7. Testing unauthorized access (without JWT) ==="
curl -s -X POST "$BASE_INVENTORY/v1/categories" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test"}' | jq .

echo -e "\n✅ Integration test complete!"
```

Make it executable and run:
```bash
chmod +x test-integration.sh
./test-integration.sh
```

---

## 📝 Important Notes

1. **Token Sharing**: Both Auth and Inventory services must use the **same `JWT_SECRET`** in their `.env` files
2. **Token Expiration**: Default is 60 minutes (3600 seconds)
3. **Public Endpoints**: All `GET` operations in Inventory Service are public (no auth required)
4. **Protected Endpoints**: All `POST`, `PUT`, `PATCH`, `DELETE` operations require JWT
5. **Stateless Validation**: Token validation is stateless - no database lookups needed
6. **Cross-Service**: JWT tokens issued by Auth Service work seamlessly in Inventory Service

---

## 🔧 Configuration

Ensure both services have matching JWT configuration in `.env`:

```env
# Auth Service + Inventory Service (.env)
JWT_SECRET=please-change-this-secret-key-in-production-use-openssl-rand-base64-32
JWT_TTL=3600
JWT_ALGO=HS256
JWT_ISSUER=auth-service
```

---

## 🎯 Next Steps

- Integrate with Sales Service (coming soon)
- Add role-based permissions
- Implement JWT blacklisting for logout
- Add refresh token rotation
- Configure CORS for frontend integration
