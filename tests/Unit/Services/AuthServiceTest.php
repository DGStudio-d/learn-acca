<?php

namespace Tests\Unit\Services;

use App\Models\Program;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\EnrollmentService;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;
    private $userRepository;
    private $enrollmentService;
    private $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = Mockery::mock(UserRepository::class);
        $this->enrollmentService = Mockery::mock(EnrollmentService::class);
        $this->notificationService = Mockery::mock(NotificationService::class);

        $this->authService = new AuthService(
            $this->userRepository,
            $this->enrollmentService,
            $this->notificationService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_register_creates_user_with_hashed_password()
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => 'student'
        ];

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('name')->andReturn('John Doe');
        $user->shouldReceive('getAttribute')->with('role')->andReturn('student');
        $user->shouldReceive('setAttribute')->andReturn(null);
        $user->shouldReceive('assignRole')->with('student')->once();
        $user->id = 1;

        $this->userRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return Hash::check('password123', $data['password']) && 
                       $data['role'] === 'student';
            }))
            ->andReturn($user);

        // Act
        $result = $this->authService->register($userData);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('John Doe', $result->name);
    }

    public function test_register_sets_default_role_when_not_provided()
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ];

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('role')->andReturn('student');
        $user->shouldReceive('setAttribute')->andReturn(null);
        $user->shouldReceive('assignRole')->with('student')->once();
        $user->id = 1;

        $this->userRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['role'] === 'student';
            }))
            ->andReturn($user);

        // Act
        $result = $this->authService->register($userData);

        // Assert
        $this->assertInstanceOf(User::class, $result);
    }

    public function test_register_enrolls_student_in_programs_when_preferences_provided()
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => 'student',
            'preferred_language' => 'en',
            'level_id' => 1
        ];

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('role')->andReturn('student');
        $user->shouldReceive('setAttribute')->andReturn(null);
        $user->shouldReceive('assignRole')->with('student')->once();
        $user->id = 1;

        $programs = new Collection([Program::factory()->make(['id' => 1])]);

        $this->userRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($user);

        $this->enrollmentService
            ->shouldReceive('enrollStudentInPrograms')
            ->once()
            ->with($user, 'en', 1)
            ->andReturn($programs);

        $this->notificationService
            ->shouldReceive('sendEnrollmentNotification')
            ->once()
            ->with($user, Mockery::type(Program::class));

        // Act
        $result = $this->authService->register($userData);

        // Assert
        $this->assertInstanceOf(User::class, $result);
    }

    public function test_login_returns_token_for_valid_credentials()
    {
        // Arrange
        $credentials = [
            'email' => 'john@example.com',
            'password' => 'password123'
        ];

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('password')->andReturn(Hash::make('password123'));
        $user->shouldReceive('tokens->delete')->once()->andReturn(true);
        $user->shouldReceive('createToken')->with('auth-token')->andReturn(
            (object) ['plainTextToken' => 'test-token']
        );

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('john@example.com')
            ->andReturn($user);

        Auth::shouldReceive('setUser')->once()->with($user);

        // Act
        $result = $this->authService->login($credentials);

        // Assert
        $this->assertEquals('test-token', $result);
    }

    public function test_login_throws_exception_for_invalid_credentials()
    {
        // Arrange
        $credentials = [
            'email' => 'john@example.com',
            'password' => 'wrongpassword'
        ];

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('john@example.com')
            ->andReturn(null);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->authService->login($credentials);
    }

    public function test_logout_revokes_user_tokens()
    {
        // Arrange
        $user = Mockery::mock(User::class);
        $user->shouldReceive('tokens->delete')->once()->andReturn(true);

        // Act
        $result = $this->authService->logout($user);

        // Assert
        $this->assertTrue($result);
    }

    public function test_get_authenticated_user_returns_current_user()
    {
        // Arrange
        $user = new User(['name' => 'John Doe']);
        Auth::shouldReceive('user')->once()->andReturn($user);

        // Act
        $result = $this->authService->getAuthenticatedUser();

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('John Doe', $result->name);
    }
}