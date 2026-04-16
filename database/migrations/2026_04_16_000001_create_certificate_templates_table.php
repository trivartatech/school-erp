<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->enum('orientation', ['landscape', 'portrait'])->default('landscape');
            $table->json('background');    // { front: { type: 'color|image', value: '...' } }
            $table->json('elements');      // array of element objects with x/y/w/h/type/style
            $table->json('custom_vars')->nullable(); // [{ key, label, placeholder }]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};
