<?php

namespace App\Http\Controllers;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Group;
use App\Models\LoanApplication;
use App\Models\LoanDocument;
use App\Services\LoanOriginationService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LosController extends Controller
{
    public function __construct(
        private LoanOriginationService $losService
    ) {}

    // ── Agent: Apply for Loan ──────────────────────────────────────────

    /**
     * GET /los/apply — Show the multi-step application form
     */
    public function apply()
    {
        $customers = Customer::with('group.center')
            ->orderBy('full_name')
            ->get();

        return view('los.apply', compact('customers'));
    }

    /**
     * POST /los/apply — Submit a new loan application
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'         => 'required|exists:customers,id',
            'applied_amount'      => 'required|numeric|min:5000|max:300000',
            'annual_interest_rate'=> 'required|numeric|min:1|max:30',
            'tenure'              => 'required|integer|min:3|max:60',
            'repayment_frequency' => 'required|in:weekly,monthly',
            'purpose'             => 'nullable|string|max:255',
            // Document uploads (optional file names for MVP)
            'doc_types'           => 'nullable|array',
            'doc_types.*'         => 'in:aadhaar_card,pan_card,voter_id,bank_passbook,income_declaration',
            'doc_numbers'         => 'nullable|array',
            'doc_numbers.*'       => 'nullable|string|max:50',
            'doc_images'          => 'nullable|array',
            'doc_images.*'        => 'nullable|image|max:5120',
        ]);

        try {
            $application = $this->losService->submitApplication($validated, auth()->user());

            // Attach documents if provided
            if (!empty($validated['doc_types'])) {
                foreach ($validated['doc_types'] as $index => $docType) {
                    $docNumber = $validated['doc_numbers'][$index] ?? null;
                    $docImage = $request->file("doc_images.{$index}");

                    // Mask Aadhaar to XXXX-XXXX-NNNN format
                    if ($docType === 'aadhaar_card' && $docNumber && strlen($docNumber) >= 4) {
                        $docNumber = 'XXXX-XXXX-' . substr($docNumber, -4);
                    }

                    $filePath = 'documents/dummy_' . $docType . '.pdf';
                    if ($docImage) {
                        $path = $docImage->store('documents', 'public');
                        $filePath = 'storage/' . $path;
                    }

                    LoanDocument::create([
                        'loan_application_id' => $application->id,
                        'document_type'       => $docType,
                        'document_number'     => $docNumber,
                        'file_path'           => $filePath,
                        'verification_status' => 'pending',
                    ]);
                }
            }

            return redirect()
                ->route('los.my-applications')
                ->with('success', "Application {$application->application_no} submitted successfully!");
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    // ── Agent: Centers and Groups ──────────────────────────────────────

    /**
     * GET /los/center — Show all centers
     */
    public function center()
    {
        $centers = Center::with('groups')->get();
        return view('los.group.center', compact('centers'));
    }

    /**
     * POST /los/center — Store a new center
     */
    public function storeCenter(Request $request)
    {
        $validated = $request->validate([
            'center_name'   => 'required|string|max:255',
            'center_code'   => 'required|string|max:50|unique:centers,center_code',
            'branch_name'   => 'required|string|max:255',
            'meeting_day'   => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'meeting_time'  => 'required|date_format:H:i',
            'field_officer' => 'required|string|max:255',
        ]);

        Center::create($validated);

        return redirect()->route('los.center')->with('success', 'Center created successfully!');
    }

    /**
     * PUT /los/center/{id} — Update an existing center
     */
    public function updateCenter(Request $request, int $id)
    {
        $center = Center::findOrFail($id);

        $validated = $request->validate([
            'center_name'   => 'required|string|max:255',
            'center_code'   => 'required|string|max:50|unique:centers,center_code,' . $id,
            'branch_name'   => 'required|string|max:255',
            'meeting_day'   => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'meeting_time'  => 'required|date_format:H:i',
            'field_officer' => 'required|string|max:255',
        ]);

        $center->update($validated);

        return redirect()->route('los.center')->with('success', "Center '{$center->center_name}' updated successfully!");
    }

    /**
     * DELETE /los/center/{id} — Delete a center
     */
    public function destroyCenter(int $id)
    {
        $center = Center::withCount('groups')->findOrFail($id);

        if ($center->groups_count > 0) {
            return redirect()->route('los.center')->with('error', "Cannot delete center '{$center->center_name}' because it has {$center->groups_count} group(s) assigned to it.");
        }

        $centerName = $center->center_name;
        $center->delete();

        return redirect()->route('los.center')->with('success', "Center '{$centerName}' deleted successfully!");
    }

    /**
     * GET /los/group — Show all groups
     */
    public function group()
    {
        $groups = Group::with(['center', 'customers'])->get();
        $centers = Center::orderBy('center_name')->get();
        return view('los.group.group', compact('groups', 'centers'));
    }

    /**
     * POST /los/group — Store a new group
     */
    public function storeGroup(Request $request)
    {
        $validated = $request->validate([
            'center_id'         => 'required|exists:centers,id',
            'group_name'        => 'required|string|max:255',
            'group_leader_name' => 'required|string|max:255',
        ]);

        Group::create($validated);

        return redirect()->route('los.group')->with('success', 'Group created successfully!');
    }

    /**
     * PUT /los/group/{id} — Update an existing group
     */
    public function updateGroup(Request $request, int $id)
    {
        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'center_id'         => 'required|exists:centers,id',
            'group_name'        => 'required|string|max:255',
            'group_leader_name' => 'required|string|max:255',
        ]);

        $group->update($validated);

        return redirect()->route('los.group')->with('success', "Group '{$group->group_name}' updated successfully!");
    }

    /**
     * DELETE /los/group/{id} — Delete a group
     */
    public function destroyGroup(int $id)
    {
        $group = Group::withCount('customers')->findOrFail($id);

        if ($group->customers_count > 0) {
            return redirect()->route('los.group')->with('error', "Cannot delete group '{$group->group_name}' because it has {$group->customers_count} customer(s) enrolled.");
        }

        $groupName = $group->group_name;
        $group->delete();

        return redirect()->route('los.group')->with('success', "Group '{$groupName}' deleted successfully!");
    }

    // ── Agent: Members (Borrowers / Customers) ──────────────────────────

    /**
     * GET /los/member — Show all members
     */
    public function member()
    {
        $members = Customer::with(['group.center', 'loans'])->orderBy('full_name')->get();
        $groups  = Group::with('center')->orderBy('group_name')->get();
        return view('los.group.member', compact('members', 'groups'));
    }

    /**
     * POST /los/member — Store a new member
     */
    public function storeMember(Request $request)
    {
        $validated = $request->validate([
            'group_id'                 => 'required|exists:groups,id',
            'customer_code'            => 'required|string|max:50|unique:customers,customer_code',
            'full_name'                => 'required|string|max:255',
            'phone'                    => 'required|string|max:15',
            'gender'                   => 'required|in:female,male,other',
            'aadhaar_last4'            => 'required|string|size:4',
            'pan_number'               => 'nullable|string|max:10',
            'address'                  => 'required|string|max:255',
            'district'                 => 'required|string|max:100',
            'state'                    => 'required|string|max:100',
            'pincode'                  => 'required|string|max:10',
            'annual_household_income'  => 'required|numeric|min:0',
            'monthly_debt_obligations' => 'required|numeric|min:0',
            'bank_account_no'          => 'nullable|string|max:30',
            'ifsc_code'                => 'nullable|string|max:15',
        ]);

        Customer::create($validated);

        return redirect()->route('los.member')->with('success', 'Member registered successfully!');
    }

    /**
     * PUT /los/member/{id} — Update an existing member
     */
    public function updateMember(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'group_id'                 => 'required|exists:groups,id',
            'customer_code'            => 'required|string|max:50|unique:customers,customer_code,' . $id,
            'full_name'                => 'required|string|max:255',
            'phone'                    => 'required|string|max:15',
            'gender'                   => 'required|in:female,male,other',
            'aadhaar_last4'            => 'required|string|size:4',
            'pan_number'               => 'nullable|string|max:10',
            'address'                  => 'required|string|max:255',
            'district'                 => 'required|string|max:100',
            'state'                    => 'required|string|max:100',
            'pincode'                  => 'required|string|max:10',
            'annual_household_income'  => 'required|numeric|min:0',
            'monthly_debt_obligations' => 'required|numeric|min:0',
            'bank_account_no'          => 'nullable|string|max:30',
            'ifsc_code'                => 'nullable|string|max:15',
        ]);

        $customer->update($validated);

        return redirect()->route('los.member')->with('success', "Member '{$customer->full_name}' updated successfully!");
    }

    /**
     * DELETE /los/member/{id} — Delete a member
     */
    public function destroyMember(int $id)
    {
        $customer = Customer::withCount('loans')->findOrFail($id);

        if ($customer->loans_count > 0) {
            return redirect()->route('los.member')->with('error', "Cannot delete member '{$customer->full_name}' because they have active loan records.");
        }

        $name = $customer->full_name;
        $customer->delete();

        return redirect()->route('los.member')->with('success', "Member '{$name}' deleted successfully!");
    }

    // ── Agent: My Applications ─────────────────────────────────────────

    /**
     * GET /los/my-applications — Agent's own applications
     */
    public function myApplications()
    {
        $applications = LoanApplication::with(['customer.group.center'])
            ->where('agent_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total'     => $applications->count(),
            'submitted' => $applications->where('stage', 'submitted')->count(),
            'approved'  => $applications->where('stage', 'approved')->count(),
            'rejected'  => $applications->where('stage', 'rejected')->count(),
        ];

        return view('los.my_applications', compact('applications', 'stats'));
    }

    // ── Manager: Pipeline Dashboard ────────────────────────────────────

    /**
     * GET /los/pipeline — Manager pipeline view
     */
    public function pipeline(Request $request)
    {
        $stageFilter = $request->input('stage', 'all');

        $query = LoanApplication::with(['customer.group.center', 'agent', 'documents'])
            ->orderByDesc('created_at');

        if ($stageFilter !== 'all') {
            $query->where('stage', $stageFilter);
        }

        $applications = $query->paginate(20)->withQueryString();

        $stageCounts = LoanApplication::selectRaw('stage, COUNT(*) as cnt')
            ->groupBy('stage')
            ->pluck('cnt', 'stage');

        return view('los.pipeline', compact('applications', 'stageFilter', 'stageCounts'));
    }

    // ── Manager: Review Docket ─────────────────────────────────────────

    /**
     * GET /los/applications/{id}/review — Full review docket
     */
    public function review(int $id)
    {
        $application = LoanApplication::with([
            'customer.group.center',
            'customer.loans.repaymentSchedules',
            'agent',
            'documents',
        ])->findOrFail($id);

        $customer = $application->customer;

        // Calculate projected EMI for FOIR gauge
        $annualRate   = (float) $application->annual_interest_rate / 100;
        $frequency    = $application->repayment_frequency;
        $periodicRate = $frequency === 'weekly' ? $annualRate / 52 : $annualRate / 12;

        $amortService = app(\App\Services\AmortizationService::class);
        $emi = $amortService->calculateEmi(
            (float) $application->applied_amount,
            $periodicRate,
            $application->tenure
        );
        $monthlyEmi   = $frequency === 'weekly' ? $emi * 4.33 : $emi;
        $monthlyIncome = $customer->annual_household_income / 12;
        $projectedFoir = $monthlyIncome > 0
            ? round((($customer->monthly_debt_obligations + $monthlyEmi) / $monthlyIncome) * 100, 2)
            : 0;

        // Existing debt summary
        $existingLoans = $customer->loans()->where('status', 'active')->get();

        return view('los.review', compact(
            'application', 'customer', 'monthlyEmi', 'monthlyIncome',
            'projectedFoir', 'existingLoans'
        ));
    }

    // ── Manager: Approve Application ───────────────────────────────────

    /**
     * POST /los/applications/{id}/approve
     */
    public function approve(Request $request, int $id)
    {
        $application = LoanApplication::findOrFail($id);
        $notes       = $request->input('review_notes');

        try {
            $loan = $this->losService->approveApplication($application, auth()->user(), $notes);

            return redirect()
                ->route('los.pipeline')
                ->with('success', "Application {$application->application_no} approved! Loan {$loan->loan_account_no} created and disbursed.");
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── Manager: Reject Application ────────────────────────────────────

    /**
     * POST /los/applications/{id}/reject
     */
    public function reject(Request $request, int $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $application = LoanApplication::findOrFail($id);

        try {
            $this->losService->rejectApplication(
                $application, auth()->user(), $request->rejection_reason
            );

            return redirect()
                ->route('los.pipeline')
                ->with('success', "Application {$application->application_no} rejected.");
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── Manager: Verify Document ───────────────────────────────────────

    /**
     * POST /los/documents/{id}/verify — Mark a document as verified
     */
    public function verifyDocument(Request $request, int $id)
    {
        $doc    = LoanDocument::findOrFail($id);
        $status = $request->input('status', 'verified');

        $doc->update([
            'verification_status' => $status,
            'verified_by'         => auth()->id(),
        ]);

        return back()->with('success', $doc->type_label . ' marked as ' . $status . '.');
    }
}
