<?php

use App\Domains\Mentoring\MentoringAvailability;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
});

it('displays the weekly availabilities in weekday order', function () {
    MentoringAvailability::query()->create(['weekday' => 3, 'starts_at_minute' => 600, 'ends_at_minute' => 1020]);
    MentoringAvailability::query()->create(['weekday' => 1, 'starts_at_minute' => 840, 'ends_at_minute' => 1080]);
    MentoringAvailability::query()->create(['weekday' => 1, 'starts_at_minute' => 540, 'ends_at_minute' => 720]);

    $this->actingAs($this->user)
        ->get(route('cms.mentoring.availabilities.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('mentoring/availabilities/index')
            ->where('availabilities.0.weekday', 1)
            ->where('availabilities.0.startsAtMinute', 540)
            ->where('availabilities.1.startsAtMinute', 840)
        );
});

it('replaces all weekly availabilities', function () {
    MentoringAvailability::query()->create(['weekday' => 1, 'starts_at_minute' => 600, 'ends_at_minute' => 1020]);

    $this->actingAs($this->user)
        ->put(route('cms.mentoring.availabilities.update'), ['availabilities' => [
            '1-0' => ['weekday' => 2, 'startsAtMinute' => 540, 'endsAtMinute' => 720],
            '1-1' => ['weekday' => 2, 'startsAtMinute' => 840, 'endsAtMinute' => 1080],
        ]])
        ->assertRedirect(route('cms.mentoring.availabilities.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('mentoring_availabilities', ['weekday' => 1]);
    $this->assertDatabaseCount('mentoring_availabilities', 2);
});

it('allows making the entire week unavailable', function () {
    MentoringAvailability::query()->create(['weekday' => 1, 'starts_at_minute' => 600, 'ends_at_minute' => 1020]);

    $this->actingAs($this->user)
        ->put(route('cms.mentoring.availabilities.update'))
        ->assertRedirect(route('cms.mentoring.availabilities.index'));

    $this->assertDatabaseCount('mentoring_availabilities', 0);
});

dataset('invalid_availabilities', [
    'invalid weekday' => [[['weekday' => 0, 'startsAtMinute' => 600, 'endsAtMinute' => 1020]], 'availabilities.0.weekday'],
    'invalid increment' => [[['weekday' => 1, 'startsAtMinute' => 601, 'endsAtMinute' => 1020]], 'availabilities.0.startsAtMinute'],
    'end before start' => [[['weekday' => 1, 'startsAtMinute' => 1020, 'endsAtMinute' => 600]], 'availabilities.0.endsAtMinute'],
]);

it('validates weekly availabilities', function (array $availabilities, string $field) {
    $this->actingAs($this->user)
        ->put(route('cms.mentoring.availabilities.update'), compact('availabilities'))
        ->assertInvalid([$field]);
})->with('invalid_availabilities');

it('denies non administrators', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('cms.mentoring.availabilities.index'))
        ->assertForbidden();
});
