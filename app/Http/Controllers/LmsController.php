<?php

namespace App\Http\Controllers;

use App\Models\Center;
use App\Models\Group;
use App\Models\Loan;
use App\Models\RepaymentSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LmsController extends Controller
{
    /**
     * Collection Day Sheet (CDS)
     * Shows group-wise member repayment status for a given center + meeting day.
     */
    public function cds(Request $request)
    {
        $centers = Center::orderBy('branch_name')->orderBy('center_name')->get();

        $selectedCenter  = null;
        $groups          = collect();
        $collectionDate  = $request->input('collection_date', Carbon::today()->toDateString());

        if ($request->filled('center_id')) {
            $selectedCenter = Center::with([
                'groups.customers.loans.repaymentSchedules' => function ($q) use ($collectionDate) {
                    $q->whereDate('due_date', $collectionDate)
                      ->orWhere(function ($q2) use ($collectionDate) {
                          $q2->where('due_date', '<', $collectionDate)
                             ->whereIn('status', ['overdue', 'partial']);
                      });
                },
            ])->findOrFail($request->center_id);

            $groups = $selectedCenter->groups;
        }

        return view('lms.cds', compact('centers', 'selectedCenter', 'groups', 'collectionDate'));
    }
}
