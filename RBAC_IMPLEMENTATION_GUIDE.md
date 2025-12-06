# RBAC Implementation Guide

## Overview

This document outlines the complete RBAC (Role-Based Access Control), Authentication, and Authorization implementation for the Hometex e-commerce project.

## Architecture

### Backend (Laravel)
- **Laravel Sanctum**: API token authentication
- **Spatie Permission**: Role and permission management
- **Custom Middleware**: Role and permission-based route protection

### Frontend (Next.js 16)
- **NextAuth.js v5 (Auth.js)**: Session management and authentication
- **Custom Provider**: Laravel Sanctum integration
- **Middleware**: Route protection based on roles and permissions

## Implementation Status

### ✅ Completed

1. **Package Installation**
   - ✅ Removed JWT auth (php-open-source-saver/jwt-auth)
   - ✅ Installed Spatie Permission (spatie/laravel-permission)
   - ✅ Laravel Sanctum already installed

2. **Model Updates**
   - ✅ User model: Added Spatie Permission trait, removed JWT
   - ✅ SalesManager model: Added Spatie Permission trait
   - ✅ Both models now support roles and permissions

3. **Authentication**
   - ✅ Updated AuthController to use Sanctum
   - ✅ Removed debug code (dd())
   - ✅ Added role assignment on login
   - ✅ Updated EcomUserController to use Sanctum
   - ✅ Fixed logout to work with Sanctum

4. **Authorization**
   - ✅ Created RolesAndPermissionsSeeder
   - ✅ Created EnsureUserHasRole middleware
   - ✅ Created EnsureUserHasPermission middleware
   - ✅ Registered middleware in Kernel

5. **Configuration**
   - ✅ Updated auth.php to use Sanctum
   - ✅ Removed JWT guard configuration
   - ✅ Created NextAuth.js setup documentation

## Next Steps

### 1. Run Migrations and Seeders

```bash
# Publish Spatie Permission migrations (if not already done)
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Run migrations
php artisan migrate

# Seed roles and permissions
php artisan db:seed --class=RolesAndPermissionsSeeder
```

### 2. Assign Roles to Existing Users

You may need to assign roles to existing users. Create a migration or seeder:

```php
// In tinker or a seeder
$admin = User::where('role_id', 1)->first();
if ($admin) {
    $admin->assignRole('admin');
}

$salesManagers = User::where('role_id', 2)->get();
foreach ($salesManagers as $sm) {
    $sm->assignRole('sales_manager');
}

$customers = User::where('role_id', 3)->get();
foreach ($customers as $customer) {
    $customer->assignRole('customer');
}
```

### 3. Update Routes (Optional - Enhanced with Permissions)

You can now use permission-based middleware in routes:

```php
// Example: Protect route with permission
Route::middleware(['auth:sanctum', 'permission:view products'])->get('/products', ...);

// Example: Protect route with role
Route::middleware(['auth:sanctum', 'role:admin'])->get('/admin/users', ...);
```

### 4. Update Frontend

Follow the `NEXTAUTH_SETUP.md` guide to:
- Install NextAuth.js v5
- Configure authentication
- Set up API client
- Create protected routes
- Implement role/permission checks

## Roles and Permissions Structure

### Roles
1. **admin**: Full access to all features
2. **sales_manager**: Limited access for sales operations
3. **customer**: Basic access for shopping

### Key Permissions

#### Product Management
- `view products`
- `create products`
- `edit products`
- `delete products`
- `duplicate products`

#### Order Management
- `view orders`
- `create orders`
- `edit orders`
- `delete orders`
- `manage orders`

#### Category Management
- `view categories`
- `create categories`
- `edit categories`
- `delete categories`

#### Review Management
- `view reviews`
- `approve reviews`
- `reject reviews`
- `delete reviews`

See `database/seeders/RolesAndPermissionsSeeder.php` for complete list.

## Usage Examples

### Backend: Check Permissions in Controllers

```php
// In a controller
public function index()
{
    if (!auth()->user()->can('view products')) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    
    // Your logic here
}
```

### Backend: Check Roles

```php
if (auth()->user()->hasRole('admin')) {
    // Admin-only logic
}
```

### Backend: Route Protection

```php
// Using middleware
Route::middleware(['auth:sanctum', 'permission:create products'])
    ->post('/products', [ProductController::class, 'store']);

// Multiple permissions (user needs at least one)
Route::middleware(['auth:sanctum', 'permission:view products|edit products'])
    ->get('/products', [ProductController::class, 'index']);
```

### Frontend: Check Permissions

```typescript
import { useAuth } from "@/hooks/useAuth"

function ProductList() {
  const { hasPermission, hasRole } = useAuth()
  
  if (!hasPermission("view products")) {
    return <div>No access</div>
  }
  
  return <div>Product list</div>
}
```

## Security Considerations

1. **Token Storage**: Sanctum tokens stored securely
2. **HTTPS**: Always use HTTPS in production
3. **CORS**: Configure properly in Laravel
4. **Rate Limiting**: Implement on authentication endpoints
5. **Token Expiration**: Configure in `config/sanctum.php`
6. **Permission Caching**: Spatie Permission caches permissions (clear when needed)

## Troubleshooting

### Permission Not Working
```bash
# Clear permission cache
php artisan permission:cache-reset
```

### User Not Getting Roles
- Check if seeder ran successfully
- Verify user has role assigned: `$user->hasRole('admin')`
- Check database: `roles`, `permissions`, `model_has_roles` tables

### Sanctum Token Issues
- Verify CORS configuration
- Check `SANCTUM_STATEFUL_DOMAINS` in `.env`
- Ensure token is sent in `Authorization: Bearer {token}` header

## Files Modified/Created

### Modified
- `app/Models/User.php`
- `app/Models/SalesManager.php`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Controllers/web_api/EcomUserController.php`
- `config/auth.php`
- `app/Http/Kernel.php`
- `composer.json` (removed JWT, added Spatie Permission)

### Created
- `database/seeders/RolesAndPermissionsSeeder.php`
- `app/Http/Middleware/EnsureUserHasRole.php`
- `app/Http/Middleware/EnsureUserHasPermission.php`
- `NEXTAUTH_SETUP.md`
- `RBAC_IMPLEMENTATION_GUIDE.md` (this file)

## Testing Checklist

- [ ] Run migrations successfully
- [ ] Seed roles and permissions
- [ ] Test admin login
- [ ] Test sales manager login
- [ ] Test customer login
- [ ] Verify role assignment on login
- [ ] Test permission-based route access
- [ ] Test role-based route access
- [ ] Test frontend authentication flow
- [ ] Test API calls with tokens
- [ ] Verify logout functionality

## Support

For issues or questions:
1. Check Spatie Permission documentation: https://spatie.be/docs/laravel-permission
2. Check Laravel Sanctum documentation: https://laravel.com/docs/sanctum
3. Check NextAuth.js documentation: https://next-auth.js.org

