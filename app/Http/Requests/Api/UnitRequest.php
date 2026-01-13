<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
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
        $unitId = $this->route('unit');
        $companyId = auth()->user()->current_company_id;

        return [
            'name' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'string',
                'max:100',
                Rule::unique('units', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($unitId),
            ],
            'short_name' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'string',
                'max:20',
                Rule::unique('units', 'short_name')
                    ->where('company_id', $companyId)
                    ->ignore($unitId),
            ],
            'base_unit_id' => [
                'nullable',
                'uuid',
                'exists:units,id',
                // Prevent setting itself as base unit
                Rule::notIn([$unitId]),
            ],
            'conversion_factor' => ['nullable', 'numeric', 'min:0.0001', 'max:999999.9999'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم الوحدة مطلوب',
            'name.unique' => 'اسم الوحدة مستخدم بالفعل',
            'name.max' => 'اسم الوحدة طويل جداً (الحد الأقصى 100 حرف)',
            'short_name.required' => 'اختصار الوحدة مطلوب',
            'short_name.unique' => 'اختصار الوحدة مستخدم بالفعل',
            'short_name.max' => 'اختصار الوحدة طويل جداً (الحد الأقصى 20 حرف)',
            'base_unit_id.exists' => 'الوحدة الأساسية غير موجودة',
            'base_unit_id.not_in' => 'لا يمكن تعيين الوحدة كوحدة أساسية لنفسها',
            'conversion_factor.min' => 'معدل التحويل يجب أن يكون أكبر من صفر',
            'conversion_factor.max' => 'معدل التحويل كبير جداً',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'اسم الوحدة',
            'short_name' => 'اختصار الوحدة',
            'base_unit_id' => 'الوحدة الأساسية',
            'conversion_factor' => 'معدل التحويل',
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

        // If no base unit, conversion factor is 1
        if (!$this->has('base_unit_id') || empty($this->input('base_unit_id'))) {
            $this->merge(['conversion_factor' => 1]);
        } elseif (!$this->has('conversion_factor')) {
            $this->merge(['conversion_factor' => 1]);
        }
    }
}
