# Droxstock API Documentation

This project uses **Scramble** to automatically generate comprehensive API documentation from your Laravel controllers and routes.

## 🚀 Quick Start

### 1. View API Documentation

Once your Laravel server is running, you can access the API documentation at:

```
http://localhost:8000/docs/api
```

### 2. OpenAPI Specification

The raw OpenAPI specification is available at:

```
http://localhost:8000/docs/api.json
```

## 📚 What's Documented

### Authentication Endpoints (`/api/v1/auth`)

- **POST** `/register` - User registration
- **POST** `/login` - User authentication
- **POST** `/logout` - User logout (protected)
- **GET** `/me` - Get current user info (protected)
- **POST** `/refresh` - Refresh access token

### Daparto Management (`/api/v1/dapartos`)

- **GET** `/` - List all dapartos with filtering and pagination
- **POST** `/` - Create new daparto
- **GET** `/{id}` - Get specific daparto
- **PUT** `/{id}` - Update daparto
- **DELETE** `/{id}` - Delete daparto
- **POST** `/{id}/restore` - Restore deleted daparto
- **GET** `/stats` - Get daparto statistics
- **GET** `/by-number/{interne_artikelnummer}` - Find by article number
- **POST** `/upload-csv` - Upload CSV for bulk import
- **GET** `/csv-job-status/{jobId}` - Check CSV processing status
- **DELETE** `/delete-all` - Delete all dapartos

### RBAC (Role-Based Access Control) Management (`/api/v1/admin`)

#### Role Management

- **GET** `/roles` - List all roles with pagination
- **POST** `/roles` - Create new role
- **GET** `/roles/{id}` - Get specific role by ID
- **PUT** `/roles/{id}` - Update existing role
- **DELETE** `/roles/{id}` - Delete role (if no users assigned)

#### Permission Management

- **GET** `/permissions` - List all permissions with pagination
- **POST** `/permissions` - Create new permission
- **GET** `/permissions/{id}` - Get specific permission by ID
- **PUT** `/permissions/{id}` - Update existing permission
- **DELETE** `/permissions/{id}` - Delete permission (if no roles assigned)
- **GET** `/permissions/statistics` - Get permission statistics
- **POST** `/permissions/{id}/clone` - Clone existing permission

#### User Role Assignment

- **POST** `/users/assign-role` - Assign single role to user
- **POST** `/users/assign-multiple-roles` - Assign multiple roles to user
- **POST** `/users/remove-role` - Remove specific role from user
- **POST** `/users/remove-all-roles` - Remove all roles from user
- **GET** `/users/{id}/permissions` - Get comprehensive user permissions overview

#### User Permission Management

- **POST** `/users/assign-permission` - Assign single permission to user
- **POST** `/users/assign-multiple-permissions` - Assign multiple permissions to user
- **POST** `/users/remove-permission` - Remove specific permission from user
- **POST** `/users/remove-all-permissions` - Remove all direct permissions from user

#### Role Permission Management

- **POST** `/roles/assign-permission` - Assign single permission to role
- **POST** `/roles/assign-multiple-permissions` - Assign multiple permissions to role
- **POST** `/roles/remove-permission` - Remove specific permission from role
- **POST** `/roles/remove-all-permissions` - Remove all permissions from role

### Admin Management (`/api/v1/admin`)

- **Roles** - Full CRUD operations for user roles
- **Users** - Full CRUD operations for user management
- **Permissions** - Role and permission management

## 🔧 Configuration

### Scramble Configuration

The Scramble configuration is located in `config/scramble.php` and includes:

- API path: `api`
- Documentation title: "Droxstock API Documentation"
- Light theme with responsive layout
- Try It feature enabled for testing endpoints
- Bearer token authentication support

### OpenAPI Annotations

All controllers are documented using OpenAPI annotations:

```php
/**
 * @OA\Post(
 *     path="/api/v1/auth/login",
 *     summary="Login user",
 *     description="Authenticates user credentials and returns access token",
 *     tags={"Authentication"},
 *     @OA\RequestBody(...),
 *     @OA\Response(...)
 * )
 */
public function login(Request $request): JsonResponse
```

## 🛠️ Development

### Adding New Endpoints

To document new API endpoints:

1. Add OpenAPI annotations to your controller methods
2. Use the `@OA\Tag` annotation to group related endpoints
3. Include `@OA\Security` for protected endpoints
4. Document all request/response schemas

### Example Annotation Structure

```php
/**
 * @OA\Get(
 *     path="/api/v1/endpoint",
 *     summary="Endpoint summary",
 *     description="Detailed description",
 *     tags={"TagName"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(...),
 *     @OA\Response(...)
 * )
 */
```

### Regenerating Documentation

After making changes to your controllers:

```bash
php artisan scramble:analyze
```

## 🔐 Authentication

The API uses Bearer token authentication:

1. **Register/Login** to get an access token
2. **Include the token** in the Authorization header:
   ```
   Authorization: Bearer {your_access_token}
   ```
3. **Use the token** for all protected endpoints

## 📖 Features

### Interactive Documentation

- **Try It** feature to test endpoints directly
- **Request/Response examples** for all endpoints
- **Parameter validation** and descriptions
- **Error response documentation**

### Search and Navigation

- **Tagged endpoints** for easy navigation
- **Search functionality** across all endpoints
- **Responsive design** for mobile and desktop

### Export Options

- **OpenAPI 3.0 specification** export
- **JSON format** for integration with other tools
- **Postman collection** compatibility

## 🚨 Troubleshooting

### Common Issues

1. **Documentation not updating**

   - Run `php artisan scramble:analyze`
   - Clear cache: `php artisan cache:clear`

2. **Annotations not working**

   - Check OpenAPI annotation syntax
   - Verify `use OpenApi\Annotations as OA;` is imported

3. **Server not starting**
   - Check if port 8000 is available
   - Verify all dependencies are installed

### Debug Mode

Enable debug mode in `.env`:

```
APP_DEBUG=true
```

## 📚 Additional Resources

- [Scramble Documentation](https://scramble.dedoc.co/)
- [OpenAPI Specification](https://swagger.io/specification/)
- [Laravel Documentation](https://laravel.com/docs)

## 🤝 Contributing

When adding new endpoints:

1. Follow the existing annotation pattern
2. Include comprehensive examples
3. Document all possible response codes
4. Add appropriate tags for organization
5. Test the documentation generation

---

**Happy API Documentation! 🎉**
