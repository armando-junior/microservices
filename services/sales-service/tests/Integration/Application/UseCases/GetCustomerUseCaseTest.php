<?php

declare(strict_types=1);

namespace Tests\Integration\Application\UseCases;

use Tests\IntegrationTestCase;
use Src\Application\UseCases\Customer\GetCustomer\GetCustomerUseCase;
use Src\Application\Exceptions\CustomerNotFoundException;
use Src\Infrastructure\Persistence\EloquentCustomerRepository;
use Src\Domain\Entities\Customer;
use Src\Domain\ValueObjects\CustomerId;
use Src\Domain\ValueObjects\CustomerName;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Phone;
use Src\Domain\ValueObjects\Document;

class GetCustomerUseCaseTest extends IntegrationTestCase
{
    private GetCustomerUseCase $useCase;
    private EloquentCustomerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new EloquentCustomerRepository();
        $this->useCase = new GetCustomerUseCase($this->repository);
    }

    /** @test */
    public function it_gets_customer_successfully(): void
    {
        // Create a customer
        $customer = $this->createCustomer();

        // Get the customer
        $result = $this->useCase->execute($customer->getId()->value());

        $this->assertEquals($customer->getId()->value(), $result->id);
        $this->assertEquals('Test Customer', $result->name);
        $this->assertEquals('test@example.com', $result->email);
        $this->assertEquals('active', $result->status);
    }

    /** @test */
    public function it_throws_exception_when_customer_not_found(): void
    {
        $this->expectException(CustomerNotFoundException::class);

        $this->useCase->execute('00000000-0000-0000-0000-000000000000');
    }

    /** @test */
    public function it_returns_customer_with_address(): void
    {
        // Create customer with address
        $customer = Customer::create(
            id: CustomerId::generate(),
            name: CustomerName::fromString('João Silva'),
            email: Email::fromString('joao@example.com'),
            phone: Phone::fromString('11987654321'),
            document: Document::fromString('11144477735'),
            addressStreet: 'Rua ABC',
            addressNumber: '123',
            addressComplement: 'Apto 45',
            addressCity: 'São Paulo',
            addressState: 'SP',
            addressZipCode: '01234567'
        );

        $this->repository->save($customer);

        // Get the customer
        $result = $this->useCase->execute($customer->getId()->value());

        $this->assertEquals('Rua ABC', $result->addressStreet);
        $this->assertEquals('123', $result->addressNumber);
        $this->assertEquals('Apto 45', $result->addressComplement);
        $this->assertEquals('São Paulo', $result->addressCity);
        $this->assertEquals('SP', $result->addressState);
        $this->assertEquals('01234567', $result->addressZipCode);
    }

    /** @test */
    public function it_returns_formatted_phone_and_document(): void
    {
        $customer = $this->createCustomer();

        $result = $this->useCase->execute($customer->getId()->value());

        $this->assertNotNull($result->phoneFormatted);
        $this->assertNotNull($result->documentFormatted);
        $this->assertEquals('CPF', $result->documentType);
    }

    private function createCustomer(): Customer
    {
        $customer = Customer::create(
            id: CustomerId::generate(),
            name: CustomerName::fromString('Test Customer'),
            email: Email::fromString('test@example.com'),
            phone: Phone::fromString('11987654321'),
            document: Document::fromString('11144477735')
        );

        $this->repository->save($customer);

        return $customer;
    }
}

