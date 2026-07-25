<?php

namespace App\Http\Cms;

use App\Domains\Mentoring\MentoringAvailability;
use App\Http\Cms\Data\Mentoring\MentoringAvailabilityData;
use App\Http\Cms\Data\Mentoring\MentoringAvailabilityUpdateData;
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
