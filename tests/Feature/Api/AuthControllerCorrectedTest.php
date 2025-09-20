<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerCorrectedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles for testing
        \Spatie\Permission\Models\Role::create(['name' => 'student']);
        \Spatie\Permission\Models\Role::create(['name' => 'teacher']);
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
    }

    public function test_user_can_register_successfully()
    {
        // Arrange
        $language = \App\Models\Language::factory()->create(['code' => 'en']);
        $level = \App\Models\Level::factory()->create(['language_id' => $language->id]);
        
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+1234567890',
            'preferred_language' => 'en',
            'level_id' => $level->id,
            'preferred_locale' => 'en'
        ];

        // Act
        $response = $this->postJson('/api/auth/register', $userData);

        // Assert
        if ($response->status() !== 201) {
            dump($response->json());
        }
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
        ])->getJson('/api/auth/profile');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'name' => 'John Doe',
                            'email' => 'john@example.com'
                        ]
                    ]
                ]);
    }
}