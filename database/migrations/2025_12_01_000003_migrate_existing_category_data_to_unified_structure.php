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
     * Migrates data from old structure (categories, sub_categories, child_sub_categories)
     * to new unified categories table
     */
    public function up(): void
    {
        // Step 1: Update existing categories to set level=1 and ensure parent_id is null
        if (Schema::hasTable('categories')) {
            DB::table('categories')
                ->whereNull('level')
                ->orWhere('level', 0)
                ->update([
                    'level' => 1,
                    'parent_id' => null,
                    'is_active' => DB::raw('COALESCE(status, 1)'),
                    'sort_order' => DB::raw('COALESCE(serial, 0)')
                ]);
        }

        // Step 2: Migrate subcategories (level 2) if sub_categories table exists
        if (Schema::hasTable('sub_categories')) {
            $subCategories = DB::table('sub_categories')->get();
            
            foreach ($subCategories as $subCategory) {
                // Check if parent category exists
                $parentCategory = DB::table('categories')
                    ->where('id', $subCategory->category_id)
                    ->where('level', 1)
                    ->first();
                
                if ($parentCategory) {
                    // Insert subcategory as level 2 category
                    $insertData = [
                        'parent_id' => $subCategory->category_id,
                        'level' => 2,
                        'name' => $subCategory->name ?? 'Unnamed Subcategory',
                        'slug' => $subCategory->slug ?? 'subcategory-' . $subCategory->id,
                        'description' => $subCategory->description,
                        'is_active' => ($subCategory->status ?? 1) == 1,
                        'sort_order' => $subCategory->serial ?? 0,
                        'photo' => $subCategory->photo,
                        'serial' => $subCategory->serial,
                        'status' => $subCategory->status,
                        'user_id' => $subCategory->user_id,
                        'created_at' => $subCategory->created_at,
                        'updated_at' => $subCategory->updated_at,
                    ];
                    
                    // Always provide image value (use photo or empty string)
                    $insertData['image'] = $subCategory->photo ?? '';
                    
                    $newId = DB::table('categories')->insertGetId($insertData);

                    // Migrate images if they exist
                    if ($subCategory->photo) {
                        DB::table('category_images')->insert([
                            'category_id' => $newId,
                            'image_path' => $subCategory->photo,
                            'image_type' => 'primary',
                            'is_primary' => true,
                            'position' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // Step 3: Migrate child subcategories (level 3) if child_sub_categories table exists
        if (Schema::hasTable('child_sub_categories')) {
            $childSubCategories = DB::table('child_sub_categories')->get();
            
            foreach ($childSubCategories as $childSubCategory) {
                // Find the parent subcategory (now a level 2 category)
                $parentSubCategory = DB::table('categories')
                    ->where('level', 2)
                    ->where(function($query) use ($childSubCategory) {
                        // Try to match by finding the subcategory that was migrated
                        $query->where('slug', 'like', '%subcategory-' . $childSubCategory->sub_category_id . '%')
                              ->orWhere('id', function($q) use ($childSubCategory) {
                                  // Find by matching the old sub_category_id
                                  $q->select('id')
                                    ->from('categories')
                                    ->where('level', 2)
                                    ->whereRaw('slug LIKE ?', ['%' . $childSubCategory->sub_category_id . '%'])
                                    ->limit(1);
                              });
                    })
                    ->first();

                // Alternative: Find parent by checking sub_categories table
                if (!$parentSubCategory && Schema::hasTable('sub_categories')) {
                    $oldSubCategory = DB::table('sub_categories')
                        ->where('id', $childSubCategory->sub_category_id)
                        ->first();
                    
                    if ($oldSubCategory) {
                        $parentSubCategory = DB::table('categories')
                            ->where('level', 2)
                            ->where('slug', $oldSubCategory->slug ?? 'subcategory-' . $oldSubCategory->id)
                            ->first();
                    }
                }

                if ($parentSubCategory) {
                    // Insert child subcategory as level 3 category
                    $insertData = [
                        'parent_id' => $parentSubCategory->id,
                        'level' => 3,
                        'name' => $childSubCategory->name ?? 'Unnamed Child Category',
                        'slug' => $childSubCategory->slug ?? 'child-category-' . $childSubCategory->id,
                        'description' => $childSubCategory->description,
                        'is_active' => ($childSubCategory->status ?? 1) == 1,
                        'sort_order' => $childSubCategory->serial ?? 0,
                        'photo' => $childSubCategory->photo,
                        'serial' => $childSubCategory->serial,
                        'status' => $childSubCategory->status,
                        'user_id' => $childSubCategory->user_id,
                        'created_at' => $childSubCategory->created_at,
                        'updated_at' => $childSubCategory->updated_at,
                    ];
                    
                    // Always provide image value (use photo or empty string)
                    $insertData['image'] = $childSubCategory->photo ?? '';
                    
                    $newId = DB::table('categories')->insertGetId($insertData);

                    // Migrate images if they exist
                    if ($childSubCategory->photo) {
                        DB::table('category_images')->insert([
                            'category_id' => $newId,
                            'image_path' => $childSubCategory->photo,
                            'image_type' => 'primary',
                            'is_primary' => true,
                            'position' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // Step 4: Migrate existing category images to category_images table
        if (Schema::hasTable('categories')) {
            $categories = DB::table('categories')
                ->where(function($query) {
                    $query->whereNotNull('image')
                          ->orWhereNotNull('photo');
                })
                ->get();

            foreach ($categories as $category) {
                $imagePath = $category->image ?? $category->photo;
                
                if ($imagePath) {
                    // Check if image already exists
                    $existingImage = DB::table('category_images')
                        ->where('category_id', $category->id)
                        ->where('is_primary', true)
                        ->first();

                    if (!$existingImage) {
                        DB::table('category_images')->insert([
                            'category_id' => $category->id,
                            'image_path' => $imagePath,
                            'image_type' => 'primary',
                            'is_primary' => true,
                            'position' => 0,
                            'created_at' => $category->created_at ?? now(),
                            'updated_at' => $category->updated_at ?? now(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible
        // Data should be backed up before running
        // To reverse, you would need to:
        // 1. Extract level 1 categories back to categories table
        // 2. Extract level 2 categories back to sub_categories table
        // 3. Extract level 3 categories back to child_sub_categories table
    }
};
