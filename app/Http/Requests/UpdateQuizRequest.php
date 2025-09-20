<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuizRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Basic role check - specific quiz ownership will be checked in the controller
        return $this->user()->hasRole(['teacher', 'admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'pass_score' => ['nullable', 'integer', 'min:1', 'max:100'],
            'allow_guest_access' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ];

        // Add file validation for file-based quizzes (if file is provided)
        if ($this->hasFile('file')) {
            $rules['file'] = ['file', 'mimes:pdf,doc,docx,txt', 'max:10240']; // 10MB max
        }

        // Add questions validation for inline quizzes (if questions are provided)
        if ($this->has('quiz_questions')) {
            $rules['quiz_questions'] = ['array', 'min:1'];
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
            'title.required' => 'Quiz title is required.',
            'title.max' => 'Quiz title cannot exceed 255 characters.',
            'file.mimes' => 'File must be a PDF, DOC, DOCX, or TXT file.',
            'file.max' => 'File size cannot exceed 10MB.',
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
}