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
    ) {
    }

    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): Response
    {
        $query = Invoice::where('company_id', $this->tenantContext->companyId())
            ->with(['customer:id,name,code']);

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

        // Stats - with try/catch to handle missing data gracefully
        $companyId = $this->tenantContext->companyId();
        $today = now()->toDateString();

        $stats = [
            'today_sales' => 0,
            'today_count' => 0,
            'pending' => 0,
            'month_sales' => 0,
        ];

        try {
            $stats['today_sales'] = Invoice::where('company_id', $companyId)
                ->whereDate('invoice_date', $today)
                ->where('status', 'paid')
                ->sum('total') ?? 0;
            $stats['today_count'] = Invoice::where('company_id', $companyId)
                ->whereDate('invoice_date', $today)
                ->count();
            $stats['pending'] = Invoice::where('company_id', $companyId)
                ->where('status', 'issued')
                ->count();
            $stats['month_sales'] = Invoice::where('company_id', $companyId)
                ->whereMonth('invoice_date', now()->month)
                ->whereYear('invoice_date', now()->year)
                ->where('status', 'paid')
                ->sum('total') ?? 0;
        } catch (\Exception $e) {
            // Ignore stats errors
        }

        // Get customers for dropdown
        $customers = Customer::where('company_id', $companyId)
            ->select('id', 'name', 'code')
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
            'notes' => 'nullable|string',
        ]);

        // Calculate totals
        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }
        $taxTotal = $subtotal * 0.15;
        $total = $subtotal + $taxTotal;

        // Generate invoice number
        $count = Invoice::where('company_id', $this->tenantContext->companyId())->count() + 1;
        $invoiceNumber = 'INV-' . now()->format('Y') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);

        // Create invoice
        $invoice = Invoice::create([
            'company_id' => $this->tenantContext->companyId(),
            'branch_id' => $this->tenantContext->branchId(),
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
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $invoice->lines()->create([
                'product_id' => $item['product_id'],
                'description' => $product?->name ?? 'Product',
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => 15,
                'tax_amount' => $lineTotal * 0.15,
                'line_total' => $lineTotal,
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
        $invoice->update([
            'status' => 'paid',
            'paid_amount' => $invoice->total,
        ]);

        return redirect()->back()->with('success', 'تم تحصيل الفاتورة بنجاح');
    }

    /**
     * Cancel invoice.
     */
    public function cancel(Invoice $invoice)
    {
        $invoice->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', 'تم إلغاء الفاتورة');
    }
}
