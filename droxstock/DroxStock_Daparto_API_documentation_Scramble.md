# 🚀 **DroxStock Daparto API Documentation (Scramble Generated)**

## 📋 **Overview**

This documentation covers the essential APIs for the DroxStock Daparto system, extracted directly from the Scramble-generated OpenAPI specification. It includes user authentication and core Daparto inventory management operations.

**Base URL:** `http://droxstock.test/api/v1`

**OpenAPI Version:** 3.1.0

---

## 🔐 **Authentication APIs**

### **1. Login User and Create Token**

**Endpoint:** `POST /v1/auth/login`

**Operation ID:** `auth.login`

**Summary:** Login user and create token

**Tags:** Auth

**Request Body:**

```json
{
    "email": "user@example.com",
    "password": "userpassword123"
}
```

**Request Schema:**

-   `email` (required): string, email format
-   `password` (required): string

**Responses:**

**200 OK - Login successful:**

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": "string",
            "name": "string",
            "email": "string",
            "roles": "string",
            "permissions": "string",
            "is_admin": "string"
        },
        "access_token": "string",
        "refresh_token": "string",
        "token_type": "Bearer",
        "expires_in": "string"
    }
}
```

**401 Unauthorized - Invalid credentials:**

```json
{
    "success": false,
    "message": "Invalid credentials",
    "errors": "string"
}
```

**422 Unprocessable Entity - Validation failed:**

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": "string"
}
```

**500 Internal Server Error - Login failed:**

```json
{
    "success": false,
    "message": "Login failed",
    "error": "string"
}
```

---

### **2. Register a New User (Self-Registration)**

**Endpoint:** `POST /v1/register/user`

**Operation ID:** `userRegistration.register`

**Summary:** Register a new user (requires admin approval)

**Tags:** UserRegistration

**Request Body Schema:** `RegisterUserRequest`

**Responses:**

**201 Created - Registration successful:**

```json
{
    "success": true,
    "message": "Registration successful! Your account is pending admin approval. You will receive an email once approved.",
    "data": {
        "user_id": "string",
        "email": "string",
        "status": "pending"
    }
}
```

**422 Unprocessable Entity - Validation failed:**

```json
{
    "$ref": "#/components/responses/ValidationException"
}
```

**500 Internal Server Error - Registration failed:**

```json
{
    "success": false,
    "message": "Registration failed. Please try again.",
    "error": "string"
}
```

---

### **3. Refresh User Token**

**Endpoint:** `POST /v1/auth/refresh`

**Operation ID:** `auth.refresh`

**Summary:** Refresh user token

**Tags:** Auth

**Request Body:**

