<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Salon;
use App\Models\Service;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call(RoleAndPermissionSeeder::class);
        $this->call(AssignRoleSeeder::class);
        $this->call(SuperAdminSeeder::class);
        Salon::factory(10)->create();
        Customer::factory(30)->create();
    }
}
