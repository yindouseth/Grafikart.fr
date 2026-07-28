<?php

namespace App\Http\Cms\Data\Mentoring;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class MentoringExceptionData extends Data
{
    public function __construct(
        public string $date,
        /** @var MentoringExceptionAvailabilityData[] */
        public array $availabilities,
    ) {}
}
