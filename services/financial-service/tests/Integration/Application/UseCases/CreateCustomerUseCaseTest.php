<?php

declare(strict_types=1);

namespace Tests\Integration\Application\UseCases;

use Tests\IntegrationTestCase;
use Src\Application\UseCases\Customer\CreateCustomer\CreateCustomerUseCase;
use Src\Application\UseCases\Customer\CreateCustomer\CreateCustomerDTO;
use Src\Application\Exceptions\EmailAlreadyExistsException;
use Src\Application\Exceptions\DocumentAlreadyExistsException;
use Src\Infrastructure\Persistence\EloquentCustomerRepository;

class CreateCustomerUseCaseTest extends IntegrationTestCase
{
    private CreateCustomerUseCase $useCase;
    private EloquentCustomerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new EloquentCustomerRepository();
        $this->useCase = new CreateCustomerUseCase($this->repository);
    }

    /** @test */
    public function it_creates_customer_successfully(): void
    {
        $dto = new CreateCustomerDTO(
            name: 'João Silva',
            email: 'joao@example.com',
            phone: '11987654321',
            document: '11144477735',
            addressStreet: 'Rua ABC',
            addressNumber: '123',
            addressCity: 'São Paulo',
            addressState: 'SP',
            addressZipCode: '01234567'
        );

        $result = $this->useCase->execute($dto);

        $this->assertNotNull($result->id);
        $this->assertEquals('João Silva', $result->name);
        $this->assertEquals('joao@example.com', $result->email);
        $this->assertEquals('active', $result->status);
        $this->assertDatabaseHas('customers', [
            'email' => 'joao@example.com',
            'document' => '11144477735',
        ]);
    }

    /** @test */
    public function it_throws_exception_when_email_already_exists(): void
    {
        $this->expectException(EmailAlreadyExistsException::class);

        // Create first customer
        $dto1 = new CreateCustomerDTO(
            name: 'João Silva',
            email: 'joao@example.com',
            phone: '11987654321',
            document: '11144477735'
        );
        $this->useCase->execute($dto1);

        // Try to create second customer with same email (using different valid CPF)
        $dto2 = new CreateCustomerDTO(
            name: 'Maria Silva',
            email: 'joao@example.com',
            phone: '11999998888',
            document: '52998224725' // Valid CPF
        );
        $this->useCase->execute($dto2);
    }

    /** @test */
    public function it_throws_exception_when_document_already_exists(): void
    {
        $this->expectException(DocumentAlreadyExistsException::class);

        // Create first customer
        $dto1 = new CreateCustomerDTO(
            name: 'João Silva',
            email: 'joao@example.com',
            phone: '11987654321',
            document: '11144477735'
        );
        $this->useCase->execute($dto1);

        // Try to create second customer with same document
        $dto2 = new CreateCustomerDTO(
            name: 'Maria Silva',
            email: 'maria@example.com',
            phone: '11999998888',
            document: '11144477735'
        );
        $this->useCase->execute($dto2);
    }

    /** @test */
    public function it_stores_address_correctly(): void
    {
        $dto = new CreateCustomerDTO(
            name: 'João Silva',
            email: 'joao@example.com',
            phone: '11987654321',
            document: '11144477735',
            addressStreet: 'Rua ABC',
            addressNumber: '123',
            addressComplement: 'Apto 45',
            addressCity: 'São Paulo',
            addressState: 'SP',
            addressZipCode: '01234567'
        );

        $result = $this->useCase->execute($dto);

        $this->assertEquals('Rua ABC', $result->addressStreet);
        $this->assertEquals('123', $result->addressNumber);
        $this->assertEquals('Apto 45', $result->addressComplement);
        $this->assertEquals('São Paulo', $result->addressCity);
        $this->assertEquals('SP', $result->addressState);
        $this->assertEquals('01234567', $result->addressZipCode);
    }
}
