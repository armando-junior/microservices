<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use Src\Domain\ValueObjects\SKU;
use Src\Domain\Exceptions\InvalidSKUException;

class SKUTest extends TestCase
{
    public function test_it_creates_valid_sku(): void
    {
        $sku = SKU::fromString('PROD-123-ABC');

        $this->assertInstanceOf(SKU::class, $sku);
        $this->assertEquals('PROD-123-ABC', $sku->value());
    }

    public function test_it_accepts_alphanumeric_with_hyphens(): void
    {
        $sku = SKU::fromString('SKU-2024-001');

        $this->assertEquals('SKU-2024-001', $sku->value());
    }

    public function test_it_throws_exception_for_too_short_sku(): void
    {
        $this->expectException(InvalidSKUException::class);
        $this->expectExceptionMessage('SKU must be at least 3 characters long');
        
        SKU::fromString('AB');
    }

    public function test_it_throws_exception_for_too_long_sku(): void
    {
        $this->expectException(InvalidSKUException::class);
        $this->expectExceptionMessage('SKU must not exceed 50 characters');
        
        SKU::fromString(str_repeat('A', 51));
    }

    public function test_it_throws_exception_for_invalid_characters(): void
    {
        $this->expectException(InvalidSKUException::class);
        $this->expectExceptionMessage('SKU can only contain letters, numbers, hyphens, and underscores');
        
        SKU::fromString('SKU@123');
    }

    public function test_it_accepts_lowercase_and_converts_to_uppercase(): void
    {
        $sku = SKU::fromString('sku-123');
        
        $this->assertEquals('SKU-123', $sku->value());
    }

    public function test_two_skus_with_same_value_are_equal(): void
    {
        $sku1 = SKU::fromString('PROD-001');
        $sku2 = SKU::fromString('PROD-001');

        $this->assertTrue($sku1->equals($sku2));
    }
}
