<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionTransaction extends Model
{
    protected $fillable = [
        'loan_id', 'schedule_id',
        'savings_account_id', 'savings_schedule_id',
        'transaction_type',
        'receipt_no', 'amount_collected',
        'collection_date', 'collected_by', 'payment_mode',
        'peer_payer_customer_id', 'remarks',
    ];

    protected $casts = [
        'amount_collected' => 'decimal:2',
        'collection_date'  => 'date',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(RepaymentSchedule::class, 'schedule_id');
    }

    public function savingsAccount(): BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class);
    }

    public function savingsSchedule(): BelongsTo
    {
        return $this->belongsTo(SavingsSchedule::class);
    }

    public function peerPayer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'peer_payer_customer_id');
    }
}
