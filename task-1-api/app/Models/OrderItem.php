<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'is_flash_sale_price',
        'subtotal',
    ];


    protected $casts = [
        'quantity'           => 'integer',
        'price'              => 'decimal:2',
        'subtotal'           => 'decimal:2',
        'is_flash_sale_price'=> 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function calculateSubtotal(): float
    {
        return $this->quantity * $this->price;
    }
}