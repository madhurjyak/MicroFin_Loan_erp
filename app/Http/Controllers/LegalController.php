<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\OtsProposal;
use App\Models\StatutoryNotice;
use Illuminate\Http\Request;

class LegalController extends Controller
{
    /**
     * Indian Statutory Legal & OTS Hub
     */
    public function index(Request $request)
    {
        $notices = StatutoryNotice::with('loan.customer')
            ->latest('dispatch_date')
            ->paginate(15, ['*'], 'notice_page');

        $otsList = OtsProposal::with('loan.customer')
            ->latest()
            ->paginate(10, ['*'], 'ots_page');

        // Loans eligible for notices (NPA/SMA-2 without notice yet)
        $eligibleLoans = Loan::with('customer')
            ->whereIn('status', ['npa', 'active'])
            ->whereHas('recoveryCase', fn ($q) => $q->where('dpd', '>=', 61))
            ->get();

        return view('recovery.legal', compact('notices', 'otsList', 'eligibleLoans'));
    }

    /**
     * OTS Calculator — AJAX-friendly endpoint
     */
    public function otsCalculate(Request $request)
    {
        $validated = $request->validate([
            'loan_id'         => 'required|exists:loans,id',
            'proposed_amount' => 'required|numeric|min:1',
        ]);

        $loan = Loan::with(['repaymentSchedules', 'recoveryCase'])->findOrFail($validated['loan_id']);

        $schedules = $loan->repaymentSchedules;

        $totalPrincipalOutstanding = $schedules->whereIn('status', ['overdue', 'pending', 'partial'])
            ->sum(fn ($s) => (float)$s->principal_due - (float)$s->principal_paid);

        $totalInterestOutstanding = $schedules->whereIn('status', ['overdue', 'pending', 'partial'])
            ->sum(fn ($s) => (float)$s->interest_due - (float)$s->interest_paid);

        $totalPenal = $schedules->sum(fn ($s) =>
            ((float)$s->penal_charges_due + (float)$s->penal_gst_due) -
            ((float)$s->penal_paid       + (float)$s->gst_paid));

        $totalOutstanding = $totalPrincipalOutstanding + $totalInterestOutstanding + $totalPenal;
        $proposed         = (float) $validated['proposed_amount'];
        $totalWaiver      = max(0, $totalOutstanding - $proposed);
        $haircutPct       = $totalOutstanding > 0
            ? round(($totalWaiver / $totalOutstanding) * 100, 2)
            : 0;

        // Waiver applied in sequence: penal+GST first, then interest, then principal
        $waiverPenalGst  = min($totalPenal, $totalWaiver);
        $remainingWaiver = max(0, $totalWaiver - $waiverPenalGst);
        $waiverInterest  = min($totalInterestOutstanding, $remainingWaiver);
        $waiverPrincipal = max(0, $totalWaiver - $waiverPenalGst - $waiverInterest);

        // Approval authority per haircut threshold
        $authority = match (true) {
            $haircutPct <= 20 => 'Branch_Manager',
            $haircutPct <= 40 => 'Regional_Credit_Committee',
            default           => 'Board',
        };

        if ($request->wantsJson()) {
            return response()->json([
                'total_outstanding'   => $totalOutstanding,
                'proposed_amount'     => $proposed,
                'total_waiver'        => $totalWaiver,
                'waiver_penal_gst'    => $waiverPenalGst,
                'waiver_interest'     => $waiverInterest,
                'waiver_principal'    => $waiverPrincipal,
                'haircut_pct'         => $haircutPct,
                'approval_authority'  => $authority,
            ]);
        }

        // Save proposal
        $proposal = OtsProposal::create([
            'loan_id'            => $loan->id,
            'total_outstanding'  => $totalOutstanding,
            'proposed_amount'    => $proposed,
            'waiver_penal_gst'   => $waiverPenalGst,
            'waiver_interest'    => $waiverInterest,
            'waiver_principal'   => $waiverPrincipal,
            'haircut_pct'        => $haircutPct,
            'approval_authority' => $authority,
            'status'             => 'pending',
        ]);

        return redirect()->route('recovery.legal')
            ->with('success', "OTS Proposal #{$proposal->id} created. Approval required from: " .
                str_replace('_', ' ', $authority) . " (Haircut: {$haircutPct}%)");
    }

    /**
     * Printable Notice Preview
     */
    public function noticePreview(int $id)
    {
        $notice = StatutoryNotice::with(['loan.customer.group.center'])->findOrFail($id);
        $loan   = $notice->loan;

        $template = match ($notice->notice_type) {
            'Sec_138_NI_Act'               => 'recovery.partials.notice_sec138',
            'Sec_25_PSSA_AutoDebit_Bounce' => 'recovery.partials.notice_sec25',
            default                        => 'recovery.partials.notice_recall',
        };

        return view($template, compact('notice', 'loan'));
    }
}
