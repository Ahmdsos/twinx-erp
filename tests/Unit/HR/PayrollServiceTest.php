<?php

declare(strict_types=1);

namespace Tests\Unit\HR;

use App\Enums\PayslipStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\HR\PayrollService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private Employee $employee;
    private PayrollPeriod $period;
    private PayrollService $payrollService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'current_branch_id' => $this->branch->id,
        ]);
        $this->actingAs($user);

        $tenantContext = app(TenantContext::class);
        $tenantContext->set($this->company, $this->branch);

        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'basic_salary' => 10000,
            'housing_allowance' => 2500,
            'transport_allowance' => 500,
            'other_allowance' => 0,
        ]);

        $this->period = PayrollPeriod::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->payrollService = app(PayrollService::class);
    }

    /**
     * Test payslip generation calculates correctly.
     */
    public function test_generate_payslip_calculates_correctly(): void
    {
        $payslip = $this->payrollService->generatePayslip($this->employee, $this->period);

        // Earnings
        $this->assertEquals(10000, (float) $payslip->basic_salary);
        $this->assertEquals(2500, (float) $payslip->housing_allowance);
        $this->assertEquals(500, (float) $payslip->transport_allowance);
        $this->assertEquals(13000, (float) $payslip->total_earnings); // 10000 + 2500 + 500

        // GOSI (10% of basic for employee)
        $this->assertEquals(1000, (float) $payslip->gosi_employee); // 10000 * 0.10
        $this->assertEquals(1200, (float) $payslip->gosi_company);  // 10000 * 0.12

        // Net
        $this->assertEquals(1000, (float) $payslip->total_deductions);
        $this->assertEquals(12000, (float) $payslip->net_salary); // 13000 - 1000
    }

    /**
     * Test payslip status workflow.
     */
    public function test_payslip_status_workflow(): void
    {
        $payslip = $this->payrollService->generatePayslip($this->employee, $this->period);
        
        // Initial status
        $this->assertEquals(PayslipStatus::DRAFT, $payslip->status);

        // Approve
        $payslip = $this->payrollService->approve($payslip);
        $this->assertEquals(PayslipStatus::APPROVED, $payslip->status);
        $this->assertNotNull($payslip->approved_at);

        // Pay
        $payslip = $this->payrollService->markPaid($payslip);
        $this->assertEquals(PayslipStatus::PAID, $payslip->status);
        $this->assertNotNull($payslip->paid_at);
    }

    /**
     * Test payslip number generation.
     */
    public function test_payslip_number_generation(): void
    {
        $payslip1 = $this->payrollService->generatePayslip($this->employee, $this->period);
        
        $employee2 = Employee::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        $payslip2 = $this->payrollService->generatePayslip($employee2, $this->period);

        $this->assertStringStartsWith('PS-', $payslip1->payslip_number);
        $this->assertNotEquals($payslip1->payslip_number, $payslip2->payslip_number);
    }

    /**
     * Test GOSI calculations for employee.
     */
    public function test_employee_gosi_calculations(): void
    {
        // Employee model calculations
        $this->assertEquals(1000, $this->employee->gosi_employee_share); // 10% of basic
        $this->assertEquals(1200, $this->employee->gosi_company_share);  // 12% of basic
    }

    /**
     * Test employee total salary calculation.
     */
    public function test_employee_total_salary(): void
    {
        $totalSalary = $this->employee->total_salary;
        
        // Basic + Housing + Transport + Other
        $this->assertEquals(13000, $totalSalary); // 10000 + 2500 + 500 + 0
    }
}
