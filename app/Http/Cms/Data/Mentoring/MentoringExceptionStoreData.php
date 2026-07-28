<?php

namespace App\Http\Cms\Data\Mentoring;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

final class MentoringExceptionStoreData extends Data
{
    /** @param Collection<int, MentoringExceptionAvailabilityData> $availabilities */
    public function __construct(
        public array $dates,
        #[DataCollectionOf(MentoringExceptionAvailabilityData::class)]
        public Collection $availabilities = new Collection,
    ) {}

    public static function rules(): array
    {
        return [
            'dates' => ['required', 'array', 'min:1'],
            'dates.*' => ['date_format:Y-m-d', 'distinct'],
            'availabilities' => ['nullable', 'array'],
        ];
    }
}
