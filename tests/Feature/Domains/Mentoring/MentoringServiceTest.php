<?php

use App\Domains\Mentoring\MentoringAvailability;
use App\Domains\Mentoring\MentoringBooking;
use App\Domains\Mentoring\MentoringException;
use App\Domains\Mentoring\MentoringService;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-07-28 10:00:00 Europe/Paris');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('it generates quarter-hour slots inside recurring Paris availability', function () {
    MentoringAvailability::query()->create([
        'weekday' => 3,
        'starts_at_minute' => 10 * 60,
        'ends_at_minute' => 12 * 60,
    ]);

    $availabilities = (new MentoringService)->findAvailabilities();

    expect($availabilities)->toHaveCount(6)
        ->and($availabilities[0]->date)->toBe('2026-07-29')
        ->and(array_map(fn (CarbonImmutable $time) => $time->toIso8601String(), $availabilities[0]->startTimes))
        ->toBe([
            '2026-07-29T08:00:00+00:00',
            '2026-07-29T08:15:00+00:00',
            '2026-07-29T08:30:00+00:00',
            '2026-07-29T08:45:00+00:00',
            '2026-07-29T09:00:00+00:00',
        ]);
});

test('it applies the notice and horizon to individual slots', function () {
    MentoringAvailability::query()->insert([
        ['weekday' => 3, 'starts_at_minute' => 9 * 60, 'ends_at_minute' => 12 * 60],
        ['weekday' => 7, 'starts_at_minute' => 9 * 60, 'ends_at_minute' => 12 * 60],
    ]);

    $availabilities = (new MentoringService)->findAvailabilities();

    expect($availabilities[0]->date)->toBe('2026-07-29')
        ->and($availabilities[0]->startTimes)->toHaveCount(5)
        ->and($availabilities[0]->startTimes[0]->toIso8601String())->toBe('2026-07-29T08:00:00+00:00')
        ->and(last($availabilities)->date)->toBe('2026-09-06')
        ->and(last($availabilities)->startTimes)->toHaveCount(5);
});

test('it lets a dated exception replace recurring availability, including a full closure', function () {
    MentoringAvailability::query()->create(['weekday' => 3, 'starts_at_minute' => 10 * 60, 'ends_at_minute' => 12 * 60]);
    MentoringException::query()->insert([
        ['date' => '2026-07-29', 'starts_at_minute' => 14 * 60, 'ends_at_minute' => 15 * 60],
        ['date' => '2026-08-05', 'starts_at_minute' => null, 'ends_at_minute' => null],
    ]);

    $availabilities = (new MentoringService)->findAvailabilities();

    expect($availabilities[0]->date)->toBe('2026-07-29')
        ->and($availabilities[0]->startTimes)->toHaveCount(1)
        ->and($availabilities[0]->startTimes[0]->toIso8601String())->toBe('2026-07-29T12:00:00+00:00')
        ->and(collect($availabilities)->pluck('date'))->not->toContain('2026-08-05');
});

test('it removes slots overlapping active bookings and their buffer', function () {
    MentoringAvailability::query()->create(['weekday' => 3, 'starts_at_minute' => 10 * 60, 'ends_at_minute' => 13 * 60]);
    $user = User::factory()->create();
    MentoringBooking::query()->create([
        'user_id' => $user->id,
        'status' => 'scheduled',
        'starts_at' => '2026-07-29 10:30:00',
        'ends_at' => '2026-07-29 11:30:00',
        'subject' => 'Sujet',
        'meeting_url' => 'https://meet.example.test/booking',
    ]);

    $startTimes = (new MentoringService)->findAvailabilities()[0]->startTimes;

    expect(array_map(fn (CarbonImmutable $time) => $time->toIso8601String(), $startTimes))
        ->toBe([
            '2026-07-29T09:45:00+00:00',
            '2026-07-29T10:00:00+00:00',
        ]);
});
