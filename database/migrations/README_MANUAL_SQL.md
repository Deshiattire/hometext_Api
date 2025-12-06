# Manual SQL Migration Guide

Since Laravel migrations are having Doctrine DBAL dependency issues, you can run these SQL scripts directly in your database.

## Steps to Execute:

### 1. Backup Your Database First!
```sql
-- Create a backup before running any migrations
mysqldump -u your_username -p your_database_name > backup_before_migration.sql
```

### 2. Run the Users Table Upgrade
Execute the SQL in `MANUAL_SQL_UPGRADE.sql` in your MySQL database.

**Note:** Some MySQL versions don't support `IF EXISTS` or `IF NOT EXISTS` in `ALTER TABLE`. If you get errors, you may need to:
- Remove the `IF EXISTS` / `IF NOT EXISTS` clauses
- Or check column existence manually before running each statement

### 3. Run the New Tables Creation
Execute the SQL in `MANUAL_SQL_NEW_TABLES.sql` to create all the new tables.

### 4. Run Data Migration (Optional)
If you have existing data to migrate (like shop_id relationships), run `MANUAL_SQL_MIGRATE_DATA.sql`.

### 5. Mark Migrations as Complete in Laravel
After running the SQL manually, you need to tell Laravel that these migrations are done:

```bash
php artisan tinker
```

Then in tinker:
```php
DB::table('migrations')->insert([
    ['migration' => '2025_11_23_235211_upgrade_users_table_structure', 'batch' => 44],
    ['migration' => '2025_11_23_235218_create_user_addresses_table', 'batch' => 44],
    ['migration' => '2025_11_23_235225_create_vendor_profiles_table', 'batch' => 44],
    ['migration' => '2025_11_23_235231_create_corporate_profiles_table', 'batch' => 44],
    ['migration' => '2025_11_23_235239_create_user_shop_access_table', 'batch' => 44],
    ['migration' => '2025_11_23_235246_create_social_logins_table', 'batch' => 44],
    ['migration' => '2025_11_23_235252_create_user_activity_logs_table', 'batch' => 44],
]);
```

Or use a simpler approach - just delete/comment out the problematic migration files and Laravel will skip them.

## Alternative: Simplified SQL (if you get errors)

If you encounter issues with `IF EXISTS` or `IF NOT EXISTS`, here's a simplified version that checks first:

```sql
-- Check and drop columns
SELECT COUNT(*) INTO @salt_exists FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'salt';
SET @sql = IF(@salt_exists > 0, 'ALTER TABLE users DROP COLUMN salt', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
```

## Troubleshooting

1. **If you get "Column already exists" errors**: The column was already added, skip that statement.
2. **If you get "Column doesn't exist" errors**: The column was already removed, skip that statement.
3. **If UUID generation fails**: Make sure you're using MySQL 5.7+ or MariaDB 10.2.7+

## After Running SQL

1. Test your application to ensure everything works
2. Update any code that references old column names (`photo` → `avatar`, `role_id` → use Spatie roles, etc.)
3. Migrate existing `shop_id` data to `user_shop_access` table if needed


