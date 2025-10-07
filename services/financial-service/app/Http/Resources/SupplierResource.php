<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Application\DTOs\Supplier\SupplierOutputDTO;

class SupplierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SupplierOutputDTO $supplier */
        $supplier = $this->resource;

        return [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'document' => $supplier->document,
            'email' => $supplier->email,
            'phone' => $supplier->phone,
            'address' => $supplier->address,
            'active' => $supplier->active,
            'created_at' => $supplier->created_at,
            'updated_at' => $supplier->updated_at,
        ];
    }
}


