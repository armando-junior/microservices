<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Product Eloquent Model
 * 
 * @property string $id
 * @property string $name
 * @property string $sku
 * @property float $price
 * @property string|null $category_id
 * @property string|null $barcode
 * @property string|null $description
 * @property string $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Product extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'products';
    
    protected $keyType = 'string';
    
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'sku',
        'price',
        'category_id',
        'barcode',
        'description',
        'status',
    ];

    protected $casts = [
        'price' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relação com Category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relação com Stock
     */
    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class);
    }
}

