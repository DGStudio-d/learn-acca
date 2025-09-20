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
        // Add performance indexes to users table
        Schema::table('users', function (Blueprint $table) {
            $table->index(['email', 'deleted_at'], 'users_email_deleted_at_index');
            $table->index(['role', 'created_at'], 'users_role_created_at_index');
            $table->index(['preferred_language', 'role'], 'users_preferred_language_role_index');
        });

        // Add performance indexes to programs table
        Schema::table('programs', function (Blueprint $table) {
            $table->index(['language_id', 'level_id', 'active'], 'programs_language_level_active_index');
            $table->index(['active', 'created_at'], 'programs_active_created_at_index');
        });

        // Add performance indexes to quizzes table
        Schema::table('quizzes', function (Blueprint $table) {
            $table->index(['program_id', 'active'], 'quizzes_program_active_index');
            $table->index(['teacher_id', 'active'], 'quizzes_teacher_active_index');
            $table->index(['allow_guest_access', 'active'], 'quizzes_guest_access_active_index');
            $table->index(['type', 'active'], 'quizzes_type_active_index');
        });

        // Add performance indexes to quiz_attempts table
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->index(['student_id', 'submitted_at'], 'quiz_attempts_student_submitted_index');
            $table->index(['quiz_id', 'passed'], 'quiz_attempts_quiz_passed_index');
            $table->index(['passed', 'submitted_at'], 'quiz_attempts_passed_submitted_index');
        });

        // Add performance indexes to meetings table
        Schema::table('meetings', function (Blueprint $table) {
            $table->index(['program_id', 'start_time'], 'meetings_program_start_time_index');
            $table->index(['teacher_id', 'start_time'], 'meetings_teacher_start_time_index');
            $table->index(['start_time', 'active'], 'meetings_start_time_active_index');
        });

        // Add performance indexes to notification_logs table
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'notification_logs_user_created_index');
            $table->index(['type', 'created_at'], 'notification_logs_type_created_index');
            $table->index(['sent_at', 'type'], 'notification_logs_sent_type_index');
        });

        // Add performance indexes to teacher_languages table
        Schema::table('teacher_languages', function (Blueprint $table) {
            $table->index(['language_id', 'user_id'], 'teacher_languages_language_user_index');
        });

        // Add performance indexes to levels table
        Schema::table('levels', function (Blueprint $table) {
            $table->index(['language_id', 'order'], 'levels_language_order_index');
        });

        // Add performance indexes to languages table
        Schema::table('languages', function (Blueprint $table) {
            $table->index(['active', 'name'], 'languages_active_name_index');
            $table->index('code', 'languages_code_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop performance indexes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_email_deleted_at_index');
            $table->dropIndex('users_role_created_at_index');
            $table->dropIndex('users_preferred_language_role_index');
        });

        // Drop performance indexes from programs table
        Schema::table('programs', function (Blueprint $table) {
            $table->dropIndex('programs_language_level_active_index');
            $table->dropIndex('programs_active_created_at_index');
        });

        // Drop performance indexes from quizzes table
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropIndex('quizzes_program_active_index');
            $table->dropIndex('quizzes_teacher_active_index');
            $table->dropIndex('quizzes_guest_access_active_index');
            $table->dropIndex('quizzes_type_active_index');
        });

        // Drop performance indexes from quiz_attempts table
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('quiz_attempts_student_submitted_index');
            $table->dropIndex('quiz_attempts_quiz_passed_index');
            $table->dropIndex('quiz_attempts_passed_submitted_index');
        });

        // Drop performance indexes from meetings table
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropIndex('meetings_program_start_time_index');
            $table->dropIndex('meetings_teacher_start_time_index');
            $table->dropIndex('meetings_start_time_active_index');
        });

        // Drop performance indexes from notification_logs table
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropIndex('notification_logs_user_created_index');
            $table->dropIndex('notification_logs_type_created_index');
            $table->dropIndex('notification_logs_sent_type_index');
        });

        // Drop performance indexes from teacher_languages table
        Schema::table('teacher_languages', function (Blueprint $table) {
            $table->dropIndex('teacher_languages_language_user_index');
        });

        // Drop performance indexes from levels table
        Schema::table('levels', function (Blueprint $table) {
            $table->dropIndex('levels_language_order_index');
        });

        // Drop performance indexes from languages table
        Schema::table('languages', function (Blueprint $table) {
            $table->dropIndex('languages_active_name_index');
            $table->dropIndex('languages_code_index');
        });
    }
};