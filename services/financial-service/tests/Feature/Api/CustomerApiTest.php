<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\FeatureTestCase;

class CustomerApiTest extends FeatureTestCase
{
    /** @test */
    public function it_creates_customer_successfully(): void
    {
        $response = $this->authPost('/api/v1/customers', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'phone' => '11987654321',
            'document' => '11144477735',
            'address_street' => 'Rua ABC',
            'address_number' => '123',
            'address_city' => 'São Paulo',
            'address_state' => 'SP',
            'address_zip_code' => '01234567',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'phone',
                'document',
                'address' => [
                    'street',
                    'number',
                    'city',
                    'state',
                    'zip_code',
                ],
                'status',
                'created_at',
            ],
        ]);
        $response->assertJson([
            'data' => [
                'name' => 'João Silva',
                'email' => 'joao@example.com',
                'status' => 'active',
                'address' => [
                    'street' => 'Rua ABC',
                    'city' => 'São Paulo',
                ],
            ],
        ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_data(): void
    {
        $response = $this->authPost('/api/v1/customers', [
            'name' => 'Jo', // Too short
            'email' => 'invalid-email',
            'phone' => '123', // Too short
            'document' => '12345', // Invalid
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors',
        ]);
        
        // Check that at least some validation errors are present
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors, 'Expected validation errors but got none');
    }

    /** @test */
    public function it_returns_409_for_duplicate_email(): void
    {
        // Create first customer
        $this->authPost('/api/v1/customers', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'phone' => '11987654321',
            'document' => '11144477735',
        ]);

        // Try to create second customer with same email (using different valid CPF)
        $response = $this->authPost('/api/v1/customers', [
            'name' => 'Maria Silva',
            'email' => 'joao@example.com',
            'phone' => '11999998888',
            'document' => '52998224725', // Valid CPF
        ]);

        $response->assertStatus(409);
        $response->assertJson([
            'error' => 'EmailAlreadyExists',
        ]);
    }

    /** @test */
    public function it_returns_409_for_duplicate_document(): void
    {
        // Create first customer
        $this->authPost('/api/v1/customers', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'phone' => '11987654321',
            'document' => '11144477735',
        ]);

        // Try to create second customer with same document
        $response = $this->authPost('/api/v1/customers', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999998888',
            'document' => '11144477735',
        ]);

        $response->assertStatus(409);
        $response->assertJson([
            'error' => 'DocumentAlreadyExists',
        ]);
    }

    /** @test */
    public function it_gets_customer_by_id(): void
    {
        // Create customer
        $createResponse = $this->authPost('/api/v1/customers', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'phone' => '11987654321',
            'document' => '11144477735',
        ]);

        $customerId = $createResponse->json('data.id');

        // Get customer
        $response = $this->authGet("/api/v1/customers/{$customerId}");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $customerId,
                'name' => 'João Silva',
                'email' => 'joao@example.com',
            ],
        ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_customer(): void
    {
        $response = $this->authGet('/api/v1/customers/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'CustomerNotFound',
        ]);
    }

    /** @test */
    public function it_lists_customers(): void
    {
        // Create multiple customers
        $this->authPost('/api/v1/customers', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'phone' => '11987654321',
            'document' => '11144477735',
        ]);

        $this->authPost('/api/v1/customers', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999998888',
            'document' => '52998224725', // Valid CPF
        ]);

        // List customers
        $response = $this->authGet('/api/v1/customers');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'status',
                ],
            ],
        ]);
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_requires_authentication(): void
    {
        $response = $this->post('/api/v1/customers', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'phone' => '11987654321',
            'document' => '11144477735',
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized',
        ]);
    }
}
