<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Application\DTOs\AccountPayable\AccountPayableOutputDTO;

class AccountPayableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AccountPayableOutputDTO $account */
        $account = $this->resource;

        return [
            'id' => $account->id,
            'supplier_id' => $account->supplier_id,
            'category_id' => $account->category_id,
            'description' => $account->description,
            'amount' => $account->amount,
            'issue_date' => $account->issue_date,
            'due_date' => $account->due_date,
            'status' => $account->status,
            'paid_at' => $account->paid_at,
            'payment_notes' => $account->payment_notes,
            'created_at' => $account->created_at,
            'updated_at' => $account->updated_at,
        ];
    }
}


