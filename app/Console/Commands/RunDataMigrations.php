<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RunDataMigrations extends Command
{
    protected $signature = 'migrate:data {--step=all : Which step to run (all, roles, verify)}';
    protected $description = 'Run data migrations for users structure upgrade';

    public function handle()
    {
        $step = $this->option('step');
        
        if ($step === 'all' || $step === 'roles') {
            $this->assignRoles();
        }
        
        if ($step === 'all' || $step === 'verify') {
            $this->verify();
        }
        
        return Command::SUCCESS;
    }
    
    private function assignRoles()
    {
        $this->info('Assigning Spatie Permission roles...');
        $this->newLine();
        
        // Ensure roles exist for both web and sanctum guards
        $rolesToCreate = [
            'admin' => 'Administrator',
            'sales_manager' => 'Sales Manager',
            'customer' => 'Customer',
            'vendor' => 'Vendor',
            'corporate' => 'Corporate User',
        ];
        
        $guards = ['web', 'sanctum'];
        
        foreach ($rolesToCreate as $roleName => $displayName) {
            foreach ($guards as $guard) {
                $role = Role::firstOrCreate(
                    ['name' => $roleName, 'guard_name' => $guard],
                    ['name' => $roleName, 'guard_name' => $guard]
                );
            }
            $this->info("✓ Role '{$roleName}' exists for all guards");
        }
        
        $this->newLine();
        
        // Assign roles based on user_type
        $typeMapping = [
            'admin' => 'admin',
            'vendor' => 'vendor',
            'corporate' => 'corporate',
            'customer' => 'customer',
        ];
        
        $users = User::all();
        $assigned = 0;
        $skipped = 0;
        
        foreach ($users as $user) {
            $userType = $user->user_type ?? 'customer';
            $roleName = $typeMapping[$userType] ?? 'customer';
            
            // Check if user has role for any guard
            $hasRole = false;
            foreach ($guards as $guard) {
                if ($user->hasRole($roleName, $guard)) {
                    $hasRole = true;
                    break;
                }
            }
            
            if ($hasRole) {
                $skipped++;
                continue;
            }
            
            // Assign role using sanctum guard (since that's what the app uses)
            try {
                $user->assignRole($roleName);
                $this->info("✓ User {$user->id} ({$user->email}): Assigned role '{$roleName}'");
                $assigned++;
            } catch (\Exception $e) {
                $this->error("✗ Error assigning role to user {$user->id}: " . $e->getMessage());
            }
        }
        
        $this->newLine();
        $this->info("Summary: {$assigned} assigned, {$skipped} already had roles");
        
        // Show role counts
        $this->newLine();
        $this->info('Role distribution:');
        foreach ($rolesToCreate as $roleName => $displayName) {
            // Count users with this role for any guard
            $count = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('roles.name', $roleName)
                ->where('model_has_roles.model_type', User::class)
                ->distinct('model_has_roles.model_id')
                ->count('model_has_roles.model_id');
            $this->line("   {$roleName}: {$count} users");
        }
    }
    
    private function verify()
    {
        $this->newLine();
        $this->info('Verifying structure...');
        $this->newLine();
        
        $requiredColumns = [
            'uuid', 'first_name', 'last_name', 'email', 'phone',
            'user_type', 'status', 'avatar', 'bio', 'deleted_at'
        ];
        
        $missing = [];
        foreach ($requiredColumns as $column) {
            if (!DB::getSchemaBuilder()->hasColumn('users', $column)) {
                $missing[] = $column;
            }
        }
        
        if (empty($missing)) {
            $this->info('✅ All required columns exist');
        } else {
            $this->error('❌ Missing columns: ' . implode(', ', $missing));
        }
        
        $newTables = [
            'user_addresses',
            'vendor_profiles',
            'corporate_profiles',
            'user_shop_access',
            'social_logins',
            'user_activity_logs'
        ];
        
        $this->newLine();
        foreach ($newTables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $this->info("✅ Table '{$table}' exists");
            } else {
                $this->error("❌ Table '{$table}' is missing");
            }
        }
        
        $this->newLine();
        $user = User::first();
        if ($user) {
            $this->info("✅ User model works");
            $this->line("   Sample user: {$user->email} (UUID: {$user->uuid})");
        }
    }
}
