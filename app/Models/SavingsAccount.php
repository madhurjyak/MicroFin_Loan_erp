<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsAccount extends Model
{
    protected $fillable = [
        'customer_id', 'account_no', 'account_type', 'deposit_amount',
        'interest_rate', 'tenure', 'frequency', 'opening_date',
        'maturity_date', 'status', 'total_principal_collected',
        'total_interest_accrued', 'maturity_amount', 'remarks',
    ];

    protected $casts = [
        'deposit_amount'           => 'decimal:2',
        'interest_rate'            => 'decimal:2',
        'total_principal_collected'=> 'decimal:2',
        'total_interest_accrued'   => 'decimal:2',
        'maturity_amount'          => 'decimal:2',
        'opening_date'             => 'date',
        'maturity_date'            => 'date',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(SavingsSchedule::class)->orderBy('installment_no');
    }

    public function collectionTransactions(): HasMany
    {
        return $this->hasMany(CollectionTransaction::class);
    }

    // ── Computed Attributes ──────────────────────────────────────────────────

    /**
     * Count of missed installments.
     */
    public function getMissedInstallmentsCountAttribute(): int
    {
        return $this->schedules()->where('status', 'missed')->count();
    }

    /**
     * Count of paid installments.
     */
    public function getPaidInstallmentsCountAttribute(): int
    {
        return $this->schedules()->where('status', 'paid')->count();
    }

    /**
     * Next pending installment.
     */
    public function getNextDueScheduleAttribute(): ?SavingsSchedule
    {
        return $this->schedules()
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('installment_no')
            ->first();
    }

    /**
     * Total expected deposits (all installments × amount_expected).
     */
    public function getTotalExpectedAttribute(): float
    {
        return (float) $this->schedules()->sum('amount_expected');
    }

    /**
     * Collection percentage.
     */
    public function getCollectionPctAttribute(): float
    {
        $expected = $this->total_expected;
        if ($expected <= 0) return 0;
        return round(($this->total_principal_collected / $expected) * 100, 2);
    }
}
