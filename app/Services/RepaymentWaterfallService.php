<?php

namespace App\Services;

use App\Models\CollectionTransaction;
use App\Models\Loan;
use App\Models\RepaymentSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * RepaymentWaterfallService
 *
 * Implements the Indian banking appropriation sequence (RBI Fair Lending):
 *   1. Penal GST (18%)
 *   2. Penal Charges
 *   3. Past Overdue Interest
 *   4. Current Interest
 *   5. Principal
 *
 * Zero capitalisation: penal charges are NEVER added to principal balance.
 * Supports JLG peer-guarantee repayment contributions.
 */
class RepaymentWaterfallService
{
    /**
     * Process a payment collection against the oldest unpaid installment(s).
     *
     * @param  Loan   $loan            The loan being repaid
     * @param  float  $amountCollected  Amount received
     * @param  string $paymentMode      cash|upi_qr|nach
     * @param  string $collectedBy      Staff name
     * @param  string|null $collectionDate  Defaults to today
     * @param  int|null $peerPayerCustomerId  For JLG peer payment
     * @return array  Summary of what was applied
     */
    public function apply(
        Loan $loan,
        float $amountCollected,
        string $paymentMode = 'cash',
        string $collectedBy = 'System',
        ?string $collectionDate = null,
        ?int $peerPayerCustomerId = null
    ): array {
        $collectionDate = $collectionDate ?? Carbon::today()->toDateString();
        $remaining      = $amountCollected;
        $summary        = [
            'gst_applied'    => 0,
            'penal_applied'  => 0,
            'interest_applied' => 0,
            'principal_applied' => 0,
            'schedules_cleared' => [],
        ];

        DB::transaction(function () use ($loan, &$remaining, &$summary, $collectionDate, $collectedBy, $paymentMode, $peerPayerCustomerId) {

            // Get all unpaid/overdue/partial schedules, oldest first
            $schedules = RepaymentSchedule::where('loan_id', $loan->id)
                ->whereIn('status', ['overdue', 'partial', 'pending'])
                ->orderBy('installment_no')
                ->get();

            foreach ($schedules as $schedule) {
                if ($remaining <= 0) break;

                $balGst     = round((float)$schedule->penal_gst_due  - (float)$schedule->gst_paid,    2);
                $balPenal   = round((float)$schedule->penal_charges_due - (float)$schedule->penal_paid,  2);
                $balInterest= round((float)$schedule->interest_due   - (float)$schedule->interest_paid, 2);
                $balPrincipal = round((float)$schedule->principal_due - (float)$schedule->principal_paid, 2);

                $applied = [
                    'gst'       => 0,
                    'penal'     => 0,
                    'interest'  => 0,
                    'principal' => 0,
                ];

                // Waterfall Step 1: Penal GST
                if ($remaining > 0 && $balGst > 0) {
                    $pay = min($remaining, $balGst);
                    $applied['gst'] = $pay;
                    $remaining -= $pay;
                    $summary['gst_applied'] += $pay;
                }

                // Waterfall Step 2: Penal Charges
                if ($remaining > 0 && $balPenal > 0) {
                    $pay = min($remaining, $balPenal);
                    $applied['penal'] = $pay;
                    $remaining -= $pay;
                    $summary['penal_applied'] += $pay;
                }

                // Waterfall Step 3 & 4: Interest (past overdue + current merged here as one bucket)
                if ($remaining > 0 && $balInterest > 0) {
                    $pay = min($remaining, $balInterest);
                    $applied['interest'] = $pay;
                    $remaining -= $pay;
                    $summary['interest_applied'] += $pay;
                }

                // Waterfall Step 5: Principal
                if ($remaining > 0 && $balPrincipal > 0) {
                    $pay = min($remaining, $balPrincipal);
                    $applied['principal'] = $pay;
                    $remaining -= $pay;
                    $summary['principal_applied'] += $pay;
                }

                // Update schedule paid amounts
                $newGstPaid      = (float)$schedule->gst_paid      + $applied['gst'];
                $newPenalPaid    = (float)$schedule->penal_paid     + $applied['penal'];
                $newInterestPaid = (float)$schedule->interest_paid  + $applied['interest'];
                $newPrincipalPaid= (float)$schedule->principal_paid + $applied['principal'];
                $newTotalPaid    = $newGstPaid + $newPenalPaid + $newInterestPaid + $newPrincipalPaid;

                // Determine new status
                $totalDue = (float)$schedule->total_due
                    + (float)$schedule->penal_charges_due
                    + (float)$schedule->penal_gst_due;

                $newStatus = 'partial';
                if ($newTotalPaid >= $totalDue - 0.01) {
                    $newStatus = 'paid';
                    $summary['schedules_cleared'][] = $schedule->installment_no;
                }

                $schedule->update([
                    'gst_paid'      => $newGstPaid,
                    'penal_paid'    => $newPenalPaid,
                    'interest_paid' => $newInterestPaid,
                    'principal_paid'=> $newPrincipalPaid,
                    'total_paid'    => $newTotalPaid,
                    'status'        => $newStatus,
                ]);

                // Record collection transaction
                $totalApplied = array_sum($applied);
                if ($totalApplied > 0) {
                    CollectionTransaction::create([
                        'loan_id'               => $loan->id,
                        'schedule_id'           => $schedule->id,
                        'receipt_no'            => 'RCP' . strtoupper(uniqid()),
                        'amount_collected'      => $totalApplied,
                        'collection_date'       => $collectionDate,
                        'collected_by'          => $collectedBy,
                        'payment_mode'          => $paymentMode,
                        'peer_payer_customer_id'=> $peerPayerCustomerId,
                        'remarks'               => $this->buildRemarks($applied),
                    ]);
                }
            }
        });

        $summary['excess_amount'] = round($remaining, 2);
        return $summary;
    }

    private function buildRemarks(array $applied): string
    {
        $parts = [];
        if ($applied['gst']       > 0) $parts[] = "GST: ₹{$applied['gst']}";
        if ($applied['penal']     > 0) $parts[] = "Penal: ₹{$applied['penal']}";
        if ($applied['interest']  > 0) $parts[] = "Interest: ₹{$applied['interest']}";
        if ($applied['principal'] > 0) $parts[] = "Principal: ₹{$applied['principal']}";
        return implode(' | ', $parts);
    }
}
