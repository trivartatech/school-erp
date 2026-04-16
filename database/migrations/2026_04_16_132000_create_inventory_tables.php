<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Asset categories
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'name']);
        });

        // Asset register
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('asset_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('asset_code')->nullable();
            $table->string('brand')->nullable();
            $table->string('model_no')->nullable();
            $table->string('serial_no')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 12, 2)->default(0);
            $table->string('supplier')->nullable();
            $table->string('warranty_until')->nullable();
            $table->integer('useful_life_years')->default(5)->comment('For depreciation');
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor', 'condemned'])->default('good');
            $table->enum('status', ['available', 'assigned', 'under_maintenance', 'disposed'])->default('available');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['school_id', 'status']);
            $table->unique(['school_id', 'asset_code'], 'assets_school_code_unique');
        });

        // Assignment tracking (who/where it's assigned)
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('assignee_type')->nullable()->comment('staff, classroom, department');
            $table->unsignedBigInteger('assignee_id')->nullable()->comment('FK to staff/classroom etc.');
            $table->string('location')->nullable()->comment('Room/block description');
            $table->date('assigned_on');
            $table->date('returned_on')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['asset_id', 'returned_on']);
        });

        // Maintenance log
        Schema::create('asset_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->date('reported_on');
            $table->string('issue_description');
            $table->enum('type', ['preventive', 'corrective', 'inspection'])->default('corrective');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'scrapped'])->default('open');
            $table->decimal('cost', 10, 2)->default(0);
            $table->date('resolved_on')->nullable();
            $table->string('vendor')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance');
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
    }
};
