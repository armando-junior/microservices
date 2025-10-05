<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Application\DTOs\ProductDTO;

/**
 * Product API Resource
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ProductDTO $product */
        $product = $this->resource;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price,
            'category_id' => $product->categoryId,
            'barcode' => $product->barcode,
            'description' => $product->description,
            'status' => $product->status,
            'created_at' => $product->createdAt,
            'updated_at' => $product->updatedAt,
        ];
    }
}

