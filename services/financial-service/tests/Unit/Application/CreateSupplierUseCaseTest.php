<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use Mockery;
use PHPUnit\Framework\TestCase;
use Src\Application\Contracts\EventPublisherInterface;
use Src\Application\DTOs\Supplier\CreateSupplierInputDTO;
use Src\Application\Exceptions\SupplierAlreadyExistsException;
use Src\Application\UseCases\Supplier\CreateSupplier\CreateSupplierUseCase;
use Src\Domain\Entities\Supplier;
use Src\Domain\Repositories\SupplierRepositoryInterface;

class CreateSupplierUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_supplier_successfully(): void
    {
        // Arrange
        $repository = Mockery::mock(SupplierRepositoryInterface::class);
        $eventPublisher = Mockery::mock(EventPublisherInterface::class);

        $repository->shouldReceive('existsByDocument')
            ->with('12345678000190')
            ->once()
            ->andReturn(false);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::type(Supplier::class));

        $eventPublisher->shouldReceive('publishAll')
            ->once();

        $useCase = new CreateSupplierUseCase($repository, $eventPublisher);

        $input = new CreateSupplierInputDTO(
            name: 'Fornecedor Teste Ltda',
            document: '12345678000190',
            email: 'test@supplier.com',
            phone: '11987654321',
            address: 'Rua Teste, 123'
        );

        // Act
        $output = $useCase->execute($input);

        // Assert
        $this->assertNotNull($output->id);
        $this->assertEquals('Fornecedor Teste Ltda', $output->name);
        $this->assertEquals('12345678000190', $output->document);
        $this->assertEquals('test@supplier.com', $output->email);
        $this->assertTrue($output->active);
    }

    public function test_it_throws_exception_when_document_already_exists(): void
    {
        // Arrange
        $repository = Mockery::mock(SupplierRepositoryInterface::class);
        $eventPublisher = Mockery::mock(EventPublisherInterface::class);

        $repository->shouldReceive('existsByDocument')
            ->with('12345678000190')
            ->once()
            ->andReturn(true);

        $useCase = new CreateSupplierUseCase($repository, $eventPublisher);

        $input = new CreateSupplierInputDTO(
            name: 'Fornecedor Teste',
            document: '12345678000190'
        );

        // Assert
        $this->expectException(SupplierAlreadyExistsException::class);
        $this->expectExceptionMessage('Supplier with document 12345678000190 already exists');

        // Act
        $useCase->execute($input);
    }

    public function test_it_creates_supplier_without_document(): void
    {
        // Arrange
        $repository = Mockery::mock(SupplierRepositoryInterface::class);
        $eventPublisher = Mockery::mock(EventPublisherInterface::class);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::type(Supplier::class));

        $eventPublisher->shouldReceive('publishAll')
            ->once();

        $useCase = new CreateSupplierUseCase($repository, $eventPublisher);

        $input = new CreateSupplierInputDTO(
            name: 'Fornecedor Teste'
        );

        // Act
        $output = $useCase->execute($input);

        // Assert
        $this->assertNotNull($output->id);
        $this->assertEquals('Fornecedor Teste', $output->name);
        $this->assertNull($output->document);
    }
}


