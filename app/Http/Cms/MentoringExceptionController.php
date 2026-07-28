<?php

namespace App\Http\Cms;

use App\Domains\Mentoring\MentoringException;
use App\Http\Cms\Data\Mentoring\MentoringExceptionAvailabilityData;
use App\Http\Cms\Data\Mentoring\MentoringExceptionStoreData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MentoringExceptionController
{
    public function store(MentoringExceptionStoreData $data): RedirectResponse
    {
        $availabilities = $data->availabilities->sortBy('startsAtMinute')->values();

        foreach ($availabilities->zip($availabilities->skip(1)) as [$current, $next]) {
            if ($next !== null && $next->startsAtMinute < $current->endsAtMinute) {
                throw ValidationException::withMessages([
                    'availabilities' => ['Les plages horaires ne peuvent pas se chevaucher.'],
                ]);
            }
        }

        DB::transaction(function () use ($data, $availabilities): void {
            MentoringException::query()
                ->where(function ($query) use ($data): void {
                    foreach ($data->dates as $date) {
                        $query->orWhereDate('date', $date);
                    }
                })
                ->delete();

            $exceptions = collect($data->dates)->flatMap(function (string $date) use ($availabilities) {
                if ($availabilities->isEmpty()) {
                    return [[
                        'date' => $date,
                        'starts_at_minute' => null,
                        'ends_at_minute' => null,
                    ]];
                }

                return $availabilities->map(fn (MentoringExceptionAvailabilityData $availability) => [
                    'date' => $date,
                    'starts_at_minute' => $availability->startsAtMinute,
                    'ends_at_minute' => $availability->endsAtMinute,
                ]);
            });

            MentoringException::query()->insert($exceptions->all());
        });

        return to_route('cms.mentoring.availabilities.index')->with('success', 'Les disponibilités spécifiques ont bien été mises à jour');
    }

    public function destroy(string $date): RedirectResponse
    {
        validator(['date' => $date], ['date' => ['date_format:Y-m-d']])->validate();

        MentoringException::query()->whereDate('date', $date)->delete();

        return to_route('cms.mentoring.availabilities.index')->with('success', 'La disponibilité spécifique a bien été supprimée');
    }
}
