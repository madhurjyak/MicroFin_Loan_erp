<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LoanApplication extends Model
{
    protected $fillable = [
        'application_no', 'customer_id', 'agent_id',
        'applied_amount', 'annual_interest_rate', 'tenure',
        'repayment_frequency', 'purpose', 'stage',
        'reviewed_by', 'review_notes', 'approved_at', 'rejection_reason',
    ];

    protected $casts = [
        'applied_amount'       => 'decimal:2',
        'annual_interest_rate' => 'decimal:2',
        'approved_at'          => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(LoanDocument::class);
    }

    public function loan(): HasOne
    {
        return $this->hasOne(Loan::class, 'loan_application_id');
    }

    // ── Accessors ──────────────────────────────────────────────────────

    /**
     * Stage badge CSS class for Tailwind
     */
    public function getStageBadgeAttribute(): string
    {
        return match($this->stage) {
            'draft'        => 'bg-slate-100 text-slate-600',
            'submitted'    => 'bg-blue-100 text-blue-700',
            'under_review' => 'bg-yellow-100 text-yellow-800',
            'approved'     => 'bg-emerald-100 text-emerald-700',
            'rejected'     => 'bg-red-100 text-red-700',
            default        => 'bg-slate-100 text-slate-600',
        };
    }

    /**
     * Human-readable stage label
     */
    public function getStageLabelAttribute(): string
    {
        return match($this->stage) {
            'draft'        => 'Draft',
            'submitted'    => 'Submitted',
            'under_review' => 'Under Review',
            'approved'     => 'Approved',
            'rejected'     => 'Rejected',
            default        => ucfirst($this->stage),
        };
    }

    /**
     * Formatted amount in Indian notation
     */
    public function getFormattedAmountAttribute(): string
    {
        return '₹' . number_format((float) $this->applied_amount, 2);
    }
}
