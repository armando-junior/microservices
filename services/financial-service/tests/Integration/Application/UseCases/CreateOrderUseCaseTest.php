<?php

declare(strict_types=1);

namespace Tests\Integration\Application\UseCases;

use Tests\IntegrationTestCase;
use Src\Application\UseCases\Order\CreateOrder\CreateOrderUseCase;
use Src\Application\UseCases\Order\CreateOrder\CreateOrderDTO;
use Src\Application\Exceptions\CustomerNotFoundException;
use Src\Infrastructure\Persistence\EloquentOrderRepository;
use Src\Infrastructure\Persistence\EloquentCustomerRepository;
use Src\Domain\Entities\Customer;
use Src\Domain\ValueObjects\CustomerId;
use Src\Domain\ValueObjects\CustomerName;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Phone;
use Src\Domain\ValueObjects\Document;

class CreateOrderUseCaseTest extends IntegrationTestCase
{
    private CreateOrderUseCase $useCase;
    private EloquentOrderRepository $orderRepository;
    private EloquentCustomerRepository $customerRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderRepository = new EloquentOrderRepository();
        $this->customerRepository = new EloquentCustomerRepository();
        $this->useCase = new CreateOrderUseCase(
            $this->orderRepository,
            $this->customerRepository
        );
    }

    /** @test */
    public function it_creates_order_successfully(): void
    {
        // Create customer first
        $customer = $this->createCustomer();

        $dto = new CreateOrderDTO(
            customerId: $customer->getId()->value(),
            notes: 'Test order'
        );

        $result = $this->useCase->execute($dto);

        $this->assertNotNull($result->id);
        $this->assertEquals($customer->getId()->value(), $result->customerId);
        $this->assertEquals('draft', $result->status);
        $this->assertEquals(0.0, $result->total);
        $this->assertStringStartsWith('ORD-', $result->orderNumber);
        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->getId()->value(),
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function it_generates_sequential_order_numbers(): void
    {
        $customer = $this->createCustomer();

        // Create multiple orders
        $dto1 = new CreateOrderDTO(customerId: $customer->getId()->value());
        $dto2 = new CreateOrderDTO(customerId: $customer->getId()->value());
        $dto3 = new CreateOrderDTO(customerId: $customer->getId()->value());

        $order1 = $this->useCase->execute($dto1);
        $order2 = $this->useCase->execute($dto2);
        $order3 = $this->useCase->execute($dto3);

        // Check sequential numbers
        $this->assertMatchesRegularExpression('/ORD-\d{4}-\d{4}/', $order1->orderNumber);
        $this->assertMatchesRegularExpression('/ORD-\d{4}-\d{4}/', $order2->orderNumber);
        $this->assertMatchesRegularExpression('/ORD-\d{4}-\d{4}/', $order3->orderNumber);
        
        // Extract numbers and verify they're sequential
        preg_match('/ORD-\d{4}-(\d{4})/', $order1->orderNumber, $matches1);
        preg_match('/ORD-\d{4}-(\d{4})/', $order2->orderNumber, $matches2);
        preg_match('/ORD-\d{4}-(\d{4})/', $order3->orderNumber, $matches3);
        
        $this->assertEquals((int)$matches1[1] + 1, (int)$matches2[1]);
        $this->assertEquals((int)$matches2[1] + 1, (int)$matches3[1]);
    }

    /** @test */
    public function it_throws_exception_when_customer_not_found(): void
    {
        $this->expectException(CustomerNotFoundException::class);

        $dto = new CreateOrderDTO(
            customerId: '00000000-0000-0000-0000-000000000000'
        );

        $this->useCase->execute($dto);
    }

    /** @test */
    public function it_stores_notes_correctly(): void
    {
        $customer = $this->createCustomer();

        $dto = new CreateOrderDTO(
            customerId: $customer->getId()->value(),
            notes: 'Entregar entre 9h e 18h'
        );

        $result = $this->useCase->execute($dto);

        $this->assertEquals('Entregar entre 9h e 18h', $result->notes);
        $this->assertDatabaseHas('orders', [
            'id' => $result->id,
            'notes' => 'Entregar entre 9h e 18h',
        ]);
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

        $this->customerRepository->save($customer);

        return $customer;
    }
}
