<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get current column list
        $columns = Schema::getColumnListing('users');
        
        // Step 1: Drop old columns if they exist
        Schema::table('users', function (Blueprint $table) use ($columns) {
            if (in_array('salt', $columns)) {
                $table->dropColumn('salt');
            }
            if (in_array('role_id', $columns)) {
                $table->dropColumn('role_id');
            }
            if (in_array('shop_id', $columns)) {
                $table->dropColumn('shop_id');
            }
        });

        // Step 2: Handle name to first_name/last_name conversion if needed
        if (in_array('name', $columns) && !in_array('first_name', $columns)) {
            // Add first_name and last_name columns
            Schema::table('users', function (Blueprint $table) {
                $table->string('first_name', 100)->nullable()->after('id');
                $table->string('last_name', 100)->nullable()->after('first_name');
            });
            
            // Migrate data from name to first_name/last_name
            DB::statement("UPDATE users SET first_name = COALESCE(name, ''), last_name = '' WHERE first_name IS NULL");
            
            // Drop name column
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('name');
            });
            
            // Update columns list
            $columns = Schema::getColumnListing('users');
        }

        // Step 3: Ensure first_name and last_name exist
        if (!in_array('first_name', $columns)) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('first_name', 100)->after('id');
            });
        }
        if (!in_array('last_name', $columns)) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('last_name', 100)->nullable()->after('first_name');
            });
        }

        // Step 4: Rename photo to avatar using raw SQL (avoids DBAL dependency)
        if (in_array('photo', $columns) && !in_array('avatar', $columns)) {
            DB::statement('ALTER TABLE `users` CHANGE `photo` `avatar` VARCHAR(255) NULL');
            $columns = Schema::getColumnListing('users'); // Refresh
        }

        // Step 5: Add UUID
        if (!in_array('uuid', $columns)) {
            Schema::table('users', function (Blueprint $table) {
                $table->uuid('uuid')->unique()->after('id');
            });
        }

        // Step 6: Update existing column sizes using raw SQL (avoids DBAL dependency)
        $columns = Schema::getColumnListing('users'); // Refresh
        // Use raw SQL to avoid DBAL dependency issues
        try {
            DB::statement('ALTER TABLE `users` MODIFY `email` VARCHAR(255) NOT NULL');
        } catch (\Exception $e) {
            // Ignore if already correct
        }
        try {
            DB::statement('ALTER TABLE `users` MODIFY `phone` VARCHAR(20) NULL');
        } catch (\Exception $e) {
            // Ignore if already correct
        }
        try {
            DB::statement('ALTER TABLE `users` MODIFY `password` VARCHAR(255) NOT NULL');
        } catch (\Exception $e) {
            // Ignore if already correct
        }
        if (in_array('avatar', $columns)) {
            try {
                DB::statement('ALTER TABLE `users` MODIFY `avatar` VARCHAR(255) NULL');
            } catch (\Exception $e) {
                // Ignore if already correct
            }
        }

        // Step 7: Add Personal Information fields
        Schema::table('users', function (Blueprint $table) use ($columns) {
            if (!in_array('phone_country_code', $columns)) {
                $table->string('phone_country_code', 5)->nullable()->default('+880')->after('phone');
            }
            if (!in_array('phone_verified_at', $columns)) {
                $table->timestamp('phone_verified_at')->nullable()->after('phone_country_code');
            }
            if (!in_array('date_of_birth', $columns)) {
                $table->date('date_of_birth')->nullable()->after('phone_verified_at');
            }
            if (!in_array('gender', $columns)) {
                $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable()->after('date_of_birth');
            }
        });

        // Step 8: Add Profile (bio) - after avatar if it exists
        $columns = Schema::getColumnListing('users'); // Refresh
        Schema::table('users', function (Blueprint $table) use ($columns) {
            if (!in_array('bio', $columns)) {
                if (in_array('avatar', $columns)) {
                    $table->text('bio')->nullable()->after('avatar');
                } else {
                    $table->text('bio')->nullable()->after('email_verified_at');
                }
            }
        });

        // Step 9: Add User Type & Status
        $columns = Schema::getColumnListing('users'); // Refresh
        Schema::table('users', function (Blueprint $table) use ($columns) {
            if (!in_array('user_type', $columns)) {
                $table->enum('user_type', ['customer', 'vendor', 'admin', 'corporate'])->default('customer')->after('bio');
            }
            if (!in_array('status', $columns)) {
                $table->enum('status', ['active', 'inactive', 'suspended', 'pending_verification'])->default('active')->after('user_type');
            }
        });

        // Step 10: Add Preferences
        $columns = Schema::getColumnListing('users'); // Refresh
        Schema::table('users', function (Blueprint $table) use ($columns) {
            if (!in_array('locale', $columns)) {
                $table->string('locale', 10)->default('en')->after('status');
            }
            if (!in_array('timezone', $columns)) {
                $table->string('timezone', 50)->default('Asia/Dhaka')->after('locale');
            }
            if (!in_array('currency', $columns)) {
                $table->string('currency', 3)->default('BDT')->after('timezone');
            }
            if (!in_array('notification_preferences', $columns)) {
                $table->json('notification_preferences')->nullable()->after('currency');
            }
        });

        // Step 11: Add Security & Activity fields
        $columns = Schema::getColumnListing('users'); // Refresh
        Schema::table('users', function (Blueprint $table) use ($columns) {
            if (!in_array('last_login_at', $columns)) {
                $table->timestamp('last_login_at')->nullable()->after('notification_preferences');
            }
            if (!in_array('last_login_ip', $columns)) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }
            if (!in_array('login_count', $columns)) {
                $table->unsignedInteger('login_count')->default(0)->after('last_login_ip');
            }
            if (!in_array('failed_login_attempts', $columns)) {
                $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('login_count');
            }
            if (!in_array('locked_until', $columns)) {
                $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            }
            if (!in_array('password_changed_at', $columns)) {
                $table->timestamp('password_changed_at')->nullable()->after('locked_until');
            }
            if (!in_array('two_factor_secret', $columns)) {
                $table->text('two_factor_secret')->nullable()->after('password_changed_at');
            }
            if (!in_array('two_factor_recovery_codes', $columns)) {
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            }
            if (!in_array('two_factor_confirmed_at', $columns)) {
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            }
        });

        // Step 12: Add Vendor/Corporate Specific fields
        $columns = Schema::getColumnListing('users'); // Refresh
        Schema::table('users', function (Blueprint $table) use ($columns) {
            if (!in_array('company_name', $columns)) {
                $table->string('company_name', 255)->nullable()->after('two_factor_confirmed_at');
            }
            if (!in_array('tax_id', $columns)) {
                $table->string('tax_id', 50)->nullable()->after('company_name');
            }
            if (!in_array('business_type', $columns)) {
                $table->enum('business_type', ['individual', 'company', 'partnership', 'corporation'])->nullable()->after('tax_id');
            }
        });

        // Step 13: Add Soft Delete
        $columns = Schema::getColumnListing('users'); // Refresh
        if (!in_array('deleted_at', $columns)) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
        }

        // Step 14: Generate UUIDs for existing users
        $users = DB::table('users')->whereNull('uuid')->orWhere('uuid', '')->get();
        foreach ($users as $user) {
            DB::table('users')->where('id', $user->id)->update(['uuid' => (string) Str::uuid()]);
        }

        // Step 15: Add indexes
        $columns = Schema::getColumnListing('users'); // Refresh
        Schema::table('users', function (Blueprint $table) use ($columns) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('users');
            
            if (!isset($indexesFound['users_phone_index']) && in_array('phone', $columns)) {
                $table->index('phone');
            }
            if (!isset($indexesFound['users_user_type_index']) && in_array('user_type', $columns)) {
                $table->index('user_type');
            }
            if (!isset($indexesFound['users_status_index']) && in_array('status', $columns)) {
                $table->index('status');
            }
            if (!isset($indexesFound['users_created_at_index']) && in_array('created_at', $columns)) {
                $table->index('created_at');
            }
            if (!isset($indexesFound['users_deleted_at_index']) && in_array('deleted_at', $columns)) {
                $table->index('deleted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = Schema::getColumnListing('users');
        
        Schema::table('users', function (Blueprint $table) use ($columns) {
            // Remove new columns
            $columnsToRemove = [
                'uuid', 'phone_country_code', 'phone_verified_at', 'date_of_birth', 'gender',
                'bio', 'user_type', 'status', 'locale', 'timezone', 'currency',
                'notification_preferences', 'last_login_at', 'last_login_ip', 'login_count',
                'failed_login_attempts', 'locked_until', 'password_changed_at',
                'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
                'company_name', 'tax_id', 'business_type', 'deleted_at'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (in_array($column, $columns)) {
                    $table->dropColumn($column);
                }
            }
            
            // Rename avatar back to photo
            if (in_array('avatar', $columns) && !in_array('photo', $columns)) {
                $table->renameColumn('avatar', 'photo');
            }
            
            // Restore old columns if they don't exist
            if (!in_array('salt', $columns)) {
                $table->string('salt')->after('password');
            }
            if (!in_array('role_id', $columns)) {
                $table->integer('role_id')->default(2)->after('salt');
            }
            if (!in_array('shop_id', $columns)) {
                $table->integer('shop_id')->after('role_id');
            }
        });
    }
};
