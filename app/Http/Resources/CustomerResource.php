<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_number' => $this->customer_number,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'email' => $this->email,
            'phone' => $this->phone,
            'vat_number' => $this->vat_number,
            'address' => $this->address,
            'city' => $this->city,
            'credit_limit' => (float) $this->credit_limit,
            'current_balance' => (float) ($this->current_balance ?? 0),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
