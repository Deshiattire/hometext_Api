-- =====================================================
-- MANUAL SQL SCRIPT FOR NEW TABLES
-- Run this after the users table upgrade
-- =====================================================

-- 1. User Addresses Table
CREATE TABLE IF NOT EXISTS `user_addresses` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `address_type` ENUM('shipping', 'billing', 'both') NOT NULL DEFAULT 'both',
    `label` VARCHAR(50) NULL COMMENT 'Home, Office, etc.',
    `is_default` BOOLEAN DEFAULT FALSE,
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

-- 2. Vendor Profiles Table
CREATE TABLE IF NOT EXISTS `vendor_profiles` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED UNIQUE NOT NULL,
    `store_name` VARCHAR(255) NOT NULL,
    `store_slug` VARCHAR(255) UNIQUE NOT NULL,
    `store_logo` VARCHAR(255) NULL,
    `store_banner` VARCHAR(255) NULL,
    `store_description` TEXT NULL,
    `business_license` VARCHAR(100) NULL,
    `tax_certificate` VARCHAR(255) NULL,
    `is_verified` BOOLEAN DEFAULT FALSE,
    `verified_at` TIMESTAMP NULL,
    `rating` DECIMAL(3, 2) DEFAULT 0.00 COMMENT 'Average rating 0-5',
    `total_reviews` INT UNSIGNED DEFAULT 0,
    `total_sales` INT UNSIGNED DEFAULT 0,
    `total_products` INT UNSIGNED DEFAULT 0,
    `bank_name` VARCHAR(255) NULL,
    `account_number` VARCHAR(255) NULL,
    `account_holder_name` VARCHAR(255) NULL,
    `routing_number` VARCHAR(50) NULL,
    `commission_rate` DECIMAL(5, 2) DEFAULT 10.00 COMMENT 'Platform commission %',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_vendor_slug` (`store_slug`),
    INDEX `idx_vendor_verified` (`is_verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Corporate Profiles Table
CREATE TABLE IF NOT EXISTS `corporate_profiles` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED UNIQUE NOT NULL,
    `company_legal_name` VARCHAR(255) NOT NULL,
    `trade_license_number` VARCHAR(100) NULL,
    `vat_registration_number` VARCHAR(50) NULL,
    `incorporation_date` DATE NULL,
    `primary_contact_name` VARCHAR(255) NOT NULL,
    `primary_contact_email` VARCHAR(255) NOT NULL,
    `primary_contact_phone` VARCHAR(20) NOT NULL,
    `industry` VARCHAR(100) NULL,
    `employee_count` INT NULL,
    `annual_revenue` BIGINT NULL,
    `credit_limit` DECIMAL(15, 2) DEFAULT 0.00,
    `payment_terms` ENUM('net_15', 'net_30', 'net_45', 'net_60', 'prepaid') DEFAULT 'prepaid',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. User Shop Access Table
CREATE TABLE IF NOT EXISTS `user_shop_access` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `shop_id` BIGINT UNSIGNED NOT NULL,
    `role` ENUM('owner', 'manager', 'staff', 'viewer') NOT NULL DEFAULT 'staff',
    `is_primary` BOOLEAN DEFAULT FALSE COMMENT 'Primary shop for multi-shop users',
    `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `revoked_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`shop_id`) REFERENCES `shops`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `user_shop_unique` (`user_id`, `shop_id`),
    INDEX `idx_user_shop_access_shop_id` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Social Logins Table
CREATE TABLE IF NOT EXISTS `social_logins` (
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

-- 6. User Activity Logs Table
CREATE TABLE IF NOT EXISTS `user_activity_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL COMMENT 'login, logout, order_placed, etc.',
    `description` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `metadata` JSON NULL COMMENT 'Additional context',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_activity_user_id` (`user_id`),
    INDEX `idx_user_activity_action` (`action`),
    INDEX `idx_user_activity_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- END OF NEW TABLES CREATION
-- =====================================================


