<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('categories')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            // Add parent_id if missing
            if (!Schema::hasColumn('categories', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            }

            // Add level if missing
            if (!Schema::hasColumn('categories', 'level')) {
                $table->integer('level')->default(1)->after('parent_id');
            }

            // Add is_active if missing
            if (!Schema::hasColumn('categories', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }

            // Add sort_order if missing
            if (!Schema::hasColumn('categories', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_active');
            }

            // Add meta_title if missing
            if (!Schema::hasColumn('categories', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('sort_order');
            }

            // Add meta_description if missing
            if (!Schema::hasColumn('categories', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }

            // Add deleted_at if missing (for soft deletes)
            if (!Schema::hasColumn('categories', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Update existing data
        if (Schema::hasColumn('categories', 'is_active')) {
            DB::table('categories')
                ->whereNull('is_active')
                ->update(['is_active' => DB::raw('COALESCE(status, 1)')]);
        }

        if (Schema::hasColumn('categories', 'sort_order')) {
            DB::table('categories')
                ->whereNull('sort_order')
                ->update(['sort_order' => DB::raw('COALESCE(serial, 0)')]);
        }

        if (Schema::hasColumn('categories', 'level')) {
            DB::table('categories')
                ->whereNull('level')
                ->orWhere('level', 0)
                ->update(['level' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop columns in down() to preserve data
    }
};




