<?php

namespace Tests\Unit\Models;

use App\Models\Enrollment;
use App\Models\Language;
use App\Models\Level;
use App\Models\Meeting;
use App\Models\Program;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_has_fillable_attributes()
    {
        // Arrange
        $fillable = [
            'language_id',
            'level_id',
            'title',
            'description',
            'active',
        ];

        // Act
        $program = new Program();

        // Assert
        $this->assertEquals($fillable, $program->getFillable());
    }

    public function test_program_casts_attributes_correctly()
    {
        // Arrange
        $program = Program::factory()->create(['active' => true]);

        // Act & Assert
        $this->assertIsBool($program->active);
    }

    public function test_program_belongs_to_language()
    {
        // Arrange
        $language = Language::factory()->create();
        $program = Program::factory()->create(['language_id' => $language->id]);

        // Act
        $programLanguage = $program->language;

        // Assert
        $this->assertInstanceOf(Language::class, $programLanguage);
        $this->assertEquals($language->id, $programLanguage->id);
    }

    public function test_program_belongs_to_level()
    {
        // Arrange
        $level = Level::factory()->create();
        $program = Program::factory()->create(['level_id' => $level->id]);

        // Act
        $programLevel = $program->level;

        // Assert
        $this->assertInstanceOf(Level::class, $programLevel);
        $this->assertEquals($level->id, $programLevel->id);
    }

    public function test_program_has_many_enrollments()
    {
        // Arrange
        $program = Program::factory()->create();
        Enrollment::factory()->count(3)->create(['program_id' => $program->id]);

        // Act
        $enrollments = $program->enrollments;

        // Assert
        $this->assertCount(3, $enrollments);
        $this->assertInstanceOf(Enrollment::class, $enrollments->first());
    }

    public function test_program_has_many_students_through_enrollments()
    {
        // Arrange
        $program = Program::factory()->create();
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        
        $program->enrollments()->create([
            'user_id' => $student1->id,
            'assigned_at' => now(),
            'access_granted_at' => now()
        ]);
        $program->enrollments()->create([
            'user_id' => $student2->id,
            'assigned_at' => now()
        ]);

        // Act
        $students = $program->students;

        // Assert
        $this->assertCount(2, $students);
        $this->assertInstanceOf(User::class, $students->first());
        $this->assertNotNull($students->first()->pivot->assigned_at);
    }

    public function test_program_has_many_quizzes()
    {
        // Arrange
        $program = Program::factory()->create();
        Quiz::factory()->count(2)->create(['program_id' => $program->id]);

        // Act
        $quizzes = $program->quizzes;

        // Assert
        $this->assertCount(2, $quizzes);
        $this->assertInstanceOf(Quiz::class, $quizzes->first());
    }

    public function test_program_has_many_meetings()
    {
        // Arrange
        $program = Program::factory()->create();
        Meeting::factory()->count(2)->create(['program_id' => $program->id]);

        // Act
        $meetings = $program->meetings;

        // Assert
        $this->assertCount(2, $meetings);
        $this->assertInstanceOf(Meeting::class, $meetings->first());
    }

    public function test_active_scope_returns_only_active_programs()
    {
        // Arrange
        Program::factory()->create(['active' => true]);
        Program::factory()->create(['active' => false]);

        // Act
        $activePrograms = Program::active()->get();

        // Assert
        $this->assertCount(1, $activePrograms);
        $this->assertTrue($activePrograms->first()->active);
    }

    public function test_get_enrollment_count_attribute()
    {
        // Arrange
        $program = Program::factory()->create();
        Enrollment::factory()->count(5)->create(['program_id' => $program->id]);

        // Act
        $enrollmentCount = $program->enrollment_count;

        // Assert
        $this->assertEquals(5, $enrollmentCount);
    }

    public function test_get_approved_enrollment_count_attribute()
    {
        // Arrange
        $program = Program::factory()->create();
        
        // Create approved enrollments
        Enrollment::factory()->count(3)->create([
            'program_id' => $program->id,
            'access_granted_at' => now()
        ]);
        
        // Create pending enrollments
        Enrollment::factory()->count(2)->create([
            'program_id' => $program->id,
            'access_granted_at' => null
        ]);

        // Act
        $approvedCount = $program->approved_enrollment_count;

        // Assert
        $this->assertEquals(3, $approvedCount);
    }

    public function test_program_can_be_created_with_all_attributes()
    {
        // Arrange
        $language = Language::factory()->create();
        $level = Level::factory()->create();
        
        $programData = [
            'language_id' => $language->id,
            'level_id' => $level->id,
            'title' => 'Advanced English',
            'description' => 'Advanced level English program',
            'active' => true,
        ];

        // Act
        $program = Program::create($programData);

        // Assert
        $this->assertDatabaseHas('programs', $programData);
        $this->assertEquals('Advanced English', $program->title);
        $this->assertTrue($program->active);
        $this->assertEquals($language->id, $program->language_id);
        $this->assertEquals($level->id, $program->level_id);
    }

    public function test_program_relationships_are_properly_loaded()
    {
        // Arrange
        $language = Language::factory()->create(['name' => 'English']);
        $level = Level::factory()->create(['name' => 'Beginner']);
        $program = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id
        ]);

        // Act
        $loadedProgram = Program::with(['language', 'level'])->find($program->id);

        // Assert
        $this->assertEquals('English', $loadedProgram->language->name);
        $this->assertEquals('Beginner', $loadedProgram->level->name);
    }
}