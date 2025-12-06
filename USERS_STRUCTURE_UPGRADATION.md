### **Current Users Table:**

```sql
id              bigint(20)
first_name      varchar(765)
last_name       varchar(765)
email           varchar(765)
phone           varchar(765)
email_verified_at timestamp
password        varchar(765)
salt            varchar(27)      ⚠️ REMOVE
photo           varchar(765)
role_id         int(11)          ⚠️ REPLACE
shop_id         bigint(20)       ⚠️ RECONSIDER
remember_token  varchar(300)
created_at      timestamp
updated_at      timestamp
```


### **Issues Identified:**

1. **❌ Manual Salt Column** - Laravel's bcrypt/argon2 handles salt automatically[^5][^6]
2. **❌ Single Role ID** - Doesn't support multiple roles (admin + vendor)[^7][^8]
3. **❌ Direct shop_id** - Poor multi-tenancy design[^9][^3][^10]
4. **❌ Oversized varchar(765)** - Industry standard: 255 max for most fields[^4][^1]
5. **❌ Missing Essential Fields** - No status, timezone, preferences, last_login[^11][^1]

***

## **Recommended Industry-Standard Users Table**

### **Option 1: Single Users Table (Recommended for Your Case)**

This design supports customers, admins, vendors, and corporate users using Spatie Permission for RBAC:[^3][^1][^7]

```sql
CREATE TABLE `users` (
  -- Primary Identity
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid` CHAR(36) UNIQUE NOT NULL COMMENT 'Public identifier for APIs',
  
  -- Authentication
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `remember_token` VARCHAR(100) NULL,
  
  -- Personal Information
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NULL,
  `phone_country_code` VARCHAR(5) NULL DEFAULT '+880',
  `phone_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `date_of_birth` DATE NULL,
  `gender` ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL,
  
  -- Profile
  `avatar` VARCHAR(255) NULL COMMENT 'Profile photo URL',
  `bio` TEXT NULL,
  
  -- User Type & Status
  `user_type` ENUM('customer', 'vendor', 'admin', 'corporate') NOT NULL DEFAULT 'customer',
  `status` ENUM('active', 'inactive', 'suspended', 'pending_verification') NOT NULL DEFAULT 'active',
  
  -- Preferences
  `locale` VARCHAR(10) DEFAULT 'en' COMMENT 'Language preference',
  `timezone` VARCHAR(50) DEFAULT 'Asia/Dhaka',
  `currency` VARCHAR(3) DEFAULT 'BDT',
  `notification_preferences` JSON NULL COMMENT 'Email, SMS, push settings',
  
  -- Security & Activity
  `last_login_at` TIMESTAMP NULL,
  `last_login_ip` VARCHAR(45) NULL COMMENT 'Support IPv6',
  `login_count` INT UNSIGNED DEFAULT 0,
  `failed_login_attempts` TINYINT UNSIGNED DEFAULT 0,
  `locked_until` TIMESTAMP NULL COMMENT 'Account lockout',
  `password_changed_at` TIMESTAMP NULL,
  `two_factor_secret` TEXT NULL,
  `two_factor_recovery_codes` TEXT NULL,
  `two_factor_confirmed_at` TIMESTAMP NULL,
  
  -- Vendor/Corporate Specific (nullable for regular customers)
  `company_name` VARCHAR(255) NULL,
  `tax_id` VARCHAR(50) NULL COMMENT 'VAT/Tax registration number',
  `business_type` ENUM('individual', 'company', 'partnership', 'corporation') NULL,
  
  -- Soft Delete
  `deleted_at` TIMESTAMP NULL,
  
  -- Timestamps
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_email` (`email`),
  INDEX `idx_phone` (`phone`),
  INDEX `idx_user_type` (`user_type`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```


***

## **Supporting Tables Architecture**

### **1. Spatie Permission Tables (RBAC)**[^8][^7]

These are auto-generated when you install `spatie/laravel-permission`:

```sql
-- Roles: super-admin, admin, vendor, customer, support
CREATE TABLE `roles` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `guard_name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `roles_name_guard_name_unique` (`name`, `guard_name`)
);

-- Permissions: view products, manage orders, etc.
CREATE TABLE `permissions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `guard_name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`, `guard_name`)
);

