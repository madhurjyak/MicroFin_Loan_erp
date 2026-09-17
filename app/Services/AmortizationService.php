<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Loan;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * AmortizationService
 *
 * Computes reducing-balance (diminishing) EMI schedules for weekly or monthly
 * loan repayments. Enforces RBI Microfinance Directions 2022:
 *   - Annual household income ≤ ₹3,00,000
 *   - Monthly repayment obligation ≤ 50% of monthly household income (FOIR)
 *   - Zero prepayment penalties
 */
class AmortizationService
{
    public const PENAL_CHARGE_PER_BOUNCE = 100.00; // ₹100 per bounced installment
    public const PENAL_GST_RATE          = 0.18;   // 18% GST on penal charges
    public const RBI_INCOME_CAP          = 300000;  // ₹3,00,000 annual income cap
    public const RBI_FOIR_MAX_PCT        = 50;      // 50% FOIR limit

    /**
     * Validate RBI microfinance eligibility rules before schedule generation.
     *
     * @throws InvalidArgumentException on rule violation
     */
    public function validateRbiLimits(Customer $customer, float $emiAmount): void
    {
        // Rule 1: Annual household income cap
        if ($customer->annual_household_income > self::RBI_INCOME_CAP) {
            throw new InvalidArgumentException(
                "Customer annual household income ₹" .
                number_format($customer->annual_household_income, 2) .
                " exceeds RBI microfinance cap of ₹3,00,000."
            );
        }

        // Rule 2: FOIR check (new EMI + existing obligations ≤ 50% monthly income)
        $monthlyIncome     = $customer->annual_household_income / 12;
        $newMonthlyObligation = $customer->monthly_debt_obligations + $emiAmount;

        if ($monthlyIncome > 0) {
            $foirPct = ($newMonthlyObligation / $monthlyIncome) * 100;
            if ($foirPct > self::RBI_FOIR_MAX_PCT) {
                throw new InvalidArgumentException(
                    sprintf(
                        "FOIR %.2f%% (monthly obligation ₹%.2f / monthly income ₹%.2f) " .
                        "exceeds RBI 50%% limit.",
                        $foirPct,
                        $newMonthlyObligation,
                        $monthlyIncome
                    )
                );
            }
        }
    }

    /**
     * Generate a reducing-balance amortization schedule.
     *
     * @param  Loan   $loan  Loan model (must have customer loaded)
     * @param  bool   $validateRbi  Whether to enforce RBI eligibility checks
     * @return array  Array of installment rows (same structure as repayment_schedules)
     */
    public function generate(Loan $loan, bool $validateRbi = true): array
    {
        $principal   = (float) $loan->principal_amount;
        $annualRate  = (float) $loan->annual_interest_rate / 100;
        $tenure      = (int) $loan->tenure;
        $frequency   = $loan->repayment_frequency; // 'weekly' or 'monthly'
        $startDate   = Carbon::parse($loan->disbursement_date);

        // Periodic interest rate
        $periodicRate = $frequency === 'weekly'
            ? $annualRate / 52
            : $annualRate / 12;

        // EMI via reducing-balance formula: P * r(1+r)^n / ((1+r)^n - 1)
        $emi = $this->calculateEmi($principal, $periodicRate, $tenure);

        // RBI validation (monthly EMI used for FOIR regardless of frequency)
        if ($validateRbi && $loan->relationLoaded('customer')) {
            $monthlyEquivalentEmi = $frequency === 'weekly' ? $emi * 4.33 : $emi;
            $this->validateRbiLimits($loan->customer, $monthlyEquivalentEmi);
        }

        $schedule       = [];
        $balance        = $principal;
        $currentDate    = clone $startDate;

        for ($i = 1; $i <= $tenure; $i++) {
            // Advance due date
            $currentDate = $frequency === 'weekly'
                ? (clone $startDate)->addWeeks($i)
                : (clone $startDate)->addMonths($i);

            $interestDue   = round($balance * $periodicRate, 2);
            $principalDue  = round($emi - $interestDue, 2);

            // Last installment: clear remaining balance to avoid rounding drift
            if ($i === $tenure) {
                $principalDue = round($balance, 2);
            }

            $openingBalance  = round($balance, 2);
            $balance         = round($balance - $principalDue, 2);
            $closingBalance  = max($balance, 0);
            $totalDue        = round($principalDue + $interestDue, 2);

            $schedule[] = [
                'installment_no'  => $i,
                'due_date'        => $currentDate->toDateString(),
                'opening_balance' => $openingBalance,
                'principal_due'   => $principalDue,
                'interest_due'    => $interestDue,
                'penal_charges_due' => 0,
                'penal_gst_due'   => 0,
                'total_due'       => $totalDue,
                'principal_paid'  => 0,
                'interest_paid'   => 0,
                'penal_paid'      => 0,
                'gst_paid'        => 0,
                'total_paid'      => 0,
                'closing_balance' => $closingBalance,
                'status'          => 'pending',
            ];
        }

        return $schedule;
    }

    /**
     * Standard reducing-balance EMI formula.
     * If periodicRate is 0 (zero-interest loan), returns simple division.
     */
    public function calculateEmi(float $principal, float $periodicRate, int $tenure): float
    {
        if ($periodicRate <= 0) {
            return round($principal / $tenure, 2);
        }
        $factor = pow(1 + $periodicRate, $tenure);
        return round(($principal * $periodicRate * $factor) / ($factor - 1), 2);
    }

    /**
     * Calculate penal charge + GST for a single bounced installment.
     * RBI Rule: Penal charges are NOT compounded; NOT added to principal.
     *
     * @return array ['penal' => float, 'gst' => float, 'total' => float]
     */
    public function penalChargeForBounce(): array
    {
        $penal = self::PENAL_CHARGE_PER_BOUNCE;
        $gst   = round($penal * self::PENAL_GST_RATE, 2);
        return [
            'penal' => $penal,
            'gst'   => $gst,
            'total' => $penal + $gst,
        ];
    }
}
