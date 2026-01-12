<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JournalLine>
 */
class JournalLineFactory extends Factory
{
    protected $model = JournalLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'journal_id' => Journal::factory(),
            'account_id' => Account::factory(),
            'cost_center_id' => null,
            'debit' => 0,
            'credit' => 0,
            'description' => fake()->sentence(),
            'line_number' => 1,
        ];
    }

    /**
     * Create as debit line.
     */
    public function debit(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'debit' => $amount,
            'credit' => 0,
        ]);
    }

    /**
     * Create as credit line.
     */
    public function credit(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'debit' => 0,
            'credit' => $amount,
        ]);
    }
}
