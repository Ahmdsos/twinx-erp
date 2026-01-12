<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'name_ar' => 'شركة ' . fake()->word(),
            'legal_name' => fake()->company() . ' LLC',
            'tax_number' => fake()->numerify('###########'),
            'commercial_register' => fake()->numerify('##########'),
            'base_currency' => 'SAR',
            'fiscal_year_start' => '01-01',
            'timezone' => 'Asia/Riyadh',
            'default_language' => 'ar',
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'country' => 'SA',
            'is_active' => true,
            'settings' => [],
        ];
    }

    /**
     * Indicate that the company is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
