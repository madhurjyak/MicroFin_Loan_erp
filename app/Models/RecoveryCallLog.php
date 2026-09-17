<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecoveryCallLog extends Model
{
    protected $fillable = [
        'recovery_case_id', 'interaction_type', 'contact_time',
        'disposition', 'ptp_date', 'ptp_amount', 'logged_by', 'notes',
    ];

    protected $casts = [
        'contact_time' => 'datetime',
        'ptp_date'     => 'date',
        'ptp_amount'   => 'decimal:2',
    ];

    public function recoveryCase(): BelongsTo
    {
        return $this->belongsTo(RecoveryCase::class);
    }
}
