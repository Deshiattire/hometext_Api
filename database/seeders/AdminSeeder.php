<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;


class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

      $admin = User::create([
         'first_name' => 'Admin',
         'last_name' => 'User',
        'email' => 'admin@hometexbd.ltd',
        'phone' => '01616101090',
        'password' => Hash::make('12345678'),
        'user_type' => 'admin',
        'status' => 'active',
        ]);
        
      // Assign admin role using Spatie Permission
      if (method_exists($admin, 'assignRole')) {
          $admin->assignRole('admin');
      }
    }
}
