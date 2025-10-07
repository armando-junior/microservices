<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Supplier\ListSuppliers;

use Src\Application\DTOs\Supplier\SupplierOutputDTO;
use Src\Domain\Repositories\SupplierRepositoryInterface;

/**
 * ListSuppliersUseCase
 * 
 * Caso de uso para listagem de fornecedores com paginação.
 */
final class ListSuppliersUseCase
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository
    ) {
    }

    /**
     * Executa o caso de uso
     * 
     * @return array{data: array<SupplierOutputDTO>, total: int, page: int, per_page: int}
     */
    public function execute(int $page = 1, int $perPage = 15): array
    {
        $result = $this->supplierRepository->paginate($page, $perPage);

        return [
            'data' => array_map(
                fn($supplier) => SupplierOutputDTO::fromEntity($supplier),
                $result['data']
            ),
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
        ];
    }
}


