<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
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
        $brandId = $this->route('brand');
        $companyId = auth()->user()->current_company_id;

        return [
            'name' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'string',
                'max:255',
                Rule::unique('brands', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($brandId),
            ],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('brands', 'slug')
                    ->where('company_id', $companyId)
                    ->ignore($brandId),
            ],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp,svg', 'max:1024'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم الماركة مطلوب',
            'name.unique' => 'اسم الماركة مستخدم بالفعل',
            'name.max' => 'اسم الماركة طويل جداً (الحد الأقصى 255 حرف)',
            'slug.unique' => 'الـ Slug مستخدم بالفعل',
            'slug.alpha_dash' => 'الـ Slug يجب أن يحتوي على أحرف وأرقام وشرطات فقط',
            'logo.image' => 'الملف يجب أن يكون صورة',
            'logo.max' => 'حجم الشعار لا يمكن أن يتجاوز 1MB',
            'website.url' => 'رابط الموقع غير صحيح',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'اسم الماركة',
            'name_ar' => 'الاسم بالعربية',
            'slug' => 'الرابط الدائم',
            'logo' => 'الشعار',
            'website' => 'الموقع الإلكتروني',
            'description' => 'الوصف',
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
            ]);
        }

        // Auto-generate slug if not provided
        if (!$this->has('slug') && $this->has('name')) {
            $this->merge(['slug' => \Str::slug($this->input('name'))]);
        }
    }
}
