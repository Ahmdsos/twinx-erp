<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayslipStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'company_id',
        'employee_id',
        'period_id',
        'payslip_number',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'other_allowance',
        'overtime_amount',
        'bonus',
        'total_earnings',
        'gosi_employee',
        'gosi_company',
        'loan_deduction',
        'other_deduction',
        'total_deductions',
        'net_salary',
        'status',
        'approved_by',
        'approved_at',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'housing_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'bonus' => 'decimal:2',
            'total_earnings' => 'decimal:2',
            'gosi_employee' => 'decimal:2',
            'gosi_company' => 'decimal:2',
            'loan_deduction' => 'decimal:2',
            'other_deduction' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'status' => PayslipStatus::class,
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'period_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
