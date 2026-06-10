<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'price',
        'flash_sale_price',
        'is_flash_sale',
        'stock',
    ];

    protected $casts = [
        'price'            => 'decimal:2',
        'flash_sale_price' => 'decimal:2',
        'is_flash_sale'    => 'boolean',
        'stock'            => 'integer',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getActivePriceAttribute(): float
    {
        if ($this->is_flash_sale && $this->flash_sale_price) {
            return (float) $this->flash_sale_price;
        }

        return (float) $this->price;
    }

    public function isStockAvailable(int $quantity): bool
    {
        return $this->stock >= $quantity;
    }


    public function decrementStock(int $quantity): bool
    {
        if (!$this->isStockAvailable($quantity)) {
            return false;
        }

        $this->decrement('stock', $quantity);
        return true;
    }
}
