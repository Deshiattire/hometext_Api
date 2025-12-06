-- =====================================================
-- MANUAL SQL UPGRADE SCRIPT FOR USERS TABLE STRUCTURE
-- Run this in your MySQL database directly
-- =====================================================

-- Step 1: Drop old columns (if they exist)
ALTER TABLE `users` 
    DROP COLUMN IF EXISTS `salt`,
    DROP COLUMN IF EXISTS `role_id`,
    DROP COLUMN IF EXISTS `shop_id`;

-- Step 2: Handle name to first_name/last_name conversion
-- Check if name column exists and first_name doesn't
SET @name_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'name'
);

SET @first_name_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'first_name'
);

-- If name exists but first_name doesn't, migrate data
SET @sql = IF(@name_exists > 0 AND @first_name_exists = 0,
    'ALTER TABLE `users` 
     ADD COLUMN `first_name` VARCHAR(100) NULL AFTER `id`,
     ADD COLUMN `last_name` VARCHAR(100) NULL AFTER `first_name`;
     UPDATE `users` SET `first_name` = COALESCE(`name`, ""), `last_name` = "" WHERE `first_name` IS NULL;
     ALTER TABLE `users` DROP COLUMN `name`;',
    'SELECT "Name column migration not needed" AS message;'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 3: Ensure first_name and last_name exist
ALTER TABLE `users` 
    ADD COLUMN IF NOT EXISTS `first_name` VARCHAR(100) NOT NULL AFTER `id`,
    ADD COLUMN IF NOT EXISTS `last_name` VARCHAR(100) NULL AFTER `first_name`;

-- Step 4: Rename photo to avatar
SET @photo_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'photo'
);

SET @avatar_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'avatar'
);

SET @sql = IF(@photo_exists > 0 AND @avatar_exists = 0,
    'ALTER TABLE `users` CHANGE `photo` `avatar` VARCHAR(255) NULL;',
    'SELECT "Avatar column migration not needed" AS message;'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 5: Add UUID column
ALTER TABLE `users` 
    ADD COLUMN IF NOT EXISTS `uuid` CHAR(36) UNIQUE NULL AFTER `id`;

-- Generate UUIDs for existing users
UPDATE `users` 
SET `uuid` = UUID() 
WHERE `uuid` IS NULL OR `uuid` = '';

-- Make UUID NOT NULL after populating
ALTER TABLE `users` 
    MODIFY `uuid` CHAR(36) NOT NULL;

-- Step 6: Update existing column sizes
ALTER TABLE `users` 
    MODIFY `email` VARCHAR(255) NOT NULL,
    MODIFY `phone` VARCHAR(20) NULL,
    MODIFY `password` VARCHAR(255) NOT NULL;

-- Update avatar if it exists
SET @avatar_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'avatar'
);

SET @sql = IF(@avatar_exists > 0,
    'ALTER TABLE `users` MODIFY `avatar` VARCHAR(255) NULL;',
    'SELECT "Avatar column does not exist" AS message;'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 7: Add Personal Information fields
ALTER TABLE `users` 
    ADD COLUMN IF NOT EXISTS `phone_country_code` VARCHAR(5) NULL DEFAULT '+880' AFTER `phone`,
    ADD COLUMN IF NOT EXISTS `phone_verified_at` TIMESTAMP NULL AFTER `phone_country_code`,
    ADD COLUMN IF NOT EXISTS `date_of_birth` DATE NULL AFTER `phone_verified_at`,
    ADD COLUMN IF NOT EXISTS `gender` ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL AFTER `date_of_birth`;

-- Step 8: Add Profile (bio) - after avatar if it exists, otherwise after email_verified_at
SET @avatar_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'avatar'
);

SET @bio_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'bio'
);

SET @sql = IF(@bio_exists = 0,
    IF(@avatar_exists > 0,
        'ALTER TABLE `users` ADD COLUMN `bio` TEXT NULL AFTER `avatar`;',
        'ALTER TABLE `users` ADD COLUMN `bio` TEXT NULL AFTER `email_verified_at`;'
    ),
    'SELECT "Bio column already exists" AS message;'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 9: Add User Type & Status
ALTER TABLE `users` 
    ADD COLUMN IF NOT EXISTS `user_type` ENUM('customer', 'vendor', 'admin', 'corporate') NOT NULL DEFAULT 'customer' AFTER `bio`,
    ADD COLUMN IF NOT EXISTS `status` ENUM('active', 'inactive', 'suspended', 'pending_verification') NOT NULL DEFAULT 'active' AFTER `user_type`;

-- Step 10: Add Preferences
ALTER TABLE `users` 
    ADD COLUMN IF NOT EXISTS `locale` VARCHAR(10) NOT NULL DEFAULT 'en' AFTER `status`,
    ADD COLUMN IF NOT EXISTS `timezone` VARCHAR(50) NOT NULL DEFAULT 'Asia/Dhaka' AFTER `locale`,
    ADD COLUMN IF NOT EXISTS `currency` VARCHAR(3) NOT NULL DEFAULT 'BDT' AFTER `timezone`,
    ADD COLUMN IF NOT EXISTS `notification_preferences` JSON NULL AFTER `currency`;

-- Step 11: Add Security & Activity fields
ALTER TABLE `users` 
    ADD COLUMN IF NOT EXISTS `last_login_at` TIMESTAMP NULL AFTER `notification_preferences`,
    ADD COLUMN IF NOT EXISTS `last_login_ip` VARCHAR(45) NULL AFTER `last_login_at`,
    ADD COLUMN IF NOT EXISTS `login_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `last_login_ip`,
    ADD COLUMN IF NOT EXISTS `failed_login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `login_count`,
    ADD COLUMN IF NOT EXISTS `locked_until` TIMESTAMP NULL AFTER `failed_login_attempts`,
    ADD COLUMN IF NOT EXISTS `password_changed_at` TIMESTAMP NULL AFTER `locked_until`,
    ADD COLUMN IF NOT EXISTS `two_factor_secret` TEXT NULL AFTER `password_changed_at`,
    ADD COLUMN IF NOT EXISTS `two_factor_recovery_codes` TEXT NULL AFTER `two_factor_secret`,
    ADD COLUMN IF NOT EXISTS `two_factor_confirmed_at` TIMESTAMP NULL AFTER `two_factor_recovery_codes`;

-- Step 12: Add Vendor/Corporate Specific fields
ALTER TABLE `users` 
    ADD COLUMN IF NOT EXISTS `company_name` VARCHAR(255) NULL AFTER `two_factor_confirmed_at`,
    ADD COLUMN IF NOT EXISTS `tax_id` VARCHAR(50) NULL AFTER `company_name`,
    ADD COLUMN IF NOT EXISTS `business_type` ENUM('individual', 'company', 'partnership', 'corporation') NULL AFTER `tax_id`;

-- Step 13: Add Soft Delete
ALTER TABLE `users` 
    ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL AFTER `updated_at`;

-- Step 14: Add Indexes
CREATE INDEX IF NOT EXISTS `users_phone_index` ON `users` (`phone`);
CREATE INDEX IF NOT EXISTS `users_user_type_index` ON `users` (`user_type`);
CREATE INDEX IF NOT EXISTS `users_status_index` ON `users` (`status`);
CREATE INDEX IF NOT EXISTS `users_created_at_index` ON `users` (`created_at`);
CREATE INDEX IF NOT EXISTS `users_deleted_at_index` ON `users` (`deleted_at`);

-- =====================================================
-- END OF USERS TABLE UPGRADE
-- =====================================================


