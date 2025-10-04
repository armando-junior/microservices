<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * User Eloquent Model
 * 
 * Bridge entre Eloquent ORM e Domain Entity.
 * Este model é usado apenas na camada de Infrastructure.
 */
class UserModel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'users';

    protected $keyType = 'string';
    
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'is_active',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

