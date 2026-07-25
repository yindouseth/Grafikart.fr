<?php

namespace App\Http\Cms\Data\Mentoring;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

final class MentoringAvailabilityUpdateData extends Data
{
    /**
     * @param  Collection<int, MentoringAvailabilityData>  $availabilities
     */
    public function __construct(
        #[DataCollectionOf(MentoringAvailabilityData::class)]
        public Collection $availabilities = new Collection,
    ) {}

    public static function rules(): array
    {
        return [
            'availabilities' => ['nullable', 'array'],
        ];
    }
}
