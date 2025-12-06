<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * CategorySeeder
 * 
 * Seeds comprehensive demo data for categories with hierarchical structure
 * Includes multiple levels, images, and realistic e-commerce categories
 */
class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data (optional - comment out if you want to keep existing data)
        // Category::truncate();
        // CategoryImage::truncate();

        // Level 1: Main Categories
        $electronics = $this->createCategory([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic devices and gadgets for home and office',
            'is_active' => true,
            'sort_order' => 1,
            'meta_title' => 'Electronics - Shop Latest Electronic Devices',
            'meta_description' => 'Browse our wide range of electronic devices including smartphones, laptops, and home appliances.',
        ]);

        $homeDecor = $this->createCategory([
            'name' => 'Home & Decor',
            'slug' => 'home-decor',
            'description' => 'Everything to make your home beautiful and comfortable',
            'is_active' => true,
            'sort_order' => 2,
            'meta_title' => 'Home Decor - Transform Your Living Space',
            'meta_description' => 'Discover beautiful home decor items including furniture, lighting, and accessories.',
        ]);

        $fashion = $this->createCategory([
            'name' => 'Fashion & Apparel',
            'slug' => 'fashion-apparel',
            'description' => 'Latest fashion trends and clothing for all occasions',
            'is_active' => true,
            'sort_order' => 3,
            'meta_title' => 'Fashion & Apparel - Latest Trends',
            'meta_description' => 'Shop the latest fashion trends and clothing for men, women, and kids.',
        ]);

        $kitchen = $this->createCategory([
            'name' => 'Kitchen & Dining',
            'slug' => 'kitchen-dining',
            'description' => 'Kitchen appliances, cookware, and dining essentials',
            'is_active' => true,
            'sort_order' => 4,
            'meta_title' => 'Kitchen & Dining - Cookware & Appliances',
            'meta_description' => 'Find everything you need for your kitchen including appliances, cookware, and dining sets.',
        ]);

        $health = $this->createCategory([
            'name' => 'Health & Beauty',
            'slug' => 'health-beauty',
            'description' => 'Health and beauty products for your wellness',
            'is_active' => true,
            'sort_order' => 5,
            'meta_title' => 'Health & Beauty Products',
            'meta_description' => 'Shop health and beauty products including skincare, makeup, and wellness items.',
        ]);

        // Level 2: Subcategories under Electronics
        $washingMachine = $this->createCategory([
            'name' => 'Washing Machine',
            'slug' => 'washing-machine',
            'description' => 'All types of washing machines for your laundry needs',
            'parent_id' => $electronics->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $mobilePhone = $this->createCategory([
            'name' => 'Mobile Phone',
            'slug' => 'mobile-phone',
            'description' => 'Smartphones and mobile accessories',
            'parent_id' => $electronics->id,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $laptop = $this->createCategory([
            'name' => 'Laptop & Computer',
            'slug' => 'laptop-computer',
            'description' => 'Laptops, desktops, and computer accessories',
            'parent_id' => $electronics->id,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $tv = $this->createCategory([
            'name' => 'TV & Audio',
            'slug' => 'tv-audio',
            'description' => 'Televisions, speakers, and audio equipment',
            'parent_id' => $electronics->id,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        // Level 2: Subcategories under Home & Decor
        $bathrobes = $this->createCategory([
            'name' => 'Bathrobes',
            'slug' => 'bathrobes',
            'description' => 'Luxurious bathrobes and towels',
            'parent_id' => $homeDecor->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $furniture = $this->createCategory([
            'name' => 'Furniture',
            'slug' => 'furniture',
            'description' => 'Home and office furniture',
            'parent_id' => $homeDecor->id,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $lighting = $this->createCategory([
            'name' => 'Lighting',
            'slug' => 'lighting',
            'description' => 'Indoor and outdoor lighting solutions',
            'parent_id' => $homeDecor->id,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Level 2: Subcategories under Fashion
        $mensWear = $this->createCategory([
            'name' => "Men's Wear",
            'slug' => 'mens-wear',
            'description' => 'Clothing and accessories for men',
            'parent_id' => $fashion->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $womensWear = $this->createCategory([
            'name' => "Women's Wear",
            'slug' => 'womens-wear',
            'description' => 'Clothing and accessories for women',
            'parent_id' => $fashion->id,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Level 2: Subcategories under Kitchen
        $cookware = $this->createCategory([
            'name' => 'Cookware',
            'slug' => 'cookware',
            'description' => 'Pots, pans, and cooking utensils',
            'parent_id' => $kitchen->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $appliances = $this->createCategory([
            'name' => 'Kitchen Appliances',
            'slug' => 'kitchen-appliances',
            'description' => 'Kitchen appliances and gadgets',
            'parent_id' => $kitchen->id,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Level 3: Child Categories under Washing Machine
        $familyWashing = $this->createCategory([
            'name' => 'Family Washing Machine',
            'slug' => 'family-washing-machine',
            'description' => 'Large capacity washing machines for families',
            'parent_id' => $washingMachine->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $smallWashing = $this->createCategory([
            'name' => 'Small Washing Machine',
            'slug' => 'small-washing-machine',
            'description' => 'Compact washing machines for small spaces',
            'parent_id' => $washingMachine->id,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $frontLoad = $this->createCategory([
            'name' => 'Front Load Washing Machine',
            'slug' => 'front-load-washing-machine',
            'description' => 'Front loading washing machines',
            'parent_id' => $washingMachine->id,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $topLoad = $this->createCategory([
            'name' => 'Top Load Washing Machine',
            'slug' => 'top-load-washing-machine',
            'description' => 'Top loading washing machines',
            'parent_id' => $washingMachine->id,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        // Level 3: Child Categories under Mobile Phone
        $smartphones = $this->createCategory([
            'name' => 'Smartphones',
            'slug' => 'smartphones',
            'description' => 'Latest smartphones from top brands',
            'parent_id' => $mobilePhone->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $accessories = $this->createCategory([
            'name' => 'Mobile Accessories',
            'slug' => 'mobile-accessories',
            'description' => 'Cases, chargers, and mobile accessories',
            'parent_id' => $mobilePhone->id,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Level 3: Child Categories under Bathrobes
        $cottonBathrobes = $this->createCategory([
            'name' => 'Cotton Bathrobes',
            'slug' => 'cotton-bathrobes',
            'description' => 'Soft cotton bathrobes',
            'parent_id' => $bathrobes->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $terryBathrobes = $this->createCategory([
            'name' => 'Terry Cloth Bathrobes',
            'slug' => 'terry-cloth-bathrobes',
            'description' => 'Luxurious terry cloth bathrobes',
            'parent_id' => $bathrobes->id,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Level 3: Child Categories under Furniture
        $sofa = $this->createCategory([
            'name' => 'Sofa & Couches',
            'slug' => 'sofa-couches',
            'description' => 'Comfortable sofas and couches',
            'parent_id' => $furniture->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $bedroom = $this->createCategory([
            'name' => 'Bedroom Furniture',
            'slug' => 'bedroom-furniture',
            'description' => 'Beds, wardrobes, and bedroom sets',
            'parent_id' => $furniture->id,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Level 3: Child Categories under Men's Wear
        $mensShirts = $this->createCategory([
            'name' => "Men's Shirts",
            'slug' => 'mens-shirts',
            'description' => 'Formal and casual shirts for men',
            'parent_id' => $mensWear->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $mensPants = $this->createCategory([
            'name' => "Men's Pants",
            'slug' => 'mens-pants',
            'description' => 'Jeans, trousers, and pants for men',
            'parent_id' => $mensWear->id,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Level 3: Child Categories under Women's Wear
        $womensDresses = $this->createCategory([
            'name' => "Women's Dresses",
            'slug' => 'womens-dresses',
            'description' => 'Elegant dresses for all occasions',
            'parent_id' => $womensWear->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $womensTops = $this->createCategory([
            'name' => "Women's Tops",
            'slug' => 'womens-tops',
            'description' => 'Tops, blouses, and shirts for women',
            'parent_id' => $womensWear->id,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Add some demo images (using placeholder paths - replace with actual images)
        $this->addDemoImages($electronics, 'electronics');
        $this->addDemoImages($homeDecor, 'home-decor');
        $this->addDemoImages($fashion, 'fashion');
        $this->addDemoImages($washingMachine, 'washing-machine');
        $this->addDemoImages($mobilePhone, 'mobile-phone');
        $this->addDemoImages($bathrobes, 'bathrobes');

        $this->command->info('✅ Categories seeded successfully!');
        $this->command->info('   - Level 1 Categories: 5');
        $this->command->info('   - Level 2 Subcategories: 12');
        $this->command->info('   - Level 3 Child Categories: 15');
    }

    /**
     * Create a category
     *
     * @param array $data
     * @return Category
     */
    private function createCategory(array $data): Category
    {
        return Category::create($data);
    }

    /**
     * Add demo images to category
     *
     * @param Category $category
     * @param string $type
     * @return void
     */
    private function addDemoImages(Category $category, string $type): void
    {
        // Create primary image
        CategoryImage::create([
            'category_id' => $category->id,
            'image_path' => "categories/demo/{$type}-primary.jpg",
            'image_type' => CategoryImage::TYPE_PRIMARY,
            'alt_text' => $category->name . ' - Primary Image',
            'width' => 800,
            'height' => 800,
            'file_size' => 150000,
            'position' => 0,
            'is_primary' => true,
            'storage_disk' => 'public',
            'mime_type' => 'image/jpeg',
        ]);

        // Create thumbnail
        CategoryImage::create([
            'category_id' => $category->id,
            'image_path' => "categories/demo/{$type}-thumb.jpg",
            'image_type' => CategoryImage::TYPE_THUMBNAIL,
            'alt_text' => $category->name . ' - Thumbnail',
            'width' => 200,
            'height' => 200,
            'file_size' => 20000,
            'position' => 1,
            'is_primary' => false,
            'storage_disk' => 'public',
            'mime_type' => 'image/jpeg',
        ]);
    }
}
