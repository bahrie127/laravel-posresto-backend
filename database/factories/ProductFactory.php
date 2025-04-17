<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'description' => $this->faker->text,
            'price' => $this->faker->randomFloat(2, 1, 100),
            'image' => $this->faker->imageUrl(),
            'stock' => $this->faker->numberBetween(1, 100),
            'status' => $this->faker->boolean,
            'is_favorite' => $this->faker->boolean,
            'category_id' => $this->faker->numberBetween(1, 4),
        ];
    }
}
