<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'display_name' => $this->display_name,
            
            // Contact
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            
            // Legal & Tax
            'vat_number' => $this->vat_number,
            'cr_number' => $this->cr_number,
            
            // Address
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            
            // Payment
            'payment_terms' => $this->payment_terms,
            'payment_terms_label' => $this->getPaymentTermsLabel(),
            
            // Accounting
            'payable_account_id' => $this->payable_account_id,
            
            // Computed - Balance
            'total_balance' => $this->whenAppended('total_balance', function () {
                return (float) $this->total_balance;
            }),
            'balance_status' => $this->getBalanceStatus(),
            
            // Statistics
            'total_purchase_orders' => $this->whenCounted('purchaseOrders'),
            'total_bills' => $this->whenCounted('bills'),
            'total_payments' => $this->whenCounted('payments'),
            
            // Status
            'is_active' => $this->is_active,
            
            // Metadata
            'metadata' => $this->metadata,
            
            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Get payment terms label
     */
    protected function getPaymentTermsLabel(): string
    {
        if ($this->payment_terms === 0) {
            return 'نقداً فوري';
        }
        
        return "آجل {$this->payment_terms} يوم";
    }

    /**
     * Get balance status
     */
    protected function getBalanceStatus(): string
    {
        $balance = $this->total_balance ?? 0;
        
        if ($balance == 0) {
            return 'clear'; // مسدد
        } elseif ($balance > 0) {
            return 'due'; // مستحق
        } else {
            return 'overpaid'; // زيادة دفع
        }
    }
}
