<?php

namespace App\Domains\Mentoring;

use Illuminate\Database\Eloquent\Model;

class MentoringAvailability extends Model
{
    protected $table = 'mentoring_availabilities';

    public $timestamps = false;

    protected $fillable = [
        'weekday',
        'starts_at_minute',
        'ends_at_minute',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'starts_at_minute' => 'integer',
            'ends_at_minute' => 'integer',
        ];
    }
}
