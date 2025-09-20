<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'preferred_language' => ['required', 'string', 'exists:languages,code'],
            'level_id' => ['required', 'integer', 'exists:levels,id'],
            'role' => ['sometimes', 'string', 'in:student,teacher,admin'],
            'notify_email' => ['sometimes', 'boolean'],
            'notify_whatsapp' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'preferred_language.exists' => 'The selected language is not available.',
            'level_id.exists' => 'The selected level is not available.',
            'role.in' => 'The role must be student, teacher, or admin.',
        ];
    }
}