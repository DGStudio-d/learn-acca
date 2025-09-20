<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\EnrollmentService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private EnrollmentService $enrollmentService,
        private NotificationService $notificationService
    ) {}

    /**
     * Register a new user.
     */
    public function register(array $userData): User
    {
        // Hash the password
        $userData['password'] = Hash::make($userData['password']);
        
        // Set default role if not provided
        if (!isset($userData['role'])) {
            $userData['role'] = 'student';
        }
        
        // Create the user
        $user = $this->userRepository->create($userData);

        // Assign role using Spatie Permission
        $user->assignRole($userData['role']);

        // If user is a student, automatically enroll them in matching programs
        if ($user->role === 'student' && isset($userData['preferred_language']) && isset($userData['level_id'])) {
            $programs = $this->enrollmentService->enrollStudentInPrograms(
                $user,
                $userData['preferred_language'],
                $userData['level_id']
            );

            // Send enrollment notifications to admins for each program
            foreach ($programs as $program) {
                $this->notificationService->sendEnrollmentNotification($user, $program);
            }
        }

        return $user;
    }

    /**
     * Authenticate user and return token.
     */
    public function login(array $credentials): string
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Set the authenticated user
        Auth::setUser($user);

        // Revoke existing tokens for security
        $user->tokens()->delete();

        // Create new token
        return $user->createToken('auth-token')->plainTextToken;
    }

    /**
     * Logout user by revoking tokens.
     */
    public function logout(User $user): bool
    {
        return $user->tokens()->delete();
    }

    /**
     * Get authenticated user with roles.
     */
    public function getAuthenticatedUser(): ?User
    {
        return Auth::user();
    }
}