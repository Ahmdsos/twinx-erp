<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'order_number' => 'PO-' . now()->format('Ymd') . '-' . fake()->unique()->numerify('#####'),
            'supplier_id' => Supplier::factory(),
            'order_date' => now(),
            'status' => PurchaseOrderStatus::DRAFT,
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'currency' => 'SAR',
            'exchange_rate' => 1,
            'created_by' => User::factory(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::CONFIRMED,
            'approved_at' => now(),
        ]);
    }
}
