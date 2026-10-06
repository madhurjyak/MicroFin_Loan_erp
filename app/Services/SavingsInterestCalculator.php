<?php

namespace App\Services;

use App\Models\SavingsAccount;
use App\Models\SavingsSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * SavingsInterestCalculator
 *
 * Calculates and updates accrued interest on active Recurring Deposit accounts.
 *
 * Interest Calculation Method: Simple Interest on running balance.
 * For each period, interest = (collected balance × annual_rate) / periods_per_year
 *
 * RBI Note: Interest is accrued but not compounded within an RD.
 * The maturity payout = Total Principal + Total Accrued Interest.
 */
class SavingsInterestCalculator
{
    /**
     * Update total_interest_accrued for all active RD accounts.
     * Called by the sfb:update-dpd daily Artisan command.
     *
     * @return array Summary statistics
     */
    public function updateAllAccruedInterest(): array
    {
        $accounts    = SavingsAccount::where('status', 'active')->with('schedules')->get();
        $updated     = 0;
        $totalInterest = 0.0;

        foreach ($accounts as $account) {
            $accrued = $this->calculateAccruedInterest($account);

            DB::table('savings_accounts')
                ->where('id', $account->id)
                ->update(['total_interest_accrued' => $accrued]);

            // Also update per-installment interest_accrued on paid schedules
            $this->updateInstallmentInterest($account);

            $totalInterest += $accrued;
            $updated++;
        }

        return [
            'accounts_updated' => $updated,
            'total_interest'   => round($totalInterest, 2),
        ];
    }

    /**
     * Calculate the total accrued interest for a single RD account.
     *
     * Uses simple interest on the running balance at each collection point:
     * Interest per period = Balance × (annualRate / periodsPerYear)
     */
    public function calculateAccruedInterest(SavingsAccount $account): float
    {
        $annualRate     = (float) $account->interest_rate / 100;
        $frequency      = $account->frequency;
        $periodsPerYear = $frequency === 'weekly' ? 52 : 12;
        $periodicRate   = $annualRate / $periodsPerYear;

        $paidSchedules = $account->schedules
            ->where('status', 'paid')
            ->sortBy('installment_no');

        $runningBalance = 0.0;
        $totalInterest  = 0.0;
        $tenureRemaining = $account->schedules->count();

        foreach ($paidSchedules as $s) {
            $runningBalance += (float) $s->amount_collected;
            // Interest on this installment's balance for its remaining tenure periods
            $periodsRemaining = $tenureRemaining - (int) $s->installment_no;
            if ($periodsRemaining > 0) {
                $totalInterest += $runningBalance * $periodicRate * $periodsRemaining;
            }
        }

        // Simpler: use the standard RD interest formula on collected installments
        // Interest = sum_i(P_i * r * (n - i) / periodsPerYear)
        // Already computed above. Normalize:
        $simpleAccrued = 0.0;
        $n = $account->schedules->count();
        foreach ($paidSchedules as $s) {
            $i = (int) $s->installment_no;
            $collected = (float) $s->amount_collected;
            // Each collected amount earns interest for the remaining (n - i) periods
            $simpleAccrued += $collected * $periodicRate * max(0, $n - $i);
        }

        return round($simpleAccrued, 2);
    }

    /**
     * Calculate per-installment interest accrued (for ledger display).
     * Each paid deposit accrues interest = amount_collected * periodicRate.
     */
    public function updateInstallmentInterest(SavingsAccount $account): void
    {
        $annualRate     = (float) $account->interest_rate / 100;
        $frequency      = $account->frequency;
        $periodsPerYear = $frequency === 'weekly' ? 52 : 12;
        $periodicRate   = $annualRate / $periodsPerYear;

        $paidSchedules = $account->schedules->where('status', 'paid');

        foreach ($paidSchedules as $s) {
            // Interest on this deposit for one period (approximation for display)
            $interest = round((float) $s->amount_collected * $periodicRate, 2);
            DB::table('savings_schedules')
                ->where('id', $s->id)
                ->update(['interest_accrued' => $interest]);
        }
    }

    /**
     * Calculate maturity value for a single account (re-calculation).
     */
    public function getProjectedMaturity(SavingsAccount $account): float
    {
        $collected       = (float) $account->total_principal_collected;
        $accruedInterest = $this->calculateAccruedInterest($account);
        return round($collected + $accruedInterest, 2);
    }
}
