<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends ApiController
{
    /**
     * List customers (paginated)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query()
            ->where('company_id', $request->user()->current_company_id);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $customers = $query->paginate($request->get('per_page', 15));

        return $this->paginated($customers);
    }

    /**
     * Show single customer
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $customer = Customer::where('company_id', $request->user()->current_company_id)
            ->findOrFail($id);

        return $this->success(new CustomerResource($customer));
    }

    /**
     * Create customer
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_number' => 'nullable|string|max:20',
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'vat_number' => 'nullable|string|max:15',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['company_id'] = $request->user()->current_company_id;

        // Generate customer number if not provided
        if (empty($validated['customer_number'])) {
            $count = Customer::where('company_id', $validated['company_id'])->count();
            $validated['customer_number'] = 'CUST-' . str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
        }

        $customer = Customer::create($validated);

        return $this->success(
            new CustomerResource($customer),
            'تم إنشاء العميل بنجاح',
            201
        );
    }

    /**
     * Update customer
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $customer = Customer::where('company_id', $request->user()->current_company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'vat_number' => 'nullable|string|max:15',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $customer->update($validated);

        return $this->success(
            new CustomerResource($customer->fresh()),
            'تم تحديث العميل بنجاح'
        );
    }

    /**
     * Delete customer
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $customer = Customer::where('company_id', $request->user()->current_company_id)
            ->findOrFail($id);

        $customer->delete();

        return $this->success(null, 'تم حذف العميل بنجاح');
    }
}
