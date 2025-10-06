<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use PHPUnit\Framework\TestCase;
use Src\Domain\Entities\Stock;
use Src\Domain\ValueObjects\StockId;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\Quantity;
use Src\Domain\Exceptions\InsufficientStockException;

class StockTest extends TestCase
{
    public function test_it_creates_stock_successfully(): void
    {
        $stock = Stock::create(
            id: StockId::generate(),
            productId: ProductId::generate(),
            initialQuantity: Quantity::fromInt(100),
            minimumQuantity: Quantity::fromInt(10),
            maximumQuantity: Quantity::fromInt(500)
        );

        $this->assertInstanceOf(Stock::class, $stock);
        $this->assertEquals(100, $stock->getQuantity()->value());
        $this->assertEquals(10, $stock->getMinimumQuantity()->value());
    }

    public function test_it_increases_stock(): void
    {
        $stock = Stock::create(
            id: StockId::generate(),
            productId: ProductId::generate(),
            initialQuantity: Quantity::fromInt(50),
            minimumQuantity: Quantity::fromInt(10)
        );

        $stock->increase(Quantity::fromInt(30), 'Purchase order', 'PO-001');

        $this->assertEquals(80, $stock->getQuantity()->value());
    }

    public function test_it_decreases_stock(): void
    {
        $stock = Stock::create(
            id: StockId::generate(),
            productId: ProductId::generate(),
            initialQuantity: Quantity::fromInt(50),
            minimumQuantity: Quantity::fromInt(10)
        );

        $stock->decrease(Quantity::fromInt(20), 'Sale', 'SALE-001');

        $this->assertEquals(30, $stock->getQuantity()->value());
    }

    public function test_it_throws_exception_when_insufficient_stock(): void
    {
        $this->expectException(InsufficientStockException::class);

        $stock = Stock::create(
            id: StockId::generate(),
            productId: ProductId::generate(),
            initialQuantity: Quantity::fromInt(10),
            minimumQuantity: Quantity::fromInt(5)
        );

        $stock->decrease(Quantity::fromInt(20), 'Sale', 'SALE-001');
    }

    public function test_it_detects_low_stock(): void
    {
        $stock = Stock::create(
            id: StockId::generate(),
            productId: ProductId::generate(),
            initialQuantity: Quantity::fromInt(10),
            minimumQuantity: Quantity::fromInt(15)
        );

        $this->assertTrue($stock->isLowStock());
    }

    public function test_it_detects_depleted_stock(): void
    {
        $stock = Stock::create(
            id: StockId::generate(),
            productId: ProductId::generate(),
            initialQuantity: Quantity::fromInt(5),
            minimumQuantity: Quantity::fromInt(10)
        );

        $stock->decrease(Quantity::fromInt(5), 'Sale', 'SALE-001');

        $this->assertTrue($stock->isDepleted());
        $this->assertEquals(0, $stock->getQuantity()->value());
    }

    public function test_it_adjusts_stock(): void
    {
        $stock = Stock::create(
            id: StockId::generate(),
            productId: ProductId::generate(),
            initialQuantity: Quantity::fromInt(100),
            minimumQuantity: Quantity::fromInt(10)
        );

        $stock->adjust(Quantity::fromInt(75), 'Inventory count');

        $this->assertEquals(75, $stock->getQuantity()->value());
    }

    public function test_it_records_movements(): void
    {
        $stock = Stock::create(
            id: StockId::generate(),
            productId: ProductId::generate(),
            initialQuantity: Quantity::fromInt(50),
            minimumQuantity: Quantity::fromInt(10)
        );

        $stock->increase(Quantity::fromInt(20), 'Purchase', 'PO-001');
        $stock->decrease(Quantity::fromInt(10), 'Sale', 'SALE-001');

        $movements = $stock->pullMovements();

        $this->assertCount(2, $movements);
        $this->assertEquals('IN', $movements[0]['type']);
        $this->assertEquals('OUT', $movements[1]['type']);
    }

    public function test_it_checks_if_can_decrease(): void
    {
        $stock = Stock::create(
            id: StockId::generate(),
            productId: ProductId::generate(),
            initialQuantity: Quantity::fromInt(50),
            minimumQuantity: Quantity::fromInt(10)
        );

        // Can decrease if quantity is less than current
        $this->assertTrue($stock->getQuantity()->isSufficient(Quantity::fromInt(30)));
        $this->assertFalse($stock->getQuantity()->isSufficient(Quantity::fromInt(60)));
    }
}
