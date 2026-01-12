<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . fake()->unique()->numerify('#####'),
            'type' => InvoiceType::SALES,
            'customer_id' => Customer::factory(),
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => InvoiceStatus::DRAFT,
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

    public function issued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::ISSUED,
            'issued_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::PAID,
            'amount_paid' => $attributes['total'] ?? 0,
            'balance_due' => 0,
        ]);
    }
}
