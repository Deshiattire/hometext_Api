# Authentication Endpoints Test Guide

After the users structure upgrade, test these endpoints to ensure everything works:

## 1. Admin/Staff Login
**Endpoint:** `POST /api/login`
**Body:**
```json
{
    "email": "admin@hometexbd.ltd",
    "password": "12345678",
    "user_type": 1
}
```

**Expected Response:**
- Should return token
- Should have `user_type: 'admin'` or appropriate type
- Should have `roles` array
- Should have `avatar` field (not `photo`)
- Should have `shop_id` if user has shop access

## 2. Customer Login
**Endpoint:** `POST /api/user-login`
**Body:**
```json
{
    "email": "customer@example.com",
    "password": "password",
    "user_type": 3
}
```

**Expected Response:**
- Should return token
- Should have `user_type: 'customer'`
- Should have `roles: ['customer']`
- Should have `avatar` field

## 3. Customer Registration
**Endpoint:** `POST /api/registration`
**Body:**
```json
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "01712345678",
    "password": "password123",
    "conf_password": "password123"
}
```

**Expected Response:**
- Should create user with `user_type: 'customer'`
- Should assign 'customer' role
- Should return token
- Should have UUID

## 4. Get Profile
**Endpoint:** `GET /api/myprofile`
**Headers:** `Authorization: Bearer {token}`

**Expected Response:**
- Should return user data with new fields:
  - `uuid`
  - `first_name`, `last_name`
  - `avatar` (not `photo`)
  - `user_type`
  - `status`
  - `addresses` array

## 5. Logout
**Endpoint:** `POST /api/logout`
**Headers:** `Authorization: Bearer {token}`

**Expected Response:**
- Should log activity
- Should delete token

## Common Issues to Check:

1. **If you get "Column not found" errors:**
   - Make sure all SQL migrations ran successfully
   - Check that `avatar` column exists (not `photo`)

2. **If roles are missing:**
   - Run the `assign_spatie_roles.php` script
   - Check that Spatie Permission tables exist

3. **If shop_id is null:**
   - Run the `migrate_shop_id_to_access.php` script
   - Check `user_shop_access` table has data

4. **If authentication fails:**
   - Check that password hashing is working
   - Verify user status is 'active'
   - Check if account is locked (`locked_until`)

## Test Checklist:

- [ ] Admin login works
- [ ] Customer login works  
- [ ] Customer registration works
- [ ] Profile endpoint returns new structure
- [ ] Logout works and logs activity
- [ ] Roles are assigned correctly
- [ ] Shop access works (if applicable)
- [ ] Avatar field works (not photo)
- [ ] UUID is generated for new users


