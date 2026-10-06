<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\RecoveryCase;
use App\Models\RepaymentSchedule;
use App\Models\SavingsAccount;
use App\Models\SavingsSchedule;
use App\Services\AmortizationService;
use App\Services\SavingsInterestCalculator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * sfb:update-dpd
 *
 * Daily batch command that:
 *  1. Scans all active loans for overdue installments
 *  2. Calculates Days Past Due (DPD)
 *  3. Classifies assets: SMA-0, SMA-1, SMA-2, NPA (RBI IRACP norms)
 *  4. Levies non-capitalised penal charges (₹100 + 18% GST) per bounced installment
 *  5. Updates recovery_cases table (upsert)
 *  6. Scans savings_schedules to mark missed RD deposits
 *  7. Triggers SavingsInterestCalculator to update total_interest_accrued on RD accounts
 *
 * Run: php artisan sfb:update-dpd
 */
class UpdateDpd extends Command
{
    protected $signature   = 'sfb:update-dpd {--dry-run : Preview changes without saving}';
    protected $description = 'Daily DPD recalculation, SMA/NPA classification, penal charge levy, and savings interest update (RBI compliant)';

    public function handle(AmortizationService $amortization, SavingsInterestCalculator $interestCalc): int
    {
        $today     = Carbon::today();
        $dryRun    = $this->option('dry-run');
        $processed = 0;
        $penalised = 0;

        $this->info("📅 Running DPD + Savings update for " . $today->format('d-M-Y') . ($dryRun ? ' [DRY RUN]' : '') . "\n");

        // ── PHASE 1: Loan DPD Recalculation ─────────────────────────────────
        $this->info("🏦 Phase 1: Loan DPD & NPA Classification");

        $loans = Loan::with(['repaymentSchedules', 'recoveryCase'])
            ->whereIn('status', ['active', 'npa'])
            ->get();

        $bar = $this->output->createProgressBar($loans->count());
        $bar->start();

        foreach ($loans as $loan) {
            $bar->advance();

            $overdueSchedules = $loan->repaymentSchedules
                ->whereIn('status', ['overdue', 'partial', 'pending'])
                ->where('due_date', '<', $today->toDateString())
                ->sortBy('installment_no');

            if ($overdueSchedules->isEmpty()) {
                // Loan is current
                if (!$dryRun && $loan->recoveryCase) {
                    $loan->recoveryCase->update([
                        'dpd'                     => 0,
                        'asset_classification'    => 'Standard',
                        'total_overdue_principal' => 0,
                        'total_overdue_interest'  => 0,
                        'total_penal_charges'     => 0,
                        'total_outstanding'       => 0,
                    ]);
                }
                $processed++;
                continue;
            }

            // DPD = days since oldest overdue installment's due date
            $oldestDueDate = Carbon::parse($overdueSchedules->first()->due_date);
            $dpd           = max(0, (int) $today->diffInDays($oldestDueDate, true));
            $classification = $this->classify($dpd);

            $totalOverduePrincipal = 0;
            $totalOverdueInterest  = 0;
            $totalPenal            = 0;

            foreach ($overdueSchedules as $schedule) {
                if (!$dryRun && $schedule->status === 'pending') {
                    $schedule->update(['status' => 'overdue']);
                }

                $totalOverduePrincipal += (float)$schedule->principal_due - (float)$schedule->principal_paid;
                $totalOverdueInterest  += (float)$schedule->interest_due  - (float)$schedule->interest_paid;

                // Levy penal charge once per bounced installment (RBI: NOT capitalised)
                if ((float)$schedule->penal_charges_due === 0.0) {
                    $penal = $amortization->penalChargeForBounce();
                    $totalPenal += $penal['total'];

                    if (!$dryRun) {
                        $newTotal = (float)$schedule->total_due + $penal['total'];
                        $schedule->update([
                            'penal_charges_due' => $penal['penal'],
                            'penal_gst_due'     => $penal['gst'],
                            'total_due'         => $newTotal,
                        ]);
                        $penalised++;
                    }
                } else {
                    $totalPenal += (float)$schedule->penal_charges_due + (float)$schedule->penal_gst_due;
                }
            }

            $totalOutstanding = $totalOverduePrincipal + $totalOverdueInterest + $totalPenal;

            if (!$dryRun) {
                RecoveryCase::updateOrCreate(
                    ['loan_id' => $loan->id],
                    [
                        'dpd'                     => $dpd,
                        'asset_classification'    => $classification,
                        'total_overdue_principal' => round($totalOverduePrincipal, 2),
                        'total_overdue_interest'  => round($totalOverdueInterest, 2),
                        'total_penal_charges'     => round($totalPenal, 2),
                        'total_outstanding'       => round($totalOutstanding, 2),
                    ]
                );

                // Escalate to NPA if DPD >= 91
                if ($dpd >= 91 && $loan->status === 'active') {
                    $loan->update(['status' => 'npa']);
                }
            }

            if ($dryRun) {
                $this->line(
                    "\n  Loan #{$loan->loan_account_no} | DPD: {$dpd} | " .
                    "Class: {$classification} | Overdue: ₹" . number_format($totalOutstanding, 2)
                );
            }

            $processed++;
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Loans processed: {$processed}");
        $this->info("⚠️  Penal charges levied (new): {$penalised} installments");
        $this->info("💡 Penal = ₹100 + 18% GST = ₹118 per bounce (non-capitalised per RBI)\n");

        // ── PHASE 2: Savings Schedules — Mark Missed Deposits ────────────────
        $this->info("💰 Phase 2: Savings — Marking Missed RD Deposits");

        $missedCount = 0;
        $overdueRdSchedules = SavingsSchedule::where('status', 'pending')
            ->where('due_date', '<', $today->toDateString())
            ->get();

        foreach ($overdueRdSchedules as $rdSchedule) {
            $missedCount++;
            if (!$dryRun) {
                $rdSchedule->update(['status' => 'missed']);
            }
        }
        $this->info("  → {$missedCount} missed RD deposits marked" . ($dryRun ? ' [DRY RUN]' : ''));

        // ── PHASE 3: Savings Interest Update ────────────────────────────────
        $this->info("\n📈 Phase 3: Updating RD Accrued Interest");

        if (!$dryRun) {
            $interestResult = $interestCalc->updateAllAccruedInterest();
            $this->info(
                "  → Updated {$interestResult['accounts_updated']} RD accounts | " .
                "Total Accrued Interest: ₹" . number_format($interestResult['total_interest'], 2)
            );
        } else {
            $activeRds = SavingsAccount::where('status', 'active')->count();
            $this->info("  → Would update {$activeRds} active RD accounts [DRY RUN]");
        }

        $this->newLine();
        $this->info("🎯 sfb:update-dpd completed successfully.");

        return Command::SUCCESS;
    }

    /**
     * RBI IRACP DPD classification rules.
     */
    private function classify(int $dpd): string
    {
        return match (true) {
            $dpd <= 0   => 'Standard',
            $dpd <= 30  => 'SMA-0',
            $dpd <= 60  => 'SMA-1',
            $dpd <= 90  => 'SMA-2',
            $dpd <= 365 => 'NPA_SubStandard',
            default     => 'Doubtful',
        };
    }
}