```json
{
    "refresh_token": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Request Schema:**

-   `refresh_token` (optional): string

**Responses:**

**200 OK - Token refreshed successfully:**

```json
{
    "success": true,
    "message": "Token refreshed successfully",
    "data": {
        "access_token": "string",
        "refresh_token": "string",
        "token_type": "Bearer",
        "expires_in": "string"
    }
}
```

**400 Bad Request - Refresh token is required:**

```json
{
    "success": false,
    "message": "Refresh token is required",
    "error": "refresh_token_missing"
}
```

**401 Unauthorized - Invalid or expired refresh token:**

```json
{
    "success": false,
    "message": "Invalid or expired refresh token",
    "error": "invalid_refresh_token"
}
```

**404 Not Found - User not found:**

```json
{
    "success": false,
    "message": "User not found",
    "error": "user_not_found"
}
```

**500 Internal Server Error - Token refresh failed:**

```json
{
    "success": false,
    "message": "Token refresh failed",
    "error": "string"
}
```

---

### **4. Logout User (Revoke Token)**

**Endpoint:** `POST /v1/auth/logout`

**Operation ID:** `auth.logout`

**Summary:** Logout user (revoke token)

**Tags:** Auth

**Security:** `bearerAuth`

**Request Body:** None

**Responses:**

**200 OK - Successfully logged out:**

```json
{
    "success": true,
    "message": "Successfully logged out"
}
```

**401 Unauthorized:**

```json
{
    "$ref": "#/components/responses/AuthenticationException"
}
```

**500 Internal Server Error - Logout failed:**

```json
{
    "success": false,
    "message": "Logout failed",
    "error": "string"
}
```

---

### **5. Get Authenticated User Info**

**Endpoint:** `GET /v1/auth/me`

**Operation ID:** `auth.me`

**Summary:** Get authenticated user info

**Tags:** Auth

**Security:** `bearerAuth`

**Request Body:** None

**Responses:**

**200 OK - User information retrieved successfully:**

```json
{
    "success": true,
    "message": "User information retrieved successfully",
    "data": {
        "user": {
            "id": "string",
            "name": "string",
            "email": "string",
            "roles": "string",
            "permissions": "string",
            "is_admin": "string"
        }
    }
}
```

**401 Unauthorized:**

```json
{
    "$ref": "#/components/responses/AuthenticationException"
}
```

**500 Internal Server Error - Failed to retrieve user information:**

```json
{
    "success": false,
    "message": "Failed to retrieve user information",
    "error": "string"
}
```

---

## 📦 **Daparto Inventory APIs**

### **6. Display a Listing of Dapartos**

**Endpoint:** `GET /v1/dapartos`

**Operation ID:** `dapartos.index`

**Summary:** Display a listing of dapartos

**Tags:** Daparto

**Security:** `bearerAuth`

**Request Body:** None

**Responses:**

**200 OK - Dapartos retrieved successfully:**

```json
{
    "success": true,
    "message": "Dapartos retrieved successfully",
    "data": {
        "$ref": "#/components/schemas/DapartoCollection"
    }
}
```

**401 Unauthorized:**

```json
{
    "$ref": "#/components/responses/AuthenticationException"
}
```

**500 Internal Server Error - Failed to retrieve dapartos:**

```json
{
    "success": false,
    "message": "Failed to retrieve dapartos",
    "error": "string"
}
```

---

### **7. Get Daparto Statistics**

**Endpoint:** `GET /v1/dapartos-stats`

**Operation ID:** `daparto.stats`

**Summary:** Get daparto statistics

**Tags:** Daparto

**Security:** `bearerAuth`

**Request Body:** None

**Responses:**

**200 OK - Statistics retrieved successfully:**

```json
{
    "success": true,
    "message": "Statistics retrieved successfully",
    "data": {
        "total_count": "string",
        "active_count": "string",
        "deleted_count": "string",
        "total_value": "string",
        "average_price": "string",
        "brands_count": "string",
        "condition_distribution": {
            "excellent": "string",
            "very_good": "string",
            "good": "string",
            "fair": "string",
            "poor": "string"
        },
        "price_ranges": {
            "low": "string",
            "medium": "string",
            "high": "string"
        }
    }
}
```

**401 Unauthorized:**

```json
{
    "$ref": "#/components/responses/AuthenticationException"
}
```

**500 Internal Server Error - Failed to retrieve statistics:**

```json
{
    "success": false,
    "message": "Failed to retrieve statistics",
    "error": "string"
}
```

---

### **8. Get Daparto by Article Number**

**Endpoint:** `GET /v1/dapartos-by-number/{interneArtikelnummer}`

**Operation ID:** `daparto.getByNumber`

**Summary:** Get daparto by article number

**Tags:** Daparto

**Security:** `bearerAuth`

**Path Parameters:**

-   `interneArtikelnummer` (required): string

**Request Body:** None

**Responses:**

**200 OK - Daparto retrieved successfully:**

```json
{
    "success": true,
    "message": "Daparto retrieved successfully",
    "data": {
        "$ref": "#/components/schemas/DapartoResource"
    }
}
```

**401 Unauthorized:**

```json
{
    "$ref": "#/components/responses/AuthenticationException"
}
```

**404 Not Found - Daparto not found:**

```json
{
    "success": false,
    "message": "Daparto not found",
    "error": "string"
}
```

**500 Internal Server Error - Failed to retrieve daparto:**

```json
{
    "success": false,
    "message": "Failed to retrieve daparto",
    "error": "string"
}
```

---

## 🔐 **Security Schemes**

### **Bearer Authentication**

```json
{
    "bearerAuth": {
        "type": "http",
        "scheme": "bearer",
        "bearerFormat": "JWT"
    }
}
```

### **Protected Endpoints**

The following endpoints require `bearerAuth` authentication:

-   `POST /v1/auth/logout`
-   `GET /v1/auth/me`
-   `GET /v1/dapartos`
-   `GET /v1/dapartos-stats`
-   `GET /v1/dapartos-by-number/{interneArtikelnummer}`

### **Public Endpoints**

The following endpoints do not require authentication:

-   `POST /v1/auth/login`
-   `POST /v1/auth/refresh`
-   `POST /v1/register/user`

---

## 📝 **Request Headers**

### **For Public Endpoints:**

```
Content-Type: application/json
Accept: application/json
```

### **For Protected Endpoints:**

```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {your_access_token}
```

---

## ⚠️ **Error Handling**

### **Common HTTP Status Codes**

-   **200 OK:** Request successful
-   **201 Created:** Resource created successfully
-   **400 Bad Request:** Invalid request data
-   **401 Unauthorized:** Authentication required or invalid
-   **404 Not Found:** Resource not found
-   **422 Validation Error:** Request validation failed
-   **500 Internal Server Error:** Server error

### **Error Response Format**

```json
{
    "success": false,
    "message": "Error description",
    "error": "Additional error details (if available)"
}
```

---

## 🚀 **Getting Started**

1. **Register a new user** using `POST /v1/register/user`
2. **Wait for admin approval** (you'll receive an email)
3. **Login** using `POST /v1/auth/login` to get access token
4. **Use the access token** in the Authorization header for protected endpoints
5. **Refresh token** when needed using `POST /v1/auth/refresh`

---

## 📞 **Support**

For technical support or questions about this API:

-   **Email:** support@droxstock.com
-   **Documentation:** Full API documentation available at `/docs/api`
-   **Status:** Check system status at `/status`

---

_This documentation is extracted directly from the Scramble-generated OpenAPI specification (`api.json`) and covers the essential DroxStock Daparto API endpoints. For complete API reference, please refer to the full OpenAPI specification._
