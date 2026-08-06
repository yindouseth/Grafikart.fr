<?php

use App\Domains\Mentoring\MentoringBooking;
use App\Infrastructure\Payment\Stripe\StripeApi;
use App\Models\User;

test('it releases expired payment holds and expires their Stripe Checkout', function () {
    $booking = MentoringBooking::query()->create([
        'user_id' => User::factory()->create()->id,
        'status' => 'pending_payment',
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
        'payment_expires_at' => now()->subMinute(),
        'stripe_checkout_session_id' => 'cs_expired_123',
        'subject' => 'Sujet',
        'meeting_url' => 'https://meet.example.test/expired-booking',
    ]);

    $this->mock(StripeApi::class)
        ->shouldReceive('expireCheckoutSession')
        ->once()
        ->with('cs_expired_123');

    $this->artisan('mentoring:expire-bookings')->assertSuccessful();

    expect(MentoringBooking::find($booking->id))->toBeNull();
});
