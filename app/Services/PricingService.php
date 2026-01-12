<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PriceList;

/**
 * Pricing Service
 * 
 * Handles multi-tier pricing logic based on:
 * 1. Customer Type (retail, semi_wholesale, quarter_wholesale, wholesale, distributor)
 * 2. Quantity-based tier pricing
 * 3. Custom Price Lists
 * 4. Additional discounts
 */
class PricingService
{
    /**
     * Get the appropriate price for a product based on customer and quantity
     */
    public function getPrice(Product $product, ?Customer $customer = null, float $quantity = 1): array
    {
        $customerType = $customer ? CustomerType::tryFrom($customer->customer_type ?? 'retail') : CustomerType::RETAIL;
        $basePrice = $this->getTierPrice($product, $customerType, $quantity);
        
        // Check for customer-specific price list
        if ($customer && $customer->price_list_id) {
            $priceList = PriceList::find($customer->price_list_id);
            if ($priceList && $priceList->isValid()) {
                $customPrice = $priceList->getPriceForProduct($product, null, $quantity);
                if ($customPrice !== null) {
                    $basePrice = $customPrice;
                }
            }
        }
        
        // Apply customer discount
        $discount = 0;
        if ($customer && $customer->discount_percentage > 0) {
            $discount = $basePrice * ($customer->discount_percentage / 100);
            $basePrice = $basePrice - $discount;
        }
        
        return [
            'unit_price' => round($basePrice, 4),
            'customer_type' => $customerType->value,
            'customer_type_name' => $customerType->nameAr(),
            'applied_discount' => round($discount, 4),
            'tier_applied' => $this->getAppliedTier($product, $quantity),
        ];
    }
    
    /**
     * Get price based on customer type tier
     */
    public function getTierPrice(Product $product, CustomerType $customerType, float $quantity = 1): float
    {
        // Check if quantity qualifies for higher tier
        $bestTier = $this->determineBestTier($product, $quantity, $customerType);
        
        $priceField = $bestTier->priceField();
        $price = $product->{$priceField};
        
        // Fallback to selling price if tier price not set
        if ($price === null || $price <= 0) {
            return (float) $product->selling_price;
        }
        
        return (float) $price;
    }
    
    /**
     * Determine the best tier based on quantity
     */
    protected function determineBestTier(Product $product, float $quantity, CustomerType $customerType): CustomerType
    {
        $tiers = [
            CustomerType::DISTRIBUTOR,
            CustomerType::WHOLESALE,
            CustomerType::QUARTER_WHOLESALE,
            CustomerType::SEMI_WHOLESALE,
            CustomerType::RETAIL,
        ];
        
        // Start from the customer's base tier and check if quantity qualifies for better
        $customerTierIndex = array_search($customerType, $tiers);
        
        foreach ($tiers as $index => $tier) {
            // Don't give better tier than customer's base tier (unless quantity qualifies)
            if ($index > $customerTierIndex) {
                continue;
            }
            
            $minQtyField = $tier->minQtyField();
            $minQty = $product->{$minQtyField} ?? 1;
            
            if ($quantity >= $minQty) {
                // Check if this tier has a valid price
                $priceField = $tier->priceField();
                $price = $product->{$priceField};
                
                if ($price !== null && $price > 0) {
                    return $tier;
                }
            }
        }
        
        return CustomerType::RETAIL;
    }
    
    /**
     * Get which tier was applied
     */
    protected function getAppliedTier(Product $product, float $quantity): string
    {
        $tiers = [
            ['type' => CustomerType::DISTRIBUTOR, 'field' => 'min_distributor_qty'],
            ['type' => CustomerType::WHOLESALE, 'field' => 'min_wholesale_qty'],
            ['type' => CustomerType::QUARTER_WHOLESALE, 'field' => 'min_quarter_wholesale_qty'],
            ['type' => CustomerType::SEMI_WHOLESALE, 'field' => 'min_semi_wholesale_qty'],
            ['type' => CustomerType::RETAIL, 'field' => 'min_retail_qty'],
        ];
        
        foreach ($tiers as $tier) {
            $minQty = $product->{$tier['field']} ?? 1;
            if ($quantity >= $minQty) {
                $priceField = $tier['type']->priceField();
                $price = $product->{$priceField};
                if ($price !== null && $price > 0) {
                    return $tier['type']->nameAr();
                }
            }
        }
        
        return 'تجزئة';
    }
    
    /**
     * Get all prices for a product (for display)
     */
    public function getAllPrices(Product $product): array
    {
        return [
            'cost_price' => (float) $product->cost_price,
            'retail_price' => (float) $product->selling_price,
            'semi_wholesale_price' => (float) ($product->semi_wholesale_price ?? $product->selling_price),
            'quarter_wholesale_price' => (float) ($product->quarter_wholesale_price ?? $product->selling_price),
            'wholesale_price' => (float) ($product->wholesale_price ?? $product->selling_price),
            'distributor_price' => (float) ($product->distributor_price ?? $product->selling_price),
            'min_quantities' => [
                'retail' => (int) ($product->min_retail_qty ?? 1),
                'semi_wholesale' => (int) ($product->min_semi_wholesale_qty ?? 12),
                'quarter_wholesale' => (int) ($product->min_quarter_wholesale_qty ?? 24),
                'wholesale' => (int) ($product->min_wholesale_qty ?? 48),
                'distributor' => (int) ($product->min_distributor_qty ?? 100),
            ],
        ];
    }
    
    /**
     * Calculate profit margin for each tier
     */
    public function getMargins(Product $product): array
    {
        $cost = (float) $product->cost_price;
        if ($cost <= 0) {
            return [];
        }
        
        $prices = $this->getAllPrices($product);
        
        $margins = [];
        foreach (['retail_price', 'semi_wholesale_price', 'quarter_wholesale_price', 'wholesale_price', 'distributor_price'] as $priceField) {
            $price = $prices[$priceField];
            $margin = (($price - $cost) / $cost) * 100;
            $margins[str_replace('_price', '', $priceField)] = [
                'price' => $price,
                'profit' => $price - $cost,
                'margin_percent' => round($margin, 2),
            ];
        }
        
        return $margins;
    }
}
