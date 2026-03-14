<?php

namespace App\Models;

use Database\Factories\FundFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fund extends Model
{
    /** @use HasFactory<FundFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'workspace_id',
        'name',
        'description',
        'target_amount',
        'current_balance',
        'currency',
        'target_date',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'string',
            'current_balance' => 'string',
            'target_date' => 'date',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(FundMovement::class);
    }

    public function progressPercentage(): ?string
    {
        if (! $this->target_amount || $this->target_amount === '0.0000') {
            return null;
        }

        $percentage = bcdiv(bcmul($this->current_balance, '100', 4), $this->target_amount, 4);

        return min((float) $percentage, 100) . '.0000';
    }
}
