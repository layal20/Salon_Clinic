<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Salon;
use App\Models\Service;
use App\Models\SuperAdmin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Salon>
 */
class SalonFactory extends Factory
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
            'logo_image' => fake()->unique()->imageUrl(300, 300),
            'description' => $this->faker->sentence(15),
            'status' => fake()->randomElement(['active', 'inactive']),
            'super_admin_id' => SuperAdmin::inRandomOrder()->first()->id,
            'latitude' => fake()->unique()->latitude(),
            'longitude' => fake()->unique()->longitude(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Salon $salon) {
            Admin::factory()->create(['salon_id' => $salon->id]);

            Employee::factory()->count(7)->create(['salon_id' => $salon->id]);

            $products = Product::factory()->count(10)->create();
            foreach ($products as $product) {
                $salon->products()->attach($product->id, ['quantity' => $this->faker->numberBetween(1, 100)]);
            }

            $services = Service::factory()->count(7)->create();
            foreach ($services as $service) {
                $salon->services()->attach($service->id);
            }
        });
    }
}
