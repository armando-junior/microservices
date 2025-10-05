<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Src\Infrastructure\Persistence\Eloquent\Models\UserModel;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_registers_a_new_user_successfully(): void
    {
        // Act
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass@123',
        ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'is_active',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_active' => true,
        ]);

        $data = $response->json('data');
        $this->assertEquals('bearer', $data['token_type']);
        $this->assertEquals(3600, $data['expires_in']);
        $this->assertNotEmpty($data['access_token']);
    }

    /**
     * @test
     */
    public function it_validates_required_fields_on_registration(): void
    {
        // Act
        $response = $this->postJson('/api/auth/register', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * @test
     */
    public function it_validates_email_format_on_registration(): void
    {
        // Act
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'SecurePass@123',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * @test
     */
    public function it_validates_password_strength_on_registration(): void
    {
        // Act
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'weak',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * @test
     */
    public function it_rejects_duplicate_email_on_registration(): void
    {
        // Arrange - Create existing user
        UserModel::create([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => password_hash('SecurePass@123', PASSWORD_BCRYPT),
            'is_active' => true,
        ]);

        // Act
        $response = $this->postJson('/api/auth/register', [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'SecurePass@123',
        ]);

        // Assert
        $response->assertStatus(409)
            ->assertJson([
                'error' => 'Email already exists',
            ]);
    }

    /**
     * @test
     */
    public function it_logs_in_with_valid_credentials(): void
    {
        // Arrange - Create user
        $password = 'SecurePass@123';
        UserModel::create([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'is_active' => true,
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => $password,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'is_active',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('bearer', $data['token_type']);
        $this->assertEquals('john@example.com', $data['user']['email']);
        $this->assertNotEmpty($data['access_token']);
    }

    /**
     * @test
     */
    public function it_rejects_login_with_invalid_email(): void
    {
        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'SecurePass@123',
        ]);

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'error' => 'User not found',
            ]);
    }

    /**
     * @test
     */
    public function it_rejects_login_with_invalid_password(): void
    {
        // Arrange - Create user
        UserModel::create([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => password_hash('CorrectPass@123', PASSWORD_BCRYPT),
            'is_active' => true,
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'WrongPass@123',
        ]);

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid credentials',
            ]);
    }

    /**
     * @test
     */
    public function it_validates_required_fields_on_login(): void
    {
        // Act
        $response = $this->postJson('/api/auth/login', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * @test
     */
    public function it_returns_authenticated_user_info(): void
    {
        // Arrange - Register and get token
        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass@123',
        ]);

        $token = $registerResponse->json('data.access_token');

        // Act
        $response = $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'is_active',
                ],
            ]);

        $this->assertEquals('john@example.com', $response->json('user.email'));
        $this->assertEquals('John Doe', $response->json('user.name'));
    }

    /**
     * @test
     */
    public function it_rejects_request_without_authentication_token(): void
    {
        // Act
        $response = $this->getJson('/api/auth/me');

        // Assert
        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_rejects_request_with_invalid_token(): void
    {
        // Act
        $response = $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer invalid-token-here',
        ]);

        // Assert
        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_logs_out_successfully(): void
    {
        // Arrange - Register and get token
        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass@123',
        ]);

        $token = $registerResponse->json('data.access_token');

        // Act
        $response = $this->postJson('/api/auth/logout', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logout successful',
            ]);
    }

    /**
     * @test
     */
    public function it_refreshes_token_successfully(): void
    {
        // Arrange - Register and get token
        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass@123',
        ]);

        $oldToken = $registerResponse->json('data.access_token');

        // Act
        $response = $this->postJson('/api/auth/refresh', [], [
            'Authorization' => 'Bearer ' . $oldToken,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'auth' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                ],
            ]);

        $newToken = $response->json('auth.access_token');
        $this->assertNotEquals($oldToken, $newToken);
    }

    /**
     * @test
     */
    public function it_normalizes_email_to_lowercase_on_registration(): void
    {
        // Act
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'John@Example.COM',
            'password' => 'SecurePass@123',
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com', // Should be lowercased
        ]);
    }

    /**
     * @test
     */
    public function it_allows_login_with_case_insensitive_email(): void
    {
        // Arrange - Create user with lowercase email
        $password = 'SecurePass@123';
        UserModel::create([
            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'is_active' => true,
        ]);

        // Act - Login with uppercase email
        $response = $this->postJson('/api/auth/login', [
            'email' => 'JOHN@EXAMPLE.COM',
            'password' => $password,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertEquals('john@example.com', $response->json('data.user.email'));
    }
}

