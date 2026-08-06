<?php

use App\Domains\Mentoring\MentoringAvailability;
use App\Domains\Mentoring\MentoringBooking;
use App\Infrastructure\Payment\Stripe\StripeApi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Stripe\Checkout\Session;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-07-28 10:00:00 Europe/Paris');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('it reserves a slot then returns the Stripe Checkout URL', function () {
    $user = User::factory()->create();
    MentoringAvailability::query()->create([
        'weekday' => 3,
        'starts_at_minute' => 10 * 60,
        'ends_at_minute' => 12 * 60,
    ]);
    $session = new Session('cs_mentoring_123');
    $session->url = 'https://checkout.stripe.com/c/pay/cs_mentoring_123';

    $this->mock(StripeApi::class)
        ->shouldReceive('createCustomer')->once()->andReturn($user)
        ->shouldReceive('createMentoringSession')->once()->andReturn($session);

    $this->actingAs($user)
        ->postJson('/api/mentoring/bookings', [
            'slot' => '2026-07-29T08:00:00+00:00',
            'subject' => 'Architecture frontend',
            'description' => 'Préparer une migration.',
        ])
        ->assertOk()
        ->assertJson(['url' => $session->url]);

    $booking = MentoringBooking::firstOrFail();
    expect($booking->status)->toBe('pending_payment')
        ->and($booking->stripe_checkout_session_id)->toBe('cs_mentoring_123')
        ->and($booking->payment_expires_at->toDateTimeString())->toBe('2026-07-28 10:30:00');
});

test('it releases the slot if Stripe Checkout cannot be created', function () {
    $user = User::factory()->create();
    MentoringAvailability::query()->create(['weekday' => 3, 'starts_at_minute' => 600, 'ends_at_minute' => 720]);
    $this->mock(StripeApi::class)
        ->shouldReceive('createCustomer')->once()->andThrow(new Exception('Stripe unavailable'));

    $this->actingAs($user)
        ->postJson('/api/mentoring/bookings', [
            'slot' => '2026-07-29T08:00:00+00:00',
            'subject' => 'Architecture frontend',
        ])
        ->assertUnprocessable();

    expect(MentoringBooking::count())->toBe(0);
});
