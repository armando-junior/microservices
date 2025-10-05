<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stock Eloquent Model
 * 
 * @property string $id
 * @property string $product_id
 * @property int $quantity
 * @property int $minimum_quantity
 * @property int|null $maximum_quantity
 * @property \Illuminate\Support\Carbon|null $last_movement_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Stock extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stocks';
    
    protected $keyType = 'string';
    
    public $incrementing = false;

    protected $fillable = [
        'id',
        'product_id',
        'quantity',
        'minimum_quantity',
        'maximum_quantity',
        'last_movement_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'minimum_quantity' => 'integer',
        'maximum_quantity' => 'integer',
        'last_movement_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relação com Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relação com StockMovements
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Verifica se o estoque está baixo
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->minimum_quantity && $this->quantity > 0;
    }

    /**
     * Verifica se o estoque está esgotado
     */
    public function isDepleted(): bool
    {
        return $this->quantity === 0;
    }
}

