<?php

namespace App\Models;

use Database\Factories\FundMovementFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundMovement extends Model
{
    /** @use HasFactory<FundMovementFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fund_id',
        'transaction_id',
        'type',
        'amount',
        'note',
        'movement_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'string',
            'movement_date' => 'date',
        ];
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
