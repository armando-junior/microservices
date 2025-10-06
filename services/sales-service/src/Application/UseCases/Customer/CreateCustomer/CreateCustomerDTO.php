<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Customer\CreateCustomer;

/**
 * Create Customer Data Transfer Object
 */
final class CreateCustomerDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $document,
        public readonly ?string $addressStreet = null,
        public readonly ?string $addressNumber = null,
        public readonly ?string $addressComplement = null,
        public readonly ?string $addressCity = null,
        public readonly ?string $addressState = null,
        public readonly ?string $addressZipCode = null
    ) {
    }
}