-- User-Role mapping (many-to-many)
CREATE TABLE `model_has_roles` (
  `role_id` BIGINT UNSIGNED NOT NULL,
  `model_type` VARCHAR(255) NOT NULL,
  `model_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `model_id`, `model_type`),
  INDEX `model_has_roles_model_id_model_type_index` (`model_id`, `model_type`)
);

-- Role-Permission mapping
CREATE TABLE `role_has_permissions` (
  `permission_id` BIGINT UNSIGNED NOT NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`, `role_id`)
);

-- Direct user permissions (optional)
CREATE TABLE `model_has_permissions` (
  `permission_id` BIGINT UNSIGNED NOT NULL,
  `model_type` VARCHAR(255) NOT NULL,
  `model_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`, `model_id`, `model_type`)
);
```


### **2. User Addresses Table**[^1][^4]

Customers and vendors can have multiple addresses:

```sql
CREATE TABLE `user_addresses` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `address_type` ENUM('shipping', 'billing', 'both') NOT NULL DEFAULT 'both',
  `label` VARCHAR(50) NULL COMMENT 'Home, Office, etc.',
  `is_default` BOOLEAN DEFAULT FALSE,
  
  -- Address Fields
  `full_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address_line_1` VARCHAR(255) NOT NULL,
  `address_line_2` VARCHAR(255) NULL,
  `city` VARCHAR(100) NOT NULL,
  `state` VARCHAR(100) NULL,
  `postal_code` VARCHAR(20) NOT NULL,
  `country_code` VARCHAR(2) DEFAULT 'BD' COMMENT 'ISO 3166-1 alpha-2',
  `latitude` DECIMAL(10, 8) NULL,
  `longitude` DECIMAL(11, 8) NULL,
  
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_addresses_user_id` (`user_id`),
  INDEX `idx_user_addresses_default` (`user_id`, `is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```


### **3. Vendor Profiles Table**[^12][^1]

Extended information for vendor-type users:

```sql
CREATE TABLE `vendor_profiles` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED UNIQUE NOT NULL,
  
  -- Business Information
  `store_name` VARCHAR(255) NOT NULL,
  `store_slug` VARCHAR(255) UNIQUE NOT NULL,
  `store_logo` VARCHAR(255) NULL,
  `store_banner` VARCHAR(255) NULL,
  `store_description` TEXT NULL,
  
  -- Legal & Verification
  `business_license` VARCHAR(100) NULL,
  `tax_certificate` VARCHAR(255) NULL,
  `is_verified` BOOLEAN DEFAULT FALSE,
  `verified_at` TIMESTAMP NULL,
  
  -- Performance Metrics
  `rating` DECIMAL(3, 2) DEFAULT 0.00 COMMENT 'Average rating 0-5',
  `total_reviews` INT UNSIGNED DEFAULT 0,
  `total_sales` INT UNSIGNED DEFAULT 0,
  `total_products` INT UNSIGNED DEFAULT 0,
  
  -- Bank Details (encrypted)
  `bank_name` VARCHAR(255) NULL,
  `account_number` VARCHAR(255) NULL,
  `account_holder_name` VARCHAR(255) NULL,
  `routing_number` VARCHAR(50) NULL,
  
  -- Commission
  `commission_rate` DECIMAL(5, 2) DEFAULT 10.00 COMMENT 'Platform commission %',
  
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_vendor_slug` (`store_slug`),
  INDEX `idx_vendor_verified` (`is_verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```


### **4. Corporate Profiles Table**[^13][^3]

For B2B corporate customers:

```sql
CREATE TABLE `corporate_profiles` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED UNIQUE NOT NULL,
  
  -- Company Information
  `company_legal_name` VARCHAR(255) NOT NULL,
  `trade_license_number` VARCHAR(100) NULL,
  `vat_registration_number` VARCHAR(50) NULL,
  `incorporation_date` DATE NULL,
  
  -- Contact Person
  `primary_contact_name` VARCHAR(255) NOT NULL,
  `primary_contact_email` VARCHAR(255) NOT NULL,
  `primary_contact_phone` VARCHAR(20) NOT NULL,
  
  -- Business Details
  `industry` VARCHAR(100) NULL,
  `employee_count` INT NULL,
  `annual_revenue` BIGINT NULL,
  
  -- Credit & Payment Terms
  `credit_limit` DECIMAL(15, 2) DEFAULT 0.00,
  `payment_terms` ENUM('net_15', 'net_30', 'net_45', 'net_60', 'prepaid') DEFAULT 'prepaid',
  
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```


### **5. User Social Logins**[^6][^5]

OAuth provider integration:

```sql
CREATE TABLE `social_logins` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `provider` VARCHAR(50) NOT NULL COMMENT 'google, facebook, apple',
  `provider_id` VARCHAR(255) NOT NULL COMMENT 'OAuth user ID',
  `provider_token` TEXT NULL,
  `provider_refresh_token` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `social_logins_provider_provider_id_unique` (`provider`, `provider_id`),
  INDEX `idx_social_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```


