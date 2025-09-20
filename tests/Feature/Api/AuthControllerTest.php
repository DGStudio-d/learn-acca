<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles for testing
        \Spatie\Permission\Models\Role::create(['name' => 'student']);
        \Spatie\Permission\Models\Role::create(['name' => 'teacher']);
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        
        // Create language and level for testing
        $language = \App\Models\Language::factory()->create(['code' => 'en', 'name' => 'English']);
        \App\Models\Level::factory()->create(['id' => 1, 'language_id' => $language->id, 'name' => 'Beginner']);
    }

    public function test_user_can_register_successfully()
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+1234567890',
            'preferred_language' => 'en',
            'level_id' => 1,
            'preferred_locale' => 'en'
        ];

        // Act
        $response = $this->postJson('/api/auth/register', $userData);

        // Assert
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'role'
                        ],
                        'token'
                    ],
                    'message'
                ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe'
        ]);
    }

    public function test_registration_validates_required_fields()
    {
        // Act
        $response = $this->postJson('/api/auth/register', []);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_validates_unique_email()
    {
        // Arrange
        User::factory()->create(['email' => 'john@example.com']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/auth/register', $userData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login_with_valid_credentials()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123')
        ]);

        $credentials = [
            'email' => 'john@example.com',
            'password' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $credentials);

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'role'
                        ],
                        'token'
                    ],
                    'message'
                ]);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        // Arrange
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123')
        ]);

        $credentials = [
            'email' => 'john@example.com',
            'password' => 'wrongpassword'
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $credentials);

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ]);
    }

    public function test_login_validates_required_fields()
    {
        // Act
        $response = $this->postJson('/api/auth/login', []);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_authenticated_user_can_logout()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Logout successful'
                ]);

        // Verify token is revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class
        ]);
    }

    public function test_logout_requires_authentication()
    {
        // Act
        $response = $this->postJson('/api/auth/logout');

        // Assert
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_profile()
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        // Assert
        $response->assertStatus(200)
                ->assertJsonPath('id', $user->id)
                ->assertJsonPath('name', 'John Doe')
                ->assertJsonPath('email', 'john@example.com');
    }

    public function test_get_profile_requires_authentication()
    {
        // Act
        $response = $this->getJson('/api/user');

        // Assert
        $response->assertStatus(401);
    }

    public function test_registration_assigns_default_student_role()
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+1234567890',
            'preferred_language' => 'en',
            'level_id' => 1
        ];

        // Act
        $response = $this->postJson('/api/auth/register', $userData);

        // Assert
        $response->assertStatus(201);
        
        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue($user->hasRole('student'));
    }

    public function test_registration_with_phone_number()
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+1234567890',
            'preferred_language' => 'en',
            'level_id' => 1
        ];

        // Act
        $response = $this->postJson('/api/auth/register', $userData);

        // Assert
        $response->assertStatus(201);
        
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'phone' => '+1234567890'
        ]);
    }
}