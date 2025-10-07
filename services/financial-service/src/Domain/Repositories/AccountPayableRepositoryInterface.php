<?php

declare(strict_types=1);

namespace Src\Domain\Repositories;

use DateTimeImmutable;
use Src\Domain\Entities\AccountPayable;
use Src\Domain\ValueObjects\AccountPayableId;
use Src\Domain\ValueObjects\PaymentStatus;
use Src\Domain\ValueObjects\SupplierId;

/**
 * AccountPayableRepositoryInterface
 * 
 * Contrato para persistência de Contas a Pagar.
 */
interface AccountPayableRepositoryInterface
{
    /**
     * Salva uma conta a pagar (create ou update)
     */
    public function save(AccountPayable $account): void;

    /**
     * Busca uma conta por ID
     */
    public function findById(AccountPayableId $id): ?AccountPayable;

    /**
     * Lista todas as contas
     * 
     * @return array<AccountPayable>
     */
    public function findAll(): array;

    /**
     * Lista contas por fornecedor
     * 
     * @return array<AccountPayable>
     */
    public function findBySupplier(SupplierId $supplierId): array;

    /**
     * Lista contas por status
     * 
     * @return array<AccountPayable>
     */
    public function findByStatus(PaymentStatus $status): array;

    /**
     * Lista contas vencidas até uma data
     * 
     * @return array<AccountPayable>
     */
    public function findOverdueUntil(DateTimeImmutable $date): array;

    /**
     * Lista contas a vencer em um período
     * 
     * @return array<AccountPayable>
     */
    public function findDueBetween(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array;

    /**
     * Lista contas com paginação e filtros
     * 
     * @param array{status?: string, supplier_id?: string, due_date_from?: string, due_date_to?: string} $filters
     * @return array{data: array<AccountPayable>, total: int, page: int, per_page: int}
     */
    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array;

    /**
     * Remove uma conta
     */
    public function delete(AccountPayableId $id): void;
}


