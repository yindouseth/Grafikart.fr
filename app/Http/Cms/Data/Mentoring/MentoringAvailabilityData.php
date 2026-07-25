<?php

namespace App\Http\Cms\Data\Mentoring;

use App\Domains\Mentoring\MentoringAvailability;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\LessThan;
use Spatie\LaravelData\Attributes\Validation\MultipleOf;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class MentoringAvailabilityData extends Data
{
    public function __construct(
        #[Required, IntegerType, Between(min: 1, max: 7)]
        public int $weekday,
        #[LessThan('endsAtMinute'), Required, IntegerType, Between(min: 0, max: 1425), MultipleOf(15)]
        public int $startsAtMinute,
        #[GreaterThan('startsAtMinute'), Required, IntegerType, Between(min: 15, max: 1440), MultipleOf(15)]
        public int $endsAtMinute,
    ) {}

    public static function fromModel(MentoringAvailability $availability): self
    {
        return new self(
            weekday: $availability->weekday,
            startsAtMinute: $availability->starts_at_minute,
            endsAtMinute: $availability->ends_at_minute,
        );
    }

    /** @return array{weekday: int, starts_at_minute: int, ends_at_minute: int} */
    public function toDatabaseAttributes(): array
    {
        return [
            'weekday' => $this->weekday,
            'starts_at_minute' => $this->startsAtMinute,
            'ends_at_minute' => $this->endsAtMinute,
        ];
    }
}
