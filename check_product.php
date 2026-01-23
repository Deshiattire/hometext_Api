<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$p = App\Models\Product::find(364);

echo "Product ID: 364\n";
echo "================\n";
echo "cost: " . $p->cost . "\n";
echo "price: " . $p->price . "\n";
echo "discount_fixed: " . $p->discount_fixed . "\n";
echo "discount_percent: " . $p->discount_percent . "\n";
echo "discount_start: " . $p->discount_start . "\n";
echo "discount_end: " . $p->discount_end . "\n";
echo "tax_rate: " . $p->tax_rate . "\n";
echo "tax_included: " . ($p->tax_included ? 'true' : 'false') . "\n";
echo "tax_class: " . $p->tax_class . "\n";
echo "updated_at: " . $p->updated_at . "\n";
