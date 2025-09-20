<?php

namespace Tests\Unit\Services;

use App\Models\Program;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\EnrollmentService;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class AuthServiceUnitTest extends TestCase
{
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

    public function test_register_hashes_password()
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => 'student'
        ];

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('role')->andReturn('student');
        $user->shouldReceive('assignRole')->with('student')->once();

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
    }

    public function test_register_sets_default_role()
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ];

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('role')->andReturn('student');
        $user->shouldReceive('assignRole')->with('student')->once();

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

    public function test_register_enrolls_student_when_preferences_provided()
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
        $user->shouldReceive('assignRole')->with('student')->once();
        $user->shouldReceive('getAttribute')->with('role')->andReturn('student');

        $program = Mockery::mock(Program::class);
        $programs = new Collection([$program]);

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
            ->with($user, $program);

        // Act
        $result = $this->authService->register($userData);

        // Assert
        $this->assertInstanceOf(User::class, $result);
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

    public function test_logout_calls_token_deletion()
    {
        // Arrange
        $user = Mockery::mock(User::class);
        $tokens = Mockery::mock();
        $tokens->shouldReceive('delete')->once()->andReturn(true);
        $user->shouldReceive('tokens')->once()->andReturn($tokens);

        // Act
        $result = $this->authService->logout($user);

        // Assert
        $this->assertTrue($result);
    }
}