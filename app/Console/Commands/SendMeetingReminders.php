<?php

namespace App\Console\Commands;

use App\Services\MeetingService;
use Illuminate\Console\Command;

class SendMeetingReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meetings:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder notifications for upcoming meetings';

    protected MeetingService $meetingService;

    public function __construct(MeetingService $meetingService)
    {
        parent::__construct();
        $this->meetingService = $meetingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Sending meeting reminders...');

        try {
            $remindersSent = $this->meetingService->sendMeetingReminders();

            if ($remindersSent > 0) {
                $this->info("Successfully sent {$remindersSent} meeting reminder(s).");
            } else {
                $this->info('No upcoming meetings found that need reminders.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to send meeting reminders: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
