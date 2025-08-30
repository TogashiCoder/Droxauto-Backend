# 🚀 Droxstock API - Postman Collection Setup Guide

This guide will help you set up and use the comprehensive Postman collection for the Droxstock API.

## 📋 What's Included

-   **Complete API Collection** - All endpoints organized by functionality
-   **Environment Variables** - Pre-configured variables for easy testing
-   **Auto-token Management** - Automatic JWT token handling
-   **Example Requests** - Ready-to-use request templates
-   **Comprehensive Documentation** - Detailed descriptions for each endpoint

## 🛠️ Setup Instructions

### Step 1: Import Collection

1. Open Postman
2. Click **Import** button
3. Select the `Droxstock_API_Collection.json` file
4. The collection will be imported with all endpoints organized

### Step 2: Import Environment

1. In Postman, click **Import** again
2. Select the `Droxstock_API_Environment.json` file
3. Select the environment from the dropdown (top-right corner)
4. Verify the environment is active

### Step 3: Configure Base URL

1. The default base URL is set to `http://localhost:8000`
2. If your API runs on a different port/domain, update the `base_url` variable
3. Right-click on the collection → **Edit** → **Variables** tab

## 🔐 Authentication Flow

### 1. Login to Get Token

1. Use the **"User Login"** request in the Authentication folder
2. Update the email/password in the request body if needed
3. Send the request
4. **The access token will be automatically saved** to the environment

### 2. All Subsequent Requests

-   The token is automatically included in the `Authorization` header
-   No need to manually copy/paste tokens
-   Token refresh is handled automatically

### 3. Refresh Token Usage

**When to Use:**

-   Access token expires (after 15 days)
-   You get "Unauthorized" or "Token expired" errors
-   You want to get a new access token without logging in again

**How to Use:**

1. Use the **"🔄 Refresh Token"** request in the Authentication folder
2. The request body contains your current refresh token
3. Send the request
4. **New tokens are automatically saved** to the environment
5. Continue using the API with the new access token

**Example Refresh Token Request:**

```json
{
    "refresh_token": "cead0de0-d973-4bbf-8f9f-949edb2ebec3"
}
```

**Benefits:**

-   ✅ No need to re-login every 15 days
-   ✅ Seamless user experience
-   ✅ Automatic token renewal
-   ✅ Secure token rotation

## 📁 Collection Organization

### 🔐 Authentication

-   **User Registration** - Create new accounts
-   **User Login** - Get JWT token
-   **Get Profile** - View current user info
-   **🔄 Refresh Token** - Renew expired tokens (Public endpoint - no auth required)
-   **User Logout** - Invalidate current token

### 📊 Daparto Management

-   **CRUD Operations** - Create, Read, Update, Delete Dapartos
-   **Data Queries** - Search, filter, and sort data
-   **Statistics** - Get data summaries and analytics
-   **⚠️ Delete All Data** - **WARNING**: Clears entire Daparto table

### 📁 CSV Processing

-   **File Upload** - Upload CSV files for processing
-   **Job Status** - Monitor processing progress
-   **Background Processing** - Large files processed asynchronously

### 👥 Admin - User Management

-   **User CRUD** - Create, manage, and delete users
-   **Role Assignment** - Assign roles to users
-   **Permission Management** - Control user access levels

### 🔑 Admin - Role Management

-   **Role CRUD** - Create and manage roles
-   **Permission Assignment** - Assign permissions to roles
-   **Access Control** - Define what each role can do

## 🚨 Important Notes

### ⚠️ Delete All Dapartos Endpoint

-   **Endpoint**: `DELETE /api/v1/dapartos-delete-all`
-   **Permission Required**: `delete dapartos`
-   **Action**: Completely clears the Daparto table
-   **Use with extreme caution** - This action cannot be undone

### 🔒 Permission Requirements

-   **Admin Routes**: Require `admin` role
-   **Protected Routes**: Require valid JWT token
-   **CSV Operations**: Require appropriate permissions
-   **Data Deletion**: Requires specific deletion permissions

## 🧪 Testing Workflow

### 1. Start with Authentication

```
1. Register a new user (optional)
2. Login with existing credentials
3. Verify token is automatically saved
```

### 2. Test Basic Operations

```
1. Get user profile
2. Create a Daparto record
3. Retrieve the created record
4. Update the record
5. Delete the record
```

### 3. Test Admin Functions (if admin user)

```
1. View all users
2. Create new roles
3. Assign permissions
4. Manage user roles
```

### 4. Test CSV Processing

```
1. Upload a CSV file
2. Check job status
3. Verify data import
```

## 📝 Environment Variables

| Variable           | Description           | Default Value           |
| ------------------ | --------------------- | ----------------------- |
| `base_url`         | API base URL          | `http://localhost:8000` |
| `access_token`     | JWT access token      | Auto-filled after login |
| `admin_email`      | Admin user email      | `admin@example.com`     |
| `admin_password`   | Admin user password   | `password`              |
| `manager_email`    | Manager user email    | `manager@example.com`   |
| `manager_password` | Manager user password | `password`              |
| `user_email`       | Regular user email    | `user@example.com`      |
| `user_password`    | Regular user password | `password`              |

## 🔄 Auto-Features

### Automatic Token Management

-   Tokens are automatically extracted from login responses
-   Stored in environment variables
-   Automatically included in subsequent requests

### Content-Type Handling

-   JSON requests automatically get `Content-Type: application/json`
-   Form data requests are properly configured

### Error Handling

-   Consistent error response format
-   Detailed error messages
-   HTTP status codes for different scenarios

## 📊 Test Data Examples

### Sample Daparto Record

```json
{
    "tiltle": "BMW Engine Part",
    "teilemarke_teilenummer": "BMW-12345",
    "preis": "299.99",
    "interne_artikelnummer": "INT-001",
    "zustand": "Neu",
    "pfand": "0.00",
    "versandklasse": "Express",
    "lieferzeit": "1-2 days"
}
```

### Sample User Creation

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "roles": ["user"]
}
```

### Sample Role Creation

```json
{
    "name": "editor",
    "guard_name": "web",
    "permissions": ["view dapartos", "edit dapartos"]
}
```

## 🚀 Quick Start Commands

### 1. Start Laravel Server

```bash
php artisan serve
```

### 2. Run Queue Worker (for CSV processing)

```bash
php artisan queue:work
```

### 3. Check API Status

```bash
curl http://localhost:8000/api/v1/auth/login
```

## 🔧 Troubleshooting

### Common Issues

1. **Token Not Working**

    - Re-run the login request
    - Check if token is saved in environment
    - Verify token hasn't expired

2. **Permission Denied**

    - Check user role and permissions
    - Ensure admin routes use admin account
    - Verify JWT token is valid

3. **CSV Upload Fails**

    - Check file format and size
    - Ensure queue worker is running
    - Verify user has upload permissions

4. **Delete All Fails**
    - Check user has `delete dapartos` permission
    - Verify admin role assignment
    - Ensure proper authentication

## 📞 Support

If you encounter issues:

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Verify database connection
3. Check queue worker status
4. Review API response messages

## 🎯 Best Practices

1. **Always test with non-admin users first**
2. **Use the delete all endpoint carefully**
3. **Monitor queue worker for CSV processing**
4. **Keep tokens secure and don't share them**
5. **Test all permission levels thoroughly**

---

**Happy Testing! 🚀**

This collection provides a complete testing environment for the Droxstock API. All endpoints are properly organized, documented, and ready for use.
