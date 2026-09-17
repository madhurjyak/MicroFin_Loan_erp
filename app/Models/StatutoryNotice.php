<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatutoryNotice extends Model
{
    protected $fillable = [
        'loan_id', 'notice_type', 'notice_ref_no',
        'dispatch_date', 'tracking_speedpost_no', 'status', 'remarks',
    ];

    protected $casts = [
        'dispatch_date' => 'date',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function getNoticeTypeLabelAttribute(): string
    {
        return match($this->notice_type) {
            'Sec_138_NI_Act'           => 'Section 138 – Negotiable Instruments Act',
            'Sec_25_PSSA_AutoDebit_Bounce' => 'Section 25 – PSSA / AutoDebit Bounce',
            'Loan_Recall_Notice'       => 'Loan Recall Notice',
            'SARFAESI_13_2'            => 'SARFAESI – Section 13(2)',
            default                    => $this->notice_type,
        };
    }
}
