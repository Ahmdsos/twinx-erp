<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): Response
    {
        $query = Invoice::where('company_id', $this->tenantContext->companyId())
            ->with(['customer:id,name,customer_number', 'lines']);
        
        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }
        
        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        
        // Filter by date range
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('invoice_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('invoice_date', '<=', $dateTo);
        }
        
        $invoices = $query->orderByDesc('invoice_date')->paginate(20)->withQueryString();
        
        // Stats
        $today = now()->toDateString();
        $stats = [
            'today_sales' => Invoice::where('company_id', $this->tenantContext->companyId())
                ->whereDate('invoice_date', $today)
                ->where('status', 'paid')
                ->sum('total'),
            'today_count' => Invoice::where('company_id', $this->tenantContext->companyId())
                ->whereDate('invoice_date', $today)
                ->count(),
            'pending' => Invoice::where('company_id', $this->tenantContext->companyId())
                ->where('status', 'issued')
                ->count(),
            'month_sales' => Invoice::where('company_id', $this->tenantContext->companyId())
                ->whereMonth('invoice_date', now()->month)
                ->whereYear('invoice_date', now()->year)
                ->where('status', 'paid')
                ->sum('total'),
        ];
        
        // Get customers for dropdown
        $customers = Customer::where('company_id', $this->tenantContext->companyId())
            ->select('id', 'name', 'customer_number')
            ->orderBy('name')
            ->get();
        
        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'customers' => $customers,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Store a newly created invoice.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        // Calculate totals
        $subtotal = 0;
        $taxTotal = 0;
        
        foreach ($validated['items'] as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'] - ($item['discount'] ?? 0);
            $subtotal += $lineTotal;
            $taxTotal += $lineTotal * 0.15; // 15% VAT
        }
        
        $total = $subtotal + $taxTotal;
        
        // Generate invoice number
        $count = Invoice::where('company_id', $this->tenantContext->companyId())->count() + 1;
        $invoiceNumber = 'INV-' . now()->format('Y') . '-' . str_pad((string)$count, 5, '0', STR_PAD_LEFT);
        
        // Create invoice
        $invoice = Invoice::create([
            'company_id' => $this->tenantContext->companyId(),
            'customer_id' => $validated['customer_id'],
            'invoice_number' => $invoiceNumber,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => $subtotal,
            'tax_amount' => $taxTotal,
            'total' => $total,
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
        ]);
        
        // Create invoice lines
        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            $invoice->lines()->create([
                'product_id' => $item['product_id'],
                'description' => $product->name,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount' => $item['discount'] ?? 0,
                'tax_rate' => 15,
                'line_total' => ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0),
            ]);
        }
        
        return redirect()->route('admin.invoices.index')
            ->with('success', 'تم إنشاء الفاتورة بنجاح');
    }

    /**
     * Mark invoice as paid.
     */
    public function markPaid(Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        
        $invoice->update([
            'status' => 'paid',
            'paid_date' => now(),
        ]);
        
        return redirect()->back()->with('success', 'تم تحصيل الفاتورة بنجاح');
    }

    /**
     * Cancel invoice.
     */
    public function cancel(Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        
        $invoice->update(['status' => 'cancelled']);
        
        return redirect()->back()->with('success', 'تم إلغاء الفاتورة');
    }
}
