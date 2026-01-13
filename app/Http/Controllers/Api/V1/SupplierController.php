<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\SupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends ApiController
{
    /**
     * List suppliers (paginated)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query()
            ->where('company_id', $request->user()->current_company_id);

        // Search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by balance status
        if ($request->has('has_balance') && $request->boolean('has_balance')) {
            $query->whereHas('bills', function ($q) {
                $q->whereIn('status', ['posted', 'partially_paid'])
                  ->where('balance_due', '>', 0);
            });
        }

        // Sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSortFields = ['name', 'code', 'created_at'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Load counts
        $query->withCount(['purchaseOrders', 'bills', 'payments']);

        $perPage = min((int) $request->get('per_page', 20), 100);
        $suppliers = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => SupplierResource::collection($suppliers),
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
            ],
        ]);
    }

    /**
     * Show single supplier
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $supplier = Supplier::where('company_id', $request->user()->current_company_id)
            ->with(['payableAccount'])
            ->withCount(['purchaseOrders', 'bills', 'payments'])
            ->findOrFail($id);

        return $this->success(new SupplierResource($supplier));
    }

    /**
     * Create supplier
     */
    public function store(SupplierRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['company_id'] = $request->user()->current_company_id;

        $supplier = Supplier::create($validated);

        return $this->success(
            new SupplierResource($supplier),
            'تم إنشاء المورد بنجاح',
            201
        );
    }

    /**
     * Update supplier
     */
    public function update(SupplierRequest $request, string $id): JsonResponse
    {
        $supplier = Supplier::where('company_id', $request->user()->current_company_id)
            ->findOrFail($id);

        $supplier->update($request->validated());

        return $this->success(
            new SupplierResource($supplier->fresh()),
            'تم تحديث المورد بنجاح'
        );
    }

    /**
     * Delete supplier
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $supplier = Supplier::where('company_id', $request->user()->current_company_id)
            ->findOrFail($id);

        // Check if supplier has bills or purchase orders
        if ($supplier->bills()->exists() || $supplier->purchaseOrders()->exists()) {
            return $this->error(
                'لا يمكن حذف المورد لوجود فواتير أو أوامر شراء مرتبطة به',
                422
            );
        }

        $supplier->delete();

        return $this->success(null, 'تم حذف المورد بنجاح');
    }

    /**
     * Bulk delete suppliers
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'uuid',
        ]);

        $companyId = $request->user()->current_company_id;
        
        $suppliers = Supplier::where('company_id', $companyId)
            ->whereIn('id', $request->ids)
            ->get();

        $deleted = 0;
        $errors = [];

        foreach ($suppliers as $supplier) {
            if ($supplier->bills()->exists() || $supplier->purchaseOrders()->exists()) {
                $errors[] = "المورد {$supplier->name} لديه فواتير أو أوامر شراء ولا يمكن حذفه";
                continue;
            }
            
            $supplier->delete();
            $deleted++;
        }

        return $this->success([
            'deleted' => $deleted,
            'errors' => $errors,
        ], "تم حذف {$deleted} مورد بنجاح");
    }

    /**
     * Get supplier statement (كشف حساب)
     */
    public function statement(Request $request, string $id): JsonResponse
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        $supplier = Supplier::where('company_id', $request->user()->current_company_id)
            ->findOrFail($id);

        // Get opening balance
        $openingBalance = $supplier->bills()
            ->where('bill_date', '<', $from)
            ->whereIn('status', ['posted', 'partially_paid'])
            ->sum('balance_due');

        // Get transactions in period
        $bills = $supplier->bills()
            ->whereBetween('bill_date', [$from, $to])
            ->orderBy('bill_date')
            ->get()
            ->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'type' => 'bill',
                    'date' => $bill->bill_date,
                    'reference' => $bill->bill_number,
                    'description' => 'فاتورة شراء',
                    'debit' => (float) $bill->total,
                    'credit' => 0,
                    'balance' => 0,
                ];
            });

        $payments = $supplier->payments()
            ->whereBetween('payment_date', [$from, $to])
            ->orderBy('payment_date')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'type' => 'payment',
                    'date' => $payment->payment_date,
                    'reference' => $payment->payment_number ?? '-',
                    'description' => 'دفعة للمورد',
                    'debit' => 0,
                    'credit' => (float) $payment->amount,
                    'balance' => 0,
                ];
            });

        // Merge and sort
        $transactions = $bills->concat($payments)
            ->sortBy('date')
            ->values();

        // Calculate running balance
        $runningBalance = $openingBalance;
        $transactions = $transactions->map(function ($transaction) use (&$runningBalance) {
            $runningBalance += $transaction['debit'] - $transaction['credit'];
            $transaction['balance'] = $runningBalance;
            return $transaction;
        });

        return $this->success([
            'supplier' => new SupplierResource($supplier),
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'opening_balance' => (float) $openingBalance,
            'transactions' => $transactions,
            'closing_balance' => (float) $runningBalance,
        ]);
    }

    /**
     * Get all suppliers (for dropdowns)
     */
    public function all(Request $request): JsonResponse
    {
        $suppliers = Supplier::where('company_id', $request->user()->current_company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'name_ar']);

        return $this->success($suppliers);
    }
}
