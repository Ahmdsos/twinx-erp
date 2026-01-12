<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'current_branch_id' => $this->branch->id,
            'is_super_admin' => true, // For simpler testing
        ]);
    }

    /**
     * Test health endpoint is public.
     */
    public function test_health_endpoint_is_public(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'ok',
            ]);
    }

    /**
     * Test products endpoint requires authentication.
     */
    public function test_products_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(401);
    }

    /**
     * Test list products with authentication.
     */
    public function test_list_products_with_auth(): void
    {
        Sanctum::actingAs($this->user);

        // Create some products
        Product::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'total'],
            ]);
    }

    /**
     * Test create product.
     */
    public function test_create_product(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/products', [
            'sku' => 'TEST-001',
            'name' => 'Test Product',
            'cost_price' => 100.00,
            'selling_price' => 150.00,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'تم إنشاء المنتج بنجاح',
            ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'TEST-001',
            'name' => 'Test Product',
        ]);
    }

    /**
     * Test show single product.
     */
    public function test_show_product(): void
    {
        Sanctum::actingAs($this->user);

        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test update product.
     */
    public function test_update_product(): void
    {
        Sanctum::actingAs($this->user);

        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'name' => 'Updated Product Name',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم تحديث المنتج بنجاح',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
        ]);
    }

    /**
     * Test delete product.
     */
    public function test_delete_product(): void
    {
        Sanctum::actingAs($this->user);

        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم حذف المنتج بنجاح',
            ]);
    }
}
