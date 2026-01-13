<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'name_ar' => $this->name_ar,
            'slug' => $this->slug,
            'description' => $this->description,
            
            // Image
            'image' => $this->image ? url('storage/' . $this->image) : null,
            
            // Hierarchy
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', fn() => [
                'id' => $this->parent->id,
                'name' => $this->parent->name,
                'name_ar' => $this->parent->name_ar,
            ]),
            
            // Children (for tree view)
            'children' => $this->whenLoaded('children', function () {
                return CategoryResource::collection($this->children->sortBy('sort_order'));
            }),
            'children_count' => $this->whenCounted('children'),
            
            // Products count
            'products_count' => $this->whenCounted('products'),
            
            // Display
            'sort_order' => (int) ($this->sort_order ?? 0),
            'depth' => $this->getDepth(),
            'full_path' => $this->getFullPath(),
            
            // Status
            'is_active' => (bool) $this->is_active,
            
            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Get category depth in tree
     */
    protected function getDepth(): int
    {
        $depth = 0;
        $parent = $this->parent;
        
        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }
        
        return $depth;
    }

    /**
     * Get full path (breadcrumb style)
     */
    protected function getFullPath(): string
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }
}
