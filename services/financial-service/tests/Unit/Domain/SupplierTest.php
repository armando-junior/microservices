<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Src\Domain\Entities\Supplier;
use Src\Domain\Events\SupplierCreated;
use Src\Domain\Exceptions\InvalidSupplierException;
use Src\Domain\ValueObjects\SupplierName;

class SupplierTest extends TestCase
{
    public function test_it_creates_supplier_successfully(): void
    {
        $name = SupplierName::fromString('Fornecedor Teste Ltda');
        
        $supplier = Supplier::create(
            name: $name,
            document: '12345678000190',
            email: 'supplier@test.com',
            phone: '11987654321',
            address: 'Rua Teste, 123'
        );

        $this->assertNotNull($supplier->id());
        $this->assertEquals('Fornecedor Teste Ltda', $supplier->name()->value());
        $this->assertEquals('12345678000190', $supplier->document());
        $this->assertEquals('supplier@test.com', $supplier->email());
        $this->assertEquals('11987654321', $supplier->phone());
        $this->assertEquals('Rua Teste, 123', $supplier->address());
        $this->assertTrue($supplier->isActive());
        $this->assertNotNull($supplier->createdAt());
        $this->assertNotNull($supplier->updatedAt());
    }

    public function test_it_records_supplier_created_event(): void
    {
        $name = SupplierName::fromString('Fornecedor Teste');
        
        $supplier = Supplier::create(
            name: $name
        );

        $events = $supplier->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(SupplierCreated::class, $events[0]);
        $this->assertEquals($supplier->id()->value(), $events[0]->supplierId);
    }

    public function test_it_throws_exception_for_invalid_email(): void
    {
        $this->expectException(InvalidSupplierException::class);
        $this->expectExceptionMessage('Invalid email format');

        $name = SupplierName::fromString('Fornecedor Teste');
        
        Supplier::create(
            name: $name,
            email: 'invalid-email'
        );
    }

    public function test_it_throws_exception_for_invalid_document(): void
    {
        $this->expectException(InvalidSupplierException::class);
        $this->expectExceptionMessage('Document must be CPF (11 digits) or CNPJ (14 digits)');

        $name = SupplierName::fromString('Fornecedor Teste');
        
        Supplier::create(
            name: $name,
            document: '12345'
        );
    }

    public function test_it_updates_supplier_info(): void
    {
        $name = SupplierName::fromString('Fornecedor Teste');
        $supplier = Supplier::create(name: $name);

        $newName = SupplierName::fromString('Fornecedor Atualizado');
        $supplier->update(
            name: $newName,
            document: '98765432000100',
            email: 'new@test.com',
            phone: '11999999999',
            address: 'Nova Rua, 456'
        );

        $this->assertEquals('Fornecedor Atualizado', $supplier->name()->value());
        $this->assertEquals('98765432000100', $supplier->document());
        $this->assertEquals('new@test.com', $supplier->email());
        $this->assertEquals('11999999999', $supplier->phone());
        $this->assertEquals('Nova Rua, 456', $supplier->address());
    }

    public function test_it_activates_supplier(): void
    {
        $name = SupplierName::fromString('Fornecedor Teste');
        $supplier = Supplier::create(name: $name);
        
        $supplier->deactivate();
        $this->assertFalse($supplier->isActive());
        
        $supplier->activate();
        $this->assertTrue($supplier->isActive());
    }

    public function test_it_deactivates_supplier(): void
    {
        $name = SupplierName::fromString('Fornecedor Teste');
        $supplier = Supplier::create(name: $name);
        
        $this->assertTrue($supplier->isActive());
        
        $supplier->deactivate();
        $this->assertFalse($supplier->isActive());
    }

    public function test_it_throws_exception_when_activating_already_active(): void
    {
        $this->expectException(InvalidSupplierException::class);
        $this->expectExceptionMessage('Supplier is already active');

        $name = SupplierName::fromString('Fornecedor Teste');
        $supplier = Supplier::create(name: $name);
        
        $supplier->activate();
    }

    public function test_it_throws_exception_when_deactivating_already_inactive(): void
    {
        $this->expectException(InvalidSupplierException::class);
        $this->expectExceptionMessage('Supplier is already inactive');

        $name = SupplierName::fromString('Fornecedor Teste');
        $supplier = Supplier::create(name: $name);
        
        $supplier->deactivate();
        $supplier->deactivate();
    }
}


