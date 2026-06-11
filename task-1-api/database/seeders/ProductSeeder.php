<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::create([
            'name' => 'Flash Sale Laptop Pro',
            'description' => 'High performance laptop for professionals',
            'price' => 20000000,
            'flash_sale_price' => 15000000,
            'is_flash_sale' => true,
            'stock' => 10
        ]);

        Product::create([
            'name' => 'Flash Sale Gaming Mouse',
            'description' => 'High precision gaming mouse',
            'price' => 1000000,
            'flash_sale_price' => 650000,
            'is_flash_sale' => true,
            'stock' => 15
        ]);

        Product::factory()->count(8)->create([
            'is_flash_sale' => false,
            'flash_sale_price' => null,
        ]);
    }
}