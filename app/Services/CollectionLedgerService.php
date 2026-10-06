<?php

namespace App\Services;

use App\Models\CollectionTransaction;
use App\Models\Loan;
use App\Models\RepaymentSchedule;
use App\Models\SavingsAccount;
use App\Models\SavingsSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * CollectionLedgerService
 *
 * ACID-compliant unified collection processor for the Kendra Collection Day Sheet.
 *
 * Handles:
 * 1. Loan EMI repayments — Indian banking waterfall appropriation:
 *    Penal GST → Penal Charges → Past Overdue Interest → Current Interest → Principal
 *
 * 2. RD Savings deposits — applied against the oldest pending savings schedule.
 *
 * 3. Combined/Bulk CDS settlement — splits a single incoming payment across
 *    both the member's loan EMI and their RD deposit in one ACID transaction.
 *
 * RBI Compliance:
 * - Zero capitalisation: Penal charges NEVER added to principal.
 * - GST (18%) levied on penal charges — tracked separately.
 */
class CollectionLedgerService
{
    // ── Public Entry Points ──────────────────────────────────────────────────

    /**
     * Process a COMBINED Kendra collection for one member:
     * Applies loanAmount to EMI waterfall and savingsAmount to RD schedule.
     *
     * @param  Loan|null           $loan
     * @param  SavingsAccount|null $savingsAccount
     * @param  float               $loanAmount     Amount designated for loan EMI
     * @param  float               $savingsAmount  Amount designated for RD deposit
     * @param  string              $paymentMode    cash|upi_qr|nach
     * @param  string              $collectedBy    Staff name
     * @param  string|null         $collectionDate  Defaults to today
     * @param  int|null            $peerPayerCustomerId  JLG peer payment
     * @return array Summary of what was applied
     */
    public function processKendraCollection(
        ?Loan           $loan,
        ?SavingsAccount $savingsAccount,
        float           $loanAmount    = 0,
        float           $savingsAmount = 0,
        string          $paymentMode   = 'cash',
        string          $collectedBy   = 'System',
        ?string         $collectionDate = null,
        ?int            $peerPayerCustomerId = null
    ): array {
        $collectionDate = $collectionDate ?? Carbon::today()->toDateString();
        $summary = [
            'loan_summary'    => null,
            'savings_summary' => null,
            'total_collected' => $loanAmount + $savingsAmount,
            'receipt_no'      => 'RCP' . strtoupper(uniqid()),
        ];

        DB::transaction(function () use (
            &$summary, $loan, $savingsAccount,
            $loanAmount, $savingsAmount,
            $paymentMode, $collectedBy, $collectionDate, $peerPayerCustomerId
        ) {
            // ── Loan Repayment Waterfall ─────────────────────────────────────
            if ($loan && $loanAmount > 0) {
                $summary['loan_summary'] = $this->applyLoanWaterfall(
                    $loan, $loanAmount, $paymentMode,
                    $collectedBy, $collectionDate,
                    $peerPayerCustomerId, $summary['receipt_no']
                );
            }

            // ── Savings Deposit ──────────────────────────────────────────────
            if ($savingsAccount && $savingsAmount > 0) {
                $summary['savings_summary'] = $this->applySavingsDeposit(
                    $savingsAccount, $savingsAmount, $paymentMode,
                    $collectedBy, $collectionDate, $summary['receipt_no']
                );
            }
        });

        return $summary;
    }

    /**
     * Process a standalone loan repayment (used from the loan ledger screen).
     */
    public function applyLoanPayment(
        Loan   $loan,
        float  $amountCollected,
        string $paymentMode    = 'cash',
        string $collectedBy    = 'System',
        ?string $collectionDate = null,
        ?int   $peerPayerCustomerId = null
    ): array {
        $collectionDate = $collectionDate ?? Carbon::today()->toDateString();
        $receiptNo      = 'RCP' . strtoupper(uniqid());
        $result         = [];

        DB::transaction(function () use (
            $loan, $amountCollected, $paymentMode,
            $collectedBy, $collectionDate, $peerPayerCustomerId, $receiptNo, &$result
        ) {
            $result = $this->applyLoanWaterfall(
                $loan, $amountCollected, $paymentMode,
                $collectedBy, $collectionDate, $peerPayerCustomerId, $receiptNo
            );
        });

        return $result;
    }