### **6. Activity Logs (Optional but Recommended)**[^11]

Track user actions for security and analytics:

```sql
CREATE TABLE `user_activity_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL COMMENT 'login, logout, order_placed, etc.',
  `description` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(500) NULL,
  `metadata` JSON NULL COMMENT 'Additional context',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_user_activity_user_id` (`user_id`),
  INDEX `idx_user_activity_action` (`action`),
  INDEX `idx_user_activity_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```


***

## **Multi-Tenancy Approach for Shops**[^10][^9][^3]

### **Remove Direct `shop_id` from Users Table**

Instead, use a pivot table for flexibility:

```sql
CREATE TABLE `user_shop_access` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `shop_id` BIGINT UNSIGNED NOT NULL,
  `role` ENUM('owner', 'manager', 'staff', 'viewer') NOT NULL DEFAULT 'staff',
  `is_primary` BOOLEAN DEFAULT FALSE COMMENT 'Primary shop for multi-shop users',
  `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` TIMESTAMP NULL,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`shop_id`) REFERENCES `shops`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_shop_unique` (`user_id`, `shop_id`),
  INDEX `idx_user_shop_access_shop_id` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Why?**[^9][^3]

- Vendors can manage multiple shops
- Admins can access all shops
- Staff members can work at different locations
- Better multi-tenancy support

***

## **Key Design Principles Applied**[^2][^1][^11]

### **1. Normalization**

- Separated addresses, vendor profiles, corporate profiles
- Avoids nullable columns for specific user types
- Reduces data redundancy[^14][^2]


### **2. Security**

- Removed manual salt (Laravel handles it)
- Added account lockout mechanism
- Two-factor authentication support
- Activity logging for audit trails[^15][^16]


### **3. Scalability**

- UUID for public-facing APIs
- Proper indexing on frequently queried columns
- JSON fields for flexible preferences
- Soft deletes for data retention[^1][^11]


### **4. User Experience**

- Multiple addresses per user
- Localization support (locale, timezone, currency)
- Social login integration
- Notification preferences[^4][^1]


### **5. Business Requirements**

- Vendor verification workflow
- Corporate credit limits
- Commission tracking
- Performance metrics[^12]

***

## **Field Size Justification**

| Field | Your Size | Recommended | Why |
| :-- | :-- | :-- | :-- |
| `first_name` | varchar(765) | varchar(100) | Names rarely exceed 50 chars [^4] |
| `last_name` | varchar(765) | varchar(100) | Same as above |
| `email` | varchar(765) | varchar(255) | RFC 5321 limit is 254 chars [^1] |
| `phone` | varchar(765) | varchar(20) | E.164 format max 15 digits + formatting [^4] |
| `password` | varchar(765) | varchar(255) | Bcrypt outputs 60 chars, 255 for future-proofing [^5] |
| `photo` | varchar(765) | varchar(255) | Standard URL length limit |

**Impact:** Reducing field sizes saves ~50% storage per user[^2][^14]

***

## **Laravel Migration Example**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            // Primary Identity
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Authentication
            $table->string('email', 255)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->rememberToken();
            
            // Personal Information
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 20)->nullable();
            $table->string('phone_country_code', 5)->default('+880');
            $table->timestamp('phone_verified_at')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            
            // Profile
            $table->string('avatar', 255)->nullable();
            $table->text('bio')->nullable();
            
            // User Type & Status
            $table->enum('user_type', ['customer', 'vendor', 'admin', 'corporate'])->default('customer');
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending_verification'])->default('active');
            
            // Preferences
            $table->string('locale', 10)->default('en');
            $table->string('timezone', 50)->default('Asia/Dhaka');
            $table->string('currency', 3)->default('BDT');
            $table->json('notification_preferences')->nullable();
            
            // Security & Activity
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->unsignedInteger('login_count')->default(0);
            $table->unsignedTinyInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            
            // Vendor/Corporate Specific
            $table->string('company_name', 255)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->enum('business_type', ['individual', 'company', 'partnership', 'corporation'])->nullable();
            
            // Soft Delete & Timestamps
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index(['email']);
            $table->index(['phone']);
            $table->index(['user_type']);
            $table->index(['status']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
```


