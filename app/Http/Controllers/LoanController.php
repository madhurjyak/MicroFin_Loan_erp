<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Services\RepaymentWaterfallService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function __construct(private RepaymentWaterfallService $waterfall) {}

    /**
     * Loan Account Ledger — full schedule, KYC, and collection history
     */
    public function show(int $id)
    {
        $loan = Loan::with([
            'customer.group.center',
            'repaymentSchedules',
            'collectionTransactions.peerPayer',
            'recoveryCase',
            'statutoryNotices',
        ])->findOrFail($id);

        $overdueSchedules = $loan->repaymentSchedules
            ->whereIn('status', ['overdue', 'partial'])
            ->count();

        $totalOutstanding = $loan->repaymentSchedules
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum(fn ($s) => (float)$s->principal_due + (float)$s->interest_due
                             + (float)$s->penal_charges_due + (float)$s->penal_gst_due
                             - (float)$s->total_paid);

        return view('lms.loan_ledger', compact('loan', 'overdueSchedules', 'totalOutstanding'));
    }

    /**
     * POST: Collect repayment via the "Collect Repayment" modal
     */
    public function collect(Request $request, int $id)
    {
        $loan = Loan::findOrFail($id);

        $validated = $request->validate([
            'amount_collected'       => 'required|numeric|min:1',
            'payment_mode'           => 'required|in:cash,upi_qr,nach,neft,rtgs',
            'collected_by'           => 'required|string|max:100',
            'collection_date'        => 'required|date',
            'peer_payer_customer_id' => 'nullable|exists:customers,id',
        ]);

        $summary = $this->waterfall->apply(
            loan: $loan,
            amountCollected: (float) $validated['amount_collected'],
            paymentMode: $validated['payment_mode'],
            collectedBy: $validated['collected_by'],
            collectionDate: $validated['collection_date'],
            peerPayerCustomerId: $validated['peer_payer_customer_id'] ?? null,
        );

        return redirect()
            ->route('lms.loans.show', $id)
            ->with('success', "Payment of ₹" . number_format($validated['amount_collected'], 2) .
                " processed. Principal: ₹" . number_format($summary['principal_applied'], 2) .
                " | Interest: ₹" . number_format($summary['interest_applied'], 2) .
                " | Penal+GST: ₹" . number_format($summary['penal_applied'] + $summary['gst_applied'], 2));
    }
}
