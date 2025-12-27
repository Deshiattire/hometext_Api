<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking Sales Managers:\n";
$salesManagers = App\Models\SalesManager::all(['id', 'name', 'email', 'phone']);
echo "Count: " . $salesManagers->count() . "\n\n";

foreach ($salesManagers as $sm) {
    echo "ID: {$sm->id}, Name: {$sm->name}, Email: {$sm->email}, Phone: {$sm->phone}\n";
}

if ($salesManagers->count() === 0) {
    echo "\nNo sales managers found. Creating one...\n";
    
    $shop = App\Models\Shop::first();
    if (!$shop) {
        echo "No shop found. Creating shop first...\n";
        $user = App\Models\User::first();
        if (!$user) {
            echo "ERROR: No users found. Please create a user first.\n";
            exit(1);
        }
        
        $shop = App\Models\Shop::create([
            'name' => 'Main Branch',
            'phone' => '01712345678',
            'email' => 'shop@example.com',
            'details' => 'Main branch',
            'status' => 1,
            'user_id' => $user->id
        ]);
        echo "Shop created with ID: {$shop->id}\n";
    }
    
    $sm = App\Models\SalesManager::create([
        'name' => 'Test Sales Manager',
        'email' => 'sales@test.com',
        'phone' => '01800000000',
        'password' => bcrypt('password'),
        'status' => 1,
        'shop_id' => $shop->id
    ]);
    
    echo "Sales Manager created:\n";
    echo "Email: {$sm->email}\n";
    echo "Password: password\n";
}
