<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\RecoveryCase;
use App\Models\RepaymentSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * LoanOriginationService
 *
 * Handles the complete lifecycle of a loan application:
 * submission → review → approval/rejection → disbursement.
 * Enforces RBI Microfinance Directions (income cap, FOIR ≤ 50%).
 */
class LoanOriginationService
{
    public function __construct(
        private AmortizationService $amortization
    ) {}

    /**
     * Submit a new loan application with RBI pre-validation.
     *
     * @throws InvalidArgumentException on RBI rule violation
     */
    public function submitApplication(array $data, User $agent): LoanApplication
    {
        $customer = Customer::findOrFail($data['customer_id']);

        // ── RBI Rule 1: Household income cap ──
        if ($customer->annual_household_income > AmortizationService::RBI_INCOME_CAP) {
            throw new InvalidArgumentException(
                'Customer annual household income ₹' .
                number_format($customer->annual_household_income, 2) .
                ' exceeds RBI microfinance cap of ₹3,00,000.'
            );
        }

        // ── RBI Rule 2: Project EMI and check FOIR ──
        $principal  = (float) $data['applied_amount'];
        $annualRate = (float) $data['annual_interest_rate'] / 100;
        $tenure     = (int) $data['tenure'];
        $frequency  = $data['repayment_frequency'] ?? 'monthly';

        $periodicRate = $frequency === 'weekly'
            ? $annualRate / 52
            : $annualRate / 12;

        $emi = $this->amortization->calculateEmi($principal, $periodicRate, $tenure);

        // Convert to monthly equivalent for FOIR
        $monthlyEmi = $frequency === 'weekly' ? $emi * 4.33 : $emi;
        $monthlyIncome = $customer->annual_household_income / 12;
        $newObligation = $customer->monthly_debt_obligations + $monthlyEmi;

        if ($monthlyIncome > 0) {
            $foirPct = ($newObligation / $monthlyIncome) * 100;
            if ($foirPct > AmortizationService::RBI_FOIR_MAX_PCT) {
                throw new InvalidArgumentException(
                    sprintf(
                        'FOIR %.2f%% (projected monthly obligation ₹%.2f / monthly income ₹%.2f) exceeds RBI 50%% limit. Projected EMI: ₹%.2f',
                        $foirPct, $newObligation, $monthlyIncome, $monthlyEmi
                    )
                );
            }
        }

        // ── Generate unique application number ──
        $year   = Carbon::now()->format('Y');
        $lastNo = LoanApplication::where('application_no', 'LIKE', "APP-{$year}-%")
            ->orderByDesc('id')
            ->value('application_no');

        $nextSeq = 1;
        if ($lastNo) {
            $parts   = explode('-', $lastNo);
            $nextSeq = ((int) end($parts)) + 1;
        }
        $applicationNo = 'APP-' . $year . '-' . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);

        return LoanApplication::create([
            'application_no'      => $applicationNo,
            'customer_id'         => $customer->id,
            'agent_id'            => $agent->id,
            'applied_amount'      => $principal,
            'annual_interest_rate'=> $data['annual_interest_rate'],
            'tenure'              => $tenure,
            'repayment_frequency' => $frequency,
            'purpose'             => $data['purpose'] ?? null,
            'stage'               => 'submitted',
        ]);
    }

    /**
     * Approve a loan application: create loan, schedule, recovery case.
     * Wrapped in DB::transaction for atomicity.
     */
    public function approveApplication(LoanApplication $application, User $manager, ?string $notes = null): Loan
    {
        if ($application->stage === 'approved') {
            throw new InvalidArgumentException('Application is already approved.');
        }

        if ($application->stage === 'rejected') {
            throw new InvalidArgumentException('Cannot approve a rejected application.');
        }

        return DB::transaction(function () use ($application, $manager, $notes) {
            // 1. Update application stage
            $application->update([
                'stage'        => 'approved',
                'reviewed_by'  => $manager->id,
                'review_notes' => $notes,
                'approved_at'  => Carbon::now(),
            ]);

            // 2. Generate loan account number
            $year   = Carbon::now()->format('Y');
            $loanCount = Loan::where('loan_account_no', 'LIKE', "SFB{$year}%")->count();
            $loanAccountNo = 'SFB' . $year . str_pad($loanCount + 1, 5, '0', STR_PAD_LEFT);

            // 3. Create active loan record
            $disbursementDate = Carbon::today();
            $maturityDate     = $disbursementDate->copy()->addMonths($application->tenure);

            $loan = Loan::create([
                'customer_id'          => $application->customer_id,
                'loan_application_id'  => $application->id,
                'loan_account_no'      => $loanAccountNo,
                'principal_amount'     => $application->applied_amount,
                'annual_interest_rate' => $application->annual_interest_rate,
                'tenure'               => $application->tenure,
                'repayment_frequency'  => $application->repayment_frequency,
                'interest_type'        => 'reducing',
                'disbursement_date'    => $disbursementDate,
                'maturity_date'        => $maturityDate,
                'status'               => 'active',
                'disbursement_mode'    => 'cash',
                'processing_fee'       => round($application->applied_amount * 0.01, 2),
                'processing_fee_gst'   => round($application->applied_amount * 0.01 * 0.18, 2),
                'purpose'              => $application->purpose,
            ]);

            // 4. Generate repayment schedule
            $loan->load('customer');
            $scheduleRows = $this->amortization->generate($loan, false);

            foreach ($scheduleRows as $row) {
                RepaymentSchedule::create(array_merge(['loan_id' => $loan->id], $row));
            }

            // 5. Create initial recovery case (Standard, 0 DPD)
            RecoveryCase::create([
                'loan_id'                => $loan->id,
                'dpd'                    => 0,
                'asset_classification'   => 'Standard',
                'total_overdue_principal' => 0,
                'total_overdue_interest'  => 0,
                'total_penal_charges'     => 0,
                'total_outstanding'       => 0,
                'assigned_officer'        => null,
                'assigned_officer_id'     => null,
            ]);

            // 6. Update customer monthly debt obligations with new EMI
            $frequency   = $application->repayment_frequency;
            $annualRate  = (float) $application->annual_interest_rate / 100;
            $periodicRate = $frequency === 'weekly' ? $annualRate / 52 : $annualRate / 12;
            $emi = $this->amortization->calculateEmi(
                (float) $application->applied_amount,
                $periodicRate,
                $application->tenure
            );
            $monthlyEmi = $frequency === 'weekly' ? $emi * 4.33 : $emi;

            $customer = $application->customer;
            $customer->update([
                'monthly_debt_obligations' => $customer->monthly_debt_obligations + $monthlyEmi,
            ]);

            return $loan;
        });
    }

    /**
     * Reject a loan application.
     */
    public function rejectApplication(LoanApplication $application, User $manager, string $reason): void
    {
        if ($application->stage === 'approved') {
            throw new InvalidArgumentException('Cannot reject an already approved application.');
        }

        $application->update([
            'stage'            => 'rejected',
            'reviewed_by'      => $manager->id,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Assign/reassign a recovery case to a field agent.
     */
    public function assignRecoveryAgent(RecoveryCase $case, User $agent, User $manager): void
    {
        $previousOfficer = $case->assigned_officer ?? 'Unassigned';

        $case->update([
            'assigned_officer_id' => $agent->id,
            'assigned_officer'    => $agent->name,
            'remarks'             => $case->remarks . "\n[" . Carbon::now()->format('d-M-Y H:i') .
                "] Reassigned from '{$previousOfficer}' to '{$agent->name}' by {$manager->name}.",
        ]);
    }
}
