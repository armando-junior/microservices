<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases;

use PHPUnit\Framework\TestCase;
use Src\Application\Exceptions\CategoryNotFoundException;
use Src\Application\Exceptions\ProductNotFoundException;
use Src\Application\UseCases\Product\UpdateProduct\UpdateProductDTO;
use Src\Application\UseCases\Product\UpdateProduct\UpdateProductUseCase;
use Src\Domain\Entities\Category;
use Src\Domain\Entities\Product;
use Src\Domain\Repositories\CategoryRepositoryInterface;
use Src\Domain\Repositories\ProductRepositoryInterface;
use Src\Domain\ValueObjects\CategoryId;
use Src\Domain\ValueObjects\CategoryName;
use Src\Domain\ValueObjects\Price;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\ProductName;
use Src\Domain\ValueObjects\SKU;

class UpdateProductUseCaseTest extends TestCase
{
    private ProductRepositoryInterface $productRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private UpdateProductUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->useCase = new UpdateProductUseCase(
            $this->productRepository,
            $this->categoryRepository
        );
    }

    public function test_it_updates_product_name(): void
    {
        // Arrange
        $productId = ProductId::generate();
        $product = Product::create(
            $productId,
            ProductName::fromString('Laptop Dell'),
            SKU::fromString('LAPTOP-001'),
            Price::fromFloat(3500.00)
        );

        $dto = new UpdateProductDTO(
            id: $productId->value(),
            name: 'Laptop Dell Inspiron',
            price: null,
            categoryId: null,
            barcode: null,
            description: null,
            status: null
        );

        $this->productRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($product);

        $this->productRepository
            ->expects($this->once())
            ->method('save');

        // Act
        $result = $this->useCase->execute($dto);

        // Assert
        $this->assertEquals('Laptop Dell Inspiron', $result->name);
    }

    public function test_it_updates_product_price(): void
    {
        // Arrange
        $productId = ProductId::generate();
        $product = Product::create(
            $productId,
            ProductName::fromString('Laptop Dell'),
            SKU::fromString('LAPTOP-001'),
            Price::fromFloat(3500.00)
        );

        $dto = new UpdateProductDTO(
            id: $productId->value(),
            name: null,
            price: 3299.99,
            categoryId: null,
            barcode: null,
            description: null,
            status: null
        );

        $this->productRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($product);

        $this->productRepository
            ->expects($this->once())
            ->method('save');

        // Act
        $result = $this->useCase->execute($dto);

        // Assert
        $this->assertEquals(3299.99, $result->price);
    }

    public function test_it_updates_product_category(): void
    {
        // Arrange
        $productId = ProductId::generate();
        $categoryId = CategoryId::generate();
        
        $product = Product::create(
            $productId,
            ProductName::fromString('Laptop Dell'),
            SKU::fromString('LAPTOP-001'),
            Price::fromFloat(3500.00)
        );

        $category = Category::create(
            $categoryId,
            CategoryName::fromString('Electronics'),
            'Electronic products'
        );

        $dto = new UpdateProductDTO(
            id: $productId->value(),
            name: null,
            price: null,
            categoryId: $categoryId->value(),
            barcode: null,
            description: null,
            status: null
        );

        $this->productRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($product);

        $this->categoryRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($category);

        $this->productRepository
            ->expects($this->once())
            ->method('save');

        // Act
        $result = $this->useCase->execute($dto);

        // Assert
        $this->assertEquals($categoryId->value(), $result->categoryId);
    }

    public function test_it_throws_exception_when_product_not_found(): void
    {
        // Arrange
        $productId = ProductId::generate()->value(); // Use valid UUID
        
        $dto = new UpdateProductDTO(
            id: $productId,
            name: 'Updated Name',
            price: null,
            categoryId: null,
            barcode: null,
            description: null,
            status: null
        );

        $this->productRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        // Assert
        $this->expectException(ProductNotFoundException::class);

        // Act
        $this->useCase->execute($dto);
    }

    public function test_it_throws_exception_when_category_not_found(): void
    {
        // Arrange
        $productId = ProductId::generate();
        $nonExistentCategoryId = CategoryId::generate()->value(); // Use valid UUID
        
        $product = Product::create(
            $productId,
            ProductName::fromString('Laptop Dell'),
            SKU::fromString('LAPTOP-001'),
            Price::fromFloat(3500.00)
        );

        $dto = new UpdateProductDTO(
            id: $productId->value(),
            name: null,
            price: null,
            categoryId: $nonExistentCategoryId,
            barcode: null,
            description: null,
            status: null
        );

        $this->productRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($product);

        $this->categoryRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        // Assert
        $this->expectException(CategoryNotFoundException::class);

        // Act
        $this->useCase->execute($dto);
    }
}
