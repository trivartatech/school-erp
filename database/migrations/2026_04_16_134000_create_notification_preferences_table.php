<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();

            // Which notification event
            $table->string('event_type', 60);
            // Which channel: push, email, sms, whatsapp
            $table->string('channel', 20);
            // Enabled or muted
            $table->boolean('enabled')->default(true);

            $table->timestamps();
            $table->unique(['user_id', 'event_type', 'channel'], 'notif_pref_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
