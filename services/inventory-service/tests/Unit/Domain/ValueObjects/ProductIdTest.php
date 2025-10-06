<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use Src\Domain\ValueObjects\ProductId;
use Src\Domain\Exceptions\InvalidProductIdException;

class ProductIdTest extends TestCase
{
    public function test_it_creates_valid_product_id(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $productId = ProductId::fromString($uuid);

        $this->assertInstanceOf(ProductId::class, $productId);
        $this->assertEquals($uuid, $productId->value());
    }

    public function test_it_generates_new_product_id(): void
    {
        $productId = ProductId::generate();

        $this->assertInstanceOf(ProductId::class, $productId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $productId->value()
        );
    }

    public function test_it_throws_exception_for_invalid_uuid(): void
    {
        $this->expectException(InvalidProductIdException::class);
        
        ProductId::fromString('invalid-uuid');
    }

    public function test_it_throws_exception_for_empty_string(): void
    {
        $this->expectException(InvalidProductIdException::class);
        
        ProductId::fromString('');
    }

    public function test_two_product_ids_with_same_value_are_equal(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $productId1 = ProductId::fromString($uuid);
        $productId2 = ProductId::fromString($uuid);

        $this->assertTrue($productId1->equals($productId2));
    }

    public function test_two_product_ids_with_different_values_are_not_equal(): void
    {
        $productId1 = ProductId::generate();
        $productId2 = ProductId::generate();

        $this->assertFalse($productId1->equals($productId2));
    }
}
