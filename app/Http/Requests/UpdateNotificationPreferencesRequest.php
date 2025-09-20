<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // User can always update their own preferences
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notify_email' => 'sometimes|boolean',
            'notify_whatsapp' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'notify_email.boolean' => 'Email notification preference must be true or false.',
            'notify_whatsapp.boolean' => 'WhatsApp notification preference must be true or false.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'notify_email' => 'email notifications',
            'notify_whatsapp' => 'WhatsApp notifications',
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Log preference changes for audit purposes
        $user = $this->user();
        $changes = [];
        
        if ($this->has('notify_email') && $this->notify_email !== $user->notify_email) {
            $changes['notify_email'] = [
                'from' => $user->notify_email,
                'to' => $this->notify_email,
            ];
        }
        
        if ($this->has('notify_whatsapp') && $this->notify_whatsapp !== $user->notify_whatsapp) {
            $changes['notify_whatsapp'] = [
                'from' => $user->notify_whatsapp,
                'to' => $this->notify_whatsapp,
            ];
        }
        
        if (!empty($changes)) {
            \Illuminate\Support\Facades\Log::info('User notification preferences updated', [
                'user_id' => $user->id,
                'changes' => $changes,
                'ip_address' => $this->ip(),
                'user_agent' => $this->userAgent(),
            ]);
        }
    }
}