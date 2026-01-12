<?php

declare(strict_types=1);

namespace App\Services\HR;

use App\Enums\PayslipStatus;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PayrollPeriod;
use App\Services\TenantContext;

/**
 * Payroll Service
 * خدمة الرواتب
 */
class PayrollService
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Generate payslip for employee
     */
    public function generatePayslip(Employee $employee, PayrollPeriod $period): Payslip
    {
        // Earnings
        $basic = (float) $employee->basic_salary;
        $housing = (float) $employee->housing_allowance;
        $transport = (float) $employee->transport_allowance;
        $other = (float) $employee->other_allowance;
        
        $totalEarnings = $basic + $housing + $transport + $other;

        // GOSI Deductions (Social Insurance)
        $gosiEmployee = $basic * 0.10;  // 10% employee share
        $gosiCompany = $basic * 0.12;   // 12% company share

        $totalDeductions = $gosiEmployee;
        $netSalary = $totalEarnings - $totalDeductions;

        return Payslip::create([
            'company_id' => $this->tenantContext->companyId(),
            'employee_id' => $employee->id,
            'period_id' => $period->id,
            'payslip_number' => $this->generatePayslipNumber($period),
            'basic_salary' => $basic,
            'housing_allowance' => $housing,
            'transport_allowance' => $transport,
            'other_allowance' => $other,
            'overtime_amount' => 0,
            'bonus' => 0,
            'total_earnings' => $totalEarnings,
            'gosi_employee' => $gosiEmployee,
            'gosi_company' => $gosiCompany,
            'loan_deduction' => 0,
            'other_deduction' => 0,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'status' => PayslipStatus::DRAFT,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Generate payslips for all active employees
     */
    public function generateBatch(PayrollPeriod $period): array
    {
        $employees = Employee::where('company_id', $this->tenantContext->companyId())
            ->where('is_active', true)
            ->whereNull('termination_date')
            ->get();

        $payslips = [];
        foreach ($employees as $employee) {
            // Check if payslip already exists
            $exists = Payslip::where('employee_id', $employee->id)
                ->where('period_id', $period->id)
                ->exists();
                
            if (!$exists) {
                $payslips[] = $this->generatePayslip($employee, $period);
            }
        }

        return $payslips;
    }

    /**
     * Approve payslip
     */
    public function approve(Payslip $payslip): Payslip
    {
        $payslip->update([
            'status' => PayslipStatus::APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $payslip->fresh();
    }

    /**
     * Mark payslip as paid
     */
    public function markPaid(Payslip $payslip): Payslip
    {
        $payslip->update([
            'status' => PayslipStatus::PAID,
            'paid_at' => now(),
        ]);

        return $payslip->fresh();
    }

    /**
     * Generate payslip number
     */
    private function generatePayslipNumber(PayrollPeriod $period): string
    {
        $count = Payslip::where('company_id', $this->tenantContext->companyId())
            ->where('period_id', $period->id)
            ->count();

        $periodMonth = $period->start_date->format('Ym');
        return "PS-{$periodMonth}-" . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
