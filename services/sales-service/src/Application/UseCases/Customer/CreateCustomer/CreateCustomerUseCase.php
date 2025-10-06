<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Customer\CreateCustomer;

use Src\Application\DTOs\CustomerDTO;
use Src\Application\Exceptions\EmailAlreadyExistsException;
use Src\Application\Exceptions\DocumentAlreadyExistsException;
use Src\Domain\Entities\Customer;
use Src\Domain\Repositories\CustomerRepositoryInterface;
use Src\Domain\ValueObjects\CustomerId;
use Src\Domain\ValueObjects\CustomerName;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Phone;
use Src\Domain\ValueObjects\Document;

/**
 * Create Customer Use Case
 * 
 * Caso de uso para criar um novo cliente.
 */
final class CreateCustomerUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {
    }

    public function execute(CreateCustomerDTO $dto): CustomerDTO
    {
        // 1. Criar Value Objects
        $email = Email::fromString($dto->email);
        $document = Document::fromString($dto->document);

        // 2. Verificar duplicados
        if ($this->customerRepository->existsEmail($email)) {
            throw EmailAlreadyExistsException::withEmail($dto->email);
        }

        if ($this->customerRepository->existsDocument($document)) {
            throw DocumentAlreadyExistsException::withDocument($dto->document);
        }

        // 3. Criar entidade Customer
        $customer = Customer::create(
            id: CustomerId::generate(),
            name: CustomerName::fromString($dto->name),
            email: $email,
            phone: Phone::fromString($dto->phone),
            document: $document,
            addressStreet: $dto->addressStreet,
            addressNumber: $dto->addressNumber,
            addressComplement: $dto->addressComplement,
            addressCity: $dto->addressCity,
            addressState: $dto->addressState,
            addressZipCode: $dto->addressZipCode
        );

        // 4. Persistir
        $this->customerRepository->save($customer);

        // 5. Retornar DTO
        return CustomerDTO::fromEntity($customer);
    }
}
