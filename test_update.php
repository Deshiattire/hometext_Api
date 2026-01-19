<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$p = App\Models\Product::find(364);

echo "BEFORE UPDATE:\n";
echo "==============\n";
echo "old_price: " . $p->old_price . "\n";
echo "discount_fixed: " . $p->discount_fixed . "\n";
echo "discount_percent: " . $p->discount_percent . "\n";
echo "discount_start: " . $p->discount_start . "\n";
echo "discount_end: " . $p->discount_end . "\n";

// Test update
$p->update([
    'old_price' => 600,
    'discount_fixed' => 100,
    'discount_percent' => 20,
    'discount_start' => '2026-02-01',
    'discount_end' => '2026-02-28',
]);

$p->refresh();

echo "\nAFTER UPDATE:\n";
echo "=============\n";
echo "old_price: " . $p->old_price . "\n";
echo "discount_fixed: " . $p->discount_fixed . "\n";
echo "discount_percent: " . $p->discount_percent . "\n";
echo "discount_start: " . $p->discount_start . "\n";
echo "discount_end: " . $p->discount_end . "\n";
echo "updated_at: " . $p->updated_at . "\n";

echo "\n✅ If values changed above, the fix worked!\n";
