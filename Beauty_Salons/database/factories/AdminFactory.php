<?php

namespace Database\Factories;

use App\Models\Salon;
use App\Models\SuperAdmin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_name' => fake()->unique()->userName(),
            'password' => Hash::make('1234567'),
            'super_admin_id' => SuperAdmin::inRandomOrder()->first()->id,
            'salon_id' => Salon::inRandomOrder()->first()->id, 
        ];
    }
}
