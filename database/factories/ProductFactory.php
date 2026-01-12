<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductType;
use App\Enums\ValuationMethod;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'category_id' => null,
            'sku' => strtoupper(fake()->unique()->lexify('SKU-??????')),
            'barcode' => fake()->ean13(),
            'name' => fake()->words(3, true),
            'name_ar' => 'منتج ' . fake()->word(),
            'type' => ProductType::PRODUCT,
            'is_trackable' => true,
            'is_purchasable' => true,
            'is_sellable' => true,
            'cost_price' => fake()->randomFloat(2, 10, 100),
            'sale_price' => fake()->randomFloat(2, 50, 200),
            'min_sale_price' => fake()->randomFloat(2, 40, 100),
            'tax_rate' => 15,
            'is_tax_inclusive' => false,
            'min_stock_level' => 10,
            'reorder_point' => 20,
            'reorder_qty' => 50,
            'valuation_method' => ValuationMethod::WEIGHTED_AVERAGE,
            'is_active' => true,
        ];
    }

    public function fifo(): static
    {
        return $this->state(fn (array $attributes) => [
            'valuation_method' => ValuationMethod::FIFO,
        ]);
    }

    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::SERVICE,
            'is_trackable' => false,
        ]);
    }
}
