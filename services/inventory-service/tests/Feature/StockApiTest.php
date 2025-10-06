<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;
use App\Models\Stock;

class StockApiTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithStock(): array
    {
        $product = Product::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'name' => 'Test Product',
            'sku' => 'TEST-STOCK-001',
            'price' => 99.99,
            'status' => 'active',
        ]);

        $stock = Stock::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'product_id' => $product->id,
            'quantity' => 100,
            'minimum_quantity' => 10,
            'maximum_quantity' => 500,
        ]);

        return [$product, $stock];
    }

    public function test_it_gets_stock_by_product_id(): void
    {
        [$product, $stock] = $this->createProductWithStock();

        $response = $this->getJson("/api/v1/stock/product/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'product_id' => $product->id,
                    'quantity' => 100,
                    'minimum_quantity' => 10,
                    'is_low_stock' => false,
                    'is_depleted' => false,
                ],
            ]);
    }

    public function test_it_increases_stock(): void
    {
        [$product, $stock] = $this->createProductWithStock();

        $response = $this->postJson("/api/v1/stock/product/{$product->id}/increase", [
            'quantity' => 50,
            'reason' => 'Purchase order received',
            'reference_id' => 'PO-2024-001',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Stock increased successfully',
                'data' => [
                    'quantity' => 150,
                ],
            ]);
    }

    public function test_it_decreases_stock(): void
    {
        [$product, $stock] = $this->createProductWithStock();

        $response = $this->postJson("/api/v1/stock/product/{$product->id}/decrease", [
            'quantity' => 30,
            'reason' => 'Sale completed',
            'reference_id' => 'SALE-2024-042',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Stock decreased successfully',
                'data' => [
                    'quantity' => 70,
                ],
            ]);
    }

    public function test_it_rejects_decrease_when_insufficient_stock(): void
    {
        [$product, $stock] = $this->createProductWithStock();

        $response = $this->postJson("/api/v1/stock/product/{$product->id}/decrease", [
            'quantity' => 200,
            'reason' => 'Large sale',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'InsufficientStock',
            ]);
    }

    public function test_it_validates_stock_operation_fields(): void
    {
        [$product, $stock] = $this->createProductWithStock();

        $response = $this->postJson("/api/v1/stock/product/{$product->id}/increase", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity', 'reason']);
    }

    public function test_it_returns_404_when_stock_not_found(): void
    {
        $fakeProductId = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->getJson("/api/v1/stock/product/{$fakeProductId}");

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'StockNotFound',
            ]);
    }
}
