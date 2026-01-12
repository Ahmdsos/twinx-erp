<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => strtoupper(fake()->unique()->lexify('CUST-????')),
            'name' => fake()->company(),
            'name_ar' => 'عميل ' . fake()->word(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'mobile' => fake()->phoneNumber(),
            'vat_number' => fake()->numerify('3#########00003'),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'country' => 'SA',
            'credit_limit' => fake()->randomFloat(2, 5000, 100000),
            'payment_terms' => fake()->randomElement([0, 7, 15, 30]),
            'is_active' => true,
        ];
    }

    public function withCreditLimit(float $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_limit' => $limit,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
