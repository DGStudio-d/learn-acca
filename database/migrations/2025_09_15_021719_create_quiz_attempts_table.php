<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->nullable()->constrained('users')->onDelete('cascade'); // Nullable for guest attempts
            $table->json('answers'); // Student's answers stored as JSON
            $table->integer('score'); // Calculated score (0-100)
            $table->boolean('passed')->default(false); // Whether the attempt passed
            $table->timestamp('submitted_at');
            $table->timestamps();
            
            // Add indexes for performance
            $table->index('quiz_id');
            $table->index('student_id');
            $table->index(['quiz_id', 'student_id']);
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
