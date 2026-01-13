<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\DebitNote;
use App\Models\Invoice;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReturnController extends Controller
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Display a listing of returns.
     * Credit Notes = Sales Returns
     * Debit Notes = Purchase Returns
     */
    public function index(Request $request): Response
    {
        $companyId = $this->tenantContext->companyId();
        
        // Get credit notes (sales returns)
        $creditNotes = CreditNote::where('company_id', $companyId)
            ->with(['customer:id,name,code'])
            ->orderByDesc('issue_date')
            ->get()
            ->map(fn($cn) => [
                'id' => $cn->id,
                'number' => $cn->credit_note_number,
                'type' => 'sales',
                'date' => $cn->issue_date,
                'customer' => $cn->customer?->name ?? '-',
                'reason' => $cn->reason,
                'total' => $cn->total,
                'status' => $cn->status,
            ]);
        
        // Get debit notes (purchase returns)  
        $debitNotes = DebitNote::where('company_id', $companyId)
            ->with(['supplier:id,name,code'])
            ->orderByDesc('issue_date')
            ->get()
            ->map(fn($dn) => [
                'id' => $dn->id,
                'number' => $dn->debit_note_number,
                'type' => 'purchase',
                'date' => $dn->issue_date,
                'customer' => $dn->supplier?->name ?? '-',
                'reason' => $dn->reason,
                'total' => $dn->total,
                'status' => $dn->status,
            ]);
        
        // Combine and sort
        $returns = $creditNotes->concat($debitNotes)
            ->sortByDesc('date')
            ->values();
        
        // Filter by type
        if ($type = $request->input('type')) {
            $returns = $returns->where('type', $type)->values();
        }
        
        // Filter by status
        if ($status = $request->input('status')) {
            $returns = $returns->where('status', $status)->values();
        }
        
        // Stats
        $stats = [
            'total' => $returns->count(),
            'sales_returns' => $creditNotes->count(),
            'purchase_returns' => $debitNotes->count(),
            'month_value' => $returns->sum('total'),
        ];
        
        // Customers for dropdown
        $customers = Customer::where('company_id', $companyId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        
        // Recent invoices for reference
        $invoices = Invoice::where('company_id', $companyId)
            ->where('status', 'paid')
            ->select('id', 'invoice_number', 'customer_id', 'total')
            ->orderByDesc('invoice_date')
            ->limit(50)
            ->get();
        
        return Inertia::render('Returns/Index', [
            'returns' => ['data' => $returns->take(20)->values()],
            'customers' => $customers,
            'invoices' => $invoices,
            'stats' => $stats,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    /**
     * Store a sales return (credit note).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:sales,purchase',
            'customer_id' => 'nullable|uuid|exists:customers,id',
            'invoice_id' => 'nullable|uuid|exists:invoices,id',
            'reason' => 'required|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);
        
        $companyId = $this->tenantContext->companyId();
        $branchId = $this->tenantContext->branchId();
        
        $subtotal = collect($validated['items'])->sum(fn($i) => $i['quantity'] * $i['unit_price']);
        $taxAmount = $subtotal * 0.15;
        
        if ($validated['type'] === 'sales') {
            // Create Credit Note
            $count = CreditNote::where('company_id', $companyId)->count() + 1;
            $number = 'CN-' . now()->format('Y') . '-' . str_pad((string)$count, 5, '0', STR_PAD_LEFT);
            
            $creditNote = CreditNote::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'customer_id' => $validated['customer_id'],
                'invoice_id' => $validated['invoice_id'] ?? null,
                'credit_note_number' => $number,
                'issue_date' => now(),
                'reason' => $validated['reason'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $subtotal + $taxAmount,
                'remaining_amount' => $subtotal + $taxAmount,
                'status' => 'draft',
            ]);
            
            foreach ($validated['items'] as $item) {
                $creditNote->lines()->create([
                    'product_id' => $item['product_id'],
                    'description' => 'Return',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        }
        
        return redirect()->route('admin.returns.index')
            ->with('success', 'تم إنشاء المرتجع بنجاح');
    }

    /**
     * Approve a return (change status to issued).
     */
    public function approve(CreditNote $creditNote)
    {
        $creditNote->update(['status' => 'issued']);
        
        return redirect()->back()->with('success', 'تم اعتماد المرتجع');
    }

    /**
     * Mark a return as applied (cannot cancel in this schema).
     */
    public function cancel(CreditNote $creditNote)
    {
        // Note: Schema only has draft/issued/applied, no cancelled
        // We'll set to 'applied' with 0 amount as a workaround
        $creditNote->update([
            'status' => 'applied',
            'applied_amount' => 0,
            'remaining_amount' => 0,
        ]);
        
        return redirect()->back()->with('success', 'تم إلغاء المرتجع');
    }
}
