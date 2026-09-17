<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Loan extends Model
{
    protected $fillable = [
        'customer_id', 'loan_application_id', 'loan_account_no', 'principal_amount',
        'annual_interest_rate', 'tenure', 'repayment_frequency',
        'interest_type', 'disbursement_date', 'maturity_date',
        'status', 'disbursement_mode', 'processing_fee',
        'processing_fee_gst', 'purpose',
    ];

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    protected $casts = [
        'principal_amount'   => 'decimal:2',
        'annual_interest_rate' => 'decimal:2',
        'processing_fee'     => 'decimal:2',
        'processing_fee_gst' => 'decimal:2',
        'disbursement_date'  => 'date',
        'maturity_date'      => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function repaymentSchedules(): HasMany
    {
        return $this->hasMany(RepaymentSchedule::class)->orderBy('installment_no');
    }

    public function collectionTransactions(): HasMany
    {
        return $this->hasMany(CollectionTransaction::class);
    }

    public function recoveryCase(): HasOne
    {
        return $this->hasOne(RecoveryCase::class);
    }

    public function statutoryNotices(): HasMany
    {
        return $this->hasMany(StatutoryNotice::class);
    }

    public function otsProposals(): HasMany
    {
        return $this->hasMany(OtsProposal::class);
    }

    /**
     * Total outstanding principal (unpaid)
     */
    public function getOutstandingPrincipalAttribute(): float
    {
        return (float) $this->repaymentSchedules()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum(\DB::raw('principal_due - principal_paid'));
    }

    /**
     * Total overdue amount across all overdue installments
     */
    public function getTotalOverdueAttribute(): float
    {
        return (float) $this->repaymentSchedules()
            ->where('status', 'overdue')
            ->sum(\DB::raw('(principal_due - principal_paid) + (interest_due - interest_paid) + (penal_charges_due - penal_paid) + (penal_gst_due - gst_paid)'));
    }
}
