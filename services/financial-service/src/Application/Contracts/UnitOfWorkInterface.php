<?php

declare(strict_types=1);

namespace Src\Application\Contracts;

/**
 * UnitOfWorkInterface
 * 
 * Contrato para gerenciamento de transações.
 */
interface UnitOfWorkInterface
{
    /**
     * Inicia uma transação
     */
    public function beginTransaction(): void;

    /**
     * Confirma uma transação
     */
    public function commit(): void;

    /**
     * Desfaz uma transação
     */
    public function rollback(): void;

    /**
     * Executa uma operação dentro de uma transação
     * 
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback): mixed;
}


