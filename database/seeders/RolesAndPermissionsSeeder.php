<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Product permissions
            'view products',
            'create products',
            'edit products',
            'delete products',
            'duplicate products',
            
            // Category permissions
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            
            // Order permissions
            'view orders',
            'create orders',
            'edit orders',
            'delete orders',
            'manage orders',
            
            // Customer permissions
            'view customers',
            'create customers',
            'edit customers',
            'delete customers',
            
            // Review permissions
            'view reviews',
            'approve reviews',
            'reject reviews',
            'delete reviews',
            
            // Shop permissions
            'view shops',
            'create shops',
            'edit shops',
            'delete shops',
            
            // Supplier permissions
            'view suppliers',
            'create suppliers',
            'edit suppliers',
            'delete suppliers',
            
            // Brand permissions
            'view brands',
            'create brands',
            'edit brands',
            'delete brands',
            
            // Transfer permissions
            'view transfers',
            'create transfers',
            'approve transfers',
            'reject transfers',
            
            // Report permissions
            'view reports',
            'export reports',
            
            // User management permissions
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(Permission::all());

        $salesManagerRole = Role::firstOrCreate(['name' => 'sales_manager']);
        $salesManagerRole->syncPermissions([
            'view products',
            'create products',
            'edit products',
            'duplicate products',
            'view categories',
            'create categories',
            'edit categories',
            'view orders',
            'create orders',
            'edit orders',
            'view customers',
            'view reviews',
            'approve reviews',
            'reject reviews',
            'view shops',
            'view suppliers',
            'view brands',
            'create brands',
            'edit brands',
            'view transfers',
            'create transfers',
            'approve transfers',
            'reject transfers',
            'view reports',
        ]);

        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $customerRole->syncPermissions([
            'view products',
            'view categories',
            'create orders',
            'view orders',
        ]);
    }
}

