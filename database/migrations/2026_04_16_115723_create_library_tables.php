<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Book catalog
        Schema::create('library_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('author', 255)->nullable();
            $table->string('isbn', 30)->nullable();
            $table->string('publisher', 255)->nullable();
            $table->year('publish_year')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('subject', 100)->nullable();
            $table->string('language', 50)->nullable()->default('English');
            $table->string('location', 100)->nullable();
            $table->unsignedSmallInteger('total_copies')->default(1);
            $table->unsignedSmallInteger('available_copies')->default(1);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('cover_image', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('barcode', 50)->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'category']);
            $table->index(['school_id', 'isbn']);
        });

        // Issue / Return records
        Schema::create('library_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained()->nullOnDelete();
            $table->string('borrower_type', 10); // student | staff
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->enum('status', ['issued', 'returned', 'overdue', 'lost'])->default('issued');
            $table->decimal('fine_amount', 8, 2)->default(0);
            $table->boolean('fine_paid')->default(false);
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'student_id']);
            $table->index(['school_id', 'due_date']);
        });

        // Fine rate & limits per school
        Schema::create('library_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete()->unique();
            $table->unsignedSmallInteger('max_issue_days')->default(14);
            $table->decimal('fine_per_day', 6, 2)->default(1.00);
            $table->unsignedTinyInteger('max_books_student')->default(3);
            $table->unsignedTinyInteger('max_books_staff')->default(5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_issues');
        Schema::dropIfExists('library_books');
        Schema::dropIfExists('library_settings');
    }
};
