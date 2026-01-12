<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        $units = [
            ['code' => 'PCS', 'name' => 'Piece', 'name_ar' => 'قطعة'],
            ['code' => 'BOX', 'name' => 'Box', 'name_ar' => 'علبة'],
            ['code' => 'CTN', 'name' => 'Carton', 'name_ar' => 'كرتون'],
            ['code' => 'KG', 'name' => 'Kilogram', 'name_ar' => 'كيلوغرام'],
            ['code' => 'LTR', 'name' => 'Liter', 'name_ar' => 'لتر'],
        ];
        
        $unit = fake()->randomElement($units);

        return [
            'company_id' => Company::factory(),
            'code' => $unit['code'] . fake()->unique()->numerify('##'),
            'name' => $unit['name'],
            'name_ar' => $unit['name_ar'],
            'is_active' => true,
        ];
    }

    public function piece(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'PCS',
            'name' => 'Piece',
            'name_ar' => 'قطعة',
        ]);
    }

    public function carton(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'CTN',
            'name' => 'Carton',
            'name_ar' => 'كرتون',
        ]);
    }
}
