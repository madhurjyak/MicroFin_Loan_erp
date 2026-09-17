<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtsProposal extends Model
{
    protected $fillable = [
        'loan_id', 'total_outstanding', 'proposed_amount',
        'waiver_penal_gst', 'waiver_interest', 'waiver_principal',
        'haircut_pct', 'approval_authority', 'status',
        'approved_by', 'approval_date', 'remarks',
    ];

    protected $casts = [
        'total_outstanding' => 'decimal:2',
        'proposed_amount'   => 'decimal:2',
        'waiver_penal_gst'  => 'decimal:2',
        'waiver_interest'   => 'decimal:2',
        'waiver_principal'  => 'decimal:2',
        'haircut_pct'       => 'decimal:2',
        'approval_date'     => 'date',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
