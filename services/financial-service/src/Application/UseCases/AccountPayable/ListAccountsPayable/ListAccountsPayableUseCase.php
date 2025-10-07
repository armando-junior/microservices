<?php

declare(strict_types=1);

namespace Src\Application\UseCases\AccountPayable\ListAccountsPayable;

use Src\Application\DTOs\AccountPayable\AccountPayableOutputDTO;
use Src\Domain\Repositories\AccountPayableRepositoryInterface;

/**
 * ListAccountsPayableUseCase
 * 
 * Caso de uso para listagem de contas a pagar com paginação e filtros.
 */
final class ListAccountsPayableUseCase
{
    public function __construct(
        private readonly AccountPayableRepositoryInterface $accountPayableRepository
    ) {
    }

    /**
     * Executa o caso de uso
     * 
     * @param array{status?: string, supplier_id?: string, due_date_from?: string, due_date_to?: string} $filters
     * @return array{data: array<AccountPayableOutputDTO>, total: int, page: int, per_page: int}
     */
    public function execute(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $result = $this->accountPayableRepository->paginate($page, $perPage, $filters);

        return [
            'data' => array_map(
                fn($account) => AccountPayableOutputDTO::fromEntity($account),
                $result['data']
            ),
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
        ];
    }
}


