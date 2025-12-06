-- =====================================================
-- DATA MIGRATION SCRIPT
-- Run this after creating the new tables
-- =====================================================

-- Migrate existing shop_id to user_shop_access table
-- This assumes users had a shop_id column before migration
-- Only run if you had shop_id data you want to preserve

-- First, check if old shop_id data exists in a backup or if you need to recreate it
-- This is a template - adjust based on your actual data

-- Example: If you have users with shop_id = 1, create access record
-- INSERT INTO `user_shop_access` (`user_id`, `shop_id`, `role`, `is_primary`, `granted_at`)
-- SELECT `id`, `shop_id`, 'owner', TRUE, NOW()
-- FROM `users_backup`  -- Replace with your backup table name if you have one
-- WHERE `shop_id` IS NOT NULL;

-- Set default user_type for existing users based on their old role_id
-- Adjust the role_id mappings based on your old system:
-- 1 = admin, 2 = sales manager, 3 = customer

UPDATE `users` 
SET `user_type` = CASE 
    WHEN `id` IN (
        -- Add your admin user IDs here if you know them
        -- Or use a different method to identify admins
        SELECT `id` FROM `users` WHERE `id` IN (1, 2, 3)  -- Example IDs
    ) THEN 'admin'
    WHEN `id` IN (
        -- Add your vendor user IDs here
        SELECT `id` FROM `users` WHERE `id` IN (4, 5, 6)  -- Example IDs
    ) THEN 'vendor'
    ELSE 'customer'
END
WHERE `user_type` = 'customer' OR `user_type` IS NULL;

-- Set status to active for all existing users
UPDATE `users` 
SET `status` = 'active' 
WHERE `status` IS NULL;

-- =====================================================
-- END OF DATA MIGRATION
-- =====================================================


