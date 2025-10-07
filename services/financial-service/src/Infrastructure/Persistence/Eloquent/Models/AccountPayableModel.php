<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * AccountPayableModel
 * 
 * Eloquent Model para contas a pagar.
 * 
 * @property string $id
 * @property string $supplier_id
 * @property string $category_id
 * @property string $description
 * @property int $amount_cents
 * @property \Carbon\Carbon $issue_date
 * @property \Carbon\Carbon $due_date
 * @property string $status (pending|paid|overdue|cancelled)
 * @property \Carbon\Carbon|null $paid_at
 * @property string|null $payment_notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AccountPayableModel extends Model
{
    use HasUuids;

    protected $table = 'accounts_payable';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'supplier_id',
        'category_id',
        'description',
        'amount_cents',
        'issue_date',
        'due_date',
        'status',
        'paid_at',
        'payment_notes',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'issue_date' => 'datetime',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento: Fornecedor
     */
    public function supplier()
    {
        return $this->belongsTo(SupplierModel::class, 'supplier_id');
    }

    /**
     * Relacionamento: Categoria
     */
    public function category()
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }
}


