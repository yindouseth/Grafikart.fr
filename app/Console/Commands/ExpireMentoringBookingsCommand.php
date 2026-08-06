<?php

namespace App\Console\Commands;

use App\Domains\Mentoring\MentoringBooking;
use App\Infrastructure\Payment\Stripe\StripeApi;
use Illuminate\Console\Command;

class ExpireMentoringBookingsCommand extends Command
{
    protected $signature = 'mentoring:expire-bookings';

    protected $description = 'Libère les créneaux de mentorat dont le paiement a expiré';

    public function handle(StripeApi $stripe): int
    {
        $bookings = MentoringBooking::query()
            ->where('status', 'pending_payment')
            ->where('payment_expires_at', '<=', now())
            ->get();

        foreach ($bookings as $booking) {
            try {
                if ($booking->stripe_checkout_session_id !== null) {
                    $stripe->expireCheckoutSession($booking->stripe_checkout_session_id);
                }
            } catch (\Throwable) {
                // Stripe has the same expires_at timestamp. Deleting locally is
                // still required to make the slot immediately selectable again.
            } finally {
                $booking->delete();
            }
        }

        $this->info("{$bookings->count()} créneau(x) libéré(s)");

        return self::SUCCESS;
    }
}
