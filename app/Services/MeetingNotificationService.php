<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MeetingNotificationService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Send meeting creation notification to all enrolled students in the program.
     */
    public function sendMeetingCreatedNotification(Meeting $meeting): void
    {
        $this->notificationService->sendMeetingNotification($meeting, 'created');
    }

    /**
     * Send meeting update notification to all enrolled students in the program.
     */
    public function sendMeetingUpdatedNotification(Meeting $meeting): void
    {
        $this->notificationService->sendMeetingNotification($meeting, 'updated');
    }

    /**
     * Send meeting cancellation notification to all enrolled students in the program.
     */
    public function sendMeetingCancelledNotification(Meeting $meeting): void
    {
        $this->notificationService->sendMeetingNotification($meeting, 'cancelled');
    }

    /**
     * Send meeting reminder notification to all enrolled students.
     */
    public function sendMeetingReminderNotification(Meeting $meeting): void
    {
        $this->notificationService->sendMeetingNotification($meeting, 'reminder');
    }


}