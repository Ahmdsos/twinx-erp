<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
        $categoryId = $this->route('category');
        $companyId = auth()->user()->current_company_id;

        return [
            'name' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'string',
                'max:255',
            ],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('product_categories', 'slug')
                    ->where('company_id', $companyId)
                    ->ignore($categoryId),
            ],
            'parent_id' => [
                'nullable',
                'uuid',
                'exists:product_categories,id',
                // Prevent setting itself as parent
                Rule::notIn([$categoryId]),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp,svg', 'max:1024'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم التصنيف مطلوب',
            'name.max' => 'اسم التصنيف طويل جداً (الحد الأقصى 255 حرف)',
            'slug.unique' => 'الـ Slug مستخدم بالفعل',
            'slug.alpha_dash' => 'الـ Slug يجب أن يحتوي على أحرف وأرقام وشرطات فقط',
            'parent_id.exists' => 'التصنيف الأب غير موجود',
            'parent_id.not_in' => 'لا يمكن تعيين التصنيف كأب لنفسه',
            'image.image' => 'الملف يجب أن يكون صورة',
            'image.max' => 'حجم الصورة لا يمكن أن يتجاوز 1MB',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'اسم التصنيف',
            'name_ar' => 'الاسم بالعربية',
            'slug' => 'الرابط الدائم',
            'parent_id' => 'التصنيف الأب',
            'description' => 'الوصف',
            'image' => 'الصورة',
            'sort_order' => 'ترتيب العرض',
            'is_active' => 'الحالة',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('POST')) {
            $this->merge([
                'is_active' => $this->input('is_active', true),
                'sort_order' => $this->input('sort_order', 0),
            ]);
        }

        // Auto-generate slug if not provided
        if (!$this->has('slug') && $this->has('name')) {
            $this->merge(['slug' => \Str::slug($this->input('name'))]);
        }
    }
}
