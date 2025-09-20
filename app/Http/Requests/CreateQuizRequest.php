<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateQuizRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole(['teacher', 'admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'program_id' => ['required', 'exists:programs,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::in(['file', 'inline'])],
            'pass_score' => ['nullable', 'integer', 'min:1', 'max:100'],
            'allow_guest_access' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ];

        // Add file validation for file-based quizzes
        if ($this->input('type') === 'file') {
            $rules['file'] = ['required', 'file', 'mimes:pdf,doc,docx,txt', 'max:10240']; // 10MB max
        }

        // Add questions validation for inline quizzes
        if ($this->input('type') === 'inline') {
            $rules['quiz_questions'] = ['required', 'array', 'min:1'];
            $rules['quiz_questions.*.question'] = ['required', 'string', 'max:1000'];
            $rules['quiz_questions.*.choices'] = ['required', 'array', 'min:2', 'max:6'];
            $rules['quiz_questions.*.choices.*'] = ['required', 'string', 'max:255'];
            $rules['quiz_questions.*.correct_answer'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'program_id.required' => 'Program is required.',
            'program_id.exists' => 'Selected program does not exist.',
            'title.required' => 'Quiz title is required.',
            'title.max' => 'Quiz title cannot exceed 255 characters.',
            'type.required' => 'Quiz type is required.',
            'type.in' => 'Quiz type must be either file or inline.',
            'file.required' => 'File is required for file-based quizzes.',
            'file.mimes' => 'File must be a PDF, DOC, DOCX, or TXT file.',
            'file.max' => 'File size cannot exceed 10MB.',
            'quiz_questions.required' => 'Questions are required for inline quizzes.',
            'quiz_questions.min' => 'At least one question is required.',
            'quiz_questions.*.question.required' => 'Question text is required.',
            'quiz_questions.*.choices.required' => 'Answer choices are required.',
            'quiz_questions.*.choices.min' => 'At least 2 answer choices are required.',
            'quiz_questions.*.choices.max' => 'Maximum 6 answer choices are allowed.',
            'quiz_questions.*.correct_answer.required' => 'Correct answer is required.',
            'pass_score.min' => 'Pass score must be at least 1.',
            'pass_score.max' => 'Pass score cannot exceed 100.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->has('pass_score')) {
            $this->merge(['pass_score' => 70]);
        }

        if (!$this->has('allow_guest_access')) {
            $this->merge(['allow_guest_access' => false]);
        }

        if (!$this->has('active')) {
            $this->merge(['active' => true]);
        }
    }
}