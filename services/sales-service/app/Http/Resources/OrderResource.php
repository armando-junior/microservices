<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Application\DTOs\OrderDTO;

/**
 * Order API Resource
 */
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var OrderDTO $this->resource */
        return [
            'id' => $this->id,
            'order_number' => $this->orderNumber,
            'customer_id' => $this->customerId,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'total' => $this->total,
            'payment_status' => $this->paymentStatus,
            'payment_method' => $this->paymentMethod,
            'notes' => $this->notes,
            'items_count' => count($this->items),
            'items' => OrderItemResource::collection($this->items),
            'confirmed_at' => $this->confirmedAt,
            'cancelled_at' => $this->cancelledAt,
            'delivered_at' => $this->deliveredAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
