<?php

namespace App\Http\Controllers;

use App\Models\Center;
use App\Models\Group;
use App\Models\Loan;
use App\Models\RepaymentSchedule;
use App\Models\SavingsAccount;
use App\Models\SavingsSchedule;
use App\Services\CollectionLedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LmsController extends Controller
{
    public function __construct(private CollectionLedgerService $ledger) {}

    /**
     * Unified Kendra Collection Day Sheet (CDS)
     *
     * Shows group-wise member collection sheet displaying BOTH:
     * - Expected weekly/monthly loan installments (EMI)
     * - Expected RD savings deposit for the same date
     *
     * Offline-first note: All data is fetched server-side per center/date
     * so it can be printed or cached for field agents in low-connectivity areas.
     */
    public function cds(Request $request)
    {
        $centers        = Center::orderBy('branch_name')->orderBy('center_name')->get();
        $selectedCenter = null;
        $groups         = collect();
        $collectionDate = $request->input('collection_date', Carbon::today()->toDateString());
        $cdsSummary     = null;

        if ($request->filled('center_id')) {
            $selectedCenter = Center::with([
                'groups.customers' => function ($q) {
                    $q->with([
                        // Active loans with due schedules on this date
                        'loans' => function ($lq) {
                            $lq->whereIn('status', ['active', 'npa'])
                               ->with(['repaymentSchedules' => function ($rq) {
                                   $rq->whereIn('status', ['overdue', 'partial', 'pending'])
                                      ->orderBy('installment_no');
                               }]);
                        },
                        // Active savings accounts with due schedules
                        'savingsAccounts' => function ($sq) {
                            $sq->where('status', 'active')
                               ->with(['schedules' => function ($ss) {
                                   $ss->whereIn('status', ['pending', 'partial', 'missed'])
                                      ->orderBy('installment_no');
                               }]);
                        },
                    ]);
                },
            ])->findOrFail($request->center_id);

            $groups = $selectedCenter->groups;

            // Build CDS summary stats
            $totalLoanDue    = 0;
            $totalSavingsDue = 0;
            $totalMembers    = 0;

            foreach ($groups as $group) {
                foreach ($group->customers as $customer) {
                    $totalMembers++;

                    // Next due loan installment
                    foreach ($customer->loans as $loan) {
                        $nextSchedule = $loan->repaymentSchedules->first();
                        if ($nextSchedule) {
                            $totalLoanDue += (float)$nextSchedule->total_due
                                + (float)$nextSchedule->penal_charges_due
                                + (float)$nextSchedule->penal_gst_due
                                - (float)$nextSchedule->total_paid;
                        }
                    }

                    // Next due savings installment
                    foreach ($customer->savingsAccounts as $sa) {
                        $nextSaving = $sa->schedules->first();
                        if ($nextSaving) {
                            $totalSavingsDue += (float)$nextSaving->amount_expected
                                - (float)$nextSaving->amount_collected;
                        }
                    }
                }
            }

            $cdsSummary = [
                'total_members'    => $totalMembers,
                'total_loan_due'   => round($totalLoanDue, 2),
                'total_saving_due' => round($totalSavingsDue, 2),
                'total_due'        => round($totalLoanDue + $totalSavingsDue, 2),
            ];
        }

        return view('lms.cds', compact(
            'centers', 'selectedCenter', 'groups',
            'collectionDate', 'cdsSummary'
        ));
    }

    /**
     * POST: Bulk CDS settlement — applies both loan EMI + RD deposit in one transaction.
     */
    public function bulkSettle(Request $request)
    {
        $validated = $request->validate([
            'customer_id'     => 'required|exists:customers,id',
            'loan_amount'     => 'nullable|numeric|min:0',
            'savings_amount'  => 'nullable|numeric|min:0',
            'payment_mode'    => 'required|in:cash,upi_qr,nach',
            'collection_date' => 'required|date',
        ]);

        $loan    = Loan::where('customer_id', $validated['customer_id'])
            ->whereIn('status', ['active', 'npa'])
            ->first();

        $savings = SavingsAccount::where('customer_id', $validated['customer_id'])
            ->where('status', 'active')
            ->first();

        $result = $this->ledger->processKendraCollection(
            loan:           $loan,
            savingsAccount: $savings,
            loanAmount:     (float) ($validated['loan_amount'] ?? 0),
            savingsAmount:  (float) ($validated['savings_amount'] ?? 0),
            paymentMode:    $validated['payment_mode'],
            collectedBy:    Auth::user()->name,
            collectionDate: $validated['collection_date'],
        );

        return response()->json([
            'success' => true,
            'receipt' => $result['receipt_no'],
            'summary' => $result,
        ]);
    }
}
