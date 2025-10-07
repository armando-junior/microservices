<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * CategoryModel
 * 
 * Eloquent Model para categorias financeiras.
 * 
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string $type (income|expense)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CategoryModel extends Model
{
    use HasUuids;

    protected $table = 'categories';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'description',
        'type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento: Contas a pagar
     */
    public function accountsPayable()
    {
        return $this->hasMany(AccountPayableModel::class, 'category_id');
    }

    /**
     * Relacionamento: Contas a receber
     */
    public function accountsReceivable()
    {
        return $this->hasMany(AccountReceivableModel::class, 'category_id');
    }
}


