<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Display a listing of warehouses.
     */
    public function index(Request $request): Response
    {
        $query = Warehouse::where('company_id', $this->tenantContext->companyId())
            ->withCount('stockItems');
        
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        
        $warehouses = $query->orderBy('name')->paginate(20)->withQueryString();
        
        $stats = [
            'total' => Warehouse::where('company_id', $this->tenantContext->companyId())->count(),
            'active' => Warehouse::where('company_id', $this->tenantContext->companyId())->where('is_active', true)->count(),
        ];
        
        return Inertia::render('Warehouses/Index', [
            'warehouses' => $warehouses,
            'stats' => $stats,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created warehouse.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'manager' => 'nullable|string|max:255',
            'is_default' => 'boolean',
        ]);
        
        $validated['company_id'] = $this->tenantContext->companyId();
        $validated['is_active'] = true;
        
        if ($validated['is_default'] ?? false) {
            Warehouse::where('company_id', $this->tenantContext->companyId())->update(['is_default' => false]);
        }
        
        Warehouse::create($validated);
        
        return redirect()->route('admin.warehouses.index')->with('success', 'تم إضافة المستودع بنجاح');
    }

    /**
     * Update the specified warehouse.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'manager' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);
        
        if ($validated['is_default'] ?? false) {
            Warehouse::where('company_id', $this->tenantContext->companyId())->where('id', '!=', $warehouse->id)->update(['is_default' => false]);
        }
        
        $warehouse->update($validated);
        
        return redirect()->route('admin.warehouses.index')->with('success', 'تم تحديث المستودع بنجاح');
    }

    /**
     * Remove the specified warehouse.
     */
    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();
        
        return redirect()->route('admin.warehouses.index')->with('success', 'تم حذف المستودع');
    }
}
