<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Customer\GetCustomer;

use Src\Application\DTOs\CustomerDTO;
use Src\Application\Exceptions\CustomerNotFoundException;
use Src\Domain\Repositories\CustomerRepositoryInterface;
use Src\Domain\ValueObjects\CustomerId;

/**
 * Get Customer Use Case
 * 
 * Caso de uso para buscar um cliente por ID.
 */
final class GetCustomerUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {
    }

    public function execute(string $id): CustomerDTO
    {
        $customerId = CustomerId::fromString($id);

        $customer = $this->customerRepository->findById($customerId);

        if (!$customer) {
            throw CustomerNotFoundException::forId($id);
        }

        return CustomerDTO::fromEntity($customer);
    }
}
