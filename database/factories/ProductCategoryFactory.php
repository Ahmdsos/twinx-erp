<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'parent_id' => null,
            'code' => strtoupper(fake()->unique()->lexify('CAT-???')),
            'name' => fake()->words(2, true),
            'name_ar' => 'فئة ' . fake()->word(),
            'level' => 1,
            'is_active' => true,
        ];
    }
}
