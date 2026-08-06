<?php

namespace App\Domains\Mentoring;

use App\Domains\Mentoring\Data\MentoringAvailabilityData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MentoringService
{
    private const TIMEZONE = 'Europe/Paris';

    public const SESSION_DURATION_MINUTES = 60;

    public const PAYMENT_TIMEOUT_MINUTES = 30;

    private const BUFFER_DURATION_MINUTES = 15;

    private const BOOKING_NOTICE_HOURS = 24;

    private const BOOKING_HORIZON_DAYS = 40;

    /**
     * Find the list of date available for a mentoring session
     *
     * Start times are UTC instants. Their local placement is calculated in the
     * mentor's timezone so that recurring rules continue to follow DST changes.
     *
     * @return MentoringAvailabilityData[]
     */
    public function findAvailabilities(): array
    {
        $now = CarbonImmutable::now(self::TIMEZONE);
        $firstBookableAt = $now->addHours(self::BOOKING_NOTICE_HOURS)->utc();
        $lastBookableAt = $now->addDays(self::BOOKING_HORIZON_DAYS)->utc();
        $firstDate = $now->startOfDay();
        $lastDate = $lastBookableAt->setTimezone(self::TIMEZONE)->startOfDay();

        // Load each input once. Grouping them up front keeps the per-day loop
        // in memory and avoids an N+1 query over the forty-day horizon.
        $weeklyAvailabilities = MentoringAvailability::query()
            ->orderBy('starts_at_minute')
            ->get()
            ->groupBy('weekday');
        $exceptions = MentoringException::query()
            ->whereBetween('date', [$firstDate->toDateString(), $lastDate->toDateString()])
            ->orderBy('starts_at_minute')
            ->get()
            ->groupBy(fn (MentoringException $exception) => $exception->date->toDateString());
        $bookings = MentoringBooking::query()
            ->whereIn('status', ['pending_payment', 'scheduled', 'PendingPayment', 'Scheduled'])
            ->whereNotNull('starts_at')
            ->whereNotNull('ends_at')
            ->where('starts_at', '<', $lastBookableAt)
            ->where('ends_at', '>', $firstBookableAt->subMinutes(self::BUFFER_DURATION_MINUTES))
            ->get(['starts_at', 'ends_at']);

        $availabilities = [];
        for ($date = $firstDate; $date->lessThanOrEqualTo($lastDate); $date = $date->addDay()) {
            $dateKey = $date->toDateString();

            // An exception is a complete replacement, including an exception
            // whose null range explicitly closes the whole day.
            $ranges = $exceptions->has($dateKey)
                ? $exceptions->get($dateKey)
                : $weeklyAvailabilities->get($date->dayOfWeekIso, collect());

            $startTimes = $this->findStartTimes($date, $ranges, $bookings, $firstBookableAt, $lastBookableAt);

            if ($startTimes !== []) {
                $availabilities[] = new MentoringAvailabilityData($dateKey, $startTimes);
            }
        }

        return $availabilities;
    }

    /**
     * Temporarily holds a slot. The overlap query is executed under a database
     * lock so two concurrent Checkout requests cannot reserve the same slot.
     */
    public function reserve(int $userId, CarbonImmutable $startsAt, string $subject, ?string $description): MentoringBooking
    {
        if (! $this->isAvailable($startsAt)) {
            throw ValidationException::withMessages(['slot' => 'Ce créneau n’est plus disponible.']);
        }

        return DB::transaction(function () use ($userId, $startsAt, $subject, $description) {
            $endsAt = $startsAt->addMinutes(self::SESSION_DURATION_MINUTES);
            $hasConflict = MentoringBooking::query()
                ->where(function ($query) {
                    $query->where('status', 'scheduled')
                        ->orWhere(function ($query) {
                            $query->where('status', 'pending_payment')
                                ->where('payment_expires_at', '>', now());
                        });
                })
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt->subMinutes(self::BUFFER_DURATION_MINUTES))
                ->lockForUpdate()
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages(['slot' => 'Ce créneau vient d’être réservé.']);
            }

            return MentoringBooking::query()->create([
                'user_id' => $userId,
                'status' => 'pending_payment',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'payment_expires_at' => now()->addMinutes(self::PAYMENT_TIMEOUT_MINUTES),
                'subject' => $subject,
                'description' => $description,
                'meeting_url' => 'https://meet.grafikart.fr/mentoring/'.str()->uuid(),
            ]);
        });
    }

    private function isAvailable(CarbonImmutable $startsAt): bool
    {
        foreach ($this->findAvailabilities() as $availability) {
            foreach ($availability->startTimes as $availableStartAt) {
                if ($availableStartAt->equalTo($startsAt)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generates the eligible starts for one local calendar date.
     *
     * It expands each effective availability range on the quarter-hour grid,
     * then removes starts outside the booking window and those colliding with
     * an active booking.
     *
     * @param  Collection<int, MentoringAvailability|MentoringException>  $ranges
     * @param  Collection<int, MentoringBooking>  $bookings
     * @return CarbonImmutable[]
     */
    private function findStartTimes(
        CarbonImmutable $date,
        Collection $ranges,
        Collection $bookings,
        CarbonImmutable $firstBookableAt,
        CarbonImmutable $lastBookableAt,
    ): array {
        $startTimes = [];

        foreach ($ranges as $range) {
            if ($range->starts_at_minute === null || $range->ends_at_minute === null) {
                continue;
            }

            $rangeStart = $date->addMinutes($range->starts_at_minute);
            $lastStart = $date->addMinutes($range->ends_at_minute - self::SESSION_DURATION_MINUTES);

            // Incrementing in local time preserves the quarter-hour grid in
            // Paris, while the returned value is the UTC instant stored by the
            // booking model.
            for ($start = $rangeStart; $start->lessThanOrEqualTo($lastStart); $start = $start->addMinutes(15)) {
                $startAt = $start->utc();
                $endsAt = $startAt->addMinutes(self::SESSION_DURATION_MINUTES);

                if ($startAt->lessThan($firstBookableAt) || $startAt->greaterThan($lastBookableAt)) {
                    continue;
                }

                if (! $this->overlapsBooking($startAt, $endsAt, $bookings)) {
                    $startTimes[] = $startAt;
                }
            }
        }

        return $startTimes;
    }

    /**
     * Determines whether a candidate session intersects an active booking.
     *
     * The blocked interval includes the booked session plus its fifteen-minute
     * buffer, so partial overlaps are rejected as well as identical starts.
     *
     * @param  Collection<int, MentoringBooking>  $bookings
     */
    private function overlapsBooking(CarbonImmutable $startAt, CarbonImmutable $endsAt, Collection $bookings): bool
    {
        foreach ($bookings as $booking) {
            $blockedUntil = $booking->ends_at->addMinutes(self::BUFFER_DURATION_MINUTES);

            if ($startAt->lessThan($blockedUntil) && $endsAt->greaterThan($booking->starts_at)) {
                return true;
            }
        }

        return false;
    }
}
