<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
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
        return [
            // Company Info
            'name' => ['sometimes', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'cr_number' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            
            // Currency Settings
            'default_currency' => ['nullable', 'string', 'size:3'],
            'decimal_places' => ['nullable', 'integer', 'min:0', 'max:4'],
            'currency_position' => ['nullable', 'in:before,after'],
            'thousand_separator' => ['nullable', 'string', 'max:1'],
            'decimal_separator' => ['nullable', 'string', 'max:1'],
            
            // Tax Settings
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_type' => ['nullable', 'in:inclusive,exclusive'],
            'tax_name' => ['nullable', 'string', 'max:100'],
            
            // Invoice Settings
            'invoice_prefix' => ['nullable', 'string', 'max:20', 'alpha_dash'],
            'quotation_prefix' => ['nullable', 'string', 'max:20', 'alpha_dash'],
            'credit_note_prefix' => ['nullable', 'string', 'max:20', 'alpha_dash'],
            'invoice_footer' => ['nullable', 'string', 'max:1000'],
            'invoice_terms' => ['nullable', 'string', 'max:2000'],
            
            // General Settings
            'date_format' => ['nullable', 'string', 'max:20'],
            'time_format' => ['nullable', 'string', 'max:20'],
            'timezone' => ['nullable', 'string', 'max:50', 'timezone'],
            'language' => ['nullable', 'in:ar,en'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.max' => 'اسم الشركة طويل جداً',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'website.url' => 'رابط الموقع غير صحيح',
            'default_currency.size' => 'رمز العملة يجب أن يكون 3 أحرف',
            'tax_rate.max' => 'نسبة الضريبة لا يمكن أن تتجاوز 100%',
            'timezone.timezone' => 'المنطقة الزمنية غير صحيحة',
            'language.in' => 'اللغة يجب أن تكون عربية أو إنجليزية',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'اسم الشركة',
            'name_ar' => 'الاسم بالعربية',
            'email' => 'البريد الإلكتروني',
            'phone' => 'الهاتف',
            'mobile' => 'الجوال',
            'address' => 'العنوان',
            'city' => 'المدينة',
            'country' => 'البلد',
            'vat_number' => 'الرقم الضريبي',
            'cr_number' => 'السجل التجاري',
            'website' => 'الموقع الإلكتروني',
            'default_currency' => 'العملة الافتراضية',
            'decimal_places' => 'الخانات العشرية',
            'tax_rate' => 'نسبة الضريبة',
            'invoice_prefix' => 'بادئة الفاتورة',
            'timezone' => 'المنطقة الزمنية',
            'language' => 'اللغة',
        ];
    }
}
