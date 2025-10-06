<?php

declare(strict_types=1);

namespace Tests\Integration\Application\UseCases;

use Tests\IntegrationTestCase;
use Src\Application\UseCases\Order\ListOrders\ListOrdersUseCase;
use Src\Infrastructure\Persistence\EloquentOrderRepository;
use Src\Infrastructure\Persistence\EloquentCustomerRepository;
use Src\Domain\Entities\Customer;
use Src\Domain\Entities\Order;
use Src\Domain\ValueObjects\CustomerId;
use Src\Domain\ValueObjects\CustomerName;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Phone;
use Src\Domain\ValueObjects\Document;
use Src\Domain\ValueObjects\OrderId;

class ListOrdersUseCaseTest extends IntegrationTestCase
{
    private ListOrdersUseCase $useCase;
    private EloquentOrderRepository $orderRepository;
    private EloquentCustomerRepository $customerRepository;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderRepository = new EloquentOrderRepository();
        $this->customerRepository = new EloquentCustomerRepository();
        $this->useCase = new ListOrdersUseCase($this->orderRepository);
        
        // Create a customer for all tests
        $this->customer = $this->createCustomer();
    }

    /** @test */
    public function it_lists_all_orders(): void
    {
        // Create multiple orders
        $this->createOrder($this->customer);
        $this->createOrder($this->customer);
        $this->createOrder($this->customer);

        // List all orders
        $results = $this->useCase->execute();

        $this->assertCount(3, $results);
        $this->assertStringStartsWith('ORD-', $results[0]->orderNumber);
    }

    /** @test */
    public function it_returns_empty_array_when_no_orders(): void
    {
        $results = $this->useCase->execute();

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /** @test */
    public function it_paginates_orders(): void
    {
        // Create 5 orders
        for ($i = 0; $i < 5; $i++) {
            $this->createOrder($this->customer);
        }

        // Get first page (2 per page)
        $page1 = $this->useCase->execute([], 1, 2);
        $this->assertCount(2, $page1);

        // Get second page
        $page2 = $this->useCase->execute([], 2, 2);
        $this->assertCount(2, $page2);

        // Verify different orders
        $this->assertNotEquals($page1[0]->id, $page2[0]->id);
    }

    /** @test */
    public function it_counts_total_orders(): void
    {
        $this->createOrder($this->customer);
        $this->createOrder($this->customer);

        $count = $this->useCase->count();

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function it_filters_orders_by_customer(): void
    {
        // Create orders for first customer
        $this->createOrder($this->customer);
        $this->createOrder($this->customer);

        // Create another customer and order
        $customer2 = $this->createCustomer('another@example.com');
        $this->createOrder($customer2);

        // Filter by first customer
        $orders = $this->useCase->execute(['customer_id' => $this->customer->getId()->value()]);
        
        $this->assertCount(2, $orders);
        $this->assertEquals($this->customer->getId()->value(), $orders[0]->customerId);
    }

    /** @test */
    public function it_filters_orders_by_status(): void
    {
        // Create draft order
        $order1 = $this->createOrder($this->customer);
        
        // Create and confirm another order
        $order2 = $this->createOrder($this->customer);
        $order2->addItem(
            \Src\Domain\Entities\OrderItem::create(
                id: \Src\Domain\ValueObjects\OrderItemId::generate(),
                productId: '550e8400-e29b-41d4-a716-446655440000', // Valid UUID
                productName: 'Test Product',
                sku: 'TEST-001',
                quantity: \Src\Domain\ValueObjects\Quantity::fromInt(1),
                unitPrice: \Src\Domain\ValueObjects\Money::fromFloat(100.0)
            )
        );
        $order2->confirm();
        $this->orderRepository->save($order2);

        // Filter by draft status
        $draftOrders = $this->useCase->execute(['status' => 'draft']);
        $this->assertCount(1, $draftOrders);
        $this->assertEquals('draft', $draftOrders[0]->status);
    }

    private function createCustomer(string $email = 'test@example.com'): Customer
    {
        $customer = Customer::create(
            id: CustomerId::generate(),
            name: CustomerName::fromString('Test Customer'),
            email: Email::fromString($email),
            phone: Phone::fromString('11987654321'),
            document: Document::fromString('11144477735')
        );

        $this->customerRepository->save($customer);

        return $customer;
    }

    private function createOrder(Customer $customer): Order
    {
        $orderNumber = $this->orderRepository->nextOrderNumber();
        $order = Order::create(
            id: OrderId::generate(),
            orderNumber: $orderNumber,
            customerId: $customer->getId()
        );

        $this->orderRepository->save($order);

        return $order;
    }
}

