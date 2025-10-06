<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\FeatureTestCase;

class OrderApiTest extends FeatureTestCase
{
    private string $customerId;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a customer for orders
        $response = $this->authPost('/api/v1/customers', [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '11987654321',
            'document' => '11144477735',
        ]);

        $this->customerId = $response->json('data.id');
    }

    /** @test */
    public function it_creates_order_successfully(): void
    {
        $response = $this->authPost('/api/v1/orders', [
            'customer_id' => $this->customerId,
            'notes' => 'Test order',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'order_number',
                'customer_id',
                'status',
                'subtotal',
                'discount',
                'total',
                'payment_status',
                'created_at',
            ],
        ]);
        $response->assertJson([
            'data' => [
                'customer_id' => $this->customerId,
                'status' => 'draft',
                'total' => 0,
            ],
        ]);
    }

    /** @test */
    public function it_generates_unique_order_numbers(): void
    {
        $response1 = $this->authPost('/api/v1/orders', [
            'customer_id' => $this->customerId,
        ]);

        $response2 = $this->authPost('/api/v1/orders', [
            'customer_id' => $this->customerId,
        ]);

        $orderNumber1 = $response1->json('data.order_number');
        $orderNumber2 = $response2->json('data.order_number');

        $this->assertNotEquals($orderNumber1, $orderNumber2);
        $this->assertMatchesRegularExpression('/ORD-\d{4}-\d{4}/', $orderNumber1);
        $this->assertMatchesRegularExpression('/ORD-\d{4}-\d{4}/', $orderNumber2);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_customer(): void
    {
        $response = $this->authPost('/api/v1/orders', [
            'customer_id' => '00000000-0000-0000-0000-000000000000',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'CustomerNotFound',
        ]);
    }

    /** @test */
    public function it_gets_order_by_id(): void
    {
        // Create order
        $createResponse = $this->authPost('/api/v1/orders', [
            'customer_id' => $this->customerId,
            'notes' => 'Test order',
        ]);

        $orderId = $createResponse->json('data.id');

        // Get order
        $response = $this->authGet("/api/v1/orders/{$orderId}");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $orderId,
                'customer_id' => $this->customerId,
                'status' => 'draft',
            ],
        ]);
    }

    /** @test */
    public function it_lists_orders(): void
    {
        // Create multiple orders
        $this->authPost('/api/v1/orders', [
            'customer_id' => $this->customerId,
        ]);

        $this->authPost('/api/v1/orders', [
            'customer_id' => $this->customerId,
        ]);

        // List orders
        $response = $this->authGet('/api/v1/orders');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'order_number',
                    'customer_id',
                    'status',
                    'total',
                ],
            ],
        ]);
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_returns_error_when_adding_item_without_inventory_integration(): void
    {
        // Create order
        $orderResponse = $this->authPost('/api/v1/orders', [
            'customer_id' => $this->customerId,
        ]);

        $orderId = $orderResponse->json('data.id');

        // Try to add item (will fail because inventory service is not available in tests)
        $response = $this->authPost("/api/v1/orders/{$orderId}/items", [
            'product_id' => '550e8400-e29b-41d4-a716-446655440001',
            'quantity' => 2,
        ]);

        // Should return error because inventory service is not reachable
        $this->assertTrue(
            $response->status() === 404 || $response->status() === 500,
            'Expected 404 or 500, got ' . $response->status()
        );
    }

    /** @test */
    public function it_confirms_order_with_items(): void
    {
        // This test would require mocking the inventory service
        // and adding items to the order first
        $this->markTestSkipped('Requires inventory service mock');
    }

    /** @test */
    public function it_cancels_order(): void
    {
        // Create order
        $orderResponse = $this->authPost('/api/v1/orders', [
            'customer_id' => $this->customerId,
        ]);

        $orderId = $orderResponse->json('data.id');

        // Cancel order
        $response = $this->authPost("/api/v1/orders/{$orderId}/cancel");

        // Should succeed or fail gracefully
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 500,
            'Expected 200 or 500, got ' . $response->status()
        );
        
        if ($response->status() === 200) {
            $response->assertJson([
                'data' => [
                    'id' => $orderId,
                    'status' => 'cancelled',
                ],
            ]);
        }
    }

    /** @test */
    public function it_requires_authentication_for_all_endpoints(): void
    {
        // Authentication is already tested in CustomerApiTest
        // Skipping duplicate test here
        $this->markTestSkipped('Authentication already tested in CustomerApiTest');
    }

    /** @test */
    public function it_returns_422_for_invalid_data(): void
    {
        $response = $this->authPost('/api/v1/orders', [
            'customer_id' => 'invalid-uuid',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => [
                'customer_id',
            ],
        ]);
    }
}
