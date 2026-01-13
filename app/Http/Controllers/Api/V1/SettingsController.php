<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Company;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends ApiController
{
    /**
     * Get all settings for current company
     */
    public function index(Request $request): JsonResponse
    {
        $company = Company::findOrFail($request->user()->current_company_id);

        $settings = [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'name_ar' => $company->name_ar,
                'email' => $company->email,
                'phone' => $company->phone,
                'mobile' => $company->mobile,
                'address' => $company->address,
                'city' => $company->city,
                'country' => $company->country,
                'postal_code' => $company->postal_code,
                'logo' => $company->logo,
                'vat_number' => $company->vat_number,
                'cr_number' => $company->cr_number,
                'website' => $company->website,
            ],
            'currency' => [
                'default_currency' => $company->default_currency ?? 'SAR',
                'decimal_places' => $company->decimal_places ?? 2,
                'currency_position' => $company->currency_position ?? 'before',
                'thousand_separator' => $company->thousand_separator ?? ',',
                'decimal_separator' => $company->decimal_separator ?? '.',
            ],
            'tax' => [
                'tax_rate' => $company->tax_rate ?? 15,
                'tax_number' => $company->vat_number,
                'tax_type' => $company->tax_type ?? 'exclusive',
                'tax_name' => $company->tax_name ?? 'ضريبة القيمة المضافة',
            ],
            'invoice' => [
                'invoice_prefix' => $company->invoice_prefix ?? 'INV-',
                'quotation_prefix' => $company->quotation_prefix ?? 'QT-',
                'credit_note_prefix' => $company->credit_note_prefix ?? 'CN-',
                'invoice_footer' => $company->invoice_footer,
                'invoice_terms' => $company->invoice_terms,
            ],
            'general' => [
                'date_format' => $company->date_format ?? 'YYYY-MM-DD',
                'time_format' => $company->time_format ?? 'HH:mm',
                'timezone' => $company->timezone ?? 'Asia/Riyadh',
                'language' => $company->language ?? 'ar',
            ],
        ];

        return $this->success($settings, 'Settings retrieved');
    }

    /**
     * Update company settings
     */
    public function update(Request $request): JsonResponse
    {
        $company = Company::findOrFail($request->user()->current_company_id);

        $validated = $request->validate([
            // Company info
            'name' => 'sometimes|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'vat_number' => 'nullable|string|max:50',
            'cr_number' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            
            // Currency settings
            'default_currency' => 'nullable|string|size:3',
            'decimal_places' => 'nullable|integer|min:0|max:4',
            'currency_position' => 'nullable|in:before,after',
            'thousand_separator' => 'nullable|string|max:1',
            'decimal_separator' => 'nullable|string|max:1',
            
            // Tax settings
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_type' => 'nullable|in:inclusive,exclusive',
            'tax_name' => 'nullable|string|max:100',
            
            // Invoice settings
            'invoice_prefix' => 'nullable|string|max:20',
            'quotation_prefix' => 'nullable|string|max:20',
            'credit_note_prefix' => 'nullable|string|max:20',
            'invoice_footer' => 'nullable|string|max:1000',
            'invoice_terms' => 'nullable|string|max:2000',
            
            // General settings
            'date_format' => 'nullable|string|max:20',
            'time_format' => 'nullable|string|max:20',
            'timezone' => 'nullable|string|max:50',
            'language' => 'nullable|in:ar,en',
        ]);

        $company->update($validated);

        return $this->success($company->fresh(), 'تم تحديث الإعدادات بنجاح');
    }

    /**
     * Upload company logo
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $company = Company::findOrFail($request->user()->current_company_id);

        // Delete old logo if exists
        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        // Store new logo
        $path = $request->file('logo')->store('logos', 'public');
        $company->update(['logo' => $path]);

        return $this->success([
            'logo' => Storage::disk('public')->url($path),
        ], 'تم رفع الشعار بنجاح');
    }

    /**
     * Get available currencies
     */
    public function currencies(Request $request): JsonResponse
    {
        $currencies = Currency::where(function ($query) use ($request) {
            $query->whereNull('company_id')
                  ->orWhere('company_id', $request->user()->current_company_id);
        })
        ->where('is_active', true)
        ->orderBy('code')
        ->get();

        return $this->success($currencies, 'Currencies retrieved');
    }

    /**
     * Update currency exchange rates
     */
    public function updateCurrencies(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currencies' => 'required|array',
            'currencies.*.code' => 'required|string|size:3',
            'currencies.*.exchange_rate' => 'required|numeric|min:0.0001',
            'currencies.*.is_active' => 'boolean',
        ]);

        $companyId = $request->user()->current_company_id;

        foreach ($validated['currencies'] as $currency) {
            Currency::updateOrCreate(
                [
                    'code' => $currency['code'],
                    'company_id' => $companyId,
                ],
                [
                    'exchange_rate' => $currency['exchange_rate'],
                    'is_active' => $currency['is_active'] ?? true,
                ]
            );
        }

        return $this->success(null, 'تم تحديث أسعار الصرف بنجاح');
    }

    /**
     * Reset settings to default
     */
    public function resetToDefault(Request $request): JsonResponse
    {
        $company = Company::findOrFail($request->user()->current_company_id);

        $company->update([
            'default_currency' => 'SAR',
            'decimal_places' => 2,
            'currency_position' => 'after',
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'tax_rate' => 15,
            'tax_type' => 'exclusive',
            'tax_name' => 'ضريبة القيمة المضافة',
            'date_format' => 'YYYY-MM-DD',
            'time_format' => 'HH:mm',
            'timezone' => 'Asia/Riyadh',
            'language' => 'ar',
        ]);

        return $this->success($company->fresh(), 'تم إعادة الإعدادات للافتراضي');
    }
}
