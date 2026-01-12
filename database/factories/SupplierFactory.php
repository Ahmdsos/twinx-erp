<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => strtoupper(fake()->unique()->lexify('SUP-????')),
            'name' => fake()->company(),
            'name_ar' => 'مورد ' . fake()->word(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'vat_number' => fake()->numerify('3#########00003'),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'country' => 'SA',
            'payment_terms' => fake()->randomElement([15, 30, 45, 60]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
