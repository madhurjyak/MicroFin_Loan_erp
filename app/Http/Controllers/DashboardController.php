<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\RecoveryCase;
use App\Models\RepaymentSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // ── Gross Loan Portfolio (GLP): sum of outstanding principal on active loans ──
        $glp = Loan::whereIn('loans.status', ['active', 'npa'])
            ->join('repayment_schedules', 'loans.id', '=', 'repayment_schedules.loan_id')
            ->sum(DB::raw('repayment_schedules.principal_due - repayment_schedules.principal_paid'));

        // ── Today's Kendra Collection Due ──
        $todayDue = RepaymentSchedule::whereDate('due_date', $today)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum(DB::raw('total_due - total_paid'));

        // ── PAR 30: Portfolio At Risk > 30 DPD ──
        $par30OutstandingPrincipal = RecoveryCase::where('dpd', '>', 30)
            ->sum('total_overdue_principal');

        $par30Pct = $glp > 0
            ? round(($par30OutstandingPrincipal / $glp) * 100, 2)
            : 0;

        // ── Gross NPA % ──
        $npaPrincipal = RecoveryCase::whereIn('asset_classification', ['NPA_SubStandard', 'Doubtful'])
            ->sum('total_overdue_principal');
        $grossNpaPct = $glp > 0
            ? round(($npaPrincipal / $glp) * 100, 2)
            : 0;

        // ── Collection Efficiency Ratio (CER): Amount Collected / Amount Due (last 30 days) ──
        $last30Due = RepaymentSchedule::where('due_date', '>=', $today->copy()->subDays(30))
            ->where('due_date', '<=', $today)
            ->sum('total_due');
        $last30Paid = RepaymentSchedule::where('due_date', '>=', $today->copy()->subDays(30))
            ->where('due_date', '<=', $today)
            ->sum('total_paid');
        $collectionEfficiency = $last30Due > 0
            ? round(($last30Paid / $last30Due) * 100, 2)
            : 0;

        // ── Bucket-wise counts for chart ──
        $buckets = RecoveryCase::selectRaw('asset_classification, COUNT(*) as count')
            ->groupBy('asset_classification')
            ->pluck('count', 'asset_classification')
            ->toArray();

        // ── Recent NPA loans for alert strip ──
        $npaLoans = Loan::with(['customer.group.center'])
            ->where('status', 'npa')
            ->latest()
            ->limit(5)
            ->get();

        // ── Active loan count ──
        $activeLoanCount = Loan::where('status', 'active')->count();
        $totalCustomers  = \App\Models\Customer::count();

        return view('dashboard.index', compact(
            'glp', 'todayDue', 'par30Pct', 'grossNpaPct',
            'collectionEfficiency', 'buckets', 'npaLoans',
            'activeLoanCount', 'totalCustomers', 'par30OutstandingPrincipal'
        ));
    }
}
