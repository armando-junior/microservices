<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category as CategoryModel;
use App\Models\Product as ProductModel;
use App\Models\Stock as StockModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductUpdateDeleteApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_updates_product_name(): void
    {
        // Arrange
        $product = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Laptop Dell',
            'sku' => 'LAPTOP-001',
            'price' => 3500.00,
            'status' => 'active',
        ]);

        // Act
        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'name' => 'Laptop Dell Inspiron 15',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Product updated successfully',
                'data' => [
                    'id' => $product->id,
                    'name' => 'Laptop Dell Inspiron 15',
                    'sku' => 'LAPTOP-001', // SKU should not change
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Laptop Dell Inspiron 15',
        ]);
    }

    public function test_it_updates_product_price(): void
    {
        // Arrange
        $product = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Laptop Dell',
            'sku' => 'LAPTOP-001',
            'price' => 3500.00,
            'status' => 'active',
        ]);

        // Act
        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'price' => 3299.99,
        ]);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => 3299.99,
        ]);
    }

    public function test_it_updates_product_category(): void
    {
        // Arrange
        $category = CategoryModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => 'active',
        ]);

        $product = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Laptop Dell',
            'sku' => 'LAPTOP-001',
            'price' => 3500.00,
            'status' => 'active',
        ]);

        // Act
        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'category_id' => $category->id,
        ]);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_it_updates_product_description_and_barcode(): void
    {
        // Arrange
        $product = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Laptop Dell',
            'sku' => 'LAPTOP-001',
            'price' => 3500.00,
            'status' => 'active',
        ]);

        // Act
        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'description' => 'Updated description with specs',
            'barcode' => '7891234567890',
        ]);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'description' => 'Updated description with specs',
            'barcode' => '7891234567890',
        ]);
    }

    public function test_it_validates_update_product_fields(): void
    {
        // Arrange
        $product = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Laptop Dell',
            'sku' => 'LAPTOP-001',
            'price' => 3500.00,
            'status' => 'active',
        ]);

        // Act
        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'price' => -100, // Invalid price
            'status' => 'invalid-status',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price', 'status']);
    }

    public function test_it_returns_404_when_updating_nonexistent_product(): void
    {
        // Arrange
        $nonExistentId = Str::uuid()->toString();

        // Act
        $response = $this->putJson("/api/v1/products/{$nonExistentId}", [
            'name' => 'Updated Name',
        ]);

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'error' => 'ProductNotFound',
            ]);
    }

    public function test_it_returns_422_when_updating_with_nonexistent_category(): void
    {
        // Arrange
        $product = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Laptop Dell',
            'sku' => 'LAPTOP-001',
            'price' => 3500.00,
            'status' => 'active',
        ]);

        $nonExistentCategoryId = Str::uuid()->toString();

        // Act
        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'category_id' => $nonExistentCategoryId,
        ]);

        // Assert
        // The FormRequest validation catches invalid category_id before UseCase
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_it_deletes_product_without_stock(): void
    {
        // Arrange
        $product = ProductModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Laptop Dell',
            'sku' => 'LAPTOP-001',
            'price' => 3500.00,
            'status' => 'active',
        ]);

        // Act
        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Product deleted successfully',
            ]);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_it_deletes_product_with_zero_stock(): void
    {
        // Arrange
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
            'quantity' => 0, // Zero stock
            'minimum_quantity' => 10,
        ]);

        // Act
        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Product deleted successfully',
            ]);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_it_cannot_delete_product_with_stock(): void
    {
        // Arrange
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
            'quantity' => 150, // Has stock
            'minimum_quantity' => 10,
        ]);

        // Act
        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        // Assert
        $response->assertStatus(409)
            ->assertJson([
                'error' => 'ProductHasStock',
            ]);

        // Product still exists
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    }

    public function test_it_returns_404_when_deleting_nonexistent_product(): void
    {
        // Arrange
        $nonExistentId = Str::uuid()->toString();

        // Act
        $response = $this->deleteJson("/api/v1/products/{$nonExistentId}");

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'error' => 'ProductNotFound',
            ]);
    }
}
