<?php

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'workspace_id',
        'recurring_template_id',
        'type',
        'concept',
        'amount',
        'currency',
        'exchange_rate',
        'category',
        'projected_date',
        'confirmed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'string',
            'exchange_rate' => 'string',
            'projected_date' => 'date',
            'confirmed_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function recurringTemplate(): BelongsTo
    {
        return $this->belongsTo(RecurringTemplate::class);
    }

    public function debtInstallment(): HasOne
    {
        return $this->hasOne(DebtInstallment::class);
    }

    public function fundMovement(): HasOne
    {
        return $this->hasOne(FundMovement::class);
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function isProjected(): bool
    {
        return $this->confirmed_at === null;
    }
}
