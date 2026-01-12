<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->city() . ' Branch',
            'name_ar' => 'فرع ' . fake()->word(),
            'code' => strtoupper(fake()->unique()->lexify('???-###')),
            'type' => 'branch',
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'latitude' => fake()->latitude(20, 30),
            'longitude' => fake()->longitude(40, 50),
            'timezone' => null,
            'is_active' => true,
            'sort_order' => 0,
            'settings' => [],
        ];
    }

    /**
     * Create as headquarters.
     */
    public function headquarters(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'headquarters',
            'name' => 'Headquarters',
            'name_ar' => 'المقر الرئيسي',
        ]);
    }

    /**
     * Create as warehouse.
     */
    public function warehouse(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'warehouse',
            'name' => fake()->city() . ' Warehouse',
            'name_ar' => 'مستودع ' . fake()->word(),
        ]);
    }

    /**
     * Indicate that the branch is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
