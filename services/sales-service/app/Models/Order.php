<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Order Eloquent Model
 * 
 * @property string $id
 * @property string $order_number
 * @property string $customer_id
 * @property string $status
 * @property float $subtotal
 * @property float $discount
 * @property float $total
 * @property string $payment_status
 * @property string|null $payment_method
 * @property string|null $notes
 * @property \Carbon\Carbon|null $confirmed_at
 * @property \Carbon\Carbon|null $cancelled_at
 * @property \Carbon\Carbon|null $delivered_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Order extends Model
{
    protected $table = 'orders';
    
    protected $keyType = 'string';
    
    public $incrementing = false;

    protected $fillable = [
        'id',
        'order_number',
        'customer_id',
        'status',
        'subtotal',
        'discount',
        'total',
        'payment_status',
        'payment_method',
        'notes',
        'confirmed_at',
        'cancelled_at',
        'delivered_at',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'discount' => 'float',
        'total' => 'float',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com cliente
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Relacionamento com itens
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
