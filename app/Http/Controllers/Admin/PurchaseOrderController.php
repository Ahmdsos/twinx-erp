<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request): Response
    {
        $companyId = $this->tenantContext->companyId();
        
        $query = PurchaseOrder::where('company_id', $companyId)
            ->with(['supplier:id,name,code']);
        
        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }
        
        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        
        $orders = $query->orderByDesc('order_date')->paginate(20)->withQueryString();
        
        // Stats with error handling
        $stats = [
            'total' => 0,
            'pending' => 0,
            'month_total' => 0,
        ];
        
        try {
            $stats['total'] = PurchaseOrder::where('company_id', $companyId)->count();
            $stats['pending'] = PurchaseOrder::where('company_id', $companyId)->where('status', 'pending')->count();
            $stats['month_total'] = PurchaseOrder::where('company_id', $companyId)
                ->whereMonth('order_date', now()->month)
                ->sum('total') ?? 0;
        } catch (\Exception $e) {
            // Ignore
        }
        
        // Get suppliers for dropdown - use 'code' not 'supplier_number'
        $suppliers = Supplier::where('company_id', $companyId)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();
        
        return Inertia::render('PurchaseOrders/Index', [
            'orders' => $orders,
            'suppliers' => $suppliers,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Store a newly created purchase order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|uuid|exists:suppliers,id',
            'expected_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        $companyId = $this->tenantContext->companyId();
        
        // Calculate totals
        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }
        $taxAmount = $subtotal * 0.15;
        $total = $subtotal + $taxAmount;
        
        // Generate order number
        $count = PurchaseOrder::where('company_id', $companyId)->count() + 1;
        $orderNumber = 'PO-' . now()->format('Y') . '-' . str_pad((string)$count, 5, '0', STR_PAD_LEFT);
        
        $order = PurchaseOrder::create([
            'company_id' => $companyId,
            'branch_id' => $this->tenantContext->branchId(),
            'supplier_id' => $validated['supplier_id'],
            'order_number' => $orderNumber,
            'order_date' => now(),
            'expected_date' => $validated['expected_date'] ?? null,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
        ]);
        
        // Create order lines
        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            $order->lines()->create([
                'product_id' => $item['product_id'],
                'description' => $product?->name ?? 'Product',
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'line_total' => $item['quantity'] * $item['unit_price'],
            ]);
        }
        
        return redirect()->route('admin.purchase-orders.index')
            ->with('success', 'تم إنشاء أمر الشراء بنجاح');
    }

    /**
     * Approve a purchase order.
     */
    public function approve(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->update(['status' => 'approved']);
        
        return redirect()->back()->with('success', 'تم اعتماد أمر الشراء');
    }

    /**
     * Cancel a purchase order.
     */
    public function cancel(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->update(['status' => 'cancelled']);
        
        return redirect()->back()->with('success', 'تم إلغاء أمر الشراء');
    }
}
