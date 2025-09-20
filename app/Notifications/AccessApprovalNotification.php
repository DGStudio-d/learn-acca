<?php

namespace App\Notifications;

use App\Models\Program;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Notifications\Messages\MailMessage;

class AccessApprovalNotification extends BaseNotification
{
    protected User $student;
    protected Program $program;

    public function __construct(User $student, Program $program)
    {
        $this->student = $student;
        $this->program = $program;
        
        parent::__construct([
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ],
            'program' => [
                'id' => $program->id,
                'title' => $program->title,
                'language' => $program->language->name,
                'level' => $program->level->name,
            ],
        ], 'access_approval');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Access Approved - Welcome to ' . $this->program->title)
            ->greeting('Hello ' . $this->student->name . '!')
            ->line('Great news! Your access to the program has been approved.')
            ->line('**Program Details:**')
            ->line('Program: ' . $this->program->title)
            ->line('Language: ' . $this->program->language->name)
            ->line('Level: ' . $this->program->level->name)
            ->line('')
            ->line('You can now:')
            ->line('• Access and attempt quizzes')
            ->line('• Join scheduled meetings')
            ->line('• View program materials')
            ->line('• Track your progress')
            ->action('Start Learning', url('/student/programs/' . $this->program->id))
            ->line('Welcome to Learn Academy! We\'re excited to support your learning journey.');
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp($notifiable): string
    {
        return "🎉 *Learn Academy - Access Approved!*\n\n" .
               "✅ Great news {$this->student->name}!\n" .
               "Your access has been approved for:\n\n" .
               "📖 *Program:* {$this->program->title}\n" .
               "🌐 *Language:* {$this->program->language->name}\n" .
               "📊 *Level:* {$this->program->level->name}\n\n" .
               "🚀 You can now:\n" .
               "• Take quizzes 📝\n" .
               "• Join meetings 👥\n" .
               "• Access materials 📚\n\n" .
               "Welcome to Learn Academy! 🎓";
    }
}