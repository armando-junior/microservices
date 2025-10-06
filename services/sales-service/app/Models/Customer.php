<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Customer Eloquent Model
 * 
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $document
 * @property string|null $address_street
 * @property string|null $address_number
 * @property string|null $address_complement
 * @property string|null $address_city
 * @property string|null $address_state
 * @property string|null $address_zip_code
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Customer extends Model
{
    protected $table = 'customers';
    
    protected $keyType = 'string';
    
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'email',
        'phone',
        'document',
        'address_street',
        'address_number',
        'address_complement',
        'address_city',
        'address_state',
        'address_zip_code',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com pedidos
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }
}
