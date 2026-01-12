<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmploymentType;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, HasUuid, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'department_id',
        'user_id',
        'employee_number',
        'first_name',
        'last_name',
        'first_name_ar',
        'last_name_ar',
        'national_id',
        'birth_date',
        'gender',
        'nationality',
        'marital_status',
        'email',
        'phone',
        'address',
        'city',
        'hire_date',
        'termination_date',
        'job_title',
        'job_title_ar',
        'employment_type',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'other_allowance',
        'gosi_number',
        'gosi_enrolled',
        'bank_name',
        'iban',
        'passport_number',
        'passport_expiry',
        'work_permit_number',
        'work_permit_expiry',
        'annual_leave_balance',
        'sick_leave_balance',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'hire_date' => 'date',
            'termination_date' => 'date',
            'passport_expiry' => 'date',
            'work_permit_expiry' => 'date',
            'employment_type' => EmploymentType::class,
            'basic_salary' => 'decimal:2',
            'housing_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'annual_leave_balance' => 'decimal:2',
            'sick_leave_balance' => 'decimal:2',
            'gosi_enrolled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getFullNameArAttribute(): string
    {
        return $this->first_name_ar && $this->last_name_ar
            ? "{$this->first_name_ar} {$this->last_name_ar}"
            : $this->full_name;
    }

    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->full_name_ar : $this->full_name;
    }

    public function getTotalSalaryAttribute(): float
    {
        return (float) $this->basic_salary 
            + (float) $this->housing_allowance 
            + (float) $this->transport_allowance 
            + (float) $this->other_allowance;
    }

    // GOSI Calculations (Saudi Social Insurance)
    public function getGosiEmployeeShareAttribute(): float
    {
        return (float) $this->basic_salary * 0.10; // 10%
    }

    public function getGosiCompanyShareAttribute(): float
    {
        return (float) $this->basic_salary * 0.12; // 12%
    }

    // Service Duration
    public function getServiceYearsAttribute(): float
    {
        $endDate = $this->termination_date ?? now();
        return $this->hire_date->diffInYears($endDate);
    }

    // End of Service Benefit (مكافأة نهاية الخدمة)
    public function getEndOfServiceBenefitAttribute(): float
    {
        $years = $this->service_years;
        $salary = (float) $this->basic_salary + (float) $this->housing_allowance;

        if ($years < 2) {
            return 0;
        } elseif ($years <= 5) {
            return ($salary / 2) * $years;
        } else {
            $first5 = ($salary / 2) * 5;
            $remaining = $salary * ($years - 5);
            return $first5 + $remaining;
        }
    }
}
