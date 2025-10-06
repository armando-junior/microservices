<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases;

use DomainException;
use PHPUnit\Framework\TestCase;
use Src\Application\Exceptions\ProductNotFoundException;
use Src\Application\Exceptions\StockNotFoundException;
use Src\Application\UseCases\Product\DeleteProduct\DeleteProductUseCase;
use Src\Domain\Entities\Product;
use Src\Domain\Entities\Stock;
use Src\Domain\Repositories\ProductRepositoryInterface;
use Src\Domain\Repositories\StockRepositoryInterface;
use Src\Domain\ValueObjects\Price;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\ProductName;
use Src\Domain\ValueObjects\Quantity;
use Src\Domain\ValueObjects\SKU;
use Src\Domain\ValueObjects\StockId;

class DeleteProductUseCaseTest extends TestCase
{
    private ProductRepositoryInterface $productRepository;
    private StockRepositoryInterface $stockRepository;
    private DeleteProductUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->stockRepository = $this->createMock(StockRepositoryInterface::class);
        $this->useCase = new DeleteProductUseCase(
            $this->productRepository,
            $this->stockRepository
        );
    }

    public function test_it_deletes_product_without_stock(): void
    {
        // Arrange
        $productId = ProductId::generate();
        $product = Product::create(
            $productId,
            ProductName::fromString('Laptop Dell'),
            SKU::fromString('LAPTOP-001'),
            Price::fromFloat(3500.00)
        );

        $this->productRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($product);

        $this->stockRepository
            ->expects($this->once())
            ->method('findByProductId')
            ->willThrowException(new StockNotFoundException('Stock not found'));

        $this->productRepository
            ->expects($this->once())
            ->method('delete');

        // Act
        $this->useCase->execute($productId->value());

        // Assert - no exception thrown
        $this->assertTrue(true);
    }

    public function test_it_deletes_product_with_depleted_stock(): void
    {
        // Arrange
        $productId = ProductId::generate();
        $product = Product::create(
            $productId,
            ProductName::fromString('Laptop Dell'),
            SKU::fromString('LAPTOP-001'),
            Price::fromFloat(3500.00)
        );

        $stock = Stock::create(
            StockId::generate(),
            $productId,
            Quantity::fromInt(0), // Depleted stock
            Quantity::fromInt(10)
        );

        $this->productRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($product);

        $this->stockRepository
            ->expects($this->once())
            ->method('findByProductId')
            ->willReturn($stock);

        $this->productRepository
            ->expects($this->once())
            ->method('delete');

        // Act
        $this->useCase->execute($productId->value());

        // Assert - no exception thrown
        $this->assertTrue(true);
    }

    public function test_it_throws_exception_when_product_not_found(): void
    {
        // Arrange
        $productId = ProductId::generate()->value(); // Use valid UUID

        $this->productRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        // Assert
        $this->expectException(ProductNotFoundException::class);

        // Act
        $this->useCase->execute($productId);
    }

    public function test_it_throws_exception_when_product_has_stock(): void
    {
        // Arrange
        $productId = ProductId::generate();
        $product = Product::create(
            $productId,
            ProductName::fromString('Laptop Dell'),
            SKU::fromString('LAPTOP-001'),
            Price::fromFloat(3500.00)
        );

        $stock = Stock::create(
            StockId::generate(),
            $productId,
            Quantity::fromInt(150), // Has stock
            Quantity::fromInt(10)
        );

        $this->productRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($product);

        $this->stockRepository
            ->expects($this->once())
            ->method('findByProductId')
            ->willReturn($stock);

        $this->productRepository
            ->expects($this->never())
            ->method('delete');

        // Assert
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot delete product with stock. Current quantity: 150');

        // Act
        $this->useCase->execute($productId->value());
    }
}
