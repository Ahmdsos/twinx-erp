<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests for HasUuid Trait
 * 
 * يتحقق من أن:
 * - UUID يتم إنشاؤه تلقائياً عند إنشاء سجل جديد
 * - المفتاح الأساسي من نوع string
 * - لا يوجد auto-increment
 */
class HasUuidTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that UUID is generated automatically on creation.
     */
    public function test_uuid_is_generated_on_creation(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'base_currency' => 'SAR',
            'is_active' => true,
        ]);

        $this->assertNotNull($company->id);
        $this->assertTrue(Str::isUuid($company->id));
    }

    /**
     * Test that model is not auto-incrementing.
     */
    public function test_model_is_not_auto_incrementing(): void
    {
        $company = new Company();

        $this->assertFalse($company->getIncrementing());
    }

    /**
     * Test that key type is string.
     */
    public function test_key_type_is_string(): void
    {
        $company = new Company();

        $this->assertEquals('string', $company->getKeyType());
    }

    /**
     * Test UUID is unique for each record.
     */
    public function test_uuid_is_unique_for_each_record(): void
    {
        $company1 = Company::create([
            'name' => 'Company One',
            'base_currency' => 'SAR',
            'is_active' => true,
        ]);

        $company2 = Company::create([
            'name' => 'Company Two',
            'base_currency' => 'SAR',
            'is_active' => true,
        ]);

        $this->assertNotEquals($company1->id, $company2->id);
        $this->assertTrue(Str::isUuid($company1->id));
        $this->assertTrue(Str::isUuid($company2->id));
    }

    /**
     * Test that existing UUID is preserved when provided.
     */
    public function test_existing_uuid_is_preserved(): void
    {
        $existingUuid = (string) Str::uuid();

        // Using new + save() because 'id' is not mass-assignable
        $company = new Company([
            'name' => 'Test Company with UUID',
            'base_currency' => 'SAR',
            'is_active' => true,
        ]);
        $company->id = $existingUuid;
        $company->save();

        $this->assertEquals($existingUuid, $company->id);
    }
}
