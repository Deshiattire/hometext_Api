<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Upgrades existing categories table to unified hierarchical structure
     * Supports unlimited nesting levels using parent_id
     */
    public function up(): void
    {
        // Modify existing categories table to add new columns
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                // Add new columns if they don't exist
                if (!Schema::hasColumn('categories', 'parent_id')) {
                    $table->unsignedBigInteger('parent_id')->nullable()->after('id');
                }
                if (!Schema::hasColumn('categories', 'level')) {
                    $table->integer('level')->default(1)->after('parent_id');
                }
                if (!Schema::hasColumn('categories', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('description');
                }
                if (!Schema::hasColumn('categories', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('is_active');
                }
                if (!Schema::hasColumn('categories', 'meta_title')) {
                    $table->string('meta_title')->nullable()->after('sort_order');
                }
                if (!Schema::hasColumn('categories', 'meta_description')) {
                    $table->text('meta_description')->nullable()->after('meta_title');
                }
                if (!Schema::hasColumn('categories', 'deleted_at')) {
                    $table->softDeletes();
                }
            });

            // Add indexes using raw SQL to avoid conflicts
            $indexes = [
                'parent_id' => 'categories_parent_id_index',
                'slug' => 'categories_slug_index',
                'is_active' => 'categories_is_active_index',
                'level' => 'categories_level_index',
                'sort_order' => 'categories_sort_order_index',
            ];

            foreach ($indexes as $column => $indexName) {
                if (Schema::hasColumn('categories', $column)) {
                    try {
                        DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON categories ({$column})");
                    } catch (\Exception $e) {
                        // Index might already exist, continue
                    }
                }
            }

            // Add composite index
            try {
                DB::statement("CREATE INDEX IF NOT EXISTS categories_parent_active_sort_index ON categories (parent_id, is_active, sort_order)");
            } catch (\Exception $e) {
                // Index might already exist, continue
            }

            // Add foreign key constraint if parent_id column exists
            if (Schema::hasColumn('categories', 'parent_id')) {
                try {
                    DB::statement("
                        ALTER TABLE categories 
                        ADD CONSTRAINT categories_parent_id_foreign 
                        FOREIGN KEY (parent_id) REFERENCES categories(id) 
                        ON DELETE CASCADE
                    ");
                } catch (\Exception $e) {
                    // Foreign key might already exist, continue
                }
            }

            // Update existing data: set level=1 and is_active based on status
            DB::table('categories')
                ->whereNull('level')
                ->orWhere('level', 0)
                ->update([
                    'level' => 1,
                    'parent_id' => null,
                    'is_active' => DB::raw('COALESCE(status, 1)'),
                    'sort_order' => DB::raw('COALESCE(serial, 0)')
                ]);
        } else {
            // Create new table if it doesn't exist
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                
                // Hierarchical structure
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->integer('level')->default(1)->comment('1=Category, 2=Subcategory, 3=Child Category');
                
                // Basic information
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                
                // Display and ordering
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                
                // Legacy fields (for backward compatibility during migration)
                $table->string('photo')->nullable()->comment('Deprecated: Use category_images table');
                $table->string('image')->nullable()->comment('Deprecated: Use category_images table');
                $table->integer('serial')->nullable()->comment('Deprecated: Use sort_order');
                $table->tinyInteger('status')->nullable()->comment('Deprecated: Use is_active');
                
                // SEO fields
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                
                // Audit fields
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                
                // Indexes for performance
                $table->index('parent_id');
                $table->index('slug');
                $table->index('is_active');
                $table->index('level');
                $table->index('sort_order');
                $table->index(['parent_id', 'is_active', 'sort_order']);
                
                // Foreign key constraint
                $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove new columns (keep legacy structure)
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                // Drop foreign key first
                try {
                    $table->dropForeign(['parent_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }

                if (Schema::hasColumn('categories', 'parent_id')) {
                    $table->dropColumn('parent_id');
                }
                if (Schema::hasColumn('categories', 'level')) {
                    $table->dropColumn('level');
                }
                if (Schema::hasColumn('categories', 'is_active')) {
                    $table->dropColumn('is_active');
                }
                if (Schema::hasColumn('categories', 'sort_order')) {
                    $table->dropColumn('sort_order');
                }
                if (Schema::hasColumn('categories', 'meta_title')) {
                    $table->dropColumn('meta_title');
                }
                if (Schema::hasColumn('categories', 'meta_description')) {
                    $table->dropColumn('meta_description');
                }
                if (Schema::hasColumn('categories', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
