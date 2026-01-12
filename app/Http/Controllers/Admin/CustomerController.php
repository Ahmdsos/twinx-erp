<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Display a listing of customers.
     */
    public function index(Request $request): Response
    {
        $query = Customer::where('company_id', $this->tenantContext->companyId());
        
        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('customer_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Filter by type
        if ($type = $request->input('type')) {
            $query->where('customer_type', $type);
        }
        
        // Filter by balance
        if ($balance = $request->input('balance')) {
            if ($balance === 'debit') {
                $query->where('balance', '<', 0);
            } elseif ($balance === 'credit') {
                $query->where('balance', '>', 0);
            } elseif ($balance === 'zero') {
                $query->where('balance', 0);
            }
        }
        
        $customers = $query->orderBy('name')->paginate(20)->withQueryString();
        
        // Stats
        $stats = [
            'total' => Customer::where('company_id', $this->tenantContext->companyId())->count(),
            'retail' => Customer::where('company_id', $this->tenantContext->companyId())->where('customer_type', 'retail')->count(),
            'wholesale' => Customer::where('company_id', $this->tenantContext->companyId())->where('customer_type', 'wholesale')->count(),
            'total_debt' => Customer::where('company_id', $this->tenantContext->companyId())->where('balance', '<', 0)->sum('balance'),
            'total_credit_limit' => Customer::where('company_id', $this->tenantContext->companyId())->sum('credit_limit'),
        ];
        
        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'stats' => $stats,
            'filters' => $request->only(['search', 'type', 'balance']),
        ]);
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'customer_type' => 'required|in:retail,semi_wholesale,quarter_wholesale,wholesale,distributor',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_number' => 'nullable|string|max:50',
            'commercial_reg' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        $validated['company_id'] = $this->tenantContext->companyId();
        $validated['customer_number'] = 'C-' . str_pad((string)(Customer::where('company_id', $this->tenantContext->companyId())->count() + 1), 5, '0', STR_PAD_LEFT);
        $validated['balance'] = 0;
        
        Customer::create($validated);
        
        return redirect()->route('admin.customers.index')
            ->with('success', 'تم إضافة العميل بنجاح');
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'customer_type' => 'required|in:retail,semi_wholesale,quarter_wholesale,wholesale,distributor',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_number' => 'nullable|string|max:50',
            'commercial_reg' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        $customer->update($validated);
        
        return redirect()->route('admin.customers.index')
            ->with('success', 'تم تحديث بيانات العميل بنجاح');
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);
        
        $customer->delete();
        
        return redirect()->route('admin.customers.index')
            ->with('success', 'تم حذف العميل بنجاح');
    }
    
    /**
     * Get customer statement.
     */
    public function statement(Customer $customer)
    {
        $this->authorize('view', $customer);
        
        // Get transactions for this customer
        $transactions = collect(); // Would be populated from invoices, payments, etc.
        
        return response()->json([
            'customer' => $customer,
            'transactions' => $transactions,
        ]);
    }
}
