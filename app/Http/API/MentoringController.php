<?php

namespace App\Http\API;

use App\Domains\Mentoring\MentoringService;

class MentoringController
{

    public function availabilities(MentoringService $service): array
    {
        return $service->findAvailabilities();
    }

}