***

## **Summary: What Changed**

| Category | Your Structure | Recommended Structure |
| :-- | :-- | :-- |
| **Salt** | Manual `salt` column | ❌ Removed (Laravel handles) |
| **Roles** | Single `role_id` | ✅ Spatie Permission (many-to-many) |
| **Shops** | Direct `shop_id` | ✅ Pivot table `user_shop_access` |
| **Field Sizes** | varchar(765) everywhere | ✅ Optimized: 100-255 based on usage |
| **User Types** | Not clearly defined | ✅ `user_type` ENUM + profile tables |
| **Verification** | Only email | ✅ Email + phone + vendor verification |
| **Security** | Basic | ✅ 2FA, account lockout, activity logs |
| **Addresses** | Not handled | ✅ Separate `user_addresses` table |
| **Preferences** | Not supported | ✅ Locale, timezone, currency, notifications |
| **Status** | Not tracked | ✅ Active/inactive/suspended states |
| **Soft Deletes** | Not implemented | ✅ `deleted_at` for data retention |

This structure follows industry standards from platforms like Shopify, WooCommerce, and Magento while remaining lean and practical for your e-commerce needs.[^2][^4][^1]
<span style="display:none">[^17][^18][^19][^20][^21][^22][^23][^24][^25][^26]</span>

<div align="center">⁂</div>

[^1]: https://savvygents.com/blog/mastering-e-commerce-database-design-tips-and-best-practices/

[^2]: https://appmaster.io/blog/designing-an-e-commerce-database

[^3]: https://clerk.com/blog/how-to-design-multitenant-saas-architecture

[^4]: https://www.red-gate.com/blog/er-diagram-for-online-shop

[^5]: https://laravel.com/docs/12.x/authentication

[^6]: https://frontegg.com/blog/laravel-authentication

[^7]: https://devpishon.hashnode.dev/streamline-role-based-access-control-with-spatie-laravel-permission

[^8]: https://www.sevensquaretech.com/multi-role-permission-system-laravel-with-code/

[^9]: https://docs.singlestore.com/db/v9.0/developer-resources/designing-for-multi-tenant-applications/

[^10]: https://www.descope.com/learn/post/multi-tenancy

[^11]: https://www.fuzen.io/posts/e-commerce-database-design-best-practices

[^12]: https://www.linkedin.com/pulse/designing-robust-database-schema-e-commerce-shirsh-sinha-7orsc

[^13]: https://www.aserto.com/blog/building-dynamic-multitenant-rbac-custom-roles

[^14]: https://www.geeksforgeeks.org/dbms/how-to-design-a-relational-database-for-e-commerce-website/

[^15]: https://orbitwebtech.com/laravel-authentication/

[^16]: https://dev.to/sharifcse58/15-laravel-security-best-practices-in-2025-2lco

[^17]: users.sql

[^18]: https://stackoverflow.com/questions/37825335/integrate-authentication-to-existing-laravel-project-and-users-database

[^19]: https://aws.amazon.com/blogs/database/amazon-dynamodb-data-modeling-for-multi-tenancy-part-1/

[^20]: https://laravel.com

[^21]: https://learn.microsoft.com/en-us/azure/azure-sql/database/saas-tenancy-app-design-patterns?view=azuresql

[^22]: https://laravel.io/forum/customized-user-database

[^23]: https://www.reportserver.net/de/dokumentation/tutorials/articles/setting-up-a-multi-tenant-system

[^24]: https://www.youtube.com/watch?v=3JBmbQsR0ag

[^25]: https://www.youtube.com/watch?v=1HamqOuv2Cw

[^26]: https://www.geeksforgeeks.org/system-design/multi-tenancy-architecture-system-design/

