<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BillStatus;
use App\Models\Bill;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bill>
 */
class BillFactory extends Factory
{
    protected $model = Bill::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'bill_number' => 'BILL-' . now()->format('Ymd') . '-' . fake()->unique()->numerify('#####'),
            'supplier_id' => Supplier::factory(),
            'bill_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => BillStatus::DRAFT,
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'amount_paid' => 0,
            'balance_due' => 0,
            'currency' => 'SAR',
            'exchange_rate' => 1,
            'created_by' => User::factory(),
        ];
    }

    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BillStatus::POSTED,
            'posted_at' => now(),
        ]);
    }
}
