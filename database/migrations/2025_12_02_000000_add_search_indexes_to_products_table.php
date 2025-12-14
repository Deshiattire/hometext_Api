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
     * Adds indexes for optimized search performance
     * Following industry best practices for e-commerce search
     */
    public function up(): void
    {
        $connection = Schema::getConnection();
        
        // Add indexes to products table
        Schema::table('products', function (Blueprint $table) {
            // Index on SKU for exact/fuzzy SKU searches
            if (!$this->indexExists('products', 'products_sku_index')) {
                $table->index('sku', 'products_sku_index');
            }
            
            // Composite index for common filter combinations
            if (!$this->indexExists('products', 'products_category_status_index')) {
                $table->index(['category_id', 'status'], 'products_category_status_index');
            }
            
            // Index for brand filtering
            if (!$this->indexExists('products', 'products_brand_index')) {
                $table->index('brand_id', 'products_brand_index');
            }
        });
        
        // Add prefix index on products.name using raw SQL (Laravel doesn't support prefix indexes directly)
        if (!$this->indexExists('products', 'products_name_index')) {
            $connection->statement('CREATE INDEX products_name_index ON products (name(255))');
        }
        
        // Add indexes to related tables for join performance
        // Categories table
        if (!$this->indexExists('categories', 'categories_name_index')) {
            $connection->statement('CREATE INDEX categories_name_index ON categories (name(255))');
        }
        
        // Sub categories table
        if (!$this->indexExists('sub_categories', 'sub_categories_name_index')) {
            $connection->statement('CREATE INDEX sub_categories_name_index ON sub_categories (name(255))');
        }
        
        // Child sub categories table
        if (!$this->indexExists('child_sub_categories', 'child_sub_categories_name_index')) {
            $connection->statement('CREATE INDEX child_sub_categories_name_index ON child_sub_categories (name(255))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = Schema::getConnection();
        
        // Drop indexes from products table
        Schema::table('products', function (Blueprint $table) {
            if ($this->indexExists('products', 'products_sku_index')) {
                $table->dropIndex('products_sku_index');
            }
            if ($this->indexExists('products', 'products_category_status_index')) {
                $table->dropIndex('products_category_status_index');
            }
            if ($this->indexExists('products', 'products_brand_index')) {
                $table->dropIndex('products_brand_index');
            }
        });
        
        // Drop prefix indexes using raw SQL
        if ($this->indexExists('products', 'products_name_index')) {
            $connection->statement('DROP INDEX products_name_index ON products');
        }
        
        if ($this->indexExists('categories', 'categories_name_index')) {
            $connection->statement('DROP INDEX categories_name_index ON categories');
        }
        
        if ($this->indexExists('sub_categories', 'sub_categories_name_index')) {
            $connection->statement('DROP INDEX sub_categories_name_index ON sub_categories');
        }
        
        if ($this->indexExists('child_sub_categories', 'child_sub_categories_name_index')) {
            $connection->statement('DROP INDEX child_sub_categories_name_index ON child_sub_categories');
        }
    }
    
    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = $connection->select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$databaseName, $table, $index]
        );
        
        return $result[0]->count > 0;
    }
};
