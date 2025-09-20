<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizService
{
    /**
     * Create a new quiz.
     */
    public function createQuiz(array $data, ?UploadedFile $file = null, ?User $teacher = null): Quiz
    {
        return DB::transaction(function () use ($data, $file, $teacher) {
            // Validate program access for teacher
            $program = Program::findOrFail($data['program_id']);
            
            // Check if teacher has access to this program's language (unless admin)
            if ($teacher && !$teacher->isAdmin()) {
                $hasAccess = $program->language->teacherLanguages()
                    ->where('user_id', $teacher->id)
                    ->exists();
                    
                if (!$hasAccess) {
                    throw new \Exception('You do not have permission to create quizzes for this program.', 403);
                }
            }
            
            $quizData = [
                'program_id' => $data['program_id'],
                'teacher_id' => $teacher ? $teacher->id : ($data['teacher_id'] ?? null),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'],
                'pass_score' => $data['pass_score'] ?? 70,
                'allow_guest_access' => $data['allow_guest_access'] ?? false,
                'active' => $data['active'] ?? true,
            ];

            // Handle file upload for file-based quizzes
            if ($data['type'] === 'file' && $file) {
                $filePath = $this->storeQuizFile($file);
                $quizData['file_path'] = $filePath;
            }

            // Handle inline questions for inline quizzes
            if ($data['type'] === 'inline' && isset($data['questions'])) {
                $quizData['questions'] = $data['questions'];
            }

            $quiz = Quiz::create($quizData);

            // Create quiz questions for inline type
            if ($data['type'] === 'inline' && isset($data['quiz_questions'])) {
                $this->createQuizQuestions($quiz, $data['quiz_questions']);
            }

            return $quiz->load(['program', 'quizQuestions']);
        });
    }

    /**
     * Update an existing quiz.
     */
    public function updateQuiz(Quiz $quiz, array $data, ?UploadedFile $file = null): Quiz
    {
        return DB::transaction(function () use ($quiz, $data, $file) {
            // Ensure we have a Quiz model instance
            if (!($quiz instanceof Quiz)) {
                throw new \InvalidArgumentException('Expected Quiz model instance, got: ' . gettype($quiz));
            }
            
            $updateData = [];
            
            // Only update fields that are provided and different from current values
            if (isset($data['title']) && $data['title'] !== $quiz->title) {
                $updateData['title'] = $data['title'];
            }
            if (isset($data['description']) && $data['description'] !== $quiz->description) {
                $updateData['description'] = $data['description'];
            }
            if (isset($data['pass_score']) && $data['pass_score'] !== $quiz->pass_score) {
                $updateData['pass_score'] = (int) $data['pass_score'];
            }
            if (isset($data['allow_guest_access']) && $data['allow_guest_access'] !== $quiz->allow_guest_access) {
                $updateData['allow_guest_access'] = (bool) $data['allow_guest_access'];
            }
            if (isset($data['active']) && $data['active'] !== $quiz->active) {
                $updateData['active'] = (bool) $data['active'];
            }

            // Handle file upload for file-based quizzes
            if ($file && $quiz->type === 'file') {
                // Delete old file if exists
                if ($quiz->file_path) {
                    Storage::disk('public')->delete($quiz->file_path);
                }
                $filePath = $this->storeQuizFile($file);
                $updateData['file_path'] = $filePath;
            }

            // Update the quiz with provided data
            if (!empty($updateData)) {
                $quiz->fill($updateData);
                $quiz->save();
            }

            // Update quiz questions for inline type
            if ($quiz->type === 'inline' && isset($data['quiz_questions'])) {
                // Delete existing questions
                $quiz->quizQuestions()->delete();
                // Create new questions
                $this->createQuizQuestions($quiz, $data['quiz_questions']);
            }

            // Refresh the model to get updated data
            $quiz->refresh();
            
            return $quiz;
        });
    }

    /**
     * Delete a quiz.
     */
    public function deleteQuiz(Quiz $quiz): bool
    {
        return DB::transaction(function () use ($quiz) {
            // Delete associated file if exists
            if ($quiz->file_path) {
                Storage::disk('public')->delete($quiz->file_path);
            }

            // Delete quiz questions
            $quiz->quizQuestions()->delete();

            // Delete quiz attempts
            $quiz->attempts()->delete();

            return $quiz->delete();
        });
    }

    /**
     * Get quizzes for a teacher's assigned programs.
     */
    public function getTeacherQuizzes(User $teacher, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Quiz::with(['program.language', 'program.level']);
        
        // If teacher is admin, show all quizzes, otherwise filter by teacher's assignments
        if (!$teacher->isAdmin()) {
            $query->where('teacher_id', $teacher->id)
                  ->whereHas('program.language.teacherLanguages', function ($q) use ($teacher) {
                      $q->where('user_id', $teacher->id);
                  });
        }

        // Apply filters
        if (isset($filters['program_id'])) {
            $query->where('program_id', $filters['program_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get quiz details with questions.
     */
    public function getQuizWithQuestions(Quiz $quiz): Quiz
    {
        return $quiz->load([
            'program.language',
            'program.level',
            'quizQuestions' => function ($query) {
                $query->ordered();
            }
        ]);
    }

    /**
     * Check if teacher can manage this quiz.
     */
    public function canTeacherManageQuiz(User $teacher, Quiz $quiz): bool
    {

        
        // Admins can manage all quizzes
        if ($teacher->isAdmin()) {
            return true;
        }
        
        // Teachers can only manage their own quizzes AND must be assigned to the program's language
        if ($quiz->teacher_id !== $teacher->id) {
            return false;
        }
        
        // Ensure program and language are loaded
        if (!$quiz->relationLoaded('program') || !$quiz->program->relationLoaded('language')) {
            $quiz->load('program.language');
        }
        
        // Check if teacher is assigned to this quiz's program language
        $hasLanguageAccess = $quiz->program->language->teacherLanguages()
            ->where('user_id', $teacher->id)
            ->exists();
        
        return $hasLanguageAccess;
    }

    /**
     * Add a question to an inline quiz.
     */
    public function addQuestion(Quiz $quiz, array $questionData): QuizQuestion
    {
        if ($quiz->type !== 'inline') {
            throw ValidationException::withMessages([
                'quiz' => 'Questions can only be added to inline quizzes.'
            ]);
        }

        $order = $quiz->quizQuestions()->max('order') + 1;

        return QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => $questionData['question'],
            'choices' => $questionData['choices'],
            'correct_answer' => $questionData['correct_answer'],
            'order' => $order,
        ]);
    }

    /**
     * Update a quiz question.
     */
    public function updateQuestion(QuizQuestion $question, array $data): QuizQuestion
    {
        $question->update([
            'question' => $data['question'] ?? $question->question,
            'choices' => $data['choices'] ?? $question->choices,
            'correct_answer' => $data['correct_answer'] ?? $question->correct_answer,
            'order' => $data['order'] ?? $question->order,
        ]);

        return $question;
    }

    /**
     * Delete a quiz question.
     */
    public function deleteQuestion(QuizQuestion $question): bool
    {
        return $question->delete();
    }

    /**
     * Reorder quiz questions.
     */
    public function reorderQuestions(Quiz $quiz, array $questionOrders): void
    {
        DB::transaction(function () use ($quiz, $questionOrders) {
            foreach ($questionOrders as $questionId => $order) {
                $quiz->quizQuestions()
                    ->where('id', $questionId)
                    ->update(['order' => $order]);
            }
        });
    }

    /**
     * Store uploaded quiz file.
     */
    private function storeQuizFile(UploadedFile $file): string
    {
        $allowedExtensions = ['pdf', 'doc', 'docx', 'txt'];
        $extension = $file->getClientOriginalExtension();

        if (!in_array(strtolower($extension), $allowedExtensions)) {
            throw ValidationException::withMessages([
                'file' => 'File must be a PDF, DOC, DOCX, or TXT file.'
            ]);
        }

        // Store file in quizzes directory
        return $file->store('quizzes', 'public');
    }

    /**
     * Create quiz questions for inline quiz.
     */
    private function createQuizQuestions(Quiz $quiz, array $questions): void
    {
        foreach ($questions as $index => $questionData) {
            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => $questionData['question'],
                'choices' => $questionData['choices'],
                'correct_answer' => $questionData['correct_answer'],
                'order' => $index + 1,
            ]);
        }
    }
}