<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Application\DTOs\CustomerDTO;

/**
 * Customer API Resource
 */
class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CustomerDTO $this->resource */
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_formatted' => $this->phoneFormatted,
            'document' => $this->document,
            'document_formatted' => $this->documentFormatted,
            'document_type' => $this->documentType,
            'address' => [
                'street' => $this->addressStreet,
                'number' => $this->addressNumber,
                'complement' => $this->addressComplement,
                'city' => $this->addressCity,
                'state' => $this->addressState,
                'zip_code' => $this->addressZipCode,
            ],
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
