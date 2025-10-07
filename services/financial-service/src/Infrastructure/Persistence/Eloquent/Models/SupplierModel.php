<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * SupplierModel
 * 
 * Eloquent Model para fornecedores.
 * 
 * @property string $id
 * @property string $name
 * @property string|null $document
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property bool $active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SupplierModel extends Model
{
    use HasUuids;

    protected $table = 'suppliers';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'document',
        'email',
        'phone',
        'address',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento: Contas a pagar
     */
    public function accountsPayable()
    {
        return $this->hasMany(AccountPayableModel::class, 'supplier_id');
    }
}


