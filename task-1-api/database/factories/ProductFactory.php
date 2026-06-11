<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $price = fake()->numberBetween(100000, 5000000);

        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price' => $price,
            'flash_sale_price' => null,
            'is_flash_sale' => false,
            'stock' => fake()->numberBetween(5, 25),
        ];
    }
}