    /**
     * Process a standalone savings deposit.
     */
    public function applySavingsPayment(
        SavingsAccount $account,
        float          $amount,
        string         $paymentMode    = 'cash',
        string         $collectedBy    = 'System',
        ?string        $collectionDate = null
    ): array {
        $collectionDate = $collectionDate ?? Carbon::today()->toDateString();
        $receiptNo      = 'RCP' . strtoupper(uniqid());
        $result         = [];

        DB::transaction(function () use (
            $account, $amount, $paymentMode, $collectedBy, $collectionDate, $receiptNo, &$result
        ) {
            $result = $this->applySavingsDeposit(
                $account, $amount, $paymentMode, $collectedBy, $collectionDate, $receiptNo
            );
        });

        return $result;
    }

    // ── Private Waterfall Engine ─────────────────────────────────────────────

    /**
     * Indian Banking Appropriation Waterfall (RBI Fair Lending Practice):
     * 1. Penal GST (18%)
     * 2. Penal Charges
     * 3. Past Overdue Interest
     * 4. Current Interest
     * 5. Principal
     */
    private function applyLoanWaterfall(
        Loan   $loan,
        float  $amountCollected,
        string $paymentMode,
        string $collectedBy,
        string $collectionDate,
        ?int   $peerPayerCustomerId,
        string $receiptNo
    ): array {
        $remaining = $amountCollected;
        $summary   = [
            'gst_applied'       => 0,
            'penal_applied'     => 0,
            'interest_applied'  => 0,
            'principal_applied' => 0,
            'schedules_cleared' => [],
            'excess_amount'     => 0,
            'receipt_no'        => $receiptNo . '-LN',
        ];

        // Fetch oldest unpaid/overdue/partial schedules
        $schedules = RepaymentSchedule::where('loan_id', $loan->id)
            ->whereIn('status', ['overdue', 'partial', 'pending'])
            ->orderBy('installment_no')
            ->get();

        foreach ($schedules as $schedule) {
            if ($remaining <= 0) break;

            $balGst      = round((float)$schedule->penal_gst_due    - (float)$schedule->gst_paid, 2);
            $balPenal    = round((float)$schedule->penal_charges_due - (float)$schedule->penal_paid, 2);
            $balInterest = round((float)$schedule->interest_due      - (float)$schedule->interest_paid, 2);
            $balPrincipal= round((float)$schedule->principal_due     - (float)$schedule->principal_paid, 2);

            $applied = ['gst' => 0, 'penal' => 0, 'interest' => 0, 'principal' => 0];

            // Step 1: Penal GST
            if ($remaining > 0 && $balGst > 0) {
                $pay = min($remaining, $balGst);
                $applied['gst'] = $pay;
                $remaining     -= $pay;
                $summary['gst_applied'] += $pay;
            }
            // Step 2: Penal Charges
            if ($remaining > 0 && $balPenal > 0) {
                $pay = min($remaining, $balPenal);
                $applied['penal'] = $pay;
                $remaining       -= $pay;
                $summary['penal_applied'] += $pay;
            }
            // Step 3 & 4: Interest (overdue + current merged)
            if ($remaining > 0 && $balInterest > 0) {
                $pay = min($remaining, $balInterest);
                $applied['interest'] = $pay;
                $remaining          -= $pay;
                $summary['interest_applied'] += $pay;
            }
            // Step 5: Principal
            if ($remaining > 0 && $balPrincipal > 0) {
                $pay = min($remaining, $balPrincipal);
                $applied['principal'] = $pay;
                $remaining           -= $pay;
                $summary['principal_applied'] += $pay;
            }

            // Update schedule
            $newGstPaid      = (float)$schedule->gst_paid       + $applied['gst'];
            $newPenalPaid    = (float)$schedule->penal_paid      + $applied['penal'];
            $newInterestPaid = (float)$schedule->interest_paid   + $applied['interest'];
            $newPrincipalPaid= (float)$schedule->principal_paid  + $applied['principal'];
            $newTotalPaid    = $newGstPaid + $newPenalPaid + $newInterestPaid + $newPrincipalPaid;

            $totalDue = (float)$schedule->total_due
                + (float)$schedule->penal_charges_due
                + (float)$schedule->penal_gst_due;

            $newStatus = 'partial';
            if ($newTotalPaid >= $totalDue - 0.01) {
                $newStatus = 'paid';
                $summary['schedules_cleared'][] = $schedule->installment_no;
            }

            $schedule->update([
                'gst_paid'       => $newGstPaid,
                'penal_paid'     => $newPenalPaid,
                'interest_paid'  => $newInterestPaid,
                'principal_paid' => $newPrincipalPaid,
                'total_paid'     => $newTotalPaid,
                'status'         => $newStatus,
            ]);

            // Record transaction
            $totalApplied = array_sum($applied);
            if ($totalApplied > 0) {
                CollectionTransaction::create([
                    'loan_id'               => $loan->id,
                    'schedule_id'           => $schedule->id,
                    'savings_account_id'    => null,
                    'savings_schedule_id'   => null,
                    'transaction_type'      => 'loan_repayment',
                    'receipt_no'            => $summary['receipt_no'] . '-' . $schedule->installment_no,
                    'amount_collected'      => $totalApplied,
                    'collection_date'       => $collectionDate,
                    'collected_by'          => $collectedBy,
                    'payment_mode'          => $paymentMode,
                    'peer_payer_customer_id'=> $peerPayerCustomerId,
                    'remarks'               => $this->buildLoanRemarks($applied),
                ]);
            }
        }

        $summary['excess_amount'] = round($remaining, 2);
        return $summary;
    }

