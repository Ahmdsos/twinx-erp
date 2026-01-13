<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request): Response
    {
        $companyId = $this->tenantContext->companyId();
        $query = Supplier::where('company_id', $companyId);
        
        // Search - use correct column names
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Filter by status
        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->input('status') === 'active');
        }
        
        $suppliers = $query->orderBy('name')->paginate(20)->withQueryString();
        
        // Stats - no balance column exists, so simplified
        $stats = [
            'total' => Supplier::where('company_id', $companyId)->count(),
            'active' => Supplier::where('company_id', $companyId)->where('is_active', true)->count(),
            'total_payable' => 0, // Would need to calculate from bills
        ];
        
        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'vat_number' => 'nullable|string|max:15',
            'cr_number' => 'nullable|string|max:20',
            'payment_terms' => 'nullable|integer|min:0',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
        ]);
        
        $companyId = $this->tenantContext->companyId();
        
        // Generate code
        $count = Supplier::where('company_id', $companyId)->count() + 1;
        $validated['code'] = 'SUP-' . str_pad((string)$count, 5, '0', STR_PAD_LEFT);
        $validated['company_id'] = $companyId;
        $validated['is_active'] = true;
        
        Supplier::create($validated);
        
        return redirect()->route('admin.suppliers.index')
            ->with('success', 'تم إضافة المورد بنجاح');
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'vat_number' => 'nullable|string|max:15',
            'cr_number' => 'nullable|string|max:20',
            'payment_terms' => 'nullable|integer|min:0',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);
        
        $supplier->update($validated);
        
        return redirect()->route('admin.suppliers.index')
            ->with('success', 'تم تحديث بيانات المورد بنجاح');
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        
        return redirect()->route('admin.suppliers.index')
            ->with('success', 'تم حذف المورد بنجاح');
    }
}
