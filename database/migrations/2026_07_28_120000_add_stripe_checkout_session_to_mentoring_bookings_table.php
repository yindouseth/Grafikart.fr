<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentoring_bookings', function (Blueprint $table) {
            $table->string('stripe_checkout_session_id')->nullable()->unique()->after('payment_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('mentoring_bookings', function (Blueprint $table) {
            $table->dropUnique(['stripe_checkout_session_id']);
            $table->dropColumn('stripe_checkout_session_id');
        });
    }
};
