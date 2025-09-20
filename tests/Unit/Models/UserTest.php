<?php

namespace Tests\Unit\Models;

use App\Models\Enrollment;
use App\Models\Language;
use App\Models\NotificationLog;
use App\Models\Program;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
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

    public function test_user_has_fillable_attributes()
    {
        // Arrange
        $fillable = [
            'name',
            'email',
            'phone',
            'password',
            'role',
            'preferred_language',
            'preferred_locale',
            'notify_email',
            'notify_whatsapp',
            'image_path',
        ];

        // Act
        $user = new User();

        // Assert
        $this->assertEquals($fillable, $user->getFillable());
    }

    public function test_user_has_hidden_attributes()
    {
        // Arrange
        $hidden = ['password', 'remember_token'];

        // Act
        $user = new User();

        // Assert
        $this->assertEquals($hidden, $user->getHidden());
    }

    public function test_user_casts_attributes_correctly()
    {
        // Arrange
        $user = User::factory()->create([
            'notify_email' => true,
            'notify_whatsapp' => false,
        ]);

        // Act & Assert
        $this->assertIsBool($user->notify_email);
        $this->assertIsBool($user->notify_whatsapp);
        $this->assertInstanceOf(\DateTime::class, $user->email_verified_at);
    }

    public function test_user_has_enrollments_relationship()
    {
        // Arrange
        $user = User::factory()->create();
        $program = Program::factory()->create();
        
        Enrollment::factory()->create([
            'user_id' => $user->id,
            'program_id' => $program->id
        ]);

        // Act
        $enrollments = $user->enrollments;

        // Assert
        $this->assertCount(1, $enrollments);
        $this->assertInstanceOf(Enrollment::class, $enrollments->first());
    }

    public function test_user_has_programs_relationship()
    {
        // Arrange
        $user = User::factory()->create();
        $program = Program::factory()->create();
        
        $user->enrollments()->create([
            'program_id' => $program->id,
            'assigned_at' => now(),
            'access_granted_at' => now()
        ]);

        // Act
        $programs = $user->programs;

        // Assert
        $this->assertCount(1, $programs);
        $this->assertInstanceOf(Program::class, $programs->first());
        $this->assertNotNull($programs->first()->pivot->assigned_at);
        $this->assertNotNull($programs->first()->pivot->access_granted_at);
    }

    public function test_user_has_approved_programs_relationship()
    {
        // Arrange
        $user = User::factory()->create();
        $program1 = Program::factory()->create();
        $program2 = Program::factory()->create();
        
        // Create approved enrollment
        $user->enrollments()->create([
            'program_id' => $program1->id,
            'access_granted_at' => now()
        ]);
        
        // Create pending enrollment
        $user->enrollments()->create([
            'program_id' => $program2->id,
            'access_granted_at' => null
        ]);

        // Act
        $approvedPrograms = $user->approvedPrograms;

        // Assert
        $this->assertCount(1, $approvedPrograms);
        $this->assertEquals($program1->id, $approvedPrograms->first()->id);
    }

    public function test_user_has_teacher_languages_relationship()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $language = Language::factory()->create();
        
        $teacher->teacherLanguages()->attach($language->id);

        // Act
        $languages = $teacher->teacherLanguages;

        // Assert
        $this->assertCount(1, $languages);
        $this->assertInstanceOf(Language::class, $languages->first());
    }

    public function test_user_has_quiz_attempts_relationship()
    {
        // Arrange
        $user = User::factory()->create();
        QuizAttempt::factory()->create(['student_id' => $user->id]);

        // Act
        $attempts = $user->quizAttempts;

        // Assert
        $this->assertCount(1, $attempts);
        $this->assertInstanceOf(QuizAttempt::class, $attempts->first());
    }

    public function test_user_has_notification_logs_relationship()
    {
        // Arrange
        $user = User::factory()->create();
        NotificationLog::factory()->create(['user_id' => $user->id]);

        // Act
        $logs = $user->notificationLogs;

        // Assert
        $this->assertCount(1, $logs);
        $this->assertInstanceOf(NotificationLog::class, $logs->first());
    }

    public function test_is_student_returns_true_for_student_role()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);

        // Act & Assert
        $this->assertTrue($student->isStudent());
        $this->assertFalse($student->isTeacher());
        $this->assertFalse($student->isAdmin());
    }

    public function test_is_teacher_returns_true_for_teacher_role()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);

        // Act & Assert
        $this->assertTrue($teacher->isTeacher());
        $this->assertFalse($teacher->isStudent());
        $this->assertFalse($teacher->isAdmin());
    }

    public function test_is_admin_returns_true_for_admin_role()
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        // Act & Assert
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isStudent());
        $this->assertFalse($admin->isTeacher());
    }

    public function test_user_uses_soft_deletes()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $user->delete();

        // Assert
        $this->assertSoftDeleted($user);
        $this->assertNotNull($user->deleted_at);
    }

    public function test_password_is_hashed_on_creation()
    {
        // Arrange & Act
        $user = User::factory()->create(['password' => 'plaintext']);

        // Assert
        $this->assertNotEquals('plaintext', $user->password);
        $this->assertTrue(\Hash::check('plaintext', $user->password));
    }
}