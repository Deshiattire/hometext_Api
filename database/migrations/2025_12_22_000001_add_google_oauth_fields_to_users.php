<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add Google OAuth fields
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id', 255)->unique()->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('users', 'oauth_provider')) {
                $table->string('oauth_provider', 50)->nullable()->after('google_id');
            }
            
            if (!Schema::hasColumn('users', 'oauth_login_count')) {
                $table->integer('oauth_login_count')->default(0)->after('oauth_provider');
            }
            
            if (!Schema::hasColumn('users', 'last_oauth_login')) {
                $table->timestamp('last_oauth_login')->nullable()->after('oauth_login_count');
            }

            // Note: avatar field already exists in users table
        });

        // Add index for faster lookups
        Schema::table('users', function (Blueprint $table) {
            $table->index('google_id');
            $table->index('oauth_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['google_id']);
            $table->dropIndex(['oauth_provider']);
            
            $table->dropColumn([
                'google_id',
                'oauth_provider',
                'oauth_login_count',
                'last_oauth_login'
            ]);
        });
    }
};
