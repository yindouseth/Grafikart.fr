<?php

use App\Domains\Mentoring\MentoringException;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
});

it('displays exceptions grouped by date', function () {
    MentoringException::query()->create(['date' => '2026-07-29', 'starts_at_minute' => 900, 'ends_at_minute' => 1020]);
    MentoringException::query()->create(['date' => '2026-07-29', 'starts_at_minute' => 600, 'ends_at_minute' => 720]);
    MentoringException::query()->create(['date' => '2026-07-28', 'starts_at_minute' => null, 'ends_at_minute' => null]);

    $this->actingAs($this->user)
        ->get(route('cms.mentoring.availabilities.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('exceptions.0.date', '2026-07-28')
            ->where('exceptions.0.availabilities', [])
            ->where('exceptions.1.date', '2026-07-29')
            ->where('exceptions.1.availabilities.0.startsAtMinute', 600)
            ->where('exceptions.1.availabilities.1.startsAtMinute', 900)
        );
});

it('creates the same specific hours for each selected date', function () {
    $this->actingAs($this->user)
        ->post(route('cms.mentoring.availabilities.exceptions.store'), [
            'dates' => ['2026-07-28', '2026-07-29'],
            'availabilities' => [
                ['startsAtMinute' => 600, 'endsAtMinute' => 720],
                ['startsAtMinute' => 900, 'endsAtMinute' => 1020],
            ],
        ])
        ->assertRedirect(route('cms.mentoring.availabilities.index'));

    $this->assertDatabaseCount('mentoring_exceptions', 4);
    $this->assertDatabaseHas('mentoring_exceptions', ['date' => '2026-07-28', 'starts_at_minute' => 600, 'ends_at_minute' => 720]);
    $this->assertDatabaseHas('mentoring_exceptions', ['date' => '2026-07-29', 'starts_at_minute' => 900, 'ends_at_minute' => 1020]);
});

it('replaces a date with an all-day unavailability', function () {
    MentoringException::query()->create(['date' => '2026-07-28', 'starts_at_minute' => 600, 'ends_at_minute' => 720]);

    $this->actingAs($this->user)
        ->post(route('cms.mentoring.availabilities.exceptions.store'), [
            'dates' => ['2026-07-28'],
        ])
        ->assertRedirect(route('cms.mentoring.availabilities.index'));

    $this->assertDatabaseCount('mentoring_exceptions', 1);
    $this->assertDatabaseHas('mentoring_exceptions', ['date' => '2026-07-28', 'starts_at_minute' => null, 'ends_at_minute' => null]);
});

it('rejects overlapping specific hours', function () {
    $this->actingAs($this->user)
        ->post(route('cms.mentoring.availabilities.exceptions.store'), [
            'dates' => ['2026-07-28'],
            'availabilities' => [
                ['startsAtMinute' => 600, 'endsAtMinute' => 720],
                ['startsAtMinute' => 690, 'endsAtMinute' => 780],
            ],
        ])
        ->assertInvalid('availabilities');
});

it('deletes all specific hours for a date', function () {
    MentoringException::query()->create(['date' => '2026-07-28', 'starts_at_minute' => 600, 'ends_at_minute' => 720]);
    MentoringException::query()->create(['date' => '2026-07-28', 'starts_at_minute' => 900, 'ends_at_minute' => 1020]);

    $this->actingAs($this->user)
        ->delete(route('cms.mentoring.availabilities.exceptions.destroy', '2026-07-28'))
        ->assertRedirect(route('cms.mentoring.availabilities.index'));

    $this->assertDatabaseMissing('mentoring_exceptions', ['date' => '2026-07-28']);
});

it('denies non administrators', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('cms.mentoring.availabilities.exceptions.store'), ['dates' => ['2026-07-28']])
        ->assertForbidden();
});
