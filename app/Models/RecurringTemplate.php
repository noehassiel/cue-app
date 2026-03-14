<?php

namespace App\Models;

use Database\Factories\RecurringTemplateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringTemplate extends Model
{
    /** @use HasFactory<RecurringTemplateFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'workspace_id',
        'concept',
        'type',
        'amount',
        'currency',
        'category',
        'frequency',
        'frequency_day',
        'next_date',
        'generate_ahead_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'string',
            'next_date' => 'date',
            'generate_ahead_days' => 'integer',
            'frequency_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
