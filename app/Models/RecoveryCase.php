<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecoveryCase extends Model
{
    protected $fillable = [
        'loan_id', 'dpd', 'asset_classification',
        'total_overdue_principal', 'total_overdue_interest',
        'total_penal_charges', 'total_outstanding',
        'assigned_officer', 'assigned_officer_id', 'last_contacted_at', 'remarks',
    ];

    protected $casts = [
        'dpd'                    => 'integer',
        'total_overdue_principal'=> 'decimal:2',
        'total_overdue_interest' => 'decimal:2',
        'total_penal_charges'    => 'decimal:2',
        'total_outstanding'      => 'decimal:2',
        'last_contacted_at'      => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function callLogs(): HasMany
    {
        return $this->hasMany(RecoveryCallLog::class)->orderByDesc('contact_time');
    }

    /**
     * The assigned agent user (FK relationship)
     */
    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    /**
     * Human readable bucket label
     */
    public function getBucketLabelAttribute(): string
    {
        return match($this->asset_classification) {
            'Standard'       => 'Standard',
            'SMA-0'          => 'SMA-0 (1–30 DPD)',
            'SMA-1'          => 'SMA-1 (31–60 DPD)',
            'SMA-2'          => 'SMA-2 (61–90 DPD)',
            'NPA_SubStandard'=> 'NPA – Sub-Standard (91+ DPD)',
            'Doubtful'       => 'NPA – Doubtful (12+ months)',
            default          => $this->asset_classification,
        };
    }
}
