<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WarehouseType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'code' => strtoupper(fake()->unique()->lexify('WH-???')),
            'name' => fake()->city() . ' Warehouse',
            'name_ar' => 'مخزن ' . fake()->word(),
            'type' => WarehouseType::MAIN,
            'address' => fake()->address(),
            'is_active' => true,
            'allow_negative_stock' => false,
        ];
    }

    public function transit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WarehouseType::TRANSIT,
            'name' => 'Transit Warehouse',
        ]);
    }

    public function allowNegative(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_negative_stock' => true,
        ]);
    }
}
