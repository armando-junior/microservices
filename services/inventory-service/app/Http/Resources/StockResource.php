<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Application\DTOs\StockDTO;

/**
 * Stock API Resource
 */
class StockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var StockDTO $stock */
        $stock = $this->resource;

        return [
            'id' => $stock->id,
            'product_id' => $stock->productId,
            'quantity' => $stock->quantity,
            'minimum_quantity' => $stock->minimumQuantity,
            'maximum_quantity' => $stock->maximumQuantity,
            'is_low_stock' => $stock->isLowStock,
            'is_depleted' => $stock->isDepleted,
            'last_movement_at' => $stock->lastMovementAt,
            'created_at' => $stock->createdAt,
            'updated_at' => $stock->updatedAt,
        ];
    }
}

