<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Application\DTOs\AccountReceivable\AccountReceivableOutputDTO;

class AccountReceivableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AccountReceivableOutputDTO $account */
        $account = $this->resource;

        return [
            'id' => $account->id,
            'customer_id' => $account->customer_id,
            'category_id' => $account->category_id,
            'description' => $account->description,
            'amount' => $account->amount,
            'issue_date' => $account->issue_date,
            'due_date' => $account->due_date,
            'status' => $account->status,
            'received_at' => $account->received_at,
            'receiving_notes' => $account->receiving_notes,
            'created_at' => $account->created_at,
            'updated_at' => $account->updated_at,
        ];
    }
}


