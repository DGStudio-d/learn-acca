<?php

namespace App\Notifications;

use App\Models\Program;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Notifications\Messages\MailMessage;

class EnrollmentNotification extends BaseNotification
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
                'phone' => $student->phone,
            ],
            'program' => [
                'id' => $program->id,
                'title' => $program->title,
                'language' => $program->language->name,
                'level' => $program->level->name,
            ],
        ], 'enrollment');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Student Enrollment - ' . $this->student->name)
            ->greeting('Hello Admin!')
            ->line('A new student has enrolled in a program and requires approval.')
            ->line('**Student Details:**')
            ->line('Name: ' . $this->student->name)
            ->line('Email: ' . $this->student->email)
            ->line('Phone: ' . $this->student->phone)
            ->line('')
            ->line('**Program Details:**')
            ->line('Program: ' . $this->program->title)
            ->line('Language: ' . $this->program->language->name)
            ->line('Level: ' . $this->program->level->name)
            ->line('')
            ->line('Please review and approve the student\'s access to begin their learning journey.')
            ->action('Review Enrollments', url('/admin/enrollments'))
            ->line('Thank you for managing Learn Academy!');
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp($notifiable): string
    {
        return "📚 *Learn Academy - New Enrollment*\n\n" .
               "🆕 A new student has enrolled and needs approval:\n\n" .
               "👤 *Student:* {$this->student->name}\n" .
               "📧 *Email:* {$this->student->email}\n" .
               "📱 *Phone:* {$this->student->phone}\n\n" .
               "📖 *Program:* {$this->program->title}\n" .
               "🌐 *Language:* {$this->program->language->name}\n" .
               "📊 *Level:* {$this->program->level->name}\n\n" .
               "Please review and approve their access. 👍";
    }
}