<?php

namespace App\Domains\Mentoring;

use Illuminate\Database\Eloquent\Model;

class MentoringException extends Model
{
    protected $table = 'mentoring_exceptions';

    public $timestamps = false;

    protected $fillable = [
        'date',
        'starts_at_minute',
        'ends_at_minute',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'starts_at_minute' => 'integer',
            'ends_at_minute' => 'integer',
        ];
    }
}
