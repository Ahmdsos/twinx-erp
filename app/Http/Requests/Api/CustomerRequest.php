<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $customerId = $this->route('customer');

        return [
            // Basic Info
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('customers', 'code')
                    ->where('company_id', $this->user()->current_company_id)
                    ->ignore($customerId),
            ],
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            
            // Contact
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
                    ->where('company_id', $this->user()->current_company_id)
                    ->ignore($customerId),
            ],
            'phone' => 'required|string|max:20',
            'mobile' => 'nullable|string|max:20',
            
            // Legal & Tax
            'vat_number' => 'nullable|string|max:50',
            'cr_number' => 'nullable|string|max:50',
            
            // Address
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|size:2',
            
            // Pricing & Payment
            'price_list_id' => 'nullable|uuid|exists:price_lists,id',
            'credit_limit' => 'nullable|numeric|min:0|max:999999999.99',
            'payment_terms' => 'nullable|integer|min:0|max:365',
            
            // Accounting
            'receivable_account_id' => 'nullable|uuid|exists:accounts,id',
            
            // Status
            'is_active' => 'boolean',
            
            // Metadata
            'metadata' => 'nullable|array',
            'metadata.notes' => 'nullable|string|max:1000',
            'metadata.tags' => 'nullable|array',
            'metadata.tags.*' => 'string|max:50',
            'metadata.custom_fields' => 'nullable|array',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'code' => 'كود العميل',
            'name' => 'اسم العميل',
            'name_ar' => 'الاسم بالعربي',
            'email' => 'البريد الإلكتروني',
            'phone' => 'رقم الهاتف',
            'mobile' => 'رقم الجوال',
            'vat_number' => 'الرقم الضريبي',
            'cr_number' => 'رقم السجل التجاري',
            'address' => 'العنوان',
            'city' => 'المدينة',
            'country' => 'الدولة',
            'price_list_id' => 'قائمة الأسعار',
            'credit_limit' => 'حد الائتمان',
            'payment_terms' => 'شروط الدفع',
            'receivable_account_id' => 'حساب المدينين',
            'is_active' => 'الحالة',
            'metadata' => 'بيانات إضافية',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'كود العميل مستخدم بالفعل',
            'name.required' => 'اسم العميل مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'vat_number.max' => 'الرقم الضريبي يجب ألا يتجاوز 50 حرف',
            'credit_limit.min' => 'حد الائتمان يجب أن يكون صفر أو أكثر',
            'credit_limit.max' => 'حد الائتمان تجاوز الحد المسموح',
            'payment_terms.min' => 'شروط الدفع يجب أن تكون صفر أو أكثر',
            'payment_terms.max' => 'شروط الدفع يجب ألا تتجاوز 365 يوم',
            'price_list_id.exists' => 'قائمة الأسعار المحددة غير موجودة',
            'country.size' => 'كود الدولة يجب أن يكون حرفين فقط',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set defaults
        if ($this->isMethod('POST')) {
            $this->merge([
                'is_active' => $this->input('is_active', true),
                'payment_terms' => $this->input('payment_terms', 30), // Default 30 days
                'credit_limit' => $this->input('credit_limit', 0),
            ]);
        }

        // Generate code if not provided (on create)
        if ($this->isMethod('POST') && !$this->has('code')) {
            $this->merge([
                'code' => $this->generateCustomerCode(),
            ]);
        }
    }

    /**
     * Generate unique customer code
     */
    protected function generateCustomerCode(): string
    {
        $companyId = $this->user()->current_company_id;
        
        $lastCustomer = \App\Models\Customer::withTrashed()
            ->where('company_id', $companyId)
            ->where('code', 'like', 'CUST-%')
            ->orderByRaw('CAST(SUBSTRING(code, 6) AS UNSIGNED) DESC')
            ->first();

        if (!$lastCustomer) {
            return 'CUST-00001';
        }

        $number = intval(substr($lastCustomer->code, 5)) + 1;
        return 'CUST-' . str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }
}
