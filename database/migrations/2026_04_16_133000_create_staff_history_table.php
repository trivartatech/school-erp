<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();

            $table->enum('event_type', [
                'joining',
                'promotion',
                'transfer',
                'demotion',
                'salary_revision',
                'department_change',
                'designation_change',
                'increment',
                'confirmation',
                'termination',
                'other',
            ]);

            // What changed (before/after)
            $table->unsignedBigInteger('from_designation_id')->nullable();
            $table->unsignedBigInteger('to_designation_id')->nullable();
            $table->unsignedBigInteger('from_department_id')->nullable();
            $table->unsignedBigInteger('to_department_id')->nullable();
            $table->decimal('from_salary', 12, 2)->nullable();
            $table->decimal('to_salary', 12, 2)->nullable();

            $table->date('effective_date');
            $table->string('order_no')->nullable()->comment('Transfer/Promotion order number');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();

            $table->timestamps();
            $table->index(['staff_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_history');
    }
};
