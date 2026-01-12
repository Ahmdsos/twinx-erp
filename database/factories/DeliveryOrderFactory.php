<?php

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\DeliveryOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryOrderFactory extends Factory
{
    protected $model = DeliveryOrder::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'delivery_number' => 'DLV-' . fake()->unique()->numerify('########'),
            'customer_name' => fake('ar_SA')->name(),
            'delivery_address' => fake('ar_SA')->address(),
            'contact_phone' => fake()->phoneNumber(),
            'status' => DeliveryStatus::PENDING,
            'delivery_fee' => fake()->randomFloat(2, 10, 50),
        ];
    }

    public function assigned(): static
    {
        return $this->state(['status' => DeliveryStatus::ASSIGNED]);
    }

    public function delivered(): static
    {
        return $this->state([
            'status' => DeliveryStatus::DELIVERED,
            'delivered_at' => now(),
        ]);
    }
}
