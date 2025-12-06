# Customer Registration & Login Testing Guide

## Overview
This guide focuses on testing customer registration and login. All product browsing routes are **public** (no authentication required).

## Important Notes
- ✅ Product routes are **PUBLIC** - no login needed to browse
- ✅ Customer registration is **PUBLIC**
- ✅ Customer login is **PUBLIC**
- 🔒 Profile routes require authentication

## Testing Customer Registration

### Endpoint
**POST** `http://localhost:8000/api/user-registration`

### Headers
```
Content-Type: application/json
Accept: application/json
```

### Request Body
```json
{
  "first_name": "John",
  "email": "john@example.com",
  "phone": "01712345678",
  "password": "password123",
  "conf_password": "password123"
}
```

### Expected Response (Success - 200)
```json
{
  "success": {
    "name": "John",
    "statue": 200,
    "message": "Registration & Authentication successfully done",
    "authorisation": {
      "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
      "type": "bearer"
    }
  }
}
```

### Validation Rules
- `first_name`: Required
- `email`: Required, must be valid email, must be unique
- `phone`: Required, must be 11 digits, must be unique
- `password`: Required, min 6 characters, max 12 characters
- `conf_password`: Required, must match password

### Error Response Examples

**Email already exists:**
```json
{
  "status": 400,
  "message": "validation_err",
  "error": {
    "email": ["The email has already been taken."]
  }
}
```

**Phone already exists:**
```json
{
  "status": 400,
  "message": "validation_err",
  "error": {
    "phone": ["The phone has already been taken."]
  }
}
```

**Password mismatch:**
```json
{
  "status": 400,
  "message": "validation_err",
  "error": {
    "conf_password": ["Password and confirm password are not same."]
  }
}
```

---

## Testing Customer Login

### Endpoint
**POST** `http://localhost:8000/api/user-login`

### Headers
```
Content-Type: application/json
Accept: application/json
```

### Request Body
```json
{
  "email": "john@example.com",
  "password": "password123",
  "user_type": 3
}
```

**Note:** `user_type` must be `3` for customers. Other values will be rejected.

### Expected Response (Success - 200)
```json
{
  "success": true,
  "message": "Successfully Login!",
  "data": [{
    "token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "name": "John",
    "phone": "01712345678",
    "photo": null,
    "email": "john@example.com",
    "role": 3,
    "roles": ["customer"],
    "permissions": ["view products", "view categories", "create orders", "view orders"]
  }],
  "error": {
    "code": 0
  }
}
```

### Error Response Examples

**Invalid credentials:**
```json
{
  "status": false,
  "message": "Login credential is not valid."
}
```

**Wrong user_type:**
```json
{
  "status": false,
  "message": "Invalid user type. This endpoint is for customers only (user_type = 3)."
}
```

**User is not a customer:**
```json
{
  "status": false,
  "message": "Invalid user type. This endpoint is for customers only."
}
```

---

## Testing Public Product Routes (No Auth Required)

### Get All Products
**GET** `http://localhost:8000/api/products`

**Headers:**
```
Accept: application/json
```
(No Authorization header needed)

### Get Product by ID
**GET** `http://localhost:8000/api/products/{id}`

### Get Product by Slug
**GET** `http://localhost:8000/api/products/slug/{slug}`

### Get Featured Products
**GET** `http://localhost:8000/api/products/featured`

All product browsing routes are **PUBLIC** - no token required!

---

## Testing Protected Profile Routes

### Get My Profile
**GET** `http://localhost:8000/api/my-profile`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Expected Response:**
```json
{
  "status": "success",
  "user": {
    "id": 1,
    "name": "John",
    "email": "john@example.com",
    "phone": "01712345678"
  },
  "customer_info": null
}
```

### Update Profile
**POST** `http://localhost:8000/api/my-profile-update`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

---

## Postman Collection Setup

### Environment Variables
Create these variables in Postman:
- `base_url`: `http://localhost:8000`
- `customer_token`: (will be set after login/registration)

### Quick Test Flow

1. **Register a new customer**
   - POST `/api/user-registration`
   - Copy the token from response
   - Save as `{{customer_token}}`

2. **Test public product route**
   - GET `/api/products`
   - Should work without token

3. **Login with registered user**
   - POST `/api/user-login`
   - Should return token

4. **Get profile (requires auth)**
   - GET `/api/my-profile`
   - Use `Authorization: Bearer {{customer_token}}`

---

## Troubleshooting

### Issue: "Unauthenticated" on profile routes
- Make sure you're sending the token in Authorization header
- Format: `Bearer YOUR_TOKEN_HERE` (with space)
- Token should be from login/registration response

### Issue: Registration fails with "email already taken"
- User already exists
- Try different email or login instead

### Issue: Login fails
- Check email/phone is correct
- Check password is correct
- Make sure `user_type` is `3`
- Verify user has `role_id = 3` in database

### Issue: Postman shows "mockRequestNotFoundError"
- This is a Postman Mock Server issue, not your API
- Make sure you're calling the actual API, not a mock
- Check the URL is correct: `http://localhost:8000/api/...`

---

## Database Verification

Check if user was created:
```sql
SELECT id, name, email, phone, role_id 
FROM users 
WHERE email = 'john@example.com';
```

Check if customer role is assigned:
```sql
SELECT u.id, u.email, r.name as role
FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON mhr.role_id = r.id
WHERE u.email = 'john@example.com';
```

---

## Quick Test Checklist

- [ ] Customer registration works
- [ ] Registration returns token
- [ ] Customer login works
- [ ] Login returns token with roles/permissions
- [ ] Product routes work without authentication
- [ ] Profile routes require authentication
- [ ] Invalid credentials return proper error
- [ ] Validation errors are clear

