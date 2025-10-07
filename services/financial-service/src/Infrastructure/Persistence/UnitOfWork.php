<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Src\Application\Contracts\UnitOfWorkInterface;
use Throwable;

/**
 * UnitOfWork
 * 
 * Implementação do Unit of Work usando Laravel DB.
 */
class UnitOfWork implements UnitOfWorkInterface
{
    public function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    public function commit(): void
    {
        DB::commit();
    }

    public function rollback(): void
    {
        DB::rollBack();
    }

    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}


