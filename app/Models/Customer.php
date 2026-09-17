<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'group_id', 'customer_code', 'full_name', 'phone',
        'pan_number', 'aadhaar_last4', 'address', 'district',
        'state', 'pincode', 'annual_household_income',
        'monthly_debt_obligations', 'bank_account_no', 'ifsc_code', 'gender',
    ];

    protected $casts = [
        'annual_household_income' => 'decimal:2',
        'monthly_debt_obligations' => 'decimal:2',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Returns masked Aadhaar in the format XXXX-XXXX-1234
     */
    public function getMaskedAadhaarAttribute(): string
    {
        return 'XXXX-XXXX-' . $this->aadhaar_last4;
    }

    /**
     * Monthly household income derived from annual
     */
    public function getMonthlyIncomeAttribute(): float
    {
        return round($this->annual_household_income / 12, 2);
    }

    /**
     * RBI FOIR: monthly obligations as % of monthly income
     */
    public function getFoirPercentAttribute(): float
    {
        if ($this->monthly_income <= 0) return 0;
        return round(($this->monthly_debt_obligations / $this->monthly_income) * 100, 2);
    }
}
