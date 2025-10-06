<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use PHPUnit\Framework\TestCase;
use Src\Domain\Entities\Order;
use Src\Domain\Entities\OrderItem;
use Src\Domain\ValueObjects\OrderId;
use Src\Domain\ValueObjects\OrderNumber;
use Src\Domain\ValueObjects\CustomerId;
use Src\Domain\ValueObjects\OrderItemId;
use Src\Domain\ValueObjects\Money;
use Src\Domain\ValueObjects\Quantity;

class OrderTest extends TestCase
{
    /** @test */
    public function it_creates_order(): void
    {
        $order = Order::create(
            id: OrderId::generate(),
            orderNumber: OrderNumber::generate(1),
            customerId: CustomerId::generate(),
            notes: 'Test order'
        );

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('draft', $order->getStatus()->value());
        $this->assertEquals(0.0, $order->getTotal()->value());
        $this->assertCount(0, $order->getItems());
    }

    /** @test */
    public function it_adds_item_to_order(): void
    {
        $order = Order::create(
            id: OrderId::generate(),
            orderNumber: OrderNumber::generate(1),
            customerId: CustomerId::generate()
        );

        $item = OrderItem::create(
            id: OrderItemId::generate(),
            productId: 'product-123',
            productName: 'Product Test',
            sku: 'TEST-001',
            quantity: Quantity::fromInt(2),
            unitPrice: Money::fromFloat(100.0)
        );

        $order->addItem($item);

        $this->assertCount(1, $order->getItems());
        $this->assertEquals(200.0, $order->getTotal()->value());
    }

    /** @test */
    public function it_throws_exception_when_adding_item_to_non_draft_order(): void
    {
        $this->expectException(\DomainException::class);

        $order = Order::create(
            id: OrderId::generate(),
            orderNumber: OrderNumber::generate(1),
            customerId: CustomerId::generate()
        );

        // Add item first, then confirm
        $item1 = OrderItem::create(
            id: OrderItemId::generate(),
            productId: 'product-1',
            productName: 'Product 1',
            sku: 'TEST-001',
            quantity: Quantity::fromInt(1),
            unitPrice: Money::fromFloat(100.0)
        );
        $order->addItem($item1);
        $order->confirm();

        // Try to add another item after confirming
        $item2 = OrderItem::create(
            id: OrderItemId::generate(),
            productId: 'product-2',
            productName: 'Product 2',
            sku: 'TEST-002',
            quantity: Quantity::fromInt(1),
            unitPrice: Money::fromFloat(100.0)
        );

        $order->addItem($item2);
    }

    /** @test */
    public function it_confirms_order(): void
    {
        $order = Order::create(
            id: OrderId::generate(),
            orderNumber: OrderNumber::generate(1),
            customerId: CustomerId::generate()
        );

        $item = OrderItem::create(
            id: OrderItemId::generate(),
            productId: 'product-123',
            productName: 'Product Test',
            sku: 'TEST-001',
            quantity: Quantity::fromInt(1),
            unitPrice: Money::fromFloat(100.0)
        );

        $order->addItem($item);
        $order->confirm();

        $this->assertEquals('confirmed', $order->getStatus()->value());
        $this->assertNotNull($order->getConfirmedAt());
    }

    /** @test */
    public function it_throws_exception_when_confirming_empty_order(): void
    {
        $this->expectException(\DomainException::class);

        $order = Order::create(
            id: OrderId::generate(),
            orderNumber: OrderNumber::generate(1),
            customerId: CustomerId::generate()
        );

        $order->confirm();
    }

    /** @test */
    public function it_cancels_order(): void
    {
        $order = Order::create(
            id: OrderId::generate(),
            orderNumber: OrderNumber::generate(1),
            customerId: CustomerId::generate()
        );

        $order->cancel('Test cancellation');

        $this->assertEquals('cancelled', $order->getStatus()->value());
        $this->assertNotNull($order->getCancelledAt());
        
        // Verify cancellation was recorded in events
        $events = $order->pullDomainEvents();
        $this->assertCount(2, $events); // OrderCreated + OrderCancelled
        $this->assertEquals('OrderCancelled', $events[1]['event']);
        $this->assertEquals('Test cancellation', $events[1]['payload']['reason']);
    }

    /** @test */
    public function it_records_domain_events(): void
    {
        $order = Order::create(
            id: OrderId::generate(),
            orderNumber: OrderNumber::generate(1),
            customerId: CustomerId::generate()
        );

        $events = $order->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertEquals('OrderCreated', $events[0]['event']);
    }

    /** @test */
    public function it_recalculates_totals_when_adding_items(): void
    {
        $order = Order::create(
            id: OrderId::generate(),
            orderNumber: OrderNumber::generate(1),
            customerId: CustomerId::generate()
        );

        $item1 = OrderItem::create(
            id: OrderItemId::generate(),
            productId: 'product-1',
            productName: 'Product 1',
            sku: 'TEST-001',
            quantity: Quantity::fromInt(2),
            unitPrice: Money::fromFloat(50.0)
        );

        $item2 = OrderItem::create(
            id: OrderItemId::generate(),
            productId: 'product-2',
            productName: 'Product 2',
            sku: 'TEST-002',
            quantity: Quantity::fromInt(1),
            unitPrice: Money::fromFloat(100.0),
            discount: Money::fromFloat(10.0)
        );

        $order->addItem($item1);
        $order->addItem($item2);

        // item1: 2 * 50 = 100 (no discount)
        // item2: 1 * 100 - 10 = 90 (with discount)
        // Total: 100 + 90 = 190
        $this->assertEquals(190.0, $order->getTotal()->value());
        $this->assertEquals(200.0, $order->getSubtotal()->value()); // 100 + 100
        $this->assertEquals(10.0, $order->getDiscount()->value());
    }
}
