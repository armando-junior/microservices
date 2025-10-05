<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Product\CreateProduct;

/**
 * Create Product Input DTO
 */
final class CreateProductDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $sku,
        public readonly float $price,
        public readonly ?string $categoryId = null,
        public readonly ?string $barcode = null,
        public readonly ?string $description = null
    ) {
    }
}

