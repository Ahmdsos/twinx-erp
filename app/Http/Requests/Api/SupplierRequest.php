<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
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
        $supplierId = $this->route('supplier');

        return [
            // Basic Info
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('suppliers', 'code')
                    ->where('company_id', $this->user()->current_company_id)
                    ->ignore($supplierId),
            ],
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            
            // Contact
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')
                    ->where('company_id', $this->user()->current_company_id)
                    ->ignore($supplierId),
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
            
            // Payment
            'payment_terms' => 'nullable|integer|min:0|max:365',
            
            // Accounting
            'payable_account_id' => 'nullable|uuid|exists:accounts,id',
            
            // Status
            'is_active' => 'boolean',
            
            // Metadata
            'metadata' => 'nullable|array',
            'metadata.notes' => 'nullable|string|max:1000',
            'metadata.tags' => 'nullable|array',
            'metadata.tags.*' => 'string|max:50',
            'metadata.bank_details' => 'nullable|array',
            'metadata.bank_details.bank_name' => 'nullable|string|max:255',
            'metadata.bank_details.account_number' => 'nullable|string|max:50',
            'metadata.bank_details.iban' => 'nullable|string|max:34',
            'metadata.bank_details.swift_code' => 'nullable|string|max:20',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'code' => 'كود المورد',
            'name' => 'اسم المورد',
            'name_ar' => 'الاسم بالعربي',
            'email' => 'البريد الإلكتروني',
            'phone' => 'رقم الهاتف',
            'mobile' => 'رقم الجوال',
            'vat_number' => 'الرقم الضريبي',
            'cr_number' => 'رقم السجل التجاري',
            'address' => 'العنوان',
            'city' => 'المدينة',
            'country' => 'الدولة',
            'payment_terms' => 'شروط الدفع',
            'payable_account_id' => 'حساب الدائنين',
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
            'code.unique' => 'كود المورد مستخدم بالفعل',
            'name.required' => 'اسم المورد مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'vat_number.max' => 'الرقم الضريبي يجب ألا يتجاوز 50 حرف',
            'payment_terms.min' => 'شروط الدفع يجب أن تكون صفر أو أكثر',
            'payment_terms.max' => 'شروط الدفع يجب ألا تتجاوز 365 يوم',
            'payable_account_id.exists' => 'الحساب المحدد غير موجود',
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
            ]);
        }

        // Generate code if not provided (on create)
        if ($this->isMethod('POST') && !$this->has('code')) {
            $this->merge([
                'code' => $this->generateSupplierCode(),
            ]);
        }
    }

    /**
     * Generate unique supplier code
     */
    protected function generateSupplierCode(): string
    {
        $companyId = $this->user()->current_company_id;
        
        $lastSupplier = \App\Models\Supplier::withTrashed()
            ->where('company_id', $companyId)
            ->where('code', 'like', 'SUPP-%')
            ->orderByRaw('CAST(SUBSTRING(code, 6) AS UNSIGNED) DESC')
            ->first();

        if (!$lastSupplier) {
            return 'SUPP-00001';
        }

        $number = intval(substr($lastSupplier->code, 5)) + 1;
        return 'SUPP-' . str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }
}
