<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingsSchedule extends Model
{
    protected $fillable = [
        'savings_account_id', 'installment_no', 'due_date',
        'amount_expected', 'amount_collected', 'interest_accrued',
        'status', 'collection_date',
    ];

    protected $casts = [
        'amount_expected'  => 'decimal:2',
        'amount_collected' => 'decimal:2',
        'interest_accrued' => 'decimal:2',
        'due_date'         => 'date',
        'collection_date'  => 'date',
    ];

    public function savingsAccount(): BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class);
    }

    /**
     * Balance due on this installment.
     */
    public function getBalanceDueAttribute(): float
    {
        return max(0, (float)$this->amount_expected - (float)$this->amount_collected);
    }
}
