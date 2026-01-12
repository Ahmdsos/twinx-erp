<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\JournalStatus;
use App\Enums\JournalType;
use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Journal>
 */
class JournalFactory extends Factory
{
    protected $model = Journal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'period_id' => AccountingPeriod::factory(),
            'reference' => 'JE-' . date('Y') . '-' . fake()->unique()->numerify('#####'),
            'type' => JournalType::GENERAL,
            'transaction_date' => now(),
            'posting_date' => null,
            'status' => JournalStatus::DRAFT,
            'total_debit' => 0,
            'total_credit' => 0,
            'currency' => 'SAR',
            'exchange_rate' => 1,
            'description' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Create as posted journal.
     */
    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JournalStatus::POSTED,
            'posting_date' => now(),
            'posted_at' => now(),
        ]);
    }

    /**
     * Create as voided journal.
     */
    public function voided(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JournalStatus::VOIDED,
        ]);
    }
}
