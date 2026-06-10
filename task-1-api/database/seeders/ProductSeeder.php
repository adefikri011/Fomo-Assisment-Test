<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::create([
            'name' => 'Flash Sale Laptop',
            'description' => 'High performance laptop',
            'price' => 15000000,
            'flash_sale_price' => 10000000,
            'is_flash_sale' => true,
            'stock' => 10
        ]);
    }
}