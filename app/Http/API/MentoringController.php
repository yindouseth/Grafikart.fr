<?php

namespace App\Http\API;

use App\Domains\Mentoring\MentoringService;
use App\Infrastructure\Payment\Stripe\StripeApi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MentoringController
{
    public function availabilities(MentoringService $service): array
    {
        return $service->findAvailabilities();
    }

    public function store(Request $request, MentoringService $service, StripeApi $stripe): JsonResponse
    {
        $data = $request->validate([
            'slot' => ['required', 'date'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
        $user = $request->user();
        assert($user instanceof User);

        $booking = $service->reserve(
            $user->id,
            CarbonImmutable::parse($data['slot'])->utc(),
            $data['subject'],
            $data['description'] ?? null,
        );

        $session = null;
        try {
            $stripe->createCustomer($user);
            $session = $stripe->createMentoringSession($user, $booking, route('pages.mentoring', absolute: true));
            $booking->update(['stripe_checkout_session_id' => $session->id]);

            return response()->json(['url' => $session->url]);
        } catch (\Throwable) {
            if ($session !== null) {
                try {
                    $stripe->expireCheckoutSession($session->id);
                } catch (\Throwable) {
                    // The Checkout itself is still capped at the same 30-minute expiry.
                }
            }
            $booking->delete();

            return response()->json(['message' => 'Impossible de contacter l’API Stripe.'], 422);
        }
    }
}
