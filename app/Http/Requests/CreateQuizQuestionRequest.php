<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateQuizQuestionRequest extends FormRequest
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
        return [
            'question' => ['required', 'string', 'max:1000'],
            'choices' => ['required', 'array', 'min:2', 'max:6'],
            'choices.*' => ['required', 'string', 'max:255'],
            'correct_answer' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'question.required' => 'Question text is required.',
            'question.max' => 'Question text cannot exceed 1000 characters.',
            'choices.required' => 'Answer choices are required.',
            'choices.min' => 'At least 2 answer choices are required.',
            'choices.max' => 'Maximum 6 answer choices are allowed.',
            'choices.*.required' => 'All answer choices must have text.',
            'choices.*.max' => 'Answer choice cannot exceed 255 characters.',
            'correct_answer.required' => 'Correct answer is required.',
            'correct_answer.max' => 'Correct answer cannot exceed 255 characters.',
        ];
    }
}