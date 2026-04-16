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
        Schema::create('disciplinary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('incident_date');
            $table->string('category', 100); // misconduct, bullying, damage, dress-code, etc.
            $table->enum('severity', ['minor', 'moderate', 'major'])->default('minor');
            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->enum('status', ['open', 'under_review', 'resolved', 'escalated'])->default('open');
            $table->enum('consequence', ['warning', 'detention', 'parent_call', 'suspension', 'expulsion', 'none'])->nullable();
            $table->date('consequence_from')->nullable();
            $table->date('consequence_to')->nullable();
            $table->boolean('parent_notified')->default(false);
            $table->date('parent_notified_at')->nullable();
            $table->text('student_statement')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'student_id']);
            $table->index(['school_id', 'incident_date']);
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_records');
    }
};
