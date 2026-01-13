<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuotationController extends Controller
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Display a listing of quotations.
     */
    public function index(Request $request): Response
    {
        $query = Quotation::where('company_id', $this->tenantContext->companyId())
            ->with(['customer:id,name,customer_number']);
        
        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('quotation_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }
        
        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        
        $quotations = $query->orderByDesc('quotation_date')->paginate(20)->withQueryString();
        
        // Stats
        $stats = [
            'total' => Quotation::where('company_id', $this->tenantContext->companyId())->count(),
            'pending' => Quotation::where('company_id', $this->tenantContext->companyId())->where('status', 'sent')->count(),
            'month_value' => Quotation::where('company_id', $this->tenantContext->companyId())
                ->whereMonth('quotation_date', now()->month)
                ->sum('total'),
        ];
        
        $customers = Customer::where('company_id', $this->tenantContext->companyId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        
        return Inertia::render('Quotations/Index', [
            'quotations' => $quotations,
            'customers' => $customers,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Store a newly created quotation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'valid_until' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        $subtotal = collect($validated['items'])->sum(fn($i) => $i['quantity'] * $i['unit_price']);
        $taxAmount = $subtotal * 0.15;
        
        $count = Quotation::where('company_id', $this->tenantContext->companyId())->count() + 1;
        $quotationNumber = 'QT-' . now()->format('Y') . '-' . str_pad((string)$count, 5, '0', STR_PAD_LEFT);
        
        $quotation = Quotation::create([
            'company_id' => $this->tenantContext->companyId(),
            'customer_id' => $validated['customer_id'],
            'quotation_number' => $quotationNumber,
            'quotation_date' => now(),
            'valid_until' => $validated['valid_until'] ?? now()->addDays(30),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount,
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
        ]);
        
        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            $quotation->lines()->create([
                'product_id' => $item['product_id'],
                'description' => $product->name,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'line_total' => $item['quantity'] * $item['unit_price'],
            ]);
        }
        
        return redirect()->route('admin.quotations.index')
            ->with('success', 'تم إنشاء عرض السعر بنجاح');
    }

    /**
     * Convert quotation to invoice.
     */
    public function convertToInvoice(Quotation $quotation)
    {
        // Mark quotation as accepted
        $quotation->update(['status' => 'accepted']);
        
        return redirect()->back()->with('success', 'تم قبول عرض السعر. يمكنك الآن إنشاء فاتورة منه.');
    }
}
