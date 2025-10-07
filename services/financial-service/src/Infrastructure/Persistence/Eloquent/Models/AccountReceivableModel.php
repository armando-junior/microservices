<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * AccountReceivableModel
 * 
 * Eloquent Model para contas a receber.
 * 
 * @property string $id
 * @property string $customer_id
 * @property string $category_id
 * @property string $description
 * @property int $amount_cents
 * @property \Carbon\Carbon $issue_date
 * @property \Carbon\Carbon $due_date
 * @property string $status (pending|received|overdue|cancelled)
 * @property \Carbon\Carbon|null $received_at
 * @property string|null $receiving_notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AccountReceivableModel extends Model
{
    use HasUuids;

    protected $table = 'accounts_receivable';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'customer_id',
        'category_id',
        'description',
        'amount_cents',
        'issue_date',
        'due_date',
        'status',
        'received_at',
        'receiving_notes',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'issue_date' => 'datetime',
        'due_date' => 'datetime',
        'received_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento: Categoria
     */
    public function category()
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }
}


