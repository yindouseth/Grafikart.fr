<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentoring_availabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday');
            $table->unsignedSmallInteger('starts_at_minute');
            $table->unsignedSmallInteger('ends_at_minute');
            $table->index('weekday');
        });

        Schema::create('mentoring_exceptions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedSmallInteger('starts_at_minute')->nullable();
            $table->unsignedSmallInteger('ends_at_minute')->nullable();
            $table->index('date');
        });

        Schema::create('mentoring_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\User::class)->constrained();
            $table->string('status');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('payment_expires_at')->nullable();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('reschedule_count')->default(0);
            $table->string('meeting_url')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentoring_availabilities');
        Schema::dropIfExists('mentoring_exceptions');
        Schema::dropIfExists('mentoring_bookings');
    }
};
