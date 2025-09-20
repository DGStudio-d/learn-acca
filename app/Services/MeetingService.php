<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\Program;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MeetingService
{
    protected MeetingNotificationService $meetingNotificationService;

    public function __construct(MeetingNotificationService $meetingNotificationService)
    {
        $this->meetingNotificationService = $meetingNotificationService;
    }
    /**
     * Create a new meeting.
     */
    public function createMeeting(array $data, User $teacher): Meeting
    {
        $this->validateMeetingData($data);
        $this->validateTeacherAccess($teacher, $data['program_id']);
        $this->validateMeetingTime($data['start_time'], $data['timezone']);

        $meeting = Meeting::create([
            'program_id' => $data['program_id'],
            'teacher_id' => $teacher->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'meeting_link' => $data['meeting_link'],
            'start_time' => $this->convertToUtc($data['start_time'], $data['timezone']),
            'timezone' => $data['timezone'],
            'active' => $data['active'] ?? true,
        ]);

        // Send notification to enrolled students
        $this->meetingNotificationService->sendMeetingCreatedNotification($meeting);

        return $meeting;
    }

    /**
     * Update an existing meeting.
     */
    public function updateMeeting(Meeting $meeting, array $data, User $teacher): Meeting
    {
        $this->validateMeetingData($data, $meeting->id);
        
        if (isset($data['program_id'])) {
            $this->validateTeacherAccess($teacher, $data['program_id']);
        }
        
        if (isset($data['start_time']) && isset($data['timezone'])) {
            $this->validateMeetingTime($data['start_time'], $data['timezone']);
            $data['start_time'] = $this->convertToUtc($data['start_time'], $data['timezone']);
        }

        $meeting->update($data);
        
        $updatedMeeting = $meeting->fresh();
        
        // Send notification to enrolled students about the update
        $this->meetingNotificationService->sendMeetingUpdatedNotification($updatedMeeting);
        
        return $updatedMeeting;
    }

    /**
     * Get meetings for a teacher.
     */
    public function getTeacherMeetings(User $teacher, array $filters = []): Collection
    {
        $query = Meeting::where('teacher_id', $teacher->id)
            ->with(['program.language', 'program.level']);

        if (isset($filters['program_id'])) {
            $query->where('program_id', $filters['program_id']);
        }

        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'past':
                    $query->past();
                    break;
                case 'today':
                    $query->today();
                    break;
            }
        }

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        return $query->orderBy('start_time', 'desc')->get();
    }

    /**
     * Get meetings for a student based on their enrolled programs.
     */
    public function getStudentMeetings(User $student, array $filters = []): Collection
    {
        $approvedProgramIds = $student->approvedPrograms()->pluck('programs.id');

        $query = Meeting::whereIn('program_id', $approvedProgramIds)
            ->where('active', true)
            ->with(['program.language', 'program.level', 'teacher']);

        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'past':
                    $query->past();
                    break;
                case 'today':
                    $query->today();
                    break;
            }
        }

        return $query->orderBy('start_time', 'asc')->get();
    }

    /**
     * Get meetings for admin (all meetings).
     */
    public function getAllMeetings(array $filters = []): Collection
    {
        $query = Meeting::with(['program.language', 'program.level', 'teacher']);

        if (isset($filters['program_id'])) {
            $query->where('program_id', $filters['program_id']);
        }

        if (isset($filters['teacher_id'])) {
            $query->where('teacher_id', $filters['teacher_id']);
        }

        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'past':
                    $query->past();
                    break;
                case 'today':
                    $query->today();
                    break;
            }
        }

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        return $query->orderBy('start_time', 'desc')->get();
    }

    /**
     * Delete a meeting.
     */
    public function deleteMeeting(Meeting $meeting, User $user): bool
    {
        // Only the teacher who created the meeting or admin can delete it
        if (!$user->isAdmin() && $meeting->teacher_id !== $user->id) {
            throw new \Exception('Unauthorized to delete this meeting');
        }

        // Send cancellation notification before deleting
        $this->meetingNotificationService->sendMeetingCancelledNotification($meeting);

        return $meeting->delete();
    }

    /**
     * Validate meeting data.
     */
    private function validateMeetingData(array $data, ?int $meetingId = null): void
    {
        $rules = [
            'program_id' => 'required|exists:programs,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'meeting_link' => 'required|url|max:500',
            'start_time' => 'required|date|after:now',
            'timezone' => 'required|string|in:' . implode(',', $this->getSupportedTimezones()),
            'active' => 'sometimes|boolean',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate that teacher has access to the program.
     */
    private function validateTeacherAccess(User $teacher, int $programId): void
    {
        if ($teacher->isAdmin()) {
            return; // Admins can create meetings for any program
        }

        $program = Program::findOrFail($programId);
        $teacherLanguageIds = $teacher->teacherLanguages()->pluck('languages.id');

        if (!$teacherLanguageIds->contains($program->language_id)) {
            throw new \Exception('Teacher does not have access to this program\'s language');
        }
    }

    /**
     * Validate meeting time is not in the past and not conflicting.
     */
    private function validateMeetingTime(string $startTime, string $timezone): void
    {
        $meetingTime = Carbon::createFromFormat('Y-m-d H:i:s', $startTime, $timezone);
        
        if ($meetingTime->isPast()) {
            throw new \Exception('Meeting time cannot be in the past');
        }

        // Additional validation: meeting should be at least 30 minutes in the future
        if ($meetingTime->lessThan(now()->addMinutes(30))) {
            throw new \Exception('Meeting must be scheduled at least 30 minutes in advance');
        }
    }

    /**
     * Convert meeting time to UTC for storage.
     */
    private function convertToUtc(string $startTime, string $timezone): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $startTime, $timezone)->utc();
    }

    /**
     * Send reminder notifications for upcoming meetings.
     */
    public function sendMeetingReminders(): int
    {
        // Get meetings that start in the next 24 hours and haven't had reminders sent
        $upcomingMeetings = Meeting::where('active', true)
            ->where('start_time', '>', now())
            ->where('start_time', '<=', now()->addHours(24))
            ->get();

        $remindersSent = 0;

        foreach ($upcomingMeetings as $meeting) {
            $this->meetingNotificationService->sendMeetingReminderNotification($meeting);
            $remindersSent++;
        }

        return $remindersSent;
    }

    /**
     * Get supported timezones.
     */
    private function getSupportedTimezones(): array
    {
        return [
            'UTC',
            'America/New_York',
            'America/Chicago',
            'America/Denver',
            'America/Los_Angeles',
            'Europe/London',
            'Europe/Paris',
            'Europe/Berlin',
            'Asia/Tokyo',
            'Asia/Shanghai',
            'Asia/Dubai',
            'Asia/Riyadh',
            'Australia/Sydney',
        ];
    }
}