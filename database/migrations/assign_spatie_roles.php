<?php

/**
 * Script to assign Spatie Permission roles to existing users based on old role_id
 * 
 * Run: php artisan tinker
 * Then: require 'database/migrations/assign_spatie_roles.php';
 */

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

echo "\n=== ASSIGNING SPATIE PERMISSION ROLES ===\n\n";

// Check if roles exist, create them if not
$rolesToCreate = [
    'admin' => 'Administrator',
    'sales_manager' => 'Sales Manager',
    'customer' => 'Customer',
    'vendor' => 'Vendor',
    'corporate' => 'Corporate User',
];

foreach ($rolesToCreate as $roleName => $displayName) {
    $role = Role::firstOrCreate(
        ['name' => $roleName, 'guard_name' => 'web'],
        ['name' => $roleName, 'guard_name' => 'web']
    );
    echo "✅ Role '{$roleName}' exists\n";
}

echo "\n";

// Check if we have old role_id data
$hasRoleIdColumn = DB::select("
    SELECT COUNT(*) as count 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'role_id'
")[0]->count > 0;

if ($hasRoleIdColumn) {
    echo "Found role_id column, migrating based on role_id...\n\n";
    
    // Map old role_id to new roles
    // Based on your old system: 1 = admin, 2 = sales manager, 3 = customer
    $roleMapping = [
        1 => 'admin',
        2 => 'sales_manager',
        3 => 'customer',
    ];
    
    $users = DB::table('users')->whereNotNull('role_id')->get();
    
    foreach ($users as $userData) {
        $roleId = $userData->role_id;
        $roleName = $roleMapping[$roleId] ?? 'customer';
        
        $user = User::find($userData->id);
        if (!$user) continue;
        
        // Remove existing roles and assign new one
        $user->syncRoles([$roleName]);
        
        echo "✅ User {$user->id} ({$user->email}): Assigned role '{$roleName}'\n";
    }
} else {
    echo "⚠ role_id column doesn't exist. Assigning roles based on user_type...\n\n";
    
    // Assign roles based on user_type
    $typeMapping = [
        'admin' => 'admin',
        'vendor' => 'vendor',
        'corporate' => 'corporate',
        'customer' => 'customer',
    ];
    
    $users = User::all();
    
    foreach ($users as $user) {
        $userType = $user->user_type ?? 'customer';
        $roleName = $typeMapping[$userType] ?? 'customer';
        
        // Check if user already has this role
        if ($user->hasRole($roleName)) {
            echo "⚠ User {$user->id} already has role '{$roleName}', skipping...\n";
            continue;
        }
        
        // Sync roles (removes old, adds new)
        $user->syncRoles([$roleName]);
        
        echo "✅ User {$user->id} ({$user->email}): Assigned role '{$roleName}' based on user_type '{$userType}'\n";
    }
}

echo "\n=== ROLE ASSIGNMENT COMPLETE ===\n\n";

// Show summary
$roleCounts = [];
foreach ($rolesToCreate as $roleName => $displayName) {
    $count = User::role($roleName)->count();
    $roleCounts[$roleName] = $count;
    echo "   {$roleName}: {$count} users\n";
}

echo "\n";


