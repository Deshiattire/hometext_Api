<?php

/**
 * Migration script to move shop_id data to user_shop_access table
 * 
 * Run: php artisan tinker
 * Then: require 'database/migrations/migrate_shop_id_to_access.php';
 */

use App\Models\User;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;

echo "\n=== MIGRATING SHOP_ID TO USER_SHOP_ACCESS ===\n\n";

// Check if users table still has shop_id column
$hasShopIdColumn = DB::select("
    SELECT COUNT(*) as count 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'shop_id'
")[0]->count > 0;

if (!$hasShopIdColumn) {
    echo "⚠ shop_id column doesn't exist in users table.\n";
    echo "   If you have backup data, you can restore it first.\n";
    echo "   Or if you have the data elsewhere, we can import it.\n\n";
    
    // Check if there's any existing data in user_shop_access
    $existingAccess = DB::table('user_shop_access')->count();
    if ($existingAccess > 0) {
        echo "✅ Found {$existingAccess} existing records in user_shop_access table\n";
    } else {
        echo "⚠ No data in user_shop_access table yet\n";
    }
    
    echo "\nTo migrate from backup, you can run:\n";
    echo "INSERT INTO user_shop_access (user_id, shop_id, role, is_primary, granted_at)\n";
    echo "SELECT id, shop_id, 'owner', TRUE, NOW() FROM users_backup WHERE shop_id IS NOT NULL;\n\n";
    exit;
}

// Get users with shop_id
$usersWithShop = DB::table('users')
    ->whereNotNull('shop_id')
    ->where('shop_id', '!=', 0)
    ->get();

if ($usersWithShop->isEmpty()) {
    echo "⚠ No users found with shop_id\n";
    exit;
}

echo "Found {$usersWithShop->count()} users with shop_id\n\n";

$migrated = 0;
$skipped = 0;
$errors = 0;

foreach ($usersWithShop as $user) {
    // Check if shop exists
    $shopExists = DB::table('shops')->where('id', $user->shop_id)->exists();
    
    if (!$shopExists) {
        echo "⚠ Shop ID {$user->shop_id} doesn't exist for user {$user->id}, skipping...\n";
        $skipped++;
        continue;
    }
    
    // Check if access already exists
    $accessExists = DB::table('user_shop_access')
        ->where('user_id', $user->id)
        ->where('shop_id', $user->shop_id)
        ->exists();
    
    if ($accessExists) {
        echo "⚠ Access already exists for user {$user->id} and shop {$user->shop_id}, skipping...\n";
        $skipped++;
        continue;
    }
    
    // Create access record
    try {
        DB::table('user_shop_access')->insert([
            'user_id' => $user->id,
            'shop_id' => $user->shop_id,
            'role' => 'owner', // Default to owner, adjust if needed
            'is_primary' => true,
            'granted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "✅ Migrated user {$user->id} → shop {$user->shop_id}\n";
        $migrated++;
    } catch (\Exception $e) {
        echo "❌ Error migrating user {$user->id}: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n=== MIGRATION SUMMARY ===\n";
echo "✅ Migrated: {$migrated}\n";
echo "⚠ Skipped: {$skipped}\n";
echo ($errors > 0 ? "❌ Errors: {$errors}\n" : "");
echo "\n=== COMPLETE ===\n\n";


