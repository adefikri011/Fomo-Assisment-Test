<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class FlashSaleRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    public function test_flash_sale_race_condition_does_not_create_negative_stock()
    {
        $product = Product::factory()->create([
            'name' => 'Sepatu Flash Sale Super',
            'price' => 100000,
            'stock' => 5
        ]);

        $successfulOrders = 0;
        $failedOrders = 0;
        $totalRequests = 20;

        for ($i = 0; $i < $totalRequests; $i++) {
            $response = $this->postJson('/api/orders', [
                'customer_name' => "User Ke-{$i}",
                'customer_email' => "user{$i}@fomotest.com",
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1
                    ]
                ]
            ]);

            if ($response->status() === 201) {
                $successfulOrders++;
            } else {
                $failedOrders++;
            }
        }

        $product->refresh();

        $this->assertGreaterThanOrEqual(0, $product->stock, "Kritis: Stok bocor menjadi minus!");

        $this->assertEquals(0, $product->stock, "Kritis: Stok tidak habis terjual seluruhnya.");

        $this->assertEquals(5, $successfulOrders, "Kritis: Jumlah order yang berhasil ({$successfulOrders}) tidak sesuai dengan kuota stok awal.");

        $this->assertEquals(15, $failedOrders, "Kritis: Sistem tidak menggagalkan request yang kehabisan stok.");

        $this->assertEquals(5, Order::count(), "Kritis: Jumlah data di tabel orders tidak sinkron dengan stok.");
    }
}
