<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizAttemptService
{
    /**
     * Submit a quiz attempt.
     */
    public function submitAttempt(Quiz $quiz, ?User $student, array $answers): QuizAttempt
    {
        return DB::transaction(function () use ($quiz, $student, $answers) {
            // Validate guest access if no student provided
            if (!$student && !$this->isGuestAccessAllowed($quiz)) {
                throw ValidationException::withMessages([
                    'quiz' => 'Guest access is not allowed for this quiz.'
                ]);
            }

            // Validate student access to quiz program if student is provided
            if ($student && !$this->canStudentAccessQuiz($student, $quiz)) {
                throw ValidationException::withMessages([
                    'quiz' => 'You do not have access to this quiz.'
                ]);
            }

            // Calculate score
            $correctAnswers = $this->calculateScore($quiz, $answers);
            $totalQuestions = $this->getTotalQuestions($quiz);
            $percentageScore = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100) : 0;
            $passed = $this->determinePassStatus($correctAnswers, $totalQuestions, $quiz->pass_score);

            // Create quiz attempt
            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'student_id' => $student?->id,
                'answers' => $answers,
                'score' => $percentageScore,
                'passed' => $passed,
                'submitted_at' => now(),
            ]);

            // Add additional attributes for response
            $attempt->setAttribute('correct_answers', $correctAnswers);
            $attempt->setAttribute('total_questions', $totalQuestions);
            $attempt->setAttribute('percentage_score', $percentageScore);

            return $attempt->load(['quiz', 'student']);
        });
    }

    /**
     * Calculate the score for a quiz attempt.
     */
    public function calculateScore(Quiz $quiz, array $answers): int
    {
        $correctAnswers = 0;

        if ($quiz->isInlineType()) {
            // For inline quizzes, check against quiz questions
            $questions = $quiz->quizQuestions()->ordered()->get();
            
            foreach ($questions as $question) {
                // Try multiple answer key formats
                $userAnswer = $answers[$question->id] ?? 
                             $answers['question_' . $question->id] ?? 
                             $answers[(string) $question->id] ?? null;
                
                if ($userAnswer !== null && $question->isCorrectAnswer($userAnswer)) {
                    $correctAnswers++;
                }
            }
        } else {
            // For file-based quizzes, check against stored questions array
            $questions = $quiz->questions ?? [];
            
            foreach ($questions as $index => $question) {
                $questionKey = 'question_' . ($index + 1);
                $userAnswer = $answers[$questionKey] ?? $answers[$index] ?? null;
                $correctAnswer = $question['correct_answer'] ?? null;
                
                if ($userAnswer !== null && $userAnswer === $correctAnswer) {
                    $correctAnswers++;
                }
            }
        }

        return $correctAnswers;
    }

    /**
     * Get quiz attempts for a student.
     */
    public function getStudentAttempts(User $student, ?Quiz $quiz = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = QuizAttempt::with(['quiz.program.language', 'quiz.program.level'])
            ->where('student_id', $student->id);

        if ($quiz) {
            $query->where('quiz_id', $quiz->id);
        }

        return $query->orderBy('submitted_at', 'desc')
                    ->paginate(15);
    }

    /**
     * Get quiz statistics for teachers/admins.
     */
    public function getQuizStatistics(Quiz $quiz): array
    {
        $totalAttempts = $quiz->attempts()->count();
        $passedAttempts = $quiz->attempts()->passed()->count();
        $failedAttempts = $quiz->attempts()->failed()->count();
        $guestAttempts = $quiz->attempts()->guest()->count();
        $studentAttempts = $quiz->attempts()->student()->count();

        $averageScore = $quiz->attempts()->avg('score') ?? 0;
        $totalQuestions = $this->getTotalQuestions($quiz);
        $averagePercentage = $totalQuestions > 0 ? ($averageScore / $totalQuestions) * 100 : 0;

        return [
            'total_attempts' => $totalAttempts,
            'passed_attempts' => $passedAttempts,
            'failed_attempts' => $failedAttempts,
            'guest_attempts' => $guestAttempts,
            'student_attempts' => $studentAttempts,
            'pass_rate' => $totalAttempts > 0 ? round(($passedAttempts / $totalAttempts) * 100, 2) : 0,
            'average_score' => round($averageScore, 2),
            'average_percentage' => round($averagePercentage, 2),
            'total_questions' => $totalQuestions,
        ];
    }

    /**
     * Get detailed quiz results for a specific attempt.
     */
    public function getAttemptResults(QuizAttempt $attempt): array
    {
        $quiz = $attempt->quiz;
        $answers = $attempt->answers ?? [];
        $results = [];

        if ($quiz->isInlineType()) {
            $questions = $quiz->quizQuestions()->ordered()->get();
            
            foreach ($questions as $question) {
                $questionKey = 'question_' . $question->id;
                $userAnswer = $answers[$questionKey] ?? null;
                $isCorrect = $userAnswer && $question->isCorrectAnswer($userAnswer);

                $results[] = [
                    'question_id' => $question->id,
                    'question' => $question->question,
                    'choices' => $question->formatted_choices,
                    'user_answer' => $userAnswer,
                    'correct_answer' => $question->correct_answer,
                    'is_correct' => $isCorrect,
                ];
            }
        } else {
            $questions = $quiz->questions ?? [];
            
            foreach ($questions as $index => $question) {
                $questionKey = 'question_' . ($index + 1);
                $userAnswer = $answers[$questionKey] ?? null;
                $correctAnswer = $question['correct_answer'] ?? null;
                $isCorrect = $userAnswer && $userAnswer === $correctAnswer;

                $results[] = [
                    'question_number' => $index + 1,
                    'question' => $question['question'] ?? '',
                    'choices' => $question['choices'] ?? [],
                    'user_answer' => $userAnswer,
                    'correct_answer' => $correctAnswer,
                    'is_correct' => $isCorrect,
                ];
            }
        }

        return [
            'attempt' => $attempt,
            'quiz' => $quiz,
            'results' => $results,
            'summary' => [
                'total_questions' => count($results),
                'correct_answers' => round(($attempt->score / 100) * count($results)),
                'incorrect_answers' => count($results) - round(($attempt->score / 100) * count($results)),
                'percentage' => $attempt->percentage_score,
                'passed' => $attempt->passed,
                'pass_score_required' => $quiz->pass_score,
            ],
        ];
    }

    /**
     * Get quiz attempts with filters for teachers/admins.
     */
    public function getQuizAttempts(Quiz $quiz, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $quiz->attempts()->with(['student']);

        // Apply filters
        if (isset($filters['passed'])) {
            if ($filters['passed']) {
                $query->passed();
            } else {
                $query->failed();
            }
        }

        if (isset($filters['guest_only']) && $filters['guest_only']) {
            $query->guest();
        }

        if (isset($filters['student_only']) && $filters['student_only']) {
            $query->student();
        }

        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('submitted_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('submitted_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('submitted_at', 'desc')
                    ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Check if guest access is allowed for a quiz.
     */
    private function isGuestAccessAllowed(Quiz $quiz): bool
    {
        // Check quiz-specific guest access setting
        if (!$quiz->allow_guest_access) {
            return false;
        }

        // Check global guest quiz access setting
        return Settings::isGuestAccessAllowed('quizzes');
    }

    /**
     * Check if student can access the quiz.
     */
    private function canStudentAccessQuiz(User $student, Quiz $quiz): bool
    {
        // Check if student is enrolled in the quiz's program
        $enrollment = $student->enrollments()
            ->where('program_id', $quiz->program_id)
            ->first();

        if (!$enrollment) {
            return false;
        }

        // Check if student has been granted access to the program
        return !is_null($enrollment->access_granted_at);
    }

    /**
     * Get total number of questions for a quiz.
     */
    private function getTotalQuestions(Quiz $quiz): int
    {
        if ($quiz->isInlineType()) {
            return $quiz->quizQuestions()->count();
        }

        return is_array($quiz->questions) ? count($quiz->questions) : 0;
    }

    /**
     * Determine if the attempt passed based on score and pass threshold.
     */
    private function determinePassStatus(int $score, int $totalQuestions, int $passScore): bool
    {
        if ($totalQuestions === 0) {
            return false;
        }

        $percentage = ($score / $totalQuestions) * 100;
        return $percentage >= $passScore;
    }
}