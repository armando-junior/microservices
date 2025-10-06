<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category as CategoryModel;
use App\Models\Product as ProductModel;
use App\Models\Stock as StockModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockAlertsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_returns_low_stock_products(): void
    {
        // Arrange - Create products with low stock
        $category = CategoryModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => 'active',
        ]);

        $product1 = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Laptop Dell',
            'sku' => 'LAPTOP-001',
            'price' => 3500.00,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $product2 = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Mouse Logitech',
            'sku' => 'MOUSE-001',
            'price' => 59.99,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        // Create low stock for product1 (5 < 10)
        StockModel::create([
            'id' => Str::uuid()->toString(),
            'product_id' => $product1->id,
            'quantity' => 5,
            'minimum_quantity' => 10,
            'maximum_quantity' => 500,
        ]);

        // Create low stock for product2 (3 < 20)
        StockModel::create([
            'id' => Str::uuid()->toString(),
            'product_id' => $product2->id,
            'quantity' => 3,
            'minimum_quantity' => 20,
            'maximum_quantity' => 200,
        ]);

        // Create normal stock for product3 (should not appear)
        $product3 = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Keyboard',
            'sku' => 'KEYBOARD-001',
            'price' => 120.00,
            'status' => 'active',
        ]);

        StockModel::create([
            'id' => Str::uuid()->toString(),
            'product_id' => $product3->id,
            'quantity' => 100,
            'minimum_quantity' => 10,
        ]);

        // Act
        $response = $this->getJson('/api/v1/stock/low-stock');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'product_id',
                        'quantity',
                        'minimum_quantity',
                        'maximum_quantity',
                        'is_low_stock',
                        'is_depleted',
                    ],
                ],
                'meta' => [
                    'total',
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Verify all returned products are flagged as low stock
        foreach ($data as $stock) {
            $this->assertTrue($stock['is_low_stock']);
            $this->assertFalse($stock['is_depleted']);
            $this->assertLessThanOrEqual($stock['minimum_quantity'], $stock['quantity']);
            $this->assertGreaterThan(0, $stock['quantity']);
        }
    }

    public function test_it_returns_empty_array_when_no_low_stock(): void
    {
        // Arrange - Create product with normal stock
        $product = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Laptop Dell',
            'sku' => 'LAPTOP-001',
            'price' => 3500.00,
            'status' => 'active',
        ]);

        StockModel::create([
            'id' => Str::uuid()->toString(),
            'product_id' => $product->id,
            'quantity' => 100,
            'minimum_quantity' => 10,
        ]);

        // Act
        $response = $this->getJson('/api/v1/stock/low-stock');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
                'meta' => [
                    'total' => 0,
                ],
            ]);
    }

    public function test_it_returns_depleted_stock_products(): void
    {
        // Arrange - Create products with depleted stock
        $product1 = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Laptop Dell',
            'sku' => 'LAPTOP-001',
            'price' => 3500.00,
            'status' => 'active',
        ]);

        $product2 = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Mouse Logitech',
            'sku' => 'MOUSE-001',
            'price' => 59.99,
            'status' => 'active',
        ]);

        // Create depleted stock for both products
        StockModel::create([
            'id' => Str::uuid()->toString(),
            'product_id' => $product1->id,
            'quantity' => 0,
            'minimum_quantity' => 10,
            'maximum_quantity' => 500,
        ]);

        StockModel::create([
            'id' => Str::uuid()->toString(),
            'product_id' => $product2->id,
            'quantity' => 0,
            'minimum_quantity' => 15,
            'maximum_quantity' => 300,
        ]);

        // Create product with stock (should not appear)
        $product3 = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Keyboard',
            'sku' => 'KEYBOARD-001',
            'price' => 120.00,
            'status' => 'active',
        ]);

        StockModel::create([
            'id' => Str::uuid()->toString(),
            'product_id' => $product3->id,
            'quantity' => 50,
            'minimum_quantity' => 10,
        ]);

        // Act
        $response = $this->getJson('/api/v1/stock/depleted');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'product_id',
                        'quantity',
                        'minimum_quantity',
                        'maximum_quantity',
                        'is_low_stock',
                        'is_depleted',
                    ],
                ],
                'meta' => [
                    'total',
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Verify all returned products are depleted
        foreach ($data as $stock) {
            $this->assertTrue($stock['is_depleted']);
            $this->assertTrue($stock['is_low_stock']); // Depleted is also low stock
            $this->assertEquals(0, $stock['quantity']);
        }
    }

    public function test_it_returns_empty_array_when_no_depleted_stock(): void
    {
        // Arrange - Create product with stock
        $product = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Laptop Dell',
            'sku' => 'LAPTOP-001',
            'price' => 3500.00,
            'status' => 'active',
        ]);

        StockModel::create([
            'id' => Str::uuid()->toString(),
            'product_id' => $product->id,
            'quantity' => 100,
            'minimum_quantity' => 10,
        ]);

        // Act
        $response = $this->getJson('/api/v1/stock/depleted');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
                'meta' => [
                    'total' => 0,
                ],
            ]);
    }
}
