<?php

namespace App\Models;

use Database\Factories\DebtInstallmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtInstallment extends Model
{
    /** @use HasFactory<DebtInstallmentFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'debt_id',
        'installment_number',
        'amount',
        'due_date',
        'paid_at',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'string',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'installment_number' => 'integer',
        ];
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }
}
