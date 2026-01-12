<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'employee_number' => 'EMP-' . fake()->unique()->numerify('####'),
            'first_name' => fake('ar_SA')->firstName(),
            'last_name' => fake('ar_SA')->lastName(),
            'first_name_ar' => fake('ar_SA')->firstName(),
            'last_name_ar' => fake('ar_SA')->lastName(),
            'national_id' => fake()->numerify('##########'),
            'birth_date' => fake()->dateTimeBetween('-50 years', '-20 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'nationality' => 'SA',
            'marital_status' => fake()->randomElement(['single', 'married']),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'hire_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'job_title' => fake()->jobTitle(),
            'employment_type' => EmploymentType::FULL_TIME,
            'basic_salary' => fake()->randomFloat(2, 5000, 25000),
            'housing_allowance' => fake()->randomFloat(2, 1000, 5000),
            'transport_allowance' => fake()->randomFloat(2, 500, 1500),
            'other_allowance' => 0,
            'gosi_enrolled' => true,
            'is_active' => true,
        ];
    }
}
