# Quick Postman Test Guide

## Step 1: Test Admin Login

**POST** `http://localhost:8000/api/login`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (raw JSON):**
```json
{
  "email": "your_admin_email@example.com",
  "password": "your_password",
  "user_type": 1
}
```

**Expected Response:**
- Status: `200 OK`
- Should contain: `token`, `roles: ["admin"]`, `permissions: [...]`

**Action:** Copy the `token` value - you'll need it for next requests

---

## Step 2: Test Protected Route (Get Products)

**GET** `http://localhost:8000/api/product`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

Replace `YOUR_TOKEN_HERE` with the token from Step 1.

**Expected Response:**
- Status: `200 OK`
- Should return products list

---

## Step 3: Test Without Token (Should Fail)

**GET** `http://localhost:8000/api/product`

**Headers:**
```
Accept: application/json
```
(No Authorization header)

**Expected Response:**
- Status: `401 Unauthorized`

---

## Step 4: Test Logout

**POST** `http://localhost:8000/api/logout`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Expected Response:**
```json
{
  "msg": "You have successfully logout"
}
```

**After logout:** Try Step 2 again with the same token - should return `401 Unauthorized`

---

## Step 5: Test Customer Login

**POST** `http://localhost:8000/api/user-login`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body:**
```json
{
  "email": "customer@example.com",
  "password": "password",
  "user_type": 3
}
```

**Expected Response:**
- Status: `200 OK`
- Should contain: `token`, `roles: ["customer"]`

---

## Quick Verification Checklist

✅ Admin login works and returns token
✅ Token allows access to protected routes
✅ Requests without token return 401
✅ Logout invalidates token
✅ Roles are returned in login response
✅ Permissions are returned in login response

---

## Common Issues & Solutions

### Issue: "Unauthenticated" (401)
- Make sure you're sending the token in the Authorization header
- Format: `Bearer YOUR_TOKEN_HERE` (with space after "Bearer")
- Check if token is expired (Sanctum tokens don't expire by default)

### Issue: "Permission denied" (403)
- User might not have the required role/permission
- Run seeder: `php artisan db:seed --class=RolesAndPermissionsSeeder`
- Check if user has role assigned in database

### Issue: Login returns error
- Check if user exists in database
- Verify password is correct
- Check user_type matches (1=admin, 2=sales_manager, 3=customer)

---

## Database Check Commands

If you want to verify roles and permissions in database:

```sql
-- See all roles
SELECT * FROM roles;

-- See all permissions  
SELECT * FROM permissions;

-- See which users have which roles
SELECT u.id, u.email, r.name as role
FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON mhr.role_id = r.id
WHERE mhr.model_type = 'App\\Models\\User';
```

