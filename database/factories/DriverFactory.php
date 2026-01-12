<?php

namespace Database\Factories;

use App\Enums\DriverStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'driver_number' => 'DRV-' . fake()->unique()->numerify('####'),
            'name' => fake('ar_SA')->name(),
            'name_ar' => fake('ar_SA')->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'license_number' => fake()->numerify('##########'),
            'license_expiry' => fake()->dateTimeBetween('+1 year', '+3 years'),
            'status' => DriverStatus::AVAILABLE,
            'commission_rate' => 5.00,
            'is_active' => true,
        ];
    }

    public function onDelivery(): static
    {
        return $this->state(['status' => DriverStatus::ON_DELIVERY]);
    }

    public function offDuty(): static
    {
        return $this->state(['status' => DriverStatus::OFF_DUTY]);
    }
}
