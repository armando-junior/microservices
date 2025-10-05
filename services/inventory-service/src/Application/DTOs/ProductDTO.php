<?php

declare(strict_types=1);

namespace Src\Application\DTOs;

use Src\Domain\Entities\Product;

/**
 * Product Data Transfer Object
 */
final class ProductDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $sku,
        public readonly float $price,
        public readonly ?string $categoryId,
        public readonly ?string $barcode,
        public readonly ?string $description,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly ?string $updatedAt
    ) {
    }

    /**
     * Cria um DTO a partir de uma entidade Product
     */
    public static function fromEntity(Product $product): self
    {
        return new self(
            id: $product->getId()->value(),
            name: $product->getName()->value(),
            sku: $product->getSku()->value(),
            price: $product->getPrice()->value(),
            categoryId: $product->getCategoryId()?->value(),
            barcode: $product->getBarcode(),
            description: $product->getDescription(),
            status: $product->getStatus(),
            createdAt: $product->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $product->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'price' => $this->price,
            'category_id' => $this->categoryId,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

