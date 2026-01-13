<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'code' => $this->code ?? $this->sku,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'description' => $this->description,
            'slug' => $this->slug,
            
            // Image
            'image_url' => $this->image_url ? url('storage/' . $this->image_url) : null,
            
            // Pricing (Multi-Tier)
            'cost_price' => (float) $this->cost_price,
            'selling_price' => (float) $this->selling_price,
            'retail_price' => (float) $this->retail_price,
            'semi_wholesale_price' => (float) ($this->semi_wholesale_price ?? 0),
            'quarter_wholesale_price' => (float) ($this->quarter_wholesale_price ?? 0),
            'wholesale_price' => (float) ($this->wholesale_price ?? 0),
            'distributor_price' => (float) ($this->distributor_price ?? 0),
            'minimum_price' => (float) ($this->minimum_price ?? 0),
            
            // Calculated prices object for easy access
            'prices' => [
                'cost' => (float) $this->cost_price,
                'retail' => (float) $this->retail_price,
                'semi_wholesale' => (float) ($this->semi_wholesale_price ?? 0),
                'quarter_wholesale' => (float) ($this->quarter_wholesale_price ?? 0),
                'wholesale' => (float) ($this->wholesale_price ?? 0),
                'distributor' => (float) ($this->distributor_price ?? 0),
                'minimum' => (float) ($this->minimum_price ?? 0),
            ],
            
            // Tax
            'tax_rate' => (float) ($this->tax_rate ?? 15),
            'tax_type' => $this->tax_type ?? 'exclusive',
            
            // Stock
            'stock_quantity' => (int) ($this->stock_quantity ?? 0),
            'reorder_level' => (int) ($this->reorder_level ?? 10),
            'track_stock' => (bool) ($this->track_stock ?? true),
            
            // Stock Status (computed)
            'stock_status' => $this->getStockStatus(),
            'is_low_stock' => $this->isLowStock(),
            'is_out_of_stock' => $this->isOutOfStock(),
            
            // Relations IDs
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'unit_id' => $this->unit_id,
            
            // Load relations if eager loaded
            'category' => $this->whenLoaded('category', fn() => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'name_ar' => $this->category->name_ar,
                'slug' => $this->category->slug,
            ]),
            'brand' => $this->whenLoaded('brand', fn() => [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'name_ar' => $this->brand->name_ar,
                'logo' => $this->brand->logo,
            ]),
            'unit' => $this->whenLoaded('unit', fn() => [
                'id' => $this->unit->id,
                'name' => $this->unit->name,
                'short_name' => $this->unit->short_name,
            ]),
            
            // Status
            'is_active' => (bool) $this->is_active,
            'is_sellable' => (bool) ($this->is_sellable ?? true),
            'is_purchasable' => (bool) ($this->is_purchasable ?? true),
            
            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Get stock status string
     */
    protected function getStockStatus(): string
    {
        if (!$this->track_stock) {
            return 'untracked';
        }

        $quantity = $this->stock_quantity ?? 0;
        $reorderLevel = $this->reorder_level ?? 10;

        if ($quantity <= 0) {
            return 'out_of_stock';
        }

        if ($quantity <= $reorderLevel) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Check if product is low on stock
     */
    protected function isLowStock(): bool
    {
        if (!$this->track_stock) {
            return false;
        }

        $quantity = $this->stock_quantity ?? 0;
        $reorderLevel = $this->reorder_level ?? 10;

        return $quantity > 0 && $quantity <= $reorderLevel;
    }

    /**
     * Check if product is out of stock
     */
    protected function isOutOfStock(): bool
    {
        if (!$this->track_stock) {
            return false;
        }

        return ($this->stock_quantity ?? 0) <= 0;
    }
}
