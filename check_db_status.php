<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Database Status Check ===\n\n";

// Product count
$productCount = App\Models\Product::count();
echo "Products count: " . $productCount . "\n";

// Check related tables
$tables = [
    'categories' => App\Models\Category::class,
    'brands' => App\Models\Brand::class,
    'shops' => App\Models\Shop::class,
];

foreach ($tables as $name => $model) {
    try {
        $count = $model::count();
        echo "$name count: $count\n";
    } catch (Exception $e) {
        echo "$name: Error - " . $e->getMessage() . "\n";
    }
}

// Check if demo_data.sql exists for restore
echo "\n=== Backup Files ===\n";
if (file_exists('database/demo_data.sql')) {
    echo "✓ database/demo_data.sql exists\n";
} else {
    echo "✗ database/demo_data.sql not found\n";
}
