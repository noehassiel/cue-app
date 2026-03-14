<?php

namespace App\Models;

use Database\Factories\DebtFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debt extends Model
{
    /** @use HasFactory<DebtFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'workspace_id',
        'name',
        'total_amount',
        'installment_amount',
        'total_installments',
        'paid_installments',
        'currency',
        'start_date',
        'payment_day',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'string',
            'installment_amount' => 'string',
            'total_installments' => 'integer',
            'paid_installments' => 'integer',
            'payment_day' => 'integer',
            'start_date' => 'date',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(DebtInstallment::class);
    }

    public function remainingInstallments(): int
    {
        return $this->total_installments - $this->paid_installments;
    }

    public function remainingAmount(): string
    {
        return bcmul($this->installment_amount, (string) $this->remainingInstallments(), 4);
    }

    public function nextInstallment(): ?DebtInstallment
    {
        return $this->installments()
            ->whereNull('paid_at')
            ->orderBy('due_date')
            ->first();
    }
}
