<?php

namespace App\Http\Controllers;

use App\Models\RecoveryCase;
use App\Models\RecoveryCallLog;
use App\Models\User;
use App\Services\LoanOriginationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RecoveryController extends Controller
{
    public function __construct(
        private LoanOriginationService $losService
    ) {}

    /**
     * Delinquency & Calling Console — bucket filters + call/visit log
     *
     * Role-based filtering:
     *   - Agent: sees only cases assigned to them
     *   - Manager/Admin: sees all cases + agent assignment
     */
    public function console(Request $request)
    {
        $bucket = $request->input('bucket', 'all');
        $user   = auth()->user();

        $query = RecoveryCase::with([
            'loan.customer.group.center',
            'callLogs',
            'assignedOfficer',
        ])->orderByDesc('dpd');

        // Agent can only see their assigned cases
        if ($user->isAgent()) {
            $query->where('assigned_officer_id', $user->id);
        }

        if ($bucket !== 'all') {
            $query->where('asset_classification', $bucket);
        }

        $cases = $query->paginate(20)->withQueryString();

        // Bucket counts (scoped for agents)
        $bucketQuery = RecoveryCase::selectRaw('asset_classification, COUNT(*) as cnt');
        if ($user->isAgent()) {
            $bucketQuery->where('assigned_officer_id', $user->id);
        }
        $bucketCounts = $bucketQuery->groupBy('asset_classification')
            ->pluck('cnt', 'asset_classification');

        // Agents available for assignment (manager/admin only)
        $agents = collect();
        if ($user->hasRole('manager', 'admin')) {
            $agents = User::where('role', 'agent')->orderBy('name')->get();
        }

        return view('recovery.console', compact('cases', 'bucket', 'bucketCounts', 'agents'));
    }

    /**
     * POST: Log a call or field visit
     * RBI Fair Practices Code: strictly reject contacts outside 08:00–19:00
     */
    public function logContact(Request $request)
    {
        $validated = $request->validate([
            'recovery_case_id' => 'required|exists:recovery_cases,id',
            'interaction_type' => 'required|in:telecalling,field_visit,email,whatsapp',
            'contact_time'     => 'required|date_format:Y-m-d\TH:i',
            'disposition'      => 'required|in:PTP,Broken_PTP,Dispute,Absconding,Crop_Failure,Medical_Emergency,RNR,Paid',
            'ptp_date'         => 'nullable|date|after_or_equal:today',
            'ptp_amount'       => 'nullable|numeric|min:1',
            'notes'            => 'nullable|string|max:1000',
        ]);

        // ── RBI Fair Practices Code: contact-hours enforcement (server-side) ──
        $contactTime = Carbon::parse($validated['contact_time']);
        $hour        = (int) $contactTime->format('H');
        $minute      = (int) $contactTime->format('i');
        $timeInMins  = $hour * 60 + $minute;

        if ($timeInMins < (8 * 60) || $timeInMins > (19 * 60)) {
            return back()
                ->withInput()
                ->withErrors(['contact_time' =>
                    'RBI Fair Practices Code: Contacts must be between 08:00 AM and 07:00 PM only.'
                ]);
        }

        // Set logged_by from authenticated user
        $validated['logged_by'] = auth()->user()->name;

        $log = RecoveryCallLog::create($validated);

        // Update last_contacted_at on the recovery case
        $log->recoveryCase->update(['last_contacted_at' => $contactTime]);

        return redirect()
            ->route('recovery.console')
            ->with('success', ucfirst($validated['interaction_type']) . ' logged successfully.');
    }

    /**
     * POST: Assign/reassign a recovery case to an agent (Manager/Admin only)
     */
    public function assignAgent(Request $request)
    {
        $validated = $request->validate([
            'recovery_case_id' => 'required|exists:recovery_cases,id',
            'agent_id'         => 'required|exists:users,id',
        ]);

        $case  = RecoveryCase::findOrFail($validated['recovery_case_id']);
        $agent = User::findOrFail($validated['agent_id']);

        $this->losService->assignRecoveryAgent($case, $agent, auth()->user());

        return redirect()
            ->route('recovery.console')
            ->with('success', "Recovery case reassigned to {$agent->name}.");
    }
}
