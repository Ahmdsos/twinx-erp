<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\TenantContext;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests for TenantContext Service
 * 
 * يتحقق من أن:
 * - يمكن تعيين السياق من الشركة والفرع
 * - يمكن تعيين السياق من المستخدم
 * - Super Admin يتجاوز القيود
 * - يمكن مسح السياق
 */
class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    private TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = new TenantContext();
    }

    /**
     * Test context is not set initially.
     */
    public function test_context_is_not_set_initially(): void
    {
        $this->assertFalse($this->tenantContext->isSet());
        $this->assertNull($this->tenantContext->company());
        $this->assertNull($this->tenantContext->branch());
    }

    /**
     * Test setting context from company and branch.
     */
    public function test_set_context_from_company_and_branch(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $this->tenantContext->set($company, $branch);

        $this->assertTrue($this->tenantContext->isSet());
        $this->assertEquals($company->id, $this->tenantContext->companyId());
        $this->assertEquals($branch->id, $this->tenantContext->branchId());
    }

    /**
     * Test setting context from IDs.
     */
    public function test_set_context_from_ids(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $result = $this->tenantContext->setFromIds($company->id, $branch->id);

        $this->assertTrue($result);
        $this->assertTrue($this->tenantContext->isSet());
    }

    /**
     * Test setting context from IDs fails with mismatched branch.
     */
    public function test_set_context_fails_with_mismatched_branch(): void
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company2->id]);

        $result = $this->tenantContext->setFromIds($company1->id, $branch->id);

        $this->assertFalse($result);
        $this->assertFalse($this->tenantContext->isSet());
    }

    /**
     * Test bypass scopes for super admin.
     */
    public function test_super_admin_bypasses_scopes(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'is_super_admin' => true,
            'current_company_id' => $company->id,
            'current_branch_id' => $branch->id,
        ]);

        $this->tenantContext->setFromUser($user);

        $this->assertTrue($this->tenantContext->isSuperAdmin());
        $this->assertTrue($this->tenantContext->shouldBypassScopes());
    }

    /**
     * Test regular user does not bypass scopes.
     */
    public function test_regular_user_does_not_bypass_scopes(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'is_super_admin' => false,
            'current_company_id' => $company->id,
            'current_branch_id' => $branch->id,
        ]);

        $this->tenantContext->setFromUser($user);

        $this->assertFalse($this->tenantContext->isSuperAdmin());
        $this->assertFalse($this->tenantContext->shouldBypassScopes());
    }

    /**
     * Test temporary bypass with callback.
     */
    public function test_temporary_bypass_scopes(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'is_super_admin' => false,
            'current_company_id' => $company->id,
            'current_branch_id' => $branch->id,
        ]);

        $this->tenantContext->setFromUser($user);

        $this->assertFalse($this->tenantContext->shouldBypassScopes());

        $result = $this->tenantContext->bypassScopes(function () {
            $this->assertTrue($this->tenantContext->shouldBypassScopes());
            return 'bypassed';
        });

        $this->assertEquals('bypassed', $result);
        $this->assertFalse($this->tenantContext->shouldBypassScopes());
    }

    /**
     * Test clearing context.
     */
    public function test_clear_context(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $this->tenantContext->set($company, $branch);
        $this->assertTrue($this->tenantContext->isSet());

        $this->tenantContext->clear();

        $this->assertFalse($this->tenantContext->isSet());
        $this->assertNull($this->tenantContext->company());
        $this->assertNull($this->tenantContext->branch());
    }

    /**
     * Test toArray method.
     */
    public function test_to_array(): void
    {
        $company = Company::factory()->create(['name' => 'Test Company']);
        $branch = Branch::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test Branch',
        ]);

        $this->tenantContext->set($company, $branch);

        $array = $this->tenantContext->toArray();

        $this->assertEquals($company->id, $array['company_id']);
        $this->assertEquals('Test Company', $array['company_name']);
        $this->assertEquals($branch->id, $array['branch_id']);
        $this->assertEquals('Test Branch', $array['branch_name']);
    }
}
