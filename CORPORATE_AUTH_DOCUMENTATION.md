# Corporate Authentication System Documentation

## Overview
This system provides a complete B2B corporate customer registration, authentication, and management system following industry best practices.

## Features

### 1. **Corporate Registration**
- Self-service registration with comprehensive company information
- Email and phone uniqueness validation
- Trade license and VAT registration number tracking
- Password strength requirements (min 8 chars, uppercase, lowercase, number)
- Pending approval status upon registration
- Automatic role assignment (corporate role via Spatie Permissions)

### 2. **Corporate Login**
- Email or phone-based authentication
- Account status validation (pending/active/suspended/rejected)
- Account lockout after 5 failed attempts (30 minutes)
- Activity logging for security audit trail
- JWT token-based authentication via Laravel Sanctum

### 3. **Admin Approval Workflow**
- View all corporate accounts with filtering
- Review pending registrations
- Approve/reject accounts with notes
- Set credit limits and payment terms
- Suspend/reactivate accounts
- Update credit terms for approved accounts

### 4. **Corporate Profile Management**
- View complete profile with company details
- Update contact information
- Track activity history
- Secure logout with token revocation

---

## API Endpoints

### **Public Endpoints (No Authentication)**

#### 1. Corporate Registration
```http
POST /api/corporate-register
Content-Type: application/json

{
  "email": "john.doe@company.com",
  "password": "SecurePass123",
  "conf_password": "SecurePass123",
  "phone": "01712345678",
  "phone_country_code": "+880",
  
  "company_legal_name": "ABC Trading Ltd",
  "trade_license_number": "TL-123456",
  "vat_registration_number": "VAT-789012",
  "incorporation_date": "2020-01-15",
  
  "primary_contact_name": "John Doe",
  "primary_contact_email": "john.doe@company.com",
  "primary_contact_phone": "+880171234567",
  
  "industry": "Textile & Apparel",
  "employee_count": 50,
  "annual_revenue": 50000000,
  
  "credit_limit": 0,
  "payment_terms": "prepaid"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Corporate account registered successfully. Your account is pending admin approval.",
  "data": {
    "user": {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "email": "john.doe@company.com",
      "phone": "01712345678",
      "company_name": "ABC Trading Ltd",
      "user_type": "corporate",
      "status": "pending"
    },
    "corporate_profile": {
      "company_legal_name": "ABC Trading Ltd",
      "primary_contact_name": "John Doe",
      "primary_contact_email": "john.doe@company.com",
      "trade_license_number": "TL-123456",
      "payment_terms": "prepaid",
      "credit_limit": "0.00"
    },
    "authorization": {
      "token": "1|abc123xyz...",
      "type": "bearer"
    }
  }
}
```

#### 2. Corporate Login
```http
POST /api/corporate-login
Content-Type: application/json

{
  "email": "john.doe@company.com",
  "password": "SecurePass123"
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
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "email": "john.doe@company.com",
      "phone": "01712345678",
      "company_name": "ABC Trading Ltd",
      "user_type": "corporate",
      "status": "active",
      "avatar": null
    },
    "corporate_profile": {
      "company_legal_name": "ABC Trading Ltd",
      "primary_contact_name": "John Doe",
      "primary_contact_email": "john.doe@company.com",
      "primary_contact_phone": "+880171234567",
      "trade_license_number": "TL-123456",
      "vat_registration_number": "VAT-789012",
      "industry": "Textile & Apparel",
      "employee_count": 50,
      "credit_limit": "100000.00",
      "payment_terms": "net_30"
    },
    "authorization": {
      "token": "2|def456uvw...",
      "type": "bearer"
    },
    "roles": ["corporate"],
    "permissions": []
  }
}
```

**Error Responses:**

- **403 Pending Approval:**
```json
{
  "success": false,
  "message": "Your corporate account is pending admin approval. You will be notified once approved."
}
```

- **423 Account Locked:**
```json
{
  "success": false,
  "message": "Your account has been temporarily locked due to multiple failed login attempts. Please try again later."
}
```

---

### **Protected Endpoints (Requires Authentication)**

**Authentication Header:**
```http
Authorization: Bearer {token}
```

#### 3. Get Corporate Profile
```http
GET /api/corporate-profile
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "email": "john.doe@company.com",
      "phone": "01712345678",
      "phone_country_code": "+880",
      "company_name": "ABC Trading Ltd",
      "user_type": "corporate",
      "status": "active",
      "avatar": null,
      "last_login_at": "2025-12-27T10:30:00.000000Z"
    },
    "corporate_profile": {
      "id": 1,
      "user_id": 1,
      "company_legal_name": "ABC Trading Ltd",
      "trade_license_number": "TL-123456",
      "vat_registration_number": "VAT-789012",
      "incorporation_date": "2020-01-15",
      "primary_contact_name": "John Doe",
      "primary_contact_email": "john.doe@company.com",
      "primary_contact_phone": "+880171234567",
      "industry": "Textile & Apparel",
      "employee_count": 50,
      "annual_revenue": 50000000,
      "credit_limit": "100000.00",
      "payment_terms": "net_30"
    },
    "addresses": []
  }
}
```

