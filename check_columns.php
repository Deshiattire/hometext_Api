<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get all columns
$columns = Illuminate\Support\Facades\Schema::getColumnListing('products');
echo "Products Table Columns:\n";
echo "========================\n";
foreach ($columns as $column) {
    echo "- " . $column . "\n";
}

// Check for specific columns
echo "\n\nChecking specific columns:\n";
echo "===========================\n";
$checkColumns = ['old_price', 'discount_fixed', 'discount_percent', 'discount_start', 'discount_end'];
foreach ($checkColumns as $col) {
    $exists = in_array($col, $columns) ? "YES" : "NO";
    echo "$col: $exists\n";
}
