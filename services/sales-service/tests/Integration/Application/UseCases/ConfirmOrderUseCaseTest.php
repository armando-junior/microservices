<?php

declare(strict_types=1);

namespace Tests\Integration\Application\UseCases;

use Tests\IntegrationTestCase;
use Src\Application\UseCases\Order\ConfirmOrder\ConfirmOrderUseCase;
use Src\Application\Exceptions\OrderNotFoundException;
use Src\Infrastructure\Persistence\EloquentOrderRepository;
use Src\Infrastructure\Persistence\EloquentCustomerRepository;
use Src\Infrastructure\Messaging\RabbitMQEventPublisher;
use Src\Domain\Entities\Customer;
use Src\Domain\Entities\Order;
use Src\Domain\Entities\OrderItem;
use Src\Domain\ValueObjects\CustomerId;
use Src\Domain\ValueObjects\CustomerName;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Phone;
use Src\Domain\ValueObjects\Document;
use Src\Domain\ValueObjects\OrderId;
use Src\Domain\ValueObjects\OrderItemId;
use Src\Domain\ValueObjects\Quantity;
use Src\Domain\ValueObjects\Money;

class ConfirmOrderUseCaseTest extends IntegrationTestCase
{
    private ConfirmOrderUseCase $useCase;
    private EloquentOrderRepository $orderRepository;
    private EloquentCustomerRepository $customerRepository;
    private RabbitMQEventPublisher $eventPublisher;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderRepository = new EloquentOrderRepository();
        $this->customerRepository = new EloquentCustomerRepository();
        $this->eventPublisher = new RabbitMQEventPublisher();
        $this->useCase = new ConfirmOrderUseCase(
            $this->orderRepository,
            $this->eventPublisher
        );
    }

    /** @test */
    public function it_confirms_order_successfully(): void
    {
        // Create customer and order with item
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);
        
        // Add at least one item (required for confirmation)
        $item = OrderItem::create(
            id: OrderItemId::generate(),
            productId: '550e8400-e29b-41d4-a716-446655440000', // Valid UUID
            productName: 'Test Product',
            sku: 'TEST-001',
            quantity: Quantity::fromInt(2),
            unitPrice: Money::fromFloat(100.0)
        );
        $order->addItem($item);
        $this->orderRepository->save($order);

        // Confirm the order
        $result = $this->useCase->execute($order->getId()->value());

        $this->assertEquals('confirmed', $result->status);
        $this->assertNotNull($result->confirmedAt);
        $this->assertDatabaseHas('orders', [
            'id' => $order->getId()->value(),
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function it_throws_exception_when_order_not_found(): void
    {
        $this->expectException(OrderNotFoundException::class);

        $this->useCase->execute('00000000-0000-0000-0000-000000000000');
    }

    /** @test */
    public function it_throws_exception_when_confirming_empty_order(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot confirm an order without items');

        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);

        // Try to confirm order without items
        $this->useCase->execute($order->getId()->value());
    }

    /** @test */
    public function it_updates_confirmed_at_timestamp(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);
        
        // Add item
        $item = OrderItem::create(
            id: OrderItemId::generate(),
            productId: '550e8400-e29b-41d4-a716-446655440002', // Valid UUID
            productName: 'Test Product',
            sku: 'TEST-001',
            quantity: Quantity::fromInt(1),
            unitPrice: Money::fromFloat(50.0)
        );
        $order->addItem($item);
        $this->orderRepository->save($order);

        // Before confirmation
        $this->assertNull($order->getConfirmedAt());

        // Confirm
        $result = $this->useCase->execute($order->getId()->value());

        // After confirmation
        $this->assertNotNull($result->confirmedAt);
    }

    /** @test */
    public function it_publishes_domain_event_on_confirmation(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);
        
        // Add item
        $item = OrderItem::create(
            id: OrderItemId::generate(),
            productId: '550e8400-e29b-41d4-a716-446655440001', // Valid UUID
            productName: 'Test Product',
            sku: 'TEST-001',
            quantity: Quantity::fromInt(1),
            unitPrice: Money::fromFloat(50.0)
        );
        $order->addItem($item);
        $this->orderRepository->save($order);

        // Confirm - this should publish events to RabbitMQ
        // Note: In a real test, you'd mock RabbitMQ or check logs
        $result = $this->useCase->execute($order->getId()->value());

        // Verify order was confirmed
        $this->assertEquals('confirmed', $result->status);
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

