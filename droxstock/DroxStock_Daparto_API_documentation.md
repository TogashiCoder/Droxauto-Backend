# 🚀 **DroxStock Daparto API Documentation**

## 📋 **Overview**

This documentation covers the essential APIs for the DroxStock Daparto system, including user authentication and core Daparto inventory management operations.

**Base URL:** `https://droxstock.test/api/v1`

---

## 🔐 **Authentication APIs**

### **1. Login User and Create Token**

**Endpoint:** `POST /auth/login`

**Description:** Authenticate user and receive access token for API access.

**Request Body:**

```json
{
    "email": "user@example.com",
    "password": "userpassword123"
}
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "roles": ["user"],
            "permissions": []
        },
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
        "refresh_token": "550e8400-e29b-41d4-a716-446655440000",
        "token_type": "Bearer",
        "expires_in": 1296000
    }
}
```

**Response (401 Unauthorized):**

```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

---

### **2. Register a New User (Self-Registration)**

**Endpoint:** `POST /register/user`

**Description:** Allow users to register themselves (requires admin approval).

**Request Body:**

```json
{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "password": "securepassword123",
    "password_confirmation": "securepassword123"
}
```

**Response (201 Created):**

```json
{
    "success": true,
    "message": "Registration successful! Your account is pending admin approval. You will receive an email once approved.",
    "data": {
        "user_id": 123,
        "email": "john.doe@example.com",
        "status": "pending"
    }
}
```

**Response (422 Validation Error):**

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

---

### **3. Refresh User Token**

**Endpoint:** `POST /auth/refresh`

**Description:** Refresh the user's access token using a valid refresh token.

**Request Body:**

```json
{
    "refresh_token": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "Token refreshed successfully",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
        "refresh_token": "550e8400-e29b-41d4-a716-446655440000",
        "token_type": "Bearer",
        "expires_in": 1296000
    }
}
```

**Response (400 Bad Request):**

```json
{
    "success": false,
    "message": "Refresh token is required",
    "error": "refresh_token_missing"
}
```

---

### **4. Logout User (Revoke Token)**

**Endpoint:** `POST /auth/logout`

**Description:** Revoke the current user's access token.

**Authentication Required:** Bearer token

**Request Body:** None

**Response (200 OK):**

```json
{
    "success": true,
    "message": "Successfully logged out"
}
```

**Response (401 Unauthorized):**

```json
{
    "message": "Unauthenticated"
}
```

---

### **5. Get Authenticated User Info**

**Endpoint:** `GET /auth/me`

**Description:** Retrieve information about the currently authenticated user.

**Authentication Required:** Bearer token

**Request Body:** None

**Response (200 OK):**

```json
{
    "success": true,
    "message": "User information retrieved successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "roles": "user",
            "permissions": "view_dapartos",
            "is_admin": false
        }
    }
}
```

**Response (401 Unauthorized):**

```json
{
    "message": "Unauthenticated"
}
```

---

## 📦 **Daparto Inventory APIs**

### **6. Display a Listing of Dapartos**

**Endpoint:** `GET /dapartos`

**Description:** Retrieve a paginated list of all Daparto inventory items.

**Authentication Required:** Bearer token

**Query Parameters:**

-   `per_page` (optional): Items per page (default: 15)
-   `search` (optional): Search term for filtering
-   `sort_by` (optional): Sort field (default: "name")
-   `sort_direction` (optional): Sort direction (default: "asc")

**Response (200 OK):**

```json
{
    "success": true,
    "message": "Dapartos retrieved successfully",
    "data": {
        "dapartos": [
            {
                "id": 1,
                "name": "Engine Oil Filter",
                "brand": "Bosch",
                "part_number": "0986AF0065",
                "price": 15.99,
                "stock_quantity": 50,
                "condition": "new"
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 5,
            "per_page": 15,
            "total": 75
        }
    }
}
```

**Response (401 Unauthorized):**

```json
{
    "message": "Unauthenticated"
}
```

---

### **7. Get Daparto Statistics**

**Endpoint:** `GET /dapartos-stats`

**Description:** Retrieve comprehensive statistics about the Daparto inventory.

**Authentication Required:** Bearer token

**Request Body:** None

**Response (200 OK):**

```json
{
    "success": true,
    "message": "Statistics retrieved successfully",
    "data": {
        "total_count": "1500",
        "active_count": "1420",
        "deleted_count": "80",
        "total_value": "45000.00",
        "average_price": "30.00",
        "brands_count": "25",
        "condition_distribution": {
            "excellent": "800",
            "very_good": "400",
            "good": "200",
            "fair": "80",
            "poor": "20"
        }
    }
}
```

**Response (401 Unauthorized):**

```json
{
    "message": "Unauthenticated"
}
```

---

### **8. Get Daparto by Article Number**

**Endpoint:** `GET /dapartos-by-number/{interneArtikelnummer}`

**Description:** Retrieve a specific Daparto item by its article number.

**Authentication Required:** Bearer token

**Path Parameters:**

-   `interneArtikelnummer` (required): The article number to search for

**Response (200 OK):**

```json
{
    "success": true,
    "message": "Daparto retrieved successfully",
    "data": {
        "id": 1,
        "name": "Engine Oil Filter",
        "brand": "Bosch",
        "part_number": "0986AF0065",
        "interne_artikelnummer": "BOS-0986AF0065",
        "price": 15.99,
        "stock_quantity": 50,
        "condition": "new",
        "description": "High-quality engine oil filter for various vehicle models",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

**Response (404 Not Found):**

```json
{
    "success": false,
    "message": "Daparto not found"
}
```

**Response (401 Unauthorized):**

```json
{
    "message": "Unauthenticated"
}
```

---

## 🔐 **Authentication & Security**

### **Security Scheme**

-   **Type:** HTTP Bearer Authentication
-   **Scheme:** Bearer
-   **Format:** JWT

### **Protected Endpoints**

The following endpoints require authentication:

-   `POST /auth/logout`
-   `GET /auth/me`
-   `GET /dapartos`
-   `GET /dapartos-stats`
-   `GET /dapartos-by-number/{interneArtikelnummer}`

### **Public Endpoints**

The following endpoints do not require authentication:

-   `POST /auth/login`
-   `POST /auth/refresh`
-   `POST /register/user`

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

1. **Register a new user** using `POST /register/user`
2. **Wait for admin approval** (you'll receive an email)
3. **Login** using `POST /auth/login` to get access token
4. **Use the access token** in the Authorization header for protected endpoints
5. **Refresh token** when needed using `POST /auth/refresh`

---

## 📞 **Support**

For technical support or questions about this API:

-   **Email:** support@droxstock.com
-   **Documentation:** Full API documentation available at `/docs/api`
-   **Status:** Check system status at `/status`

---

_This documentation covers the essential DroxStock Daparto API endpoints. For complete API reference, please refer to the full OpenAPI specification._
