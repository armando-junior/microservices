<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases;

use PHPUnit\Framework\TestCase;
use Src\Application\UseCases\Stock\GetLowStock\GetLowStockUseCase;
use Src\Domain\Entities\Stock;
use Src\Domain\Repositories\StockRepositoryInterface;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\Quantity;
use Src\Domain\ValueObjects\StockId;

class GetLowStockUseCaseTest extends TestCase
{
    private StockRepositoryInterface $stockRepository;
    private GetLowStockUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->stockRepository = $this->createMock(StockRepositoryInterface::class);
        $this->useCase = new GetLowStockUseCase($this->stockRepository);
    }

    public function test_it_returns_low_stock_products(): void
    {
        // Arrange
        $stock1 = Stock::create(
            StockId::generate(),
            ProductId::generate(),
            Quantity::fromInt(5), // Below minimum
            Quantity::fromInt(10)
        );

        $stock2 = Stock::create(
            StockId::generate(),
            ProductId::generate(),
            Quantity::fromInt(3), // Below minimum
            Quantity::fromInt(20)
        );

        $this->stockRepository
            ->expects($this->once())
            ->method('findLowStock')
            ->willReturn([$stock1, $stock2]);

        // Act
        $result = $this->useCase->execute();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertTrue($result[0]->isLowStock);
        $this->assertTrue($result[1]->isLowStock);
        $this->assertFalse($result[0]->isDepleted);
        $this->assertFalse($result[1]->isDepleted);
    }

    public function test_it_returns_empty_array_when_no_low_stock(): void
    {
        // Arrange
        $this->stockRepository
            ->expects($this->once())
            ->method('findLowStock')
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
            Quantity::fromInt(5),
            Quantity::fromInt(10)
        );

        $this->stockRepository
            ->expects($this->once())
            ->method('findLowStock')
            ->willReturn([$stock]);

        // Act
        $result = $this->useCase->execute();

        // Assert
        $this->assertEquals($productId->value(), $result[0]->productId);
        $this->assertEquals(5, $result[0]->quantity);
        $this->assertEquals(10, $result[0]->minimumQuantity);
        $this->assertTrue($result[0]->isLowStock);
        $this->assertFalse($result[0]->isDepleted);
    }
}
