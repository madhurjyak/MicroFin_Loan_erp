<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanDocument extends Model
{
    protected $fillable = [
        'loan_application_id', 'document_type', 'document_number',
        'file_path', 'verification_status', 'verified_by',
    ];

    // ── Relationships ──────────────────────────────────────────────────

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ── Accessors ──────────────────────────────────────────────────────

    /**
     * Human-readable document type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->document_type) {
            'aadhaar_card'       => 'Aadhaar Card',
            'pan_card'           => 'PAN Card',
            'voter_id'           => 'Voter ID',
            'bank_passbook'      => 'Bank Passbook',
            'income_declaration' => 'Income Declaration',
            default              => ucfirst(str_replace('_', ' ', $this->document_type)),
        };
    }

    /**
     * Verification status badge CSS class
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->verification_status) {
            'pending'  => 'bg-yellow-100 text-yellow-700',
            'verified' => 'bg-emerald-100 text-emerald-700',
            'rejected' => 'bg-red-100 text-red-700',
            default    => 'bg-slate-100 text-slate-600',
        };
    }
}
