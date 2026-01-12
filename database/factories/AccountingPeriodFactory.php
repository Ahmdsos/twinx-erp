<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PeriodStatus;
use App\Models\AccountingPeriod;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AccountingPeriod>
 */
class AccountingPeriodFactory extends Factory
{
    protected $model = AccountingPeriod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = Carbon::now()->startOfMonth();

        return [
            'company_id' => Company::factory(),
            'name' => $date->format('F Y'),
            'name_ar' => $date->locale('ar')->translatedFormat('F Y'),
            'start_date' => $date->copy()->startOfMonth(),
            'end_date' => $date->copy()->endOfMonth(),
            'fiscal_year' => $date->year,
            'period_number' => $date->month,
            'status' => PeriodStatus::OPEN,
            'closed_at' => null,
            'closed_by' => null,
        ];
    }

    /**
     * Create a closed period.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PeriodStatus::CLOSED,
            'closed_at' => now(),
        ]);
    }

    /**
     * Create a locked period.
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PeriodStatus::LOCKED,
            'closed_at' => now(),
        ]);
    }

    /**
     * Create for specific month.
     */
    public function forMonth(int $year, int $month): static
    {
        $date = Carbon::create($year, $month, 1);

        return $this->state(fn (array $attributes) => [
            'name' => $date->format('F Y'),
            'start_date' => $date->copy()->startOfMonth(),
            'end_date' => $date->copy()->endOfMonth(),
            'fiscal_year' => $year,
            'period_number' => $month,
        ]);
    }
}
