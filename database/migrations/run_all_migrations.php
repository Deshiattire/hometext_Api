<?php

/**
 * Master script to run all data migrations
 * 
 * Run: php artisan tinker
 * Then: require 'database/migrations/run_all_migrations.php';
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     USERS STRUCTURE UPGRADE - DATA MIGRATION SCRIPT         ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Step 1: Migrate shop_id to user_shop_access
echo "STEP 1: Migrating shop_id to user_shop_access table...\n";
echo str_repeat("-", 60) . "\n";
require 'database/migrations/migrate_shop_id_to_access.php';

// Step 2: Assign Spatie Permission roles
echo "\n\n";
echo "STEP 2: Assigning Spatie Permission roles...\n";
echo str_repeat("-", 60) . "\n";
require 'database/migrations/assign_spatie_roles.php';

// Step 3: Verification
echo "\n\n";
echo "STEP 3: Final verification...\n";
echo str_repeat("-", 60) . "\n";
require 'database/migrations/verify_structure.php';

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║              ALL MIGRATIONS COMPLETED SUCCESSFULLY!         ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";


