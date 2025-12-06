# Postman Testing Guide for RBAC & Authentication

This guide will help you test the authentication and RBAC implementation using Postman.

## Prerequisites

1. **Run Migrations** (if not already done):
   ```bash
   php artisan migrate
   ```

2. **Seed Roles and Permissions**:
   ```bash
   php artisan db:seed --class=RolesAndPermissionsSeeder
   ```

3. **Create Test Users** (or use existing ones):
   - Admin user (role_id = 1)
   - Sales Manager user (role_id = 2)
   - Customer user (role_id = 3)

## Postman Setup

### 1. Create Environment Variables

Create a new environment in Postman with these variables:

- `base_url`: `http://localhost:8000` (or your API URL)
- `admin_token`: (will be set after login)
- `sales_manager_token`: (will be set after login)
- `customer_token`: (will be set after login)

### 2. Base URL Setup

Set the base URL in Postman:
```
{{base_url}}/api
```

## Testing Authentication

### Test 1: Admin Login

**Request:**
- Method: `POST`
- URL: `{{base_url}}/api/login`
- Headers:
  ```
  Content-Type: application/json
  Accept: application/json
  ```
- Body (raw JSON):
  ```json
  {
    "email": "admin@example.com",
    "password": "password",
    "user_type": 1
  }
  ```

**Expected Response:**
```json
{
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "name": "Admin User",
  "phone": "1234567890",
  "photo": null,
  "email": "admin@example.com",
  "role": 1,
  "roles": ["admin"],
  "permissions": ["view products", "create products", ...],
  "employee_type": null,
  "branch": null
}
```

**Action:** Copy the `token` value and save it as `{{admin_token}}`

### Test 2: Sales Manager Login

**Request:**
- Method: `POST`
- URL: `{{base_url}}/api/login`
- Body:
  ```json
  {
    "email": "sales@example.com",
    "password": "password",
    "user_type": 2
  }
  ```

**Expected Response:** Similar to admin, but with `role: 2` and `roles: ["sales_manager"]`

### Test 3: Customer Login (E-commerce User)

**Request:**
- Method: `POST`
- URL: `{{base_url}}/api/user-login`
- Body:
  ```json
  {
    "email": "customer@example.com",
    "password": "password",
    "user_type": 3
  }
  ```

**Expected Response:**
```json
{
  "success": true,
  "message": "Successfully Login!",
  "data": [{
    "token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "name": "Customer Name",
    "phone": "1234567890",
    "photo": null,
    "email": "customer@example.com",
    "role": 3,
    "roles": ["customer"],
    "permissions": ["view products", "view categories", "create orders", "view orders"]
  }]
}
```

## Testing Protected Routes

### Test 4: Access Products (Admin)

**Request:**
- Method: `GET`
- URL: `{{base_url}}/api/product`
- Headers:
  ```
  Authorization: Bearer {{admin_token}}
  Accept: application/json
  ```

**Expected:** Should return products list (200 OK)

### Test 5: Create Product (Admin)

**Request:**
- Method: `POST`
- URL: `{{base_url}}/api/product`
- Headers:
  ```
  Authorization: Bearer {{admin_token}}
  Content-Type: application/json
  Accept: application/json
  ```
- Body:
  ```json
  {
    "name": "Test Product",
    "description": "Test Description",
    "price": 100.00,
    "stock": 50
  }
  ```

**Expected:** Should create product (201 Created)

### Test 6: Access Products Without Token

**Request:**
- Method: `GET`
- URL: `{{base_url}}/api/product`
- Headers:
  ```
  Accept: application/json
  ```
  (No Authorization header)

**Expected:** Should return 401 Unauthorized

### Test 7: Access Admin Route as Sales Manager

**Request:**
- Method: `GET`
- URL: `{{base_url}}/api/product`
- Headers:
  ```
  Authorization: Bearer {{sales_manager_token}}
  Accept: application/json
  ```

**Expected:** Should work if sales_manager has permission (200 OK)

### Test 8: Access Admin-Only Route as Customer

**Request:**
- Method: `GET`
- URL: `{{base_url}}/api/product`
- Headers:
  ```
  Authorization: Bearer {{customer_token}}
  Accept: application/json
  ```

**Expected:** Should return 403 Forbidden (if route requires admin permission)

## Testing Logout

### Test 9: Logout

