<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->unique()->name(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'price' => $this->faker->numberBetween(10000, 100000),
            'admin_id' => Admin::inRandomOrder()->first()->id,
            'date' => fake()->date(),
            'description' => $this->faker->sentence(15),
            'time' => fake()->time(),
            'employee_id' => Employee::inRandomOrder()->first()->id,
        ];
    }

    
}
