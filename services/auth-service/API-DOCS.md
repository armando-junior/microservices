# 🔐 Auth Service - API Documentation

**Version:** 1.0.0  
**Base URL:** `http://localhost:8000/api`  
**Authentication:** JWT Bearer Token

---

## 📋 Table of Contents

1. [Authentication Endpoints](#authentication-endpoints)
2. [User Management Endpoints](#user-management-endpoints)
3. [Error Responses](#error-responses)
4. [Request Examples](#request-examples)

---

## 🔑 Authentication Endpoints

### 1. Register User

Create a new user account.

**Endpoint:** `POST /api/auth/register`  
**Authentication:** Not required

#### Request Body

```json
{
  "name": "John Doe",
  "email": "john.doe@example.com",
  "password": "SecureP@ss123"
}
```

#### Validation Rules

- `name`: Required, string, 1-100 characters, only letters, spaces, hyphens, apostrophes, dots
- `email`: Required, valid email (RFC 5322 + DNS check), max 255 characters, unique
- `password`: Required, min 8 characters, must contain:
  - At least one uppercase letter
  - At least one lowercase letter
  - At least one digit
  - At least one special character (@$!%*?&)

#### Success Response (201 Created)

```json
{
  "message": "User registered successfully",
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john.doe@example.com",
    "is_active": true,
    "email_verified_at": null,
    "created_at": "2025-10-04T12:00:00+00:00",
    "updated_at": "2025-10-04T12:00:00+00:00"
  },
  "auth": {
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

#### Error Responses

- **409 Conflict** - Email already exists
- **400 Bad Request** - Validation failed
- **500 Internal Server Error** - Unexpected error

---

### 2. Login User

Authenticate and get access token.

**Endpoint:** `POST /api/auth/login`  
**Authentication:** Not required

#### Request Body

```json
{
  "email": "john.doe@example.com",
  "password": "SecureP@ss123"
}
```

#### Validation Rules

- `email`: Required, valid email, max 255 characters
- `password`: Required, min 8 characters, max 255 characters

#### Success Response (200 OK)

```json
{
  "message": "Login successful",
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john.doe@example.com",
    "is_active": true,
    "email_verified_at": null,
    "created_at": "2025-10-04T12:00:00+00:00",
    "updated_at": "2025-10-04T12:00:00+00:00"
  },
  "auth": {
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

#### Error Responses

- **401 Unauthorized** - Invalid credentials
- **404 Not Found** - User not found
- **400 Bad Request** - Validation failed
- **500 Internal Server Error** - Unexpected error

---

### 3. Logout User

Revoke current access token.

**Endpoint:** `POST /api/auth/logout`  
**Authentication:** Required (JWT Bearer Token)

#### Headers

```
Authorization: Bearer {access_token}
```

#### Success Response (200 OK)

```json
{
  "message": "Logout successful"
}
```

#### Error Responses

- **401 Unauthorized** - Invalid or missing token
- **500 Internal Server Error** - Unexpected error

---

### 4. Refresh Token

Get a new access token.

**Endpoint:** `POST /api/auth/refresh`  
**Authentication:** Required (JWT Bearer Token)

#### Headers

```
Authorization: Bearer {access_token}
```

#### Success Response (200 OK)

```json
{
  "message": "Token refreshed successfully",
  "auth": {
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

#### Error Responses

- **401 Unauthorized** - Invalid or missing token
- **404 Not Found** - User not found
- **500 Internal Server Error** - Unexpected error

---

### 5. Get Current User

Get authenticated user information.

**Endpoint:** `GET /api/auth/me`  
**Authentication:** Required (JWT Bearer Token)

#### Headers

```
Authorization: Bearer {access_token}
```

#### Success Response (200 OK)

```json
{
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john.doe@example.com",
    "is_active": true,
    "email_verified_at": null,
    "created_at": "2025-10-04T12:00:00+00:00",
    "updated_at": "2025-10-04T12:00:00+00:00"
  }
}
```

#### Error Responses

- **401 Unauthorized** - Invalid or missing token
- **404 Not Found** - User not found
- **500 Internal Server Error** - Unexpected error

---

## 👤 User Management Endpoints

### 6. Get User by ID

Get user information by ID.

**Endpoint:** `GET /api/users/{id}`  
**Authentication:** Required (JWT Bearer Token)

#### Headers

```
Authorization: Bearer {access_token}
```

#### URL Parameters

- `id`: User UUID

#### Success Response (200 OK)

```json
{
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john.doe@example.com",
    "is_active": true,
    "email_verified_at": null,
    "created_at": "2025-10-04T12:00:00+00:00",
    "updated_at": "2025-10-04T12:00:00+00:00"
  }
}
```

#### Error Responses

- **401 Unauthorized** - Invalid or missing token
- **403 Forbidden** - You can only access your own data
- **404 Not Found** - User not found
- **500 Internal Server Error** - Unexpected error

---

### 7. Update User

Update user information.

**Endpoint:** `PUT /api/users/{id}` or `PATCH /api/users/{id}`  
**Authentication:** Required (JWT Bearer Token)

#### Headers

```
Authorization: Bearer {access_token}
```

#### URL Parameters

- `id`: User UUID

#### Request Body (partial update allowed)

```json
{
  "name": "Jane Doe",
  "email": "jane.doe@example.com"
}
```

#### Validation Rules

- `name`: Optional, string, 1-100 characters, only letters, spaces, hyphens, apostrophes, dots
- `email`: Optional, valid email (RFC 5322 + DNS check), max 255 characters, unique (excluding current user)

#### Success Response (200 OK)

```json
{
  "message": "User updated successfully",
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Jane Doe",
    "email": "jane.doe@example.com",
    "is_active": true,
    "email_verified_at": null,
    "created_at": "2025-10-04T12:00:00+00:00",
    "updated_at": "2025-10-04T13:30:00+00:00"
  }
}
```

#### Error Responses

- **401 Unauthorized** - Invalid or missing token
- **403 Forbidden** - You can only update your own data
- **404 Not Found** - User not found
- **400 Bad Request** - Validation failed
- **500 Internal Server Error** - Unexpected error

---

### 8. Delete User (Deactivate)

Deactivate user account (soft delete).

**Endpoint:** `DELETE /api/users/{id}`  
**Authentication:** Required (JWT Bearer Token)

#### Headers

```
Authorization: Bearer {access_token}
```

#### URL Parameters

- `id`: User UUID

#### Success Response (200 OK)

```json
{
  "message": "User deactivated successfully"
}
```

#### Error Responses

- **401 Unauthorized** - Invalid or missing token
- **403 Forbidden** - You can only delete your own account
- **404 Not Found** - User not found
- **500 Internal Server Error** - Unexpected error

---

### 9. Health Check

Check service health status.

**Endpoint:** `GET /api/health`  
**Authentication:** Not required

#### Success Response (200 OK)

```json
{
  "status": "ok",
  "service": "auth-service",
  "timestamp": "2025-10-04T12:00:00+00:00"
}
```

---

## ❌ Error Responses

All error responses follow this structure:

```json
{
  "error": "Error type",
  "message": "Detailed error message"
}
```

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | OK - Request succeeded |
| 201 | Created - Resource created successfully |
| 400 | Bad Request - Validation failed or invalid data |
| 401 | Unauthorized - Missing or invalid authentication |
| 403 | Forbidden - No permission to access resource |
| 404 | Not Found - Resource not found |
| 409 | Conflict - Resource already exists |
| 500 | Internal Server Error - Unexpected error |

---

## 📝 Request Examples

### Using cURL

#### Register

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "password": "SecureP@ss123"
  }'
```

#### Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "SecureP@ss123"
  }'
```

#### Get Current User

```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer {access_token}"
```

#### Update User

```bash
curl -X PUT http://localhost:8000/api/users/{user_id} \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Doe",
    "email": "jane.doe@example.com"
  }'
```

#### Logout

```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer {access_token}"
```

---

## 🔒 Security Notes

1. **JWT Tokens**
   - Tokens expire after 1 hour (configurable via `JWT_TTL`)
   - Tokens are stored in Redis blacklist on logout
   - Always use HTTPS in production

2. **Password Requirements**
   - Minimum 8 characters
   - Must contain uppercase, lowercase, digit, and special character
   - Hashed using bcrypt (cost factor: 12)

3. **Rate Limiting**
   - Implement rate limiting at API Gateway level (Kong)
   - Recommended: 60 requests per minute per IP

4. **CORS**
   - Configure CORS headers in production
   - Whitelist only trusted origins

5. **Input Validation**
   - All inputs are validated and sanitized
   - Email validation includes DNS check
   - SQL injection protection via Eloquent ORM

---

## 📚 Additional Resources

- [Architecture Documentation](./ARCHITECTURE.md)
- [Clean Architecture Layers](./ARCHITECTURE.md#clean-architecture-layers)
- [Domain Events](./ARCHITECTURE.md#domain-events)
- [Repository Pattern](./ARCHITECTURE.md#repository-pattern)

---

**Last Updated:** 2025-10-04  
**Maintained by:** Auth Service Team

