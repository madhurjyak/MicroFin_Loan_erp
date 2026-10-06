<?php

namespace App\Http\Controllers;

use App\Models\CollectionTransaction;
use App\Models\Customer;
use App\Models\SavingsAccount;
use App\Models\SavingsSchedule;
use App\Services\CollectionLedgerService;
use App\Services\SavingsOriginationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavingsController extends Controller
{
    public function __construct(
        private SavingsOriginationService $originationService,
        private CollectionLedgerService   $ledgerService
    ) {}

    /**
     * List all RD accounts (paginated).
     */
    public function index(Request $request)
    {
        $query = SavingsAccount::with(['customer.group.center'])
            ->withCount(['schedules as total_installments'])
            ->withCount(['schedules as paid_installments' => fn($q) => $q->where('status', 'paid')])
            ->withCount(['schedules as missed_installments' => fn($q) => $q->where('status', 'missed')]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('full_name', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_code', 'like', '%' . $request->search . '%');
            })->orWhere('account_no', 'like', '%' . $request->search . '%');
        }

        $accounts = $query->latest()->paginate(25);

        // Summary stats
        $totalSavingsMobilized = SavingsAccount::sum('total_principal_collected');
        $totalInterestAccrued  = SavingsAccount::sum('total_interest_accrued');
        $totalSavingsAccounts  = SavingsAccount::where('status', 'active')->count();
        $activeCount           = $totalSavingsAccounts;
        $maturingThisMonth = SavingsAccount::where('status', 'active')
            ->whereMonth('maturity_date', now()->month)
            ->whereYear('maturity_date', now()->year)
            ->count();

        return view('sms.index', compact(
            'accounts', 'totalSavingsMobilized', 'totalInterestAccrued', 'totalSavingsAccounts', 'activeCount', 'maturingThisMonth'
        ));
    }

    /**
     * Show an individual RD account ledger.
     */
    public function show(int $id)
    {
        $account = SavingsAccount::with([
            'customer.group.center',
            'schedules',
        ])->findOrFail($id);

        $transactions = CollectionTransaction::where('savings_account_id', $account->id)
            ->orderByDesc('collection_date')
            ->limit(50)
            ->get();

        $paidInstallments    = $account->schedules->where('status', 'paid')->count();
        $pendingInstallments = $account->schedules->whereIn('status', ['pending', 'partial'])->count();
        $missedInstallments  = $account->schedules->where('status', 'missed')->count();

        return view('sms.show', compact(
            'account', 'transactions',
            'paidInstallments', 'pendingInstallments', 'missedInstallments'
        ));
    }

    /**
     * Show the form to open a new RD account.
     */
    public function create()
    {
        $customers = Customer::with('group.center')
            ->orderBy('full_name')
            ->get();
            
        $centers = \App\Models\Center::orderBy('center_name')->get();
        $groups = \App\Models\Group::orderBy('group_name')->get();

        return view('sms.create', compact('customers', 'centers', 'groups'));
    }

    /**
     * Store a new RD account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'deposit_amount' => 'required|numeric|min:50',
            'interest_rate'  => 'required|numeric|min:1|max:12',
            'tenure'         => 'required|integer|min:6|max:60',
            'frequency'      => 'required|in:weekly,monthly',
            'opening_date'   => 'required|date',
        ]);

        try {
            $customer = Customer::findOrFail($validated['customer_id']);
            $account  = $this->originationService->openAccount($customer, $validated);

            return redirect()
                ->route('sms.savings.show', $account->id)
                ->with('success', "RD Account {$account->account_no} opened successfully for {$customer->full_name}.");
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Collect a deposit into an RD account.
     */
    public function collect(Request $request, int $id)
    {
        $account = SavingsAccount::findOrFail($id);

        $validated = $request->validate([
            'amount'          => 'required|numeric|min:1',
            'payment_mode'    => 'required|in:cash,upi_qr,nach',
            'collection_date' => 'required|date',
            'collected_by'    => 'required|string|max:100',
        ]);

        try {
            $result = $this->ledgerService->applySavingsPayment(
                $account,
                (float) $validated['amount'],
                $validated['payment_mode'],
                $validated['collected_by'],
                $validated['collection_date']
            );

            return back()->with('success', sprintf(
                'Deposit of ₹%s recorded. Receipt: %s | Installments cleared: %s',
                number_format($result['amount_deposited'], 2),
                $result['receipt_no'],
                count($result['schedules_cleared']) ?: 'Partial'
            ));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Collection failed: ' . $e->getMessage()]);
        }
    }

    /**
     * API: Return savings account data for AJAX (used in CDS).
     */
    public function apiAccountData(int $customerId)
    {
        $account = SavingsAccount::where('customer_id', $customerId)
            ->where('status', 'active')
            ->with(['schedules' => fn($q) => $q->whereIn('status', ['pending', 'partial', 'missed'])->orderBy('installment_no')->limit(3)])
            ->first();

        if (!$account) {
            return response()->json(['account' => null]);
        }

        return response()->json([
            'account' => [
                'id'                       => $account->id,
                'account_no'               => $account->account_no,
                'deposit_amount'           => (float) $account->deposit_amount,
                'frequency'                => $account->frequency,
                'total_principal_collected'=> (float) $account->total_principal_collected,
                'total_interest_accrued'   => (float) $account->total_interest_accrued,
                'next_due'                 => $account->schedules->first()?->due_date?->format('d-M-Y'),
                'next_due_amount'          => (float) ($account->schedules->first()?->amount_expected ?? 0),
            ],
        ]);
    }
}
