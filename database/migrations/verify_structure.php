<?php

/**
 * Verification script to test the new users structure
 * Run: php artisan tinker
 * Then: require 'database/migrations/verify_structure.php';
 */

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "\n=== VERIFYING USERS TABLE STRUCTURE ===\n\n";

// Check required columns
$requiredColumns = [
    'uuid', 'first_name', 'last_name', 'email', 'phone',
    'user_type', 'status', 'avatar', 'bio', 'deleted_at'
];

$missingColumns = [];
foreach ($requiredColumns as $column) {
    if (!Schema::hasColumn('users', $column)) {
        $missingColumns[] = $column;
    }
}

if (empty($missingColumns)) {
    echo "✅ All required columns exist\n";
} else {
    echo "❌ Missing columns: " . implode(', ', $missingColumns) . "\n";
}

// Check new tables
$newTables = [
    'user_addresses',
    'vendor_profiles',
    'corporate_profiles',
    'user_shop_access',
    'social_logins',
    'user_activity_logs'
];

echo "\n=== CHECKING NEW TABLES ===\n";
foreach ($newTables as $table) {
    if (Schema::hasTable($table)) {
        echo "✅ Table '{$table}' exists\n";
    } else {
        echo "❌ Table '{$table}' is missing\n";
    }
}

// Test User model
echo "\n=== TESTING USER MODEL ===\n";
try {
    $user = User::first();
    if ($user) {
        echo "✅ User model works\n";
        echo "   - UUID: " . ($user->uuid ?? 'missing') . "\n";
        echo "   - Name: " . ($user->name ?? 'missing') . "\n";
        echo "   - User Type: " . ($user->user_type ?? 'missing') . "\n";
        echo "   - Status: " . ($user->status ?? 'missing') . "\n";
    } else {
        echo "⚠ No users found in database\n";
    }
} catch (\Exception $e) {
    echo "❌ Error testing User model: " . $e->getMessage() . "\n";
}

// Check relationships
echo "\n=== TESTING RELATIONSHIPS ===\n";
try {
    $user = User::first();
    if ($user) {
        $relationships = [
            'addresses' => method_exists($user, 'addresses'),
            'vendorProfile' => method_exists($user, 'vendorProfile'),
            'corporateProfile' => method_exists($user, 'corporateProfile'),
            'shopAccess' => method_exists($user, 'shopAccess'),
        ];
        
        foreach ($relationships as $rel => $exists) {
            echo ($exists ? "✅" : "❌") . " Relationship '{$rel}'\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error testing relationships: " . $e->getMessage() . "\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n\n";


