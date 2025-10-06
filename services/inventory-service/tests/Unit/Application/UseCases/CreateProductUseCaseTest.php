<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases;

use PHPUnit\Framework\TestCase;
use Mockery;
use Src\Application\UseCases\Product\CreateProduct\CreateProductUseCase;
use Src\Application\UseCases\Product\CreateProduct\CreateProductDTO;
use Src\Application\Exceptions\SKUAlreadyExistsException;
use Src\Domain\Repositories\ProductRepositoryInterface;
use Src\Domain\ValueObjects\SKU;

class CreateProductUseCaseTest extends TestCase
{
    private ProductRepositoryInterface $productRepository;
    private CreateProductUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->useCase = new CreateProductUseCase($this->productRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_product_successfully(): void
    {
        $dto = new CreateProductDTO(
            name: 'Test Product',
            sku: 'TEST-001',
            price: 99.99,
            categoryId: '550e8400-e29b-41d4-a716-446655440000',
            barcode: '1234567890',
            description: 'Test description'
        );

        $this->productRepository
            ->shouldReceive('existsSKU')
            ->once()
            ->andReturn(false);

        $this->productRepository
            ->shouldReceive('save')
            ->once();

        $result = $this->useCase->execute($dto);

        $this->assertEquals('Test Product', $result->name);
        $this->assertEquals('TEST-001', $result->sku);
        $this->assertEquals(99.99, $result->price);
    }

    public function test_it_throws_exception_when_sku_already_exists(): void
    {
        $this->expectException(SKUAlreadyExistsException::class);

        $dto = new CreateProductDTO(
            name: 'Test Product',
            sku: 'TEST-001',
            price: 99.99
        );

        $this->productRepository
            ->shouldReceive('existsSKU')
            ->once()
            ->andReturn(true);

        $this->useCase->execute($dto);
    }
}
