<?php

declare(strict_types=1);

namespace Tests\Integration\Application\UseCases;

use Tests\IntegrationTestCase;
use Src\Application\UseCases\Order\GetOrder\GetOrderUseCase;
use Src\Application\Exceptions\OrderNotFoundException;
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

class GetOrderUseCaseTest extends IntegrationTestCase
{
    private GetOrderUseCase $useCase;
    private EloquentOrderRepository $orderRepository;
    private EloquentCustomerRepository $customerRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderRepository = new EloquentOrderRepository();
        $this->customerRepository = new EloquentCustomerRepository();
        $this->useCase = new GetOrderUseCase($this->orderRepository);
    }

    /** @test */
    public function it_gets_order_successfully(): void
    {
        // Create customer and order
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);

        // Get the order
        $result = $this->useCase->execute($order->getId()->value());

        $this->assertEquals($order->getId()->value(), $result->id);
        $this->assertEquals($customer->getId()->value(), $result->customerId);
        $this->assertEquals('draft', $result->status);
        $this->assertStringStartsWith('ORD-', $result->orderNumber);
    }

    /** @test */
    public function it_throws_exception_when_order_not_found(): void
    {
        $this->expectException(OrderNotFoundException::class);

        $this->useCase->execute('00000000-0000-0000-0000-000000000000');
    }

    /** @test */
    public function it_returns_order_with_notes(): void
    {
        $customer = $this->createCustomer();
        
        $orderNumber = $this->orderRepository->nextOrderNumber();
        $order = Order::create(
            id: OrderId::generate(),
            orderNumber: $orderNumber,
            customerId: $customer->getId(),
            notes: 'Deliver between 9AM and 6PM'
        );

        $this->orderRepository->save($order);

        $result = $this->useCase->execute($order->getId()->value());

        $this->assertEquals('Deliver between 9AM and 6PM', $result->notes);
    }

    /** @test */
    public function it_returns_order_totals(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);

        $result = $this->useCase->execute($order->getId()->value());

        $this->assertEquals(0.0, $result->subtotal);
        $this->assertEquals(0.0, $result->discount);
        $this->assertEquals(0.0, $result->total);
        $this->assertEquals('pending', $result->paymentStatus);
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

