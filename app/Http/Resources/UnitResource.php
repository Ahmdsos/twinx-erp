<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            
            // Basic Info
            'name' => $this->name,
            'short_name' => $this->short_name,
            'description' => $this->description,
            
            // Conversion
            'base_unit_id' => $this->base_unit_id,
            'conversion_factor' => (float) ($this->conversion_factor ?? 1),
            'is_base_unit' => is_null($this->base_unit_id),
            
            // Base unit relation
            'base_unit' => $this->whenLoaded('baseUnit', fn() => [
                'id' => $this->baseUnit->id,
                'name' => $this->baseUnit->name,
                'short_name' => $this->baseUnit->short_name,
            ]),
            
            // Derived units
            'derived_units' => $this->whenLoaded('derivedUnits', function () {
                return $this->derivedUnits->map(fn($unit) => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'short_name' => $unit->short_name,
                    'conversion_factor' => (float) $unit->conversion_factor,
                ]);
            }),
            'derived_units_count' => $this->whenCounted('derivedUnits'),
            
            // Products count
            'products_count' => $this->whenCounted('products'),
            
            // Status
            'is_active' => (bool) $this->is_active,
            
            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
