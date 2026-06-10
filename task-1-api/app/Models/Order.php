<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'customer_email',
        'total_price',
        'status',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
    ];

    const STATUS_PENDING   = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function recalculateTotalPrice(): void
    {
        $total = $this->orderItems()->sum('subtotal');

        $this->update(['total_price' => $total]);
    }
    
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}