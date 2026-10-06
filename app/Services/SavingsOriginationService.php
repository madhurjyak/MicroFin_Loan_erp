<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SavingsAccount;
use App\Models\SavingsSchedule;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * SavingsOriginationService
 *
 * Handles Recurring Deposit (RD) account creation and schedule generation
 * for the Indian MFI savings mobilization programme.
 *
 * Business Rules:
 * - Weekly or monthly deposit frequency
 * - Minimum tenure: 6 months; Maximum: 60 months
 * - Interest calculated on running balance (simple interest per period)
 * - Account number format: RD{YYYY}{NNNNNN}
 */
class SavingsOriginationService
{
    public const MIN_TENURE_MONTHS = 6;
    public const MAX_TENURE_MONTHS = 60;
    public const MIN_WEEKLY_DEPOSIT  = 50.00;    // ₹50 minimum weekly RD
    public const MIN_MONTHLY_DEPOSIT = 200.00;   // ₹200 minimum monthly RD

    /**
     * Open a new RD account and generate the deposit schedule.
     *
     * @param  Customer $customer
     * @param  array    $params  {deposit_amount, interest_rate, tenure, frequency, opening_date}
     * @return SavingsAccount
     * @throws InvalidArgumentException
     */
    public function openAccount(Customer $customer, array $params): SavingsAccount
    {
        $this->validate($params);

        $openingDate  = Carbon::parse($params['opening_date'] ?? today());
        $tenure       = (int) $params['tenure'];
        $maturityDate = $openingDate->copy()->addMonths($tenure);
        $depositAmt   = (float) $params['deposit_amount'];
        $rate         = (float) $params['interest_rate'];
        $frequency    = $params['frequency'];

        // Calculate expected maturity amount
        $maturityAmount = $this->calculateMaturityAmount(
            $depositAmt, $rate, $tenure, $frequency
        );

        // Generate sequential account number: RD + Year + 6-digit sequence
        $accountNo = $this->generateAccountNo();

        $account = SavingsAccount::create([
            'customer_id'              => $customer->id,
            'account_no'               => $accountNo,
            'account_type'             => 'rd',
            'deposit_amount'           => $depositAmt,
            'interest_rate'            => $rate,
            'tenure'                   => $tenure,
            'frequency'                => $frequency,
            'opening_date'             => $openingDate->toDateString(),
            'maturity_date'            => $maturityDate->toDateString(),
            'status'                   => 'active',
            'total_principal_collected'=> 0,
            'total_interest_accrued'   => 0,
            'maturity_amount'          => $maturityAmount,
        ]);

        // Generate deposit schedule
        $this->generateSchedule($account, $openingDate);

        return $account;
    }

    /**
     * Generate the deposit installment schedule for an RD account.
     */
    public function generateSchedule(SavingsAccount $account, Carbon $openingDate): void
    {
        $frequency   = $account->frequency;
        $tenure      = (int) $account->tenure;
        $depositAmt  = (float) $account->deposit_amount;

        // Number of installments based on frequency and tenure (months)
        $installments = $frequency === 'weekly'
            ? (int) round($tenure * 52 / 12)  // Convert months to weeks
            : $tenure;                          // Monthly: tenure = installment count

        for ($i = 1; $i <= $installments; $i++) {
            $dueDate = $frequency === 'weekly'
                ? $openingDate->copy()->addWeeks($i)
                : $openingDate->copy()->addMonths($i);

            SavingsSchedule::create([
                'savings_account_id' => $account->id,
                'installment_no'     => $i,
                'due_date'           => $dueDate->toDateString(),
                'amount_expected'    => $depositAmt,
                'amount_collected'   => 0,
                'interest_accrued'   => 0,
                'status'             => 'pending',
            ]);
        }
    }

    /**
     * Calculate the expected maturity amount for an RD.
     *
     * Formula: Simple interest on each installment's period-weighted balance.
     * M = P * n + P * n*(n+1)/2 * r / (12 * n_per_year)
     * Where: P = deposit amount, n = total installments, r = annual rate (decimal)
     */
    public function calculateMaturityAmount(
        float $depositAmount,
        float $annualRate,
        int   $tenureMonths,
        string $frequency
    ): float {
        $r = $annualRate / 100;

        if ($frequency === 'weekly') {
            $n = (int) round($tenureMonths * 52 / 12);
            $periodsPerYear = 52;
        } else {
            $n = $tenureMonths;
            $periodsPerYear = 12;
        }

        // Simple interest RD maturity formula
        // Each instalment P earns interest for its remaining periods
        // Total Interest = P * r/periodsPerYear * sum(1..n) = P * r/periodsPerYear * n*(n+1)/2
        $totalPrincipal = $depositAmount * $n;
        $totalInterest  = $depositAmount * ($r / $periodsPerYear) * ($n * ($n + 1) / 2);

        return round($totalPrincipal + $totalInterest, 2);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function validate(array $params): void
    {
        $tenure    = (int) ($params['tenure'] ?? 0);
        $deposit   = (float) ($params['deposit_amount'] ?? 0);
        $frequency = $params['frequency'] ?? 'weekly';

        if ($tenure < self::MIN_TENURE_MONTHS || $tenure > self::MAX_TENURE_MONTHS) {
            throw new InvalidArgumentException(
                "RD tenure must be between " . self::MIN_TENURE_MONTHS .
                " and " . self::MAX_TENURE_MONTHS . " months. Given: {$tenure}"
            );
        }

        $minDeposit = $frequency === 'weekly' ? self::MIN_WEEKLY_DEPOSIT : self::MIN_MONTHLY_DEPOSIT;
        if ($deposit < $minDeposit) {
            throw new InvalidArgumentException(
                "Minimum {$frequency} RD deposit is ₹{$minDeposit}. Given: ₹{$deposit}"
            );
        }
    }

    private function generateAccountNo(): string
    {
        $year     = now()->format('Y');
        $sequence = SavingsAccount::whereYear('created_at', $year)->count() + 1;
        return 'RD' . $year . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }
}
