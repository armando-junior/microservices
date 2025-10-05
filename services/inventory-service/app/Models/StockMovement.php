<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockMovement Eloquent Model
 * 
 * @property string $id
 * @property string $stock_id
 * @property string $type
 * @property int $quantity
 * @property int $previous_quantity
 * @property int $new_quantity
 * @property string $reason
 * @property string|null $reference_id
 * @property \Illuminate\Support\Carbon $created_at
 */
class StockMovement extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_movements';
    
    protected $keyType = 'string';
    
    public $incrementing = false;
    
    public $timestamps = false; // Apenas created_at, sem updated_at

    protected $fillable = [
        'id',
        'stock_id',
        'type',
        'quantity',
        'previous_quantity',
        'new_quantity',
        'reason',
        'reference_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'previous_quantity' => 'integer',
        'new_quantity' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Relação com Stock
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * Boot method para garantir created_at
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->created_at) {
                $model->created_at = now();
            }
        });
    }
}

