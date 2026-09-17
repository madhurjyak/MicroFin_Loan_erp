<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepaymentSchedule extends Model
{
    protected $fillable = [
        'loan_id', 'installment_no', 'due_date',
        'principal_due', 'interest_due', 'penal_charges_due', 'penal_gst_due',
        'total_due', 'principal_paid', 'interest_paid', 'penal_paid', 'gst_paid',
        'total_paid', 'opening_balance', 'closing_balance', 'status',
    ];

    protected $casts = [
        'due_date'           => 'date',
        'principal_due'      => 'decimal:2',
        'interest_due'       => 'decimal:2',
        'penal_charges_due'  => 'decimal:2',
        'penal_gst_due'      => 'decimal:2',
        'total_due'          => 'decimal:2',
        'principal_paid'     => 'decimal:2',
        'interest_paid'      => 'decimal:2',
        'penal_paid'         => 'decimal:2',
        'gst_paid'           => 'decimal:2',
        'total_paid'         => 'decimal:2',
        'opening_balance'    => 'decimal:2',
        'closing_balance'    => 'decimal:2',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function collectionTransactions(): HasMany
    {
        return $this->hasMany(CollectionTransaction::class, 'schedule_id');
    }

    public function getBalanceDueAttribute(): float
    {
        return (float) ($this->total_due - $this->total_paid);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date->isPast() && $this->status !== 'paid';
    }
}