    /**
     * Apply a savings deposit to the oldest pending installment(s).
     */
    private function applySavingsDeposit(
        SavingsAccount $account,
        float          $amount,
        string         $paymentMode,
        string         $collectedBy,
        string         $collectionDate,
        string         $receiptNo
    ): array {
        $remaining = $amount;
        $summary   = [
            'amount_deposited'   => 0,
            'schedules_cleared'  => [],
            'excess_amount'      => 0,
            'receipt_no'         => $receiptNo . '-SV',
        ];

        $schedules = SavingsSchedule::where('savings_account_id', $account->id)
            ->whereIn('status', ['pending', 'partial', 'missed'])
            ->orderBy('installment_no')
            ->get();

        foreach ($schedules as $schedule) {
            if ($remaining <= 0) break;

            $balDue = round((float)$schedule->amount_expected - (float)$schedule->amount_collected, 2);
            if ($balDue <= 0) continue;

            $pay       = min($remaining, $balDue);
            $remaining -= $pay;
            $newCollected  = (float)$schedule->amount_collected + $pay;
            $newStatus     = $newCollected >= (float)$schedule->amount_expected - 0.01 ? 'paid' : 'partial';

            $schedule->update([
                'amount_collected' => $newCollected,
                'status'           => $newStatus,
                'collection_date'  => $collectionDate,
            ]);

            $summary['amount_deposited'] += $pay;
            if ($newStatus === 'paid') {
                $summary['schedules_cleared'][] = $schedule->installment_no;
            }

            // Update account principal collected total
            $account->increment('total_principal_collected', $pay);

            // Record transaction
            CollectionTransaction::create([
                'loan_id'               => null,
                'schedule_id'           => null,
                'savings_account_id'    => $account->id,
                'savings_schedule_id'   => $schedule->id,
                'transaction_type'      => 'savings_deposit',
                'receipt_no'            => $summary['receipt_no'] . '-' . $schedule->installment_no,
                'amount_collected'      => $pay,
                'collection_date'       => $collectionDate,
                'collected_by'          => $collectedBy,
                'payment_mode'          => $paymentMode,
                'peer_payer_customer_id'=> null,
                'remarks'               => "RD Deposit #" . $schedule->installment_no . ": ₹" . number_format($pay, 2),
            ]);
        }

        $summary['excess_amount'] = round($remaining, 2);
        return $summary;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function buildLoanRemarks(array $applied): string
    {
        $parts = [];
        if ($applied['gst']       > 0) $parts[] = "Penal GST: ₹{$applied['gst']}";
        if ($applied['penal']     > 0) $parts[] = "Penal: ₹{$applied['penal']}";
        if ($applied['interest']  > 0) $parts[] = "Interest: ₹{$applied['interest']}";
        if ($applied['principal'] > 0) $parts[] = "Principal: ₹{$applied['principal']}";
        return implode(' | ', $parts);
    }
}
