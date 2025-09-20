<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Language;
use App\Models\Level;
use App\Models\Program;
use App\Models\Meeting;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SendMeetingRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'teacher']);
        Role::create(['name' => 'student']);
    }

    public function test_send_meeting_reminders_command_works()
    {
        // Create test data
        $language = Language::factory()->create();
        $level = Level::factory()->create(['language_id' => $language->id]);
        $program = Program::factory()->create([
            'language_id' => $language->id,
            'level_id' => $level->id,
        ]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->assignRole('teacher');

        $student = User::factory()->create([
            'role' => 'student',
            'notify_email' => true,
        ]);
        $student->assignRole('student');

        Enrollment::create([
            'user_id' => $student->id,
            'program_id' => $program->id,
            'assigned_at' => now(),
            'access_granted_at' => now(),
            'approved_by' => 1,
        ]);

        // Create a meeting that starts in 12 hours (within 24 hour window)
        Meeting::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'start_time' => now()->addHours(12),
            'active' => true,
        ]);

        // Create a meeting that starts in 2 days (outside 24 hour window)
        Meeting::factory()->create([
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'start_time' => now()->addDays(2),
            'active' => true,
        ]);

        $this->artisan('meetings:send-reminders')
            ->expectsOutput('Sending meeting reminders...')
            ->expectsOutput('Successfully sent 1 meeting reminder(s).')
            ->assertExitCode(0);
    }

    public function test_send_meeting_reminders_command_with_no_meetings()
    {
        $this->artisan('meetings:send-reminders')
            ->expectsOutput('Sending meeting reminders...')
            ->expectsOutput('No upcoming meetings found that need reminders.')
            ->assertExitCode(0);
    }
}