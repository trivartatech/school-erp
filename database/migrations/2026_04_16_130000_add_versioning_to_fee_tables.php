<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add versioning columns to fee_structures
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->date('effective_from')->nullable()->after('due_date');
            $table->date('effective_to')->nullable()->after('effective_from');
            $table->unsignedBigInteger('superseded_by')->nullable()->after('effective_to');
            $table->text('change_reason')->nullable()->after('superseded_by');
        });

        // Link fee_payments to exact fee_structure version used at time of payment
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('fee_structure_id')->nullable()->after('fee_head_id');
            $table->json('fee_structure_snapshot')->nullable()->after('fee_structure_id')
                ->comment('Snapshot of fee structure at time of payment for audit trail');
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropColumn(['effective_from', 'effective_to', 'superseded_by', 'change_reason']);
        });

        Schema::table('fee_payments', function (Blueprint $table) {
            $table->dropColumn(['fee_structure_id', 'fee_structure_snapshot']);
        });
    }
};
