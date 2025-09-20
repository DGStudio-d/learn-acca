<?php

namespace App\Notifications;

use App\Models\Meeting;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Notifications\Messages\MailMessage;

class MeetingNotification extends BaseNotification
{
    protected Meeting $meeting;
    protected string $action; // 'created', 'updated', 'cancelled', 'reminder'

    public function __construct(Meeting $meeting, string $action)
    {
        $this->meeting = $meeting;
        $this->action = $action;
        
        $meeting->load(['program.language', 'program.level', 'teacher']);
        
        parent::__construct([
            'meeting' => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'description' => $meeting->description,
                'meeting_link' => $meeting->meeting_link,
                'start_time' => $meeting->start_time->toISOString(),
                'timezone' => $meeting->timezone,
                'formatted_time' => $meeting->start_time->format('Y-m-d H:i:s T'),
            ],
            'program' => [
                'id' => $meeting->program->id,
                'title' => $meeting->program->title,
                'language' => $meeting->program->language->name,
                'level' => $meeting->program->level->name,
            ],
            'teacher' => [
                'id' => $meeting->teacher->id,
                'name' => $meeting->teacher->name,
            ],
            'action' => $action,
        ], 'meeting_' . $action);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $message = new MailMessage();
        
        switch ($this->action) {
            case 'created':
                $message->subject('New Meeting Scheduled: ' . $this->meeting->title)
                    ->greeting('Hello!')
                    ->line('A new meeting has been scheduled for your program.');
                break;
                
            case 'updated':
                $message->subject('Meeting Updated: ' . $this->meeting->title)
                    ->greeting('Hello!')
                    ->line('A meeting in your program has been updated.');
                break;
                
            case 'cancelled':
                $message->subject('Meeting Cancelled: ' . $this->meeting->title)
                    ->greeting('Hello!')
                    ->line('Unfortunately, a meeting in your program has been cancelled.');
                break;
                
            case 'reminder':
                $message->subject('Meeting Reminder: ' . $this->meeting->title)
                    ->greeting('Hello!')
                    ->line('This is a reminder about your upcoming meeting.');
                break;
        }

        $message->line('**Meeting Details:**')
            ->line('Title: ' . $this->meeting->title)
            ->line('Program: ' . $this->meeting->program->title)
            ->line('Language: ' . $this->meeting->program->language->name)
            ->line('Level: ' . $this->meeting->program->level->name)
            ->line('Teacher: ' . $this->meeting->teacher->name)
            ->line('Date & Time: ' . $this->meeting->start_time->format('Y-m-d H:i:s T'));

        if ($this->meeting->description) {
            $message->line('Description: ' . $this->meeting->description);
        }

        if ($this->action !== 'cancelled') {
            $message->action('Join Meeting', $this->meeting->meeting_link);
        }

        $message->line('Thank you for being part of Learn Academy!');

        return $message;
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp($notifiable): string
    {
        $emoji = match ($this->action) {
            'created' => '🆕',
            'updated' => '📝',
            'cancelled' => '❌',
            'reminder' => '⏰',
            default => '📅',
        };

        $actionText = match ($this->action) {
            'created' => 'New meeting scheduled!',
            'updated' => 'Meeting updated!',
            'cancelled' => 'Meeting cancelled!',
            'reminder' => 'Meeting reminder!',
            default => 'Meeting notification!',
        };

        $message = "📚 *Learn Academy*\n\n" .
                  "{$emoji} {$actionText}\n\n" .
                  "📋 *Title:* {$this->meeting->title}\n" .
                  "🌐 *Language:* {$this->meeting->program->language->name}\n" .
                  "📊 *Level:* {$this->meeting->program->level->name}\n" .
                  "👨‍🏫 *Teacher:* {$this->meeting->teacher->name}\n" .
                  "📅 *Time:* {$this->meeting->start_time->format('Y-m-d H:i:s T')}\n";

        if ($this->meeting->description) {
            $message .= "📝 *Description:* {$this->meeting->description}\n";
        }

        if ($this->action !== 'cancelled') {
            $message .= "\n🔗 *Join:* {$this->meeting->meeting_link}";
        }

        return $message;
    }
}