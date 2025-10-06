<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_product_successfully(): void
    {
        $categoryResponse = $this->postJson('/api/v1/categories', [
            'name' => 'Electronics',
            'description' => 'Electronic products',
        ]);

        $categoryId = $categoryResponse->json('data.id');

        $response = $this->postJson('/api/v1/products', [
            'name' => 'Laptop Dell',
            'sku' => 'LAPTOP-DELL-001',
            'price' => 2999.99,
            'category_id' => $categoryId,
            'barcode' => '1234567890',
            'description' => 'Dell laptop i7',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'sku',
                    'price',
                    'category_id',
                    'barcode',
                    'description',
                    'status',
                    'created_at',
                ],
            ])
            ->assertJson([
                'message' => 'Product created successfully',
                'data' => [
                    'name' => 'Laptop Dell',
                    'sku' => 'LAPTOP-DELL-001',
                    'price' => 2999.99,
                    'status' => 'active',
                ],
            ]);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'sku', 'price']);
    }

    public function test_it_validates_sku_format(): void
    {
        $response = $this->postJson('/api/v1/products', [
            'name' => 'Product',
            'sku' => 'invalid-sku-lowercase',
            'price' => 99.99,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_it_rejects_duplicate_sku(): void
    {
        $this->postJson('/api/v1/products', [
            'name' => 'Product 1',
            'sku' => 'DUPLICATE-SKU',
            'price' => 99.99,
        ]);

        $response = $this->postJson('/api/v1/products', [
            'name' => 'Product 2',
            'sku' => 'DUPLICATE-SKU',
            'price' => 149.99,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'error' => 'SKUAlreadyExists',
            ]);
    }

    public function test_it_gets_product_by_id(): void
    {
        $createResponse = $this->postJson('/api/v1/products', [
            'name' => 'Test Product',
            'sku' => 'TEST-PROD-001',
            'price' => 49.99,
        ]);

        $productId = $createResponse->json('data.id');

        $response = $this->getJson("/api/v1/products/{$productId}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $productId,
                    'name' => 'Test Product',
                    'sku' => 'TEST-PROD-001',
                ],
            ]);
    }

    public function test_it_returns_404_for_non_existent_product(): void
    {
        $fakeId = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->getJson("/api/v1/products/{$fakeId}");

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'ProductNotFound',
            ]);
    }

    public function test_it_lists_products(): void
    {
        $this->postJson('/api/v1/products', [
            'name' => 'Product 1',
            'sku' => 'PROD-001',
            'price' => 99.99,
        ]);

        $this->postJson('/api/v1/products', [
            'name' => 'Product 2',
            'sku' => 'PROD-002',
            'price' => 149.99,
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'sku',
                        'price',
                        'status',
                    ],
                ],
                'meta' => [
                    'page',
                    'per_page',
                ],
            ]);

        $this->assertCount(2, $response->json('data'));
    }
}
