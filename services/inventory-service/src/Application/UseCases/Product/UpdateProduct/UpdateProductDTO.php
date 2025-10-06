<?php

declare(strict_types=1);

namespace Src\Application\UseCases\Product\UpdateProduct;

/**
 * Update Product DTO
 */
final readonly class UpdateProductDTO
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?float $price = null,
        public ?string $categoryId = null,
        public ?string $barcode = null,
        public ?string $description = null,
        public ?string $status = null,
    ) {
    }
}
