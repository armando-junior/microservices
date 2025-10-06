<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Customer\ListCustomers;

use Src\Application\DTOs\CustomerDTO;
use Src\Domain\Repositories\CustomerRepositoryInterface;

/**
 * List Customers Use Case
 * 
 * Lista clientes com filtros e paginação.
 */
final class ListCustomersUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {
    }

    /**
     * Executa o caso de uso
     * 
     * @return CustomerDTO[]
     */
    public function execute(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $customers = $this->customerRepository->list($filters, $page, $perPage);
        
        return array_map(
            fn($customer) => CustomerDTO::fromEntity($customer),
            $customers
        );
    }

    /**
     * Retorna o total de clientes (para paginação)
     */
    public function count(array $filters = []): int
    {
        return $this->customerRepository->count($filters);
    }
}
