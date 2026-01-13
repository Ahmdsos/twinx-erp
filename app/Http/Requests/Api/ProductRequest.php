<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $productId = $this->route('product');
        $companyId = auth()->user()->current_company_id;

        $rules = [
            // Basic Info
            'sku' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'string',
                'max:50',
                Rule::unique('products', 'sku')
                    ->where('company_id', $companyId)
                    ->ignore($productId),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'barcode')
                    ->where('company_id', $companyId)
                    ->ignore($productId),
            ],
            'name' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            
            // Relations
            'category_id' => ['nullable', 'uuid', 'exists:product_categories,id'],
            'brand_id' => ['nullable', 'uuid', 'exists:brands,id'],
            'unit_id' => ['nullable', 'uuid', 'exists:units,id'],
            
            // Pricing (Multi-Tier)
            'cost_price' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'numeric', 'min:0', 'max:9999999.99'],
            'selling_price' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'numeric', 'min:0', 'max:9999999.99'],
            'retail_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'semi_wholesale_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'quarter_wholesale_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'distributor_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'minimum_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            
            // Tax
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_type' => ['nullable', 'in:inclusive,exclusive'],
            
            // Stock
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'track_stock' => ['nullable', 'boolean'],
            
            // Status
            'is_active' => ['nullable', 'boolean'],
            'is_sellable' => ['nullable', 'boolean'],
            'is_purchasable' => ['nullable', 'boolean'],
            
            // Image
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'image_url' => ['nullable', 'string', 'max:500'],
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'sku.required' => 'كود المنتج (SKU) مطلوب',
            'sku.unique' => 'كود المنتج (SKU) مستخدم بالفعل',
            'barcode.unique' => 'الباركود مستخدم بالفعل',
            'name.required' => 'اسم المنتج مطلوب',
            'name.max' => 'اسم المنتج طويل جداً (الحد الأقصى 255 حرف)',
            'cost_price.required' => 'سعر التكلفة مطلوب',
            'cost_price.min' => 'سعر التكلفة لا يمكن أن يكون سالباً',
            'selling_price.required' => 'سعر البيع مطلوب',
            'selling_price.min' => 'سعر البيع لا يمكن أن يكون سالباً',
            'tax_rate.max' => 'نسبة الضريبة لا يمكن أن تتجاوز 100%',
            'image.image' => 'الملف يجب أن يكون صورة',
            'image.mimes' => 'الصورة يجب أن تكون من نوع: jpeg, png, jpg, gif, webp',
            'image.max' => 'حجم الصورة لا يمكن أن يتجاوز 2MB',
            'category_id.exists' => 'التصنيف المحدد غير موجود',
            'brand_id.exists' => 'الماركة المحددة غير موجودة',
            'unit_id.exists' => 'الوحدة المحددة غير موجودة',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'sku' => 'كود المنتج',
            'barcode' => 'الباركود',
            'name' => 'اسم المنتج',
            'name_ar' => 'الاسم بالعربية',
            'description' => 'الوصف',
            'category_id' => 'التصنيف',
            'brand_id' => 'الماركة',
            'unit_id' => 'الوحدة',
            'cost_price' => 'سعر التكلفة',
            'selling_price' => 'سعر البيع',
            'retail_price' => 'سعر التجزئة',
            'wholesale_price' => 'سعر الجملة',
            'tax_rate' => 'نسبة الضريبة',
            'stock_quantity' => 'كمية المخزون',
            'reorder_level' => 'حد إعادة الطلب',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if ($this->isMethod('POST')) {
            $this->merge([
                'is_active' => $this->input('is_active', true),
                'is_sellable' => $this->input('is_sellable', true),
                'is_purchasable' => $this->input('is_purchasable', true),
                'track_stock' => $this->input('track_stock', true),
                'tax_rate' => $this->input('tax_rate', config('app.default_tax_rate', 15)),
                'tax_type' => $this->input('tax_type', 'exclusive'),
            ]);
        }

        // If retail_price not set, use selling_price
        if (!$this->has('retail_price') && $this->has('selling_price')) {
            $this->merge(['retail_price' => $this->input('selling_price')]);
        }
    }
}
