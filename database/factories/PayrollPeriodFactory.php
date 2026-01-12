<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollPeriodFactory extends Factory
{
    protected $model = PayrollPeriod::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-3 months', 'now');
        $start = \Carbon\Carbon::instance($startDate)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [
            'company_id' => Company::factory(),
            'name' => $start->format('F Y'),
            'start_date' => $start,
            'end_date' => $end,
            'pay_date' => $end->copy()->addDays(5),
            'status' => 'open',
        ];
    }
}
