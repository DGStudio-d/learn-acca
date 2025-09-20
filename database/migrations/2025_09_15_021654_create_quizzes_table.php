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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['file', 'inline'])->default('inline');
            $table->string('file_path')->nullable(); // For file-based quizzes
            $table->integer('pass_score')->default(70); // Percentage required to pass
            $table->boolean('allow_guest_access')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            // Add indexes for performance
            $table->index('program_id');
            $table->index('teacher_id');
            $table->index(['active', 'allow_guest_access']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
