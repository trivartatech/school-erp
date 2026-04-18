<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 7)->default('#6366f1'); // hex
            $table->string('emblem')->nullable();           // file path
            $table->foreignId('incharge_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('captain_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('house_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('house_id')->constrained('houses')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            // One house per student per academic year
            $table->unique(['student_id', 'academic_year_id']);
        });

        Schema::create('house_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('house_id')->constrained('houses')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->enum('category', ['sports', 'academic', 'cultural', 'discipline', 'general'])->default('general');
            $table->integer('points'); // positive = award, negative = deduction
            $table->string('description');
            $table->nullableMorphs('reference'); // link to DisciplinaryRecord etc.
            $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_points');
        Schema::dropIfExists('house_students');
        Schema::dropIfExists('houses');
    }
};
