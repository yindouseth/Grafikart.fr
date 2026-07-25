<?php

namespace App\Domains\Premium\Models;

use App\Domains\Premium\Factory\TransactionFactory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'refunded_at' => 'immutable_datetime',
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
     * @return MorphTo<Model, $this>
     */
    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isRefunded(): bool
    {
        return $this->refunded_at !== null;
    }

    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }
}
