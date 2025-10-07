<?php

declare(strict_types=1);

namespace Src\Application\UseCases\AccountReceivable\ListAccountsReceivable;

use Src\Application\DTOs\AccountReceivable\AccountReceivableOutputDTO;
use Src\Domain\Repositories\AccountReceivableRepositoryInterface;

/**
 * ListAccountsReceivableUseCase
 * 
 * Caso de uso para listagem de contas a receber com paginação e filtros.
 */
final class ListAccountsReceivableUseCase
{
    public function __construct(
        private readonly AccountReceivableRepositoryInterface $accountReceivableRepository
    ) {
    }

    /**
     * Executa o caso de uso
     * 
     * @param array{status?: string, customer_id?: string, due_date_from?: string, due_date_to?: string} $filters
     * @return array{data: array<AccountReceivableOutputDTO>, total: int, page: int, per_page: int}
     */
    public function execute(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $result = $this->accountReceivableRepository->paginate($page, $perPage, $filters);

        return [
            'data' => array_map(
                fn($account) => AccountReceivableOutputDTO::fromEntity($account),
                $result['data']
            ),
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
        ];
    }
}


