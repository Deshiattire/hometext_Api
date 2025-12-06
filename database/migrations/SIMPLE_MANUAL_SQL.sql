-- =====================================================
-- SIMPLIFIED MANUAL SQL UPGRADE (Works on all MySQL versions)
-- Run this in your MySQL database directly
-- =====================================================

-- IMPORTANT: Run these one section at a time and check for errors
-- If a column already exists, skip that ALTER TABLE statement

-- =====================================================
-- PART 1: DROP OLD COLUMNS
-- =====================================================
-- Uncomment and run these if the columns exist:
-- ALTER TABLE `users` DROP COLUMN `salt`;
-- ALTER TABLE `users` DROP COLUMN `role_id`;
-- ALTER TABLE `users` DROP COLUMN `shop_id`;

-- =====================================================
-- PART 2: HANDLE NAME TO FIRST_NAME/LAST_NAME
-- =====================================================
-- Only run this if you have a 'name' column and no 'first_name' column:
-- ALTER TABLE `users` ADD COLUMN `first_name` VARCHAR(100) NULL AFTER `id`;
-- ALTER TABLE `users` ADD COLUMN `last_name` VARCHAR(100) NULL AFTER `first_name`;
-- UPDATE `users` SET `first_name` = COALESCE(`name`, ''), `last_name` = '' WHERE `first_name` IS NULL;
-- ALTER TABLE `users` DROP COLUMN `name`;
-- ALTER TABLE `users` MODIFY `first_name` VARCHAR(100) NOT NULL;

-- If first_name doesn't exist at all:
-- ALTER TABLE `users` ADD COLUMN `first_name` VARCHAR(100) NOT NULL AFTER `id`;
-- ALTER TABLE `users` ADD COLUMN `last_name` VARCHAR(100) NULL AFTER `first_name`;

-- =====================================================
-- PART 3: RENAME PHOTO TO AVATAR
-- =====================================================
-- Only run this if you have 'photo' column and no 'avatar' column:
-- ALTER TABLE `users` CHANGE `photo` `avatar` VARCHAR(255) NULL;

-- =====================================================
-- PART 4: ADD UUID
-- =====================================================
ALTER TABLE `users` ADD COLUMN `uuid` CHAR(36) NULL AFTER `id`;
UPDATE `users` SET `uuid` = UUID() WHERE `uuid` IS NULL OR `uuid` = '';
ALTER TABLE `users` MODIFY `uuid` CHAR(36) NOT NULL;
ALTER TABLE `users` ADD UNIQUE KEY `users_uuid_unique` (`uuid`);

-- =====================================================
-- PART 5: UPDATE COLUMN SIZES
-- =====================================================
ALTER TABLE `users` 
    MODIFY `email` VARCHAR(255) NOT NULL,
    MODIFY `phone` VARCHAR(20) NULL,
    MODIFY `password` VARCHAR(255) NOT NULL;

-- If avatar exists:
-- ALTER TABLE `users` MODIFY `avatar` VARCHAR(255) NULL;

-- =====================================================
-- PART 6: ADD PERSONAL INFORMATION FIELDS
-- =====================================================
ALTER TABLE `users` 
    ADD COLUMN `phone_country_code` VARCHAR(5) NULL DEFAULT '+880' AFTER `phone`,
    ADD COLUMN `phone_verified_at` TIMESTAMP NULL AFTER `phone_country_code`,
    ADD COLUMN `date_of_birth` DATE NULL AFTER `phone_verified_at`,
    ADD COLUMN `gender` ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL AFTER `date_of_birth`;

-- =====================================================
-- PART 7: ADD PROFILE (BIO)
-- =====================================================
-- If avatar exists, add after avatar:
-- ALTER TABLE `users` ADD COLUMN `bio` TEXT NULL AFTER `avatar`;

-- If avatar doesn't exist, add after email_verified_at:
-- ALTER TABLE `users` ADD COLUMN `bio` TEXT NULL AFTER `email_verified_at`;

-- =====================================================
-- PART 8: ADD USER TYPE & STATUS
-- =====================================================
ALTER TABLE `users` 
    ADD COLUMN `user_type` ENUM('customer', 'vendor', 'admin', 'corporate') NOT NULL DEFAULT 'customer' AFTER `bio`,
    ADD COLUMN `status` ENUM('active', 'inactive', 'suspended', 'pending_verification') NOT NULL DEFAULT 'active' AFTER `user_type`;

-- Set existing users to active
UPDATE `users` SET `status` = 'active' WHERE `status` IS NULL;

-- =====================================================
-- PART 9: ADD PREFERENCES
-- =====================================================
ALTER TABLE `users` 
    ADD COLUMN `locale` VARCHAR(10) NOT NULL DEFAULT 'en' AFTER `status`,
    ADD COLUMN `timezone` VARCHAR(50) NOT NULL DEFAULT 'Asia/Dhaka' AFTER `locale`,
    ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT 'BDT' AFTER `timezone`,
    ADD COLUMN `notification_preferences` JSON NULL AFTER `currency`;

-- =====================================================
-- PART 10: ADD SECURITY & ACTIVITY FIELDS
-- =====================================================
ALTER TABLE `users` 
    ADD COLUMN `last_login_at` TIMESTAMP NULL AFTER `notification_preferences`,
    ADD COLUMN `last_login_ip` VARCHAR(45) NULL AFTER `last_login_at`,
    ADD COLUMN `login_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `last_login_ip`,
    ADD COLUMN `failed_login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `login_count`,
    ADD COLUMN `locked_until` TIMESTAMP NULL AFTER `failed_login_attempts`,
    ADD COLUMN `password_changed_at` TIMESTAMP NULL AFTER `locked_until`,
    ADD COLUMN `two_factor_secret` TEXT NULL AFTER `password_changed_at`,
    ADD COLUMN `two_factor_recovery_codes` TEXT NULL AFTER `two_factor_secret`,
    ADD COLUMN `two_factor_confirmed_at` TIMESTAMP NULL AFTER `two_factor_recovery_codes`;

-- =====================================================
-- PART 11: ADD VENDOR/CORPORATE FIELDS
-- =====================================================
ALTER TABLE `users` 
    ADD COLUMN `company_name` VARCHAR(255) NULL AFTER `two_factor_confirmed_at`,
    ADD COLUMN `tax_id` VARCHAR(50) NULL AFTER `company_name`,
    ADD COLUMN `business_type` ENUM('individual', 'company', 'partnership', 'corporation') NULL AFTER `tax_id`;

-- =====================================================
-- PART 12: ADD SOFT DELETE
-- =====================================================
ALTER TABLE `users` ADD COLUMN `deleted_at` TIMESTAMP NULL AFTER `updated_at`;

-- =====================================================
-- PART 13: ADD INDEXES
-- =====================================================
CREATE INDEX `users_phone_index` ON `users` (`phone`);
CREATE INDEX `users_user_type_index` ON `users` (`user_type`);
CREATE INDEX `users_status_index` ON `users` (`status`);
CREATE INDEX `users_created_at_index` ON `users` (`created_at`);
CREATE INDEX `users_deleted_at_index` ON `users` (`deleted_at`);

-- =====================================================
-- END OF USERS TABLE UPGRADE
-- =====================================================


