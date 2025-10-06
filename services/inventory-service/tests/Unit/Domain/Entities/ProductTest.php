<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use PHPUnit\Framework\TestCase;
use Src\Domain\Entities\Product;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\ValueObjects\ProductName;
use Src\Domain\ValueObjects\SKU;
use Src\Domain\ValueObjects\Price;
use Src\Domain\ValueObjects\CategoryId;

class ProductTest extends TestCase
{
    public function test_it_creates_product_successfully(): void
    {
        $product = Product::create(
            id: ProductId::generate(),
            name: ProductName::fromString('Test Product'),
            sku: SKU::fromString('TEST-001'),
            price: Price::fromFloat(99.99),
            categoryId: CategoryId::generate(),
            barcode: '1234567890',
            description: 'Test description'
        );

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Test Product', $product->getName()->value());
        $this->assertEquals('TEST-001', $product->getSku()->value());
        $this->assertEquals(99.99, $product->getPrice()->value());
        $this->assertEquals('active', $product->getStatus());
    }

    public function test_it_creates_product_without_optional_fields(): void
    {
        $product = Product::create(
            id: ProductId::generate(),
            name: ProductName::fromString('Minimal Product'),
            sku: SKU::fromString('MIN-001'),
            price: Price::fromFloat(10.0)
        );

        $this->assertNull($product->getCategoryId());
        $this->assertNull($product->getBarcode());
        $this->assertNull($product->getDescription());
    }

    public function test_it_updates_product_name(): void
    {
        $product = Product::create(
            id: ProductId::generate(),
            name: ProductName::fromString('Old Name'),
            sku: SKU::fromString('PROD-001'),
            price: Price::fromFloat(50.0)
        );

        $product->updateName(ProductName::fromString('New Name'));

        $this->assertEquals('New Name', $product->getName()->value());
    }

    public function test_it_updates_product_price(): void
    {
        $product = Product::create(
            id: ProductId::generate(),
            name: ProductName::fromString('Product'),
            sku: SKU::fromString('PROD-001'),
            price: Price::fromFloat(50.0)
        );

        $product->updatePrice(Price::fromFloat(75.0));

        $this->assertEquals(75.0, $product->getPrice()->value());
    }

    public function test_it_activates_product(): void
    {
        $product = Product::create(
            id: ProductId::generate(),
            name: ProductName::fromString('Product'),
            sku: SKU::fromString('PROD-001'),
            price: Price::fromFloat(50.0)
        );

        $product->deactivate();
        $this->assertEquals('inactive', $product->getStatus());

        $product->activate();
        $this->assertEquals('active', $product->getStatus());
    }

    public function test_it_discontinues_product(): void
    {
        $product = Product::create(
            id: ProductId::generate(),
            name: ProductName::fromString('Product'),
            sku: SKU::fromString('PROD-001'),
            price: Price::fromFloat(50.0)
        );

        $product->discontinue();

        $this->assertEquals('discontinued', $product->getStatus());
    }

    public function test_it_checks_if_product_is_active(): void
    {
        $product = Product::create(
            id: ProductId::generate(),
            name: ProductName::fromString('Product'),
            sku: SKU::fromString('PROD-001'),
            price: Price::fromFloat(50.0)
        );

        $this->assertTrue($product->isActive());

        $product->deactivate();
        $this->assertFalse($product->isActive());
    }
}
