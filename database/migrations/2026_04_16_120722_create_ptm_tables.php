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
        // PTM event / session (one per school per date)
        Schema::create('ptm_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('slot_duration_minutes')->default(15);
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->timestamps();

            $table->index(['school_id', 'date']);
        });

        // Teacher availability slots within a PTM session
        Schema::create('ptm_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('ptm_sessions')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->time('slot_time');
            $table->boolean('is_booked')->default(false);
            $table->timestamps();

            $table->unique(['session_id', 'staff_id', 'slot_time']);
        });

        // Parent bookings
        Schema::create('ptm_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained('ptm_slots')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['booked', 'completed', 'cancelled', 'no_show'])->default('booked');
            $table->text('meeting_notes')->nullable(); // teacher fills after meeting
            $table->timestamps();

            $table->unique(['slot_id', 'student_id']); // one booking per slot per student
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ptm_bookings');
        Schema::dropIfExists('ptm_slots');
        Schema::dropIfExists('ptm_sessions');
    }
};
