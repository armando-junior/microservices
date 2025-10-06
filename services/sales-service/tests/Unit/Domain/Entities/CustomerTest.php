<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use PHPUnit\Framework\TestCase;
use Src\Domain\Entities\Customer;
use Src\Domain\ValueObjects\CustomerId;
use Src\Domain\ValueObjects\CustomerName;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Phone;
use Src\Domain\ValueObjects\Document;

class CustomerTest extends TestCase
{
    /** @test */
    public function it_creates_customer(): void
    {
        $customer = Customer::create(
            id: CustomerId::generate(),
            name: CustomerName::fromString('João Silva'),
            email: Email::fromString('joao@example.com'),
            phone: Phone::fromString('11987654321'),
            document: Document::fromString('11144477735'),
            addressStreet: 'Rua ABC',
            addressNumber: '123',
            addressCity: 'São Paulo',
            addressState: 'SP',
            addressZipCode: '01234567'
        );

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('João Silva', $customer->getName()->value());
        $this->assertEquals('joao@example.com', $customer->getEmail()->value());
        $this->assertTrue($customer->isActive());
        $this->assertEquals('active', $customer->getStatus());
    }

    /** @test */
    public function it_updates_customer_info(): void
    {
        $customer = Customer::create(
            id: CustomerId::generate(),
            name: CustomerName::fromString('João Silva'),
            email: Email::fromString('joao@example.com'),
            phone: Phone::fromString('11987654321'),
            document: Document::fromString('11144477735')
        );

        $customer->updateInfo(
            name: CustomerName::fromString('João Pedro Silva'),
            email: Email::fromString('joao.pedro@example.com'),
            phone: Phone::fromString('11999998888')
        );

        $this->assertEquals('João Pedro Silva', $customer->getName()->value());
        $this->assertEquals('joao.pedro@example.com', $customer->getEmail()->value());
        $this->assertNotNull($customer->getUpdatedAt());
    }

    /** @test */
    public function it_activates_customer(): void
    {
        $customer = Customer::create(
            id: CustomerId::generate(),
            name: CustomerName::fromString('João Silva'),
            email: Email::fromString('joao@example.com'),
            phone: Phone::fromString('11987654321'),
            document: Document::fromString('11144477735')
        );

        $customer->deactivate();
        $this->assertEquals('inactive', $customer->getStatus());

        $customer->activate();
        $this->assertTrue($customer->isActive());
        $this->assertEquals('active', $customer->getStatus());
    }

    /** @test */
    public function it_deactivates_customer(): void
    {
        $customer = Customer::create(
            id: CustomerId::generate(),
            name: CustomerName::fromString('João Silva'),
            email: Email::fromString('joao@example.com'),
            phone: Phone::fromString('11987654321'),
            document: Document::fromString('11144477735')
        );

        $customer->deactivate();

        $this->assertFalse($customer->isActive());
        $this->assertEquals('inactive', $customer->getStatus());
    }

    /** @test */
    public function it_records_domain_events(): void
    {
        $customer = Customer::create(
            id: CustomerId::generate(),
            name: CustomerName::fromString('João Silva'),
            email: Email::fromString('joao@example.com'),
            phone: Phone::fromString('11987654321'),
            document: Document::fromString('11144477735')
        );

        $events = $customer->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertEquals('CustomerCreated', $events[0]['event']);
        $this->assertArrayHasKey('customer_id', $events[0]['payload']);
    }

    /** @test */
    public function it_pulls_events_only_once(): void
    {
        $customer = Customer::create(
            id: CustomerId::generate(),
            name: CustomerName::fromString('João Silva'),
            email: Email::fromString('joao@example.com'),
            phone: Phone::fromString('11987654321'),
            document: Document::fromString('11144477735')
        );

        $events1 = $customer->pullDomainEvents();
        $events2 = $customer->pullDomainEvents();

        $this->assertCount(1, $events1);
        $this->assertCount(0, $events2); // Should be empty after pulling
    }
}
