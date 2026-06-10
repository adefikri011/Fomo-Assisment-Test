<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {

            $order = Order::create([
                'customer_name'  => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'total_price'    => 0,
                'status'         => 'pending'
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {

                $product = Product::where('id', $item['product_id'])
                    ->lockForUpdate()
                    ->first();

                if ($product->stock < $item['quantity']) {
                    throw new \Exception('Insufficient stock');
                }

                $price = $product->is_flash_sale
                    ? $product->flash_sale_price
                    : $product->price;

                $subtotal = $price * $item['quantity'];

                OrderItem::create([
                    'order_id'            => $order->id,
                    'product_id'          => $product->id,
                    'quantity'            => $item['quantity'],
                    'price'               => $price,
                    'is_flash_sale_price' => $product->is_flash_sale,
                    'subtotal'            => $subtotal
                ]);

                $product->decrement('stock', $item['quantity']);

                $total += $subtotal;
            }

            $order->update(['total_price' => $total]);

            return $order->load('orderItems');
        });
    }
}
