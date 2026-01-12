<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(AccountType::cases());

        return [
            'company_id' => Company::factory(),
            'parent_id' => null,
            'code' => $type->codePrefix() . fake()->unique()->numerify('###'),
            'name' => fake()->words(3, true),
            'name_ar' => 'حساب ' . fake()->word(),
            'type' => $type,
            'is_group' => false,
            'is_system' => false,
            'level' => 1,
            'normal_balance' => $type->normalBalance(),
            'is_active' => true,
            'allow_direct_posting' => true,
            'description' => fake()->sentence(),
            'metadata' => [],
        ];
    }

    /**
     * Create as a group (parent) account.
     */
    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_group' => true,
            'allow_direct_posting' => false,
        ]);
    }

    /**
     * Create as inactive account.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create as asset account.
     */
    public function asset(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::ASSET,
            'code' => '1' . fake()->unique()->numerify('###'),
            'normal_balance' => 'debit',
        ]);
    }

    /**
     * Create as liability account.
     */
    public function liability(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::LIABILITY,
            'code' => '2' . fake()->unique()->numerify('###'),
            'normal_balance' => 'credit',
        ]);
    }

    /**
     * Create as expense account.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::EXPENSE,
            'code' => '6' . fake()->unique()->numerify('###'),
            'normal_balance' => 'debit',
        ]);
    }

    /**
     * Create as revenue account.
     */
    public function revenue(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::REVENUE,
            'code' => '4' . fake()->unique()->numerify('###'),
            'normal_balance' => 'credit',
        ]);
    }
}
