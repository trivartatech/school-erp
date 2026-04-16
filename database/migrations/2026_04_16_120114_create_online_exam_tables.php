<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Online quiz/test definitions
        Schema::create('online_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->enum('type', ['mcq', 'descriptive', 'mixed'])->default('mcq');
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->decimal('pass_marks', 8, 2)->default(0);
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_options')->default(false);
            $table->boolean('show_result_immediately')->default(true);
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->json('target_classes')->nullable(); // array of class IDs
            $table->json('target_sections')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status']);
        });

        // Questions
        Schema::create('online_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('online_quizzes')->cascadeOnDelete();
            $table->text('question_text');
            $table->enum('type', ['mcq', 'true_false', 'short_answer', 'descriptive'])->default('mcq');
            $table->decimal('marks', 6, 2)->default(1);
            $table->json('options')->nullable();    // [{ text, is_correct }]
            $table->text('correct_answer')->nullable(); // for short_answer
            $table->text('explanation')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });

        // Student attempts
        Schema::create('online_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('online_quizzes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('submitted_at')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('passed')->nullable();
            $table->enum('status', ['in_progress', 'submitted', 'auto_submitted', 'graded'])->default('in_progress');
            $table->unsignedSmallInteger('tab_switches')->default(0);
            $table->timestamps();

            $table->unique(['quiz_id', 'student_id']); // one attempt per student per quiz
            $table->index(['quiz_id', 'status']);
        });

        // Per-question responses
        Schema::create('online_quiz_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('online_quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('online_quiz_questions')->cascadeOnDelete();
            $table->text('answer')->nullable();           // selected option index or text
            $table->boolean('is_correct')->nullable();   // null for descriptive
            $table->decimal('marks_awarded', 6, 2)->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_quiz_responses');
        Schema::dropIfExists('online_quiz_attempts');
        Schema::dropIfExists('online_quiz_questions');
        Schema::dropIfExists('online_quizzes');
    }
};
