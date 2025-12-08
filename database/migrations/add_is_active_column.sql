-- Add is_active column to categories table if it doesn't exist
-- Run this SQL directly in your MySQL database if migrations aren't working

-- Check and add is_active column
SET @dbname = DATABASE();
SET @tablename = 'categories';
SET @columnname = 'is_active';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' BOOLEAN DEFAULT TRUE AFTER description')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Update existing records: set is_active based on status column if it exists
UPDATE categories 
SET is_active = COALESCE(status, 1) 
WHERE is_active IS NULL;

-- If status column doesn't exist, set all to active
UPDATE categories 
SET is_active = 1 
WHERE is_active IS NULL;

-- Add index on is_active for better query performance
CREATE INDEX IF NOT EXISTS categories_is_active_index ON categories(is_active);

