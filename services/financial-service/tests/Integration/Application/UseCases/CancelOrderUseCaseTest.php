<?php

declare(strict_types=1);

namespace Tests\Integration\Application\UseCases;

use Tests\IntegrationTestCase;
use Src\Application\UseCases\Order\CancelOrder\CancelOrderUseCase;
use Src\Application\UseCases\Order\CancelOrder\CancelOrderDTO;
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
use Mockery;

class CancelOrderUseCaseTest extends IntegrationTestCase
{
    private CancelOrderUseCase $useCase;
    private EloquentOrderRepository $orderRepository;
    private EloquentCustomerRepository $customerRepository;
    private $eventPublisher;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderRepository = new EloquentOrderRepository();
        $this->customerRepository = new EloquentCustomerRepository();
        
        // Mock RabbitMQEventPublisher to avoid RabbitMQ dependency in tests
        $this->eventPublisher = Mockery::mock(RabbitMQEventPublisher::class);
        $this->eventPublisher->shouldReceive('publishAll')->andReturn(true);
        
        $this->useCase = new CancelOrderUseCase(
            $this->orderRepository,
            $this->eventPublisher
        );
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_cancels_order_successfully(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);

        $dto = new CancelOrderDTO(
            orderId: $order->getId()->value(),
            reason: 'Customer requested cancellation'
        );

        $result = $this->useCase->execute($dto);

        $this->assertEquals('cancelled', $result->status);
        $this->assertNotNull($result->cancelledAt);
        $this->assertDatabaseHas('orders', [
            'id' => $order->getId()->value(),
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function it_throws_exception_when_order_not_found(): void
    {
        $this->expectException(OrderNotFoundException::class);

        $dto = new CancelOrderDTO(
            orderId: '00000000-0000-0000-0000-000000000000',
            reason: 'Test'
        );

        $this->useCase->execute($dto);
    }

    /** @test */
    public function it_cancels_with_reason(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);

        $dto = new CancelOrderDTO(
            orderId: $order->getId()->value(),
            reason: 'Out of stock'
        );

        $result = $this->useCase->execute($dto);

        $this->assertEquals('cancelled', $result->status);
        // Note: reason is stored in events, not in order entity directly
    }

    /** @test */
    public function it_cancels_without_reason(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);

        $dto = new CancelOrderDTO(
            orderId: $order->getId()->value()
        );

        $result = $this->useCase->execute($dto);

        $this->assertEquals('cancelled', $result->status);
    }

    /** @test */
    public function it_updates_cancelled_at_timestamp(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);

        // Before cancellation
        $this->assertNull($order->getCancelledAt());

        $dto = new CancelOrderDTO(
            orderId: $order->getId()->value(),
            reason: 'Test'
        );

        $result = $this->useCase->execute($dto);

        // After cancellation
        $this->assertNotNull($result->cancelledAt);
    }

    /** @test */
    public function it_throws_exception_when_cancelling_already_delivered_order(): void
    {
        $this->expectException(\DomainException::class);

        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);
        
        // Add item and confirm
        $item = OrderItem::create(
            id: OrderItemId::generate(),
            productId: '550e8400-e29b-41d4-a716-446655440000', // Valid UUID
            productName: 'Test Product',
            sku: 'TEST-001',
            quantity: Quantity::fromInt(1),
            unitPrice: Money::fromFloat(50.0)
        );
        $order->addItem($item);
        $order->confirm();
        
        // Mark as delivered
        $order->deliver();
        $this->orderRepository->save($order);

        // Try to cancel delivered order
        $dto = new CancelOrderDTO(
            orderId: $order->getId()->value(),
            reason: 'Test'
        );

        $this->useCase->execute($dto);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_publishes_domain_event_on_cancellation(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);

        $dto = new CancelOrderDTO(
            orderId: $order->getId()->value(),
            reason: 'Stock issue'
        );

        // Create a fresh mock with exact expectation
        $eventPublisher = Mockery::mock(RabbitMQEventPublisher::class);
        $eventPublisher->shouldReceive('publishAll')->once()->andReturn(true);

        // Recreate UseCase with the new mock
        $useCase = new CancelOrderUseCase(
            $this->orderRepository,
            $eventPublisher
        );

        // Cancel - this should publish events (mocked)
        $result = $useCase->execute($dto);

        // Verify order was cancelled
        $this->assertEquals('cancelled', $result->status);
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

