<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases;

use PHPUnit\Framework\TestCase;
use Src\Application\UseCases\Stock\GetDepletedStock\GetDepletedStockUseCase;
use Src\Domain\Entities\Stock;
use Src\Domain\Repositories\StockRepositoryInterface;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\Quantity;
use Src\Domain\ValueObjects\StockId;

class GetDepletedStockUseCaseTest extends TestCase
{
    private StockRepositoryInterface $stockRepository;
    private GetDepletedStockUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->stockRepository = $this->createMock(StockRepositoryInterface::class);
        $this->useCase = new GetDepletedStockUseCase($this->stockRepository);
    }

    public function test_it_returns_depleted_stock_products(): void
    {
        // Arrange
        $stock1 = Stock::create(
            StockId::generate(),
            ProductId::generate(),
            Quantity::fromInt(0), // Depleted
            Quantity::fromInt(10)
        );

        $stock2 = Stock::create(
            StockId::generate(),
            ProductId::generate(),
            Quantity::fromInt(0), // Depleted
            Quantity::fromInt(20)
        );

        $this->stockRepository
            ->expects($this->once())
            ->method('findDepleted')
            ->willReturn([$stock1, $stock2]);

        // Act
        $result = $this->useCase->execute();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertTrue($result[0]->isDepleted);
        $this->assertTrue($result[1]->isDepleted);
        $this->assertEquals(0, $result[0]->quantity);
        $this->assertEquals(0, $result[1]->quantity);
    }

    public function test_it_returns_empty_array_when_no_depleted_stock(): void
    {
        // Arrange
        $this->stockRepository
            ->expects($this->once())
            ->method('findDepleted')
            ->willReturn([]);

        // Act
        $result = $this->useCase->execute();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_it_includes_correct_dto_properties(): void
    {
        // Arrange
        $productId = ProductId::generate();
        $stock = Stock::create(
            StockId::generate(),
            $productId,
            Quantity::fromInt(0), // Depleted
            Quantity::fromInt(15)
        );

        $this->stockRepository
            ->expects($this->once())
            ->method('findDepleted')
            ->willReturn([$stock]);

        // Act
        $result = $this->useCase->execute();

        // Assert
        $this->assertEquals($productId->value(), $result[0]->productId);
        $this->assertEquals(0, $result[0]->quantity);
        $this->assertEquals(15, $result[0]->minimumQuantity);
        $this->assertTrue($result[0]->isLowStock); // Also flagged as low stock
        $this->assertTrue($result[0]->isDepleted);
    }
}
