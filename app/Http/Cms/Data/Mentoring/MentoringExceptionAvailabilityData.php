<?php

namespace App\Http\Cms\Data\Mentoring;

use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\LessThan;
use Spatie\LaravelData\Attributes\Validation\MultipleOf;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class MentoringExceptionAvailabilityData extends Data
{
    public function __construct(
        #[LessThan('endsAtMinute'), Required, IntegerType, Between(min: 0, max: 1425), MultipleOf(15)]
        public int $startsAtMinute,
        #[GreaterThan('startsAtMinute'), Required, IntegerType, Between(min: 15, max: 1440), MultipleOf(15)]
        public int $endsAtMinute,
    ) {}
}
