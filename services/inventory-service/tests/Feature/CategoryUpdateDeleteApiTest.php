<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category as CategoryModel;
use App\Models\Product as ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CategoryUpdateDeleteApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_updates_category_name(): void
    {
        // Arrange
        $category = CategoryModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => 'active',
        ]);

        // Act
        $response = $this->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'Electronics and Computers',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Category updated successfully',
                'data' => [
                    'id' => $category->id,
                    'name' => 'Electronics and Computers',
                    'slug' => 'electronics-and-computers',
                    'status' => 'active',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Electronics and Computers',
        ]);
    }

    public function test_it_updates_category_description(): void
    {
        // Arrange
        $category = CategoryModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Old description',
            'status' => 'active',
        ]);

        // Act
        $response = $this->putJson("/api/v1/categories/{$category->id}", [
            'description' => 'Updated description for electronics',
        ]);

        // Assert
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'description' => 'Updated description for electronics',
        ]);
    }

    public function test_it_updates_category_status_to_inactive(): void
    {
        // Arrange
        $category = CategoryModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => 'active',
        ]);

        // Act
        $response = $this->putJson("/api/v1/categories/{$category->id}", [
            'status' => 'inactive',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'inactive',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'status' => 'inactive',
        ]);
    }

    public function test_it_validates_update_category_fields(): void
    {
        // Arrange
        $category = CategoryModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => 'active',
        ]);

        // Act
        $response = $this->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'A', // Too short
            'status' => 'invalid-status',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'status']);
    }

    public function test_it_returns_404_when_updating_nonexistent_category(): void
    {
        // Arrange
        $nonExistentId = Str::uuid()->toString();

        // Act
        $response = $this->putJson("/api/v1/categories/{$nonExistentId}", [
            'name' => 'Updated Name',
        ]);

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'error' => 'CategoryNotFound',
            ]);
    }

    public function test_it_deletes_category_without_products(): void
    {
        // Arrange
        $category = CategoryModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Empty Category',
            'slug' => 'empty-category',
            'status' => 'active',
        ]);

        // Act
        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Category deleted successfully',
            ]);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_it_cannot_delete_category_with_products(): void
    {
        // Arrange
        $category = CategoryModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => 'active',
        ]);

        ProductModel::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Laptop',
            'sku' => 'LAPTOP-001',
            'price' => 3500.00,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        // Act
        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        // Assert
        $response->assertStatus(409)
            ->assertJson([
                'error' => 'CategoryHasProducts',
            ]);

        // Category still exists
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_it_returns_404_when_deleting_nonexistent_category(): void
    {
        // Arrange
        $nonExistentId = Str::uuid()->toString();

        // Act
        $response = $this->deleteJson("/api/v1/categories/{$nonExistentId}");

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'error' => 'CategoryNotFound',
            ]);
    }
}
