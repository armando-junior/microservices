<?php

declare(strict_types=1);

namespace Src\Domain\Repositories;

use DateTimeImmutable;
use Src\Domain\Entities\AccountReceivable;
use Src\Domain\ValueObjects\AccountReceivableId;
use Src\Domain\ValueObjects\ReceivableStatus;

/**
 * AccountReceivableRepositoryInterface
 * 
 * Contrato para persistência de Contas a Receber.
 */
interface AccountReceivableRepositoryInterface
{
    /**
     * Salva uma conta a receber (create ou update)
     */
    public function save(AccountReceivable $account): void;

    /**
     * Busca uma conta por ID
     */
    public function findById(AccountReceivableId $id): ?AccountReceivable;

    /**
     * Lista todas as contas
     * 
     * @return array<AccountReceivable>
     */
    public function findAll(): array;

    /**
     * Lista contas por cliente
     * 
     * @return array<AccountReceivable>
     */
    public function findByCustomer(string $customerId): array;

    /**
     * Lista contas por status
     * 
     * @return array<AccountReceivable>
     */
    public function findByStatus(ReceivableStatus $status): array;

    /**
     * Lista contas vencidas até uma data
     * 
     * @return array<AccountReceivable>
     */
    public function findOverdueUntil(DateTimeImmutable $date): array;

    /**
     * Lista contas a vencer em um período
     * 
     * @return array<AccountReceivable>
     */
    public function findDueBetween(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array;

    /**
     * Lista contas com paginação e filtros
     * 
     * @param array{status?: string, customer_id?: string, due_date_from?: string, due_date_to?: string} $filters
     * @return array{data: array<AccountReceivable>, total: int, page: int, per_page: int}
     */
    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array;

    /**
     * Remove uma conta
     */
    public function delete(AccountReceivableId $id): void;
}


