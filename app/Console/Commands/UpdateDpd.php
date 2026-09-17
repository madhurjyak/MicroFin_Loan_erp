<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\RecoveryCase;
use App\Models\RepaymentSchedule;
use App\Services\AmortizationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * sfb:update-dpd
 *
 * Daily batch command that:
 *  1. Scans all active loans for overdue installments
 *  2. Calculates Days Past Due (DPD)
 *  3. Classifies assets: SMA-0, SMA-1, SMA-2, NPA
 *  4. Levies non-capitalised penal charges (₹100 + 18% GST) for bounced installments
 *  5. Updates recovery_cases table
 *
 * Run: php artisan sfb:update-dpd
 */
class UpdateDpd extends Command
{
    protected $signature   = 'sfb:update-dpd {--dry-run : Preview changes without saving}';
    protected $description = 'Daily DPD recalculation, SMA/NPA classification, and penal charge levy (RBI compliant)';

    public function handle(AmortizationService $amortization): int
    {
        $today    = Carbon::today();
        $dryRun   = $this->option('dry-run');
        $processed = 0;
        $penalised = 0;

        $this->info("📅 Running DPD update for " . $today->format('d-M-Y') . ($dryRun ? ' [DRY RUN]' : '') . "\n");

        // Only process active loans
        $loans = Loan::with(['repaymentSchedules', 'recoveryCase'])
            ->whereIn('status', ['active', 'npa'])
            ->get();

        $bar = $this->output->createProgressBar($loans->count());
        $bar->start();

        foreach ($loans as $loan) {
            $bar->advance();

            // Find the oldest unpaid/overdue schedule past due date
            $overdueSchedules = $loan->repaymentSchedules
                ->whereIn('status', ['overdue', 'partial', 'pending'])
                ->where('due_date', '<', $today->toDateString())
                ->sortBy('installment_no');

            if ($overdueSchedules->isEmpty()) {
                // Loan is current — if recovery case exists, mark as Standard
                if (!$dryRun && $loan->recoveryCase) {
                    $loan->recoveryCase->update([
                        'dpd'                    => 0,
                        'asset_classification'   => 'Standard',
                        'total_overdue_principal'=> 0,
                        'total_overdue_interest' => 0,
                        'total_penal_charges'    => 0,
                        'total_outstanding'      => 0,
                    ]);
                }
                $processed++;
                continue;
            }

            // DPD = today minus due date of oldest overdue installment (always positive)
            $oldestDueDate = Carbon::parse($overdueSchedules->first()->due_date);
            $dpd           = max(0, (int) $today->diffInDays($oldestDueDate, true));

            // Asset classification per RBI IRACP norms
            $classification = $this->classify($dpd);

            // Mark overdue installments and levy penal charges
            $totalOverduePrincipal = 0;
            $totalOverdueInterest  = 0;
            $totalPenal            = 0;

            foreach ($overdueSchedules as $schedule) {
                // Update status to overdue
                if (!$dryRun && $schedule->status === 'pending') {
                    $schedule->update(['status' => 'overdue']);
                }

                $totalOverduePrincipal += (float)$schedule->principal_due - (float)$schedule->principal_paid;
                $totalOverdueInterest  += (float)$schedule->interest_due  - (float)$schedule->interest_paid;

                // Levy penal charge if not already levied for this installment
                if ((float)$schedule->penal_charges_due === 0.0) {
                    $penal = $amortization->penalChargeForBounce();
                    $totalPenal += $penal['total'];

                    if (!$dryRun) {
                        // RBI CRITICAL: Penal added to schedule row ONLY — NEVER to principal balance
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
                // Upsert recovery case
                RecoveryCase::updateOrCreate(
                    ['loan_id' => $loan->id],
                    [
                        'dpd'                    => $dpd,
                        'asset_classification'   => $classification,
                        'total_overdue_principal'=> round($totalOverduePrincipal, 2),
                        'total_overdue_interest' => round($totalOverdueInterest, 2),
                        'total_penal_charges'    => round($totalPenal, 2),
                        'total_outstanding'      => round($totalOutstanding, 2),
                    ]
                );

                // Escalate loan status to NPA if DPD >= 91
                if ($dpd >= 91 && $loan->status === 'active') {
                    $loan->update(['status' => 'npa']);
                }
            }

            if ($dryRun) {
                $this->line("\n  Loan #{$loan->loan_account_no} | DPD: {$dpd} | Class: {$classification} | Overdue: ₹" . number_format($totalOutstanding, 2));
            }

            $processed++;
        }

        $bar->finish();

        $this->newLine(2);
        $this->info("✅ Processed: {$processed} loans");
        $this->info("⚠️  Penal charges levied (new): {$penalised} installments");
        $this->info("💡 Penal = ₹100 + 18% GST = ₹118 per bounce (non-capitalised per RBI)");

        return Command::SUCCESS;
    }

    /**
     * RBI IRACP DPD classification rules.
     */
    private function classify(int $dpd): string
    {
        return match (true) {
            $dpd <= 0         => 'Standard',
            $dpd <= 30        => 'SMA-0',
            $dpd <= 60        => 'SMA-1',
            $dpd <= 90        => 'SMA-2',
            $dpd <= 365       => 'NPA_SubStandard',
            default           => 'Doubtful',
        };
    }
}
