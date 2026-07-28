<?php

namespace App\Http\Cms;

use App\Domains\Mentoring\MentoringAvailability;
use App\Domains\Mentoring\MentoringException;
use App\Http\Cms\Data\Mentoring\MentoringAvailabilityData;
use App\Http\Cms\Data\Mentoring\MentoringAvailabilityUpdateData;
use App\Http\Cms\Data\Mentoring\MentoringExceptionAvailabilityData;
use App\Http\Cms\Data\Mentoring\MentoringExceptionData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class MentoringAvailabilityController
{
    public function index(): Response
    {
        return Inertia::render('mentoring/availabilities/index', [
            'availabilities' => MentoringAvailability::query()
                ->orderBy('weekday')
                ->orderBy('starts_at_minute')
                ->get()
                ->map(fn (MentoringAvailability $availability) => MentoringAvailabilityData::fromModel($availability))
                ->values(),
            'exceptions' => MentoringException::query()
                ->orderBy('date')
                ->orderBy('starts_at_minute')
                ->get()
                ->groupBy(fn (MentoringException $exception) => $exception->date->toDateString())
                ->map(fn ($exceptions, string $date) => new MentoringExceptionData(
                    date: $date,
                    availabilities: $exceptions
                        ->filter(fn (MentoringException $exception) => $exception->starts_at_minute !== null)
                        ->map(fn (MentoringException $exception) => new MentoringExceptionAvailabilityData(
                            startsAtMinute: $exception->starts_at_minute,
                            endsAtMinute: $exception->ends_at_minute,
                        ))
                        ->values()
                        ->all(),
                ))
                ->values(),
        ]);
    }

    public function update(MentoringAvailabilityUpdateData $data): RedirectResponse
    {
        DB::transaction(function () use ($data): void {
            MentoringAvailability::query()->truncate();
            MentoringAvailability::query()->insert(
                $data->availabilities->map(fn (MentoringAvailabilityData $availability) => $availability->toDatabaseAttributes())->all(),
            );
        });

        return to_route('cms.mentoring.availabilities.index')->with('success', 'Les disponibilités ont bien été mises à jour');
    }
}