**Request:**
- Method: `POST`
- URL: `{{base_url}}/api/logout`
- Headers:
  ```
  Authorization: Bearer {{admin_token}}
  Accept: application/json
  ```

**Expected Response:**
```json
{
  "msg": "You have successfully logout"
}
```

**After logout:** Try accessing a protected route with the same token - should return 401

## Testing User Profile

### Test 10: Get Customer Profile

**Request:**
- Method: `GET`
- URL: `{{base_url}}/api/my-profile`
- Headers:
  ```
  Authorization: Bearer {{customer_token}}
  Accept: application/json
  ```

**Expected:** Should return user profile data

## Testing Role-Based Access

### Test 11: Check User Roles (via API response)

After login, check the `roles` array in the response:
- Admin should have: `["admin"]`
- Sales Manager should have: `["sales_manager"]`
- Customer should have: `["customer"]`

### Test 12: Check Permissions

After login, check the `permissions` array:
- Admin should have all permissions
- Sales Manager should have limited permissions
- Customer should have basic permissions

## Postman Collection Structure

Create a collection with these folders:

```
Hometex API Tests
├── Authentication
│   ├── Admin Login
│   ├── Sales Manager Login
│   ├── Customer Login
│   └── Logout
├── Admin Routes
│   ├── Get Products
│   ├── Create Product
│   ├── Update Product
│   └── Delete Product
├── Sales Manager Routes
│   ├── Get Products
│   ├── Create Product
│   └── View Reports
└── Customer Routes
    ├── Get Products (Public)
    ├── Get My Profile
    └── Create Order
```

## Automated Testing Script (Postman Tests)

Add these tests to your Postman requests:

### Login Test Script:
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has token", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('token');
    pm.expect(jsonData.token).to.be.a('string');
    
    // Save token to environment
    if (jsonData.role === 1) {
        pm.environment.set("admin_token", jsonData.token);
    } else if (jsonData.role === 2) {
        pm.environment.set("sales_manager_token", jsonData.token);
    } else if (jsonData.role === 3) {
        pm.environment.set("customer_token", jsonData.token);
    }
});

pm.test("Response has roles array", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('roles');
    pm.expect(jsonData.roles).to.be.an('array');
});

pm.test("Response has permissions array", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('permissions');
    pm.expect(jsonData.permissions).to.be.an('array');
});
```

### Protected Route Test Script:
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response time is less than 500ms", function () {
    pm.expect(pm.response.responseTime).to.be.below(500);
});
```

### Unauthorized Test Script:
```javascript
pm.test("Status code is 401", function () {
    pm.response.to.have.status(401);
});

pm.test("Response has error message", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('message');
});
```

## Quick Test Checklist

- [ ] Admin can login and get token
- [ ] Sales Manager can login and get token
- [ ] Customer can login and get token
- [ ] Admin can access protected routes
- [ ] Sales Manager can access allowed routes
- [ ] Customer can access public routes
- [ ] Unauthenticated requests return 401
- [ ] Unauthorized requests return 403
- [ ] Logout invalidates token
- [ ] Roles are assigned correctly
- [ ] Permissions are assigned correctly

## Troubleshooting

### Issue: "Unauthenticated" error
- Check if token is being sent in Authorization header
- Verify token format: `Bearer {token}`
- Check if token has expired (Sanctum tokens don't expire by default)

### Issue: "Permission denied" error
- Verify user has the required role/permission
- Check if roles were seeded: `php artisan db:seed --class=RolesAndPermissionsSeeder`
- Verify user has role assigned: Check database `model_has_roles` table

### Issue: Token not working after logout
- This is expected behavior - tokens are deleted on logout
- Login again to get a new token

## Database Verification

You can verify roles and permissions in the database:

```sql
-- Check roles
SELECT * FROM roles;

-- Check permissions
SELECT * FROM permissions;

-- Check user roles
SELECT u.id, u.email, r.name as role
FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON mhr.role_id = r.id;

-- Check role permissions
SELECT r.name as role, p.name as permission
FROM roles r
JOIN role_has_permissions rhp ON r.id = rhp.role_id
JOIN permissions p ON rhp.permission_id = p.id;
```

## Next Steps

1. Test all authentication flows
2. Test all protected routes
3. Verify role-based access control
4. Test permission-based restrictions
5. Set up automated tests in Postman
6. Create a Postman collection for your team

