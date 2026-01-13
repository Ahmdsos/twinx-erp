<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends ApiController
{
    /**
     * List units (paginated)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Unit::query()
            ->where('company_id', $request->user()->current_company_id)
            ->with('baseUnit:id,name,short_name');

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('short_name', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by base units only
        if ($request->boolean('base_only')) {
            $query->whereNull('base_unit_id');
        }

        $query->orderBy('name');

        $units = $query->paginate($request->get('per_page', 50));

        return $this->paginated($units);
    }

    /**
     * List all units (without pagination for dropdowns)
     */
    public function all(Request $request): JsonResponse
    {
        $units = Unit::where('company_id', $request->user()->current_company_id)
            ->where('is_active', true)
            ->with('baseUnit:id,name,short_name')
            ->orderBy('name')
            ->get(['id', 'name', 'short_name', 'base_unit_id', 'conversion_factor']);

        return $this->success($units, 'All units retrieved');
    }

    /**
     * Show single unit
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $unit = Unit::where('company_id', $request->user()->current_company_id)
            ->with(['baseUnit', 'derivedUnits'])
            ->withCount('products')
            ->findOrFail($id);

        return $this->success($unit);
    }

    /**
     * Create unit
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'short_name' => 'required|string|max:20',
            'base_unit_id' => 'nullable|uuid|exists:units,id',
            'conversion_factor' => 'nullable|numeric|min:0.0001|max:999999',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['company_id'] = $request->user()->current_company_id;
        $validated['is_active'] = $validated['is_active'] ?? true;
        
        // If no base unit, conversion factor is 1
        if (empty($validated['base_unit_id'])) {
            $validated['conversion_factor'] = 1;
        }

        $unit = Unit::create($validated);

        return $this->success($unit->load('baseUnit'), 'تم إنشاء الوحدة بنجاح', 201);
    }

    /**
     * Update unit
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $unit = Unit::where('company_id', $request->user()->current_company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'short_name' => 'sometimes|string|max:20',
            'base_unit_id' => 'nullable|uuid|exists:units,id',
            'conversion_factor' => 'nullable|numeric|min:0.0001|max:999999',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        // Prevent setting itself as base unit
        if (isset($validated['base_unit_id']) && $validated['base_unit_id'] === $id) {
            return $this->error('لا يمكن تعيين الوحدة كوحدة أساسية لنفسها', 422);
        }

        $unit->update($validated);

        return $this->success($unit->fresh()->load('baseUnit'), 'تم تحديث الوحدة بنجاح');
    }

    /**
     * Delete unit
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $unit = Unit::where('company_id', $request->user()->current_company_id)
            ->withCount(['products', 'derivedUnits'])
            ->findOrFail($id);

        // Check for products
        if ($unit->products_count > 0) {
            return $this->error('لا يمكن حذف وحدة مرتبطة بمنتجات', 422);
        }

        // Check for derived units
        if ($unit->derived_units_count > 0) {
            return $this->error('لا يمكن حذف وحدة لها وحدات مشتقة', 422);
        }

        $unit->delete();

        return $this->success(null, 'تم حذف الوحدة بنجاح');
    }

    /**
     * Convert quantity between units
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_unit_id' => 'required|uuid|exists:units,id',
            'to_unit_id' => 'required|uuid|exists:units,id',
            'quantity' => 'required|numeric|min:0',
        ]);

        $fromUnit = Unit::findOrFail($validated['from_unit_id']);
        $toUnit = Unit::findOrFail($validated['to_unit_id']);

        // Convert to base unit first, then to target unit
        $baseQuantity = $validated['quantity'] * $fromUnit->conversion_factor;
        $convertedQuantity = $baseQuantity / $toUnit->conversion_factor;

        return $this->success([
            'from_quantity' => $validated['quantity'],
            'from_unit' => $fromUnit->short_name,
            'to_quantity' => round($convertedQuantity, 4),
            'to_unit' => $toUnit->short_name,
        ], 'تم التحويل بنجاح');
    }
}
