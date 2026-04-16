<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete()
                ->comment('Academic year of graduation');

            $table->string('final_class')->nullable()->comment('Class/Grade at graduation');
            $table->string('passout_year', 9)->nullable()->comment('e.g. 2025-26');
            $table->decimal('final_percentage', 5, 2)->nullable();
            $table->string('final_grade', 5)->nullable();

            // Post-school info
            $table->string('current_occupation')->nullable();
            $table->string('current_employer')->nullable();
            $table->string('current_city')->nullable();
            $table->string('current_state')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('personal_phone', 20)->nullable();
            $table->string('linkedin_url')->nullable();
            $table->text('achievements')->nullable();
            $table->text('notes')->nullable();

            $table->date('graduated_on')->nullable();
            $table->unsignedBigInteger('graduated_by')->nullable();

            $table->unique('student_id');
            $table->index(['school_id', 'passout_year']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni');
    }
};
