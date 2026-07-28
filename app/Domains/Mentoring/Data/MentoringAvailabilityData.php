<?php

namespace App\Domains\Mentoring\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MentoringAvailabilityData extends Data
{

    public function __construct(
        // Date formatted using YYYY-MM-DD
        public string $date,
        // @var DateTime[]
        public array $startTimes,
    )
    {

    }

}
