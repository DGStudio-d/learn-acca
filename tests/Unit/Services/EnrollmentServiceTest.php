<?php

namespace Tests\Unit\Services;

use App\Models\Enrollment;
use App\Models\Language;
use App\Models\Level;
use App\Models\Program;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EnrollmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private EnrollmentService $enrollmentService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use real UserRepository for integration-style testing
        $userRepository = new \App\Repositories\UserRepository();
        $this->enrollmentService = new EnrollmentService($userRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_enroll_student_in_programs_creates_enrollments()
    {
        // Arrange
        $language = Language::factory()->create(['code' => 'en']);
        $level = Level::factory()->create(['language_id' => $language->id]);
        $program1 = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id
        ]);
        $program2 = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id
        ]);

        $student = User::factory()->create(['role' => 'student']);

        // Act
        $programs = $this->enrollmentService->enrollStudentInPrograms($student, 'en', $level->id);

        // Assert
        $this->assertCount(2, $programs);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'program_id' => $program1->id
        ]);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'program_id' => $program2->id
        ]);
    }

    public function test_enroll_student_in_programs_returns_empty_for_no_matching_programs()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);

        // Act
        $programs = $this->enrollmentService->enrollStudentInPrograms($student, 'nonexistent', 999);

        // Assert
        $this->assertCount(0, $programs);
    }

    public function test_enroll_student_in_programs_skips_existing_enrollments()
    {
        // Arrange
        $language = Language::factory()->create(['code' => 'en']);
        $level = Level::factory()->create(['language_id' => $language->id]);
        $program = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id
        ]);

        $student = User::factory()->create(['role' => 'student']);
        
        // Create existing enrollment
        Enrollment::create([
            'user_id' => $student->id,
            'program_id' => $program->id
        ]);

        // Act
        $programs = $this->enrollmentService->enrollStudentInPrograms($student, 'en', $level->id);

        // Assert
        $this->assertCount(1, $programs);
        $this->assertEquals(1, Enrollment::where('user_id', $student->id)->count());
    }

    public function test_enroll_student_in_program_creates_single_enrollment()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);
        $program = Program::factory()->create();

        // Act
        $enrollment = $this->enrollmentService->enrollStudentInProgram($student, $program);

        // Assert
        $this->assertInstanceOf(Enrollment::class, $enrollment);
        $this->assertEquals($student->id, $enrollment->user_id);
        $this->assertEquals($program->id, $enrollment->program_id);
        $this->assertNotNull($enrollment->assigned_at);
        $this->assertNull($enrollment->access_granted_at);
    }

    public function test_enroll_student_in_program_returns_existing_enrollment()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);
        $program = Program::factory()->create();
        
        $existingEnrollment = Enrollment::create([
            'user_id' => $student->id,
            'program_id' => $program->id,
            'assigned_at' => now()->subDay()
        ]);

        // Act
        $enrollment = $this->enrollmentService->enrollStudentInProgram($student, $program);

        // Assert
        $this->assertEquals($existingEnrollment->id, $enrollment->id);
        $this->assertEquals(1, Enrollment::where('user_id', $student->id)->count());
    }

    public function test_get_pending_enrollments_returns_unapproved_enrollments()
    {
        // Arrange
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        $program = Program::factory()->create();

        // Create pending enrollment
        Enrollment::create([
            'user_id' => $student1->id,
            'program_id' => $program->id,
            'access_granted_at' => null
        ]);

        // Create approved enrollment
        Enrollment::create([
            'user_id' => $student2->id,
            'program_id' => $program->id,
            'access_granted_at' => now()
        ]);

        // Act
        $pendingEnrollments = $this->enrollmentService->getPendingEnrollments();

        // Assert
        $this->assertCount(1, $pendingEnrollments);
        $this->assertEquals($student1->id, $pendingEnrollments->first()->user_id);
    }

    public function test_get_pending_enrollments_with_program_filter()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);
        $program1 = Program::factory()->create();
        $program2 = Program::factory()->create();

        Enrollment::create([
            'user_id' => $student->id,
            'program_id' => $program1->id,
            'access_granted_at' => null
        ]);

        Enrollment::create([
            'user_id' => $student->id,
            'program_id' => $program2->id,
            'access_granted_at' => null
        ]);

        // Act
        $pendingEnrollments = $this->enrollmentService->getPendingEnrollments(['program_id' => $program1->id]);

        // Assert
        $this->assertCount(1, $pendingEnrollments);
        $this->assertEquals($program1->id, $pendingEnrollments->first()->program_id);
    }

    public function test_get_student_enrollments_returns_user_enrollments()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $program1 = Program::factory()->create();
        $program2 = Program::factory()->create();

        Enrollment::create(['user_id' => $student->id, 'program_id' => $program1->id]);
        Enrollment::create(['user_id' => $student->id, 'program_id' => $program2->id]);
        Enrollment::create(['user_id' => $otherStudent->id, 'program_id' => $program1->id]);

        // Act
        $enrollments = $this->enrollmentService->getStudentEnrollments($student);

        // Assert
        $this->assertCount(2, $enrollments);
        $this->assertTrue($enrollments->every(fn($e) => $e->user_id === $student->id));
    }

    public function test_get_program_enrollments_returns_program_enrollments()
    {
        // Arrange
        $program = Program::factory()->create();
        $otherProgram = Program::factory()->create();
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);

        Enrollment::create(['user_id' => $student1->id, 'program_id' => $program->id]);
        Enrollment::create(['user_id' => $student2->id, 'program_id' => $program->id]);
        Enrollment::create(['user_id' => $student1->id, 'program_id' => $otherProgram->id]);

        // Act
        $enrollments = $this->enrollmentService->getProgramEnrollments($program);

        // Assert
        $this->assertCount(2, $enrollments);
        $this->assertTrue($enrollments->every(fn($e) => $e->program_id === $program->id));
    }

    public function test_is_student_enrolled_returns_true_for_enrolled_student()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);
        $program = Program::factory()->create();
        
        Enrollment::create([
            'user_id' => $student->id,
            'program_id' => $program->id
        ]);

        // Act
        $result = $this->enrollmentService->isStudentEnrolled($student, $program);

        // Assert
        $this->assertTrue($result);
    }

    public function test_is_student_enrolled_returns_false_for_non_enrolled_student()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);
        $program = Program::factory()->create();

        // Act
        $result = $this->enrollmentService->isStudentEnrolled($student, $program);

        // Assert
        $this->assertFalse($result);
    }

    public function test_get_enrollment_stats_returns_correct_counts()
    {
        // Arrange
        $program = Program::factory()->create();
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        $student3 = User::factory()->create(['role' => 'student']);

        // Create enrollments with different statuses
        Enrollment::create([
            'user_id' => $student1->id,
            'program_id' => $program->id,
            'access_granted_at' => now()
        ]);
        Enrollment::create([
            'user_id' => $student2->id,
            'program_id' => $program->id,
            'access_granted_at' => null
        ]);
        Enrollment::create([
            'user_id' => $student3->id,
            'program_id' => $program->id,
            'access_granted_at' => now()
        ]);

        // Act
        $stats = $this->enrollmentService->getEnrollmentStats($program);

        // Assert
        $this->assertEquals(3, $stats['total_enrollments']);
        $this->assertEquals(2, $stats['approved_enrollments']);
        $this->assertEquals(1, $stats['pending_enrollments']);
    }
}