<?php

namespace App\Domains\Mentoring;

use App\Domains\Premium\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class MentoringBooking extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'status',
        'starts_at',
        'ends_at',
        'payment_expires_at',
        'subject',
        'description',
        'reschedule_count',
        'meeting_url',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'payment_expires_at' => 'immutable_datetime',
            'reschedule_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphOne<Transaction, $this>
     */
    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }
}