#### 4. Update Corporate Profile
```http
PUT /api/corporate-profile
Authorization: Bearer {token}
Content-Type: application/json

{
  "phone": "01798765432",
  "primary_contact_name": "Jane Doe",
  "primary_contact_email": "jane.doe@company.com",
  "primary_contact_phone": "+880179876543",
  "industry": "Textile Manufacturing",
  "employee_count": 75,
  "annual_revenue": 75000000
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "user": { /* updated user */ },
    "corporate_profile": { /* updated profile */ }
  }
}
```

#### 5. Corporate Logout
```http
POST /api/corporate-logout
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Successfully logged out"
}
```

---

### **Admin Endpoints (Requires Admin Authentication)**

**Authentication Header:**
```http
Authorization: Bearer {admin_token}
```

#### 6. Get All Corporate Accounts
```http
GET /api/corporate?status=pending&search=ABC&per_page=20
Authorization: Bearer {admin_token}
```

**Query Parameters:**
- `status` (optional): Filter by status (pending/active/suspended/rejected)
- `search` (optional): Search by company name, email, or trade license
- `per_page` (optional): Items per page (default: 15)

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "email": "john.doe@company.com",
        "company_name": "ABC Trading Ltd",
        "user_type": "corporate",
        "status": "pending",
        "created_at": "2025-12-27T09:00:00.000000Z",
        "corporate_profile": { /* profile data */ }
      }
    ],
    "per_page": 20,
    "total": 5
  }
}
```

#### 7. Get Pending Registrations
```http
GET /api/corporate/pending
Authorization: Bearer {admin_token}
```

#### 8. Get Corporate Account Details
```http
GET /api/corporate/{id}
Authorization: Bearer {admin_token}
```

#### 9. Approve Corporate Account
```http
POST /api/corporate/{id}/approve
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "credit_limit": 100000,
  "payment_terms": "net_30",
  "admin_notes": "Verified trade license and references"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Corporate account approved successfully",
  "data": {
    "user": { /* updated user with status: active */ },
    "corporate_profile": { /* updated profile with credit terms */ }
  }
}
```

#### 10. Reject Corporate Account
```http
POST /api/corporate/{id}/reject
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "rejection_reason": "Unable to verify trade license"
}
```

#### 11. Suspend Corporate Account
```http
POST /api/corporate/{id}/suspend
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "suspension_reason": "Payment overdue by 90 days"
}
```

#### 12. Reactivate Corporate Account
```http
POST /api/corporate/{id}/reactivate
Authorization: Bearer {admin_token}
```

#### 13. Update Credit Terms
```http
PUT /api/corporate/{id}/credit-terms
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "credit_limit": 150000,
  "payment_terms": "net_45"
}
```

---

## Database Structure

### Users Table
```sql
- user_type: ENUM('customer', 'vendor', 'admin', 'corporate')
- status: ENUM('active', 'inactive', 'suspended', 'pending_verification', 'pending', 'rejected')
- company_name: Company legal name
- tax_id: VAT/Tax registration number
- business_type: Industry/business type
```

### Corporate Profiles Table
```sql
- user_id: Foreign key to users table (one-to-one)
- company_legal_name: Official registered name
- trade_license_number: Trade license number
- vat_registration_number: VAT registration
- incorporation_date: Date of incorporation
- primary_contact_name: Main contact person
- primary_contact_email: Contact email
- primary_contact_phone: Contact phone
- industry: Business industry
- employee_count: Number of employees
- annual_revenue: Annual revenue (integer)
- credit_limit: Maximum credit allowed (decimal)
- payment_terms: ENUM('net_15', 'net_30', 'net_45', 'net_60', 'prepaid')
```

---

## Security Features

### 1. Password Requirements
- Minimum 8 characters
- Must contain uppercase letter
- Must contain lowercase letter
- Must contain number
- Regex: `/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/`

### 2. Account Protection
- Account lockout after 5 failed login attempts
- 30-minute lockout duration
- Failed attempt tracking in database

### 3. Status Validation
- Pending accounts cannot login (must be approved)
- Suspended accounts cannot login
- Rejected accounts cannot login
- Only active accounts have full access

### 4. Activity Logging
All actions are logged in `user_activity_logs` table:
- Registration
- Login attempts (success/failure)
- Profile updates
- Admin actions (approve/reject/suspend)
- Logout

### 5. Token Management
- Laravel Sanctum for token-based auth
- Token revocation on logout
- Multiple device support

---

## Workflow

### Registration Flow
1. Corporate user submits registration form
2. System validates all required fields
3. User account created with `status = pending`
4. Corporate profile created with company details
5. `corporate` role assigned (Spatie Permissions)
6. Activity logged
7. Token generated (but login restricted until approved)
8. Admin notification sent (TODO)

### Approval Flow
1. Admin reviews pending corporate accounts
2. Admin verifies trade license and documents
3. Admin sets credit limit and payment terms
4. Admin approves account (status changed to `active`)
5. Activity logged
6. Approval email sent to corporate user (TODO)
7. Corporate user can now login and place orders

### Login Flow
1. User submits credentials (email/phone + password)
2. System verifies user type is `corporate`
3. System checks password
4. System validates account status
5. System checks if account is locked
6. Login recorded with IP and user agent
7. Token generated and returned

---

## Frontend Integration Guide

### Registration Form Fields

**User Account:**
- Email (required, unique)
- Phone (required, 11 digits, unique)
- Password (required, min 8 chars, strength validation)
- Confirm Password (required, must match)

**Company Information:**
- Company Legal Name (required)
- Trade License Number (optional, unique if provided)
- VAT Registration Number (optional, unique if provided)
- Incorporation Date (optional, must be past date)

**Primary Contact:**
- Contact Name (required)
- Contact Email (required)
- Contact Phone (required)

**Business Details:**
- Industry (optional dropdown)
- Employee Count (optional number)
- Annual Revenue (optional number)

### Login Form
- Email/Phone (single input)
- Password
- Remember Me (optional)

### Admin Dashboard Features
- Pending approvals list with company details
- Search/filter corporate accounts
- Approve/reject actions with notes
- Credit limit management
- Account suspension/reactivation
- Activity history view

---

## Best Practices Implemented

### 1. **Separation of Concerns**
- Dedicated controller for corporate auth (`CorporateAuthController`)
- Separate admin management controller (`CorporateManagementController`)
- Clear route grouping

### 2. **Industry Standards**
- Multi-step approval workflow (common in B2B)
- Credit management system
- Payment terms flexibility
- Trade license validation
- Activity audit trail

### 3. **Security First**
- Strong password requirements
- Account lockout mechanism
- Activity logging for compliance
- Token-based authentication
- Status-based access control

### 4. **Scalability**
- Proper database relationships (one-to-one)
- Role-based access control (Spatie Permissions)
- Extensible profile structure
- Transaction safety (DB::beginTransaction)

### 5. **User Experience**
- Clear error messages
- Proper status feedback (pending/approved/rejected)
- Activity history for transparency
- Flexible search and filtering

---

## TODO - Future Enhancements

1. **Email Notifications**
   - Registration confirmation
   - Approval notification
   - Rejection notification with reason
   - Suspension alert
   - Credit limit changes

2. **Document Upload**
   - Trade license document
   - VAT certificate
   - Incorporation certificate
   - Director identification

3. **Credit Management**
   - Credit usage tracking
   - Credit limit warnings
   - Payment history
   - Invoice generation

4. **Advanced Features**
   - Sub-users for corporate accounts
   - Department-wise access control
   - Bulk ordering capabilities
   - Custom pricing tiers
   - Contract management

5. **Reporting**
   - Corporate customer analytics
   - Credit utilization reports
   - Payment trend analysis
   - Order history reports

---

## Testing

### Test Corporate Registration
```bash
curl -X POST http://localhost:8000/api/corporate-register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@company.com",
    "password": "TestPass123",
    "conf_password": "TestPass123",
    "phone": "01712345678",
    "company_legal_name": "Test Company Ltd",
    "primary_contact_name": "Test User",
    "primary_contact_email": "test@company.com",
    "primary_contact_phone": "+880171234567",
    "industry": "Testing"
  }'
```

### Test Corporate Login (Before Approval)
```bash
curl -X POST http://localhost:8000/api/corporate-login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@company.com",
    "password": "TestPass123"
  }'
```
Expected: 403 error "Your corporate account is pending admin approval"

### Test Admin Approval
```bash
curl -X POST http://localhost:8000/api/corporate/1/approve \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {admin_token}" \
  -d '{
    "credit_limit": 50000,
    "payment_terms": "net_30",
    "admin_notes": "Test approval"
  }'
```

### Test Corporate Login (After Approval)
```bash
curl -X POST http://localhost:8000/api/corporate-login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@company.com",
    "password": "TestPass123"
  }'
```
Expected: 200 success with token and profile data

---

## Support

For issues or questions about the corporate authentication system:
1. Check activity logs in `user_activity_logs` table
2. Review Laravel logs in `storage/logs/laravel.log`
3. Verify database relationships and migrations
4. Test with debug mode enabled for detailed error messages
