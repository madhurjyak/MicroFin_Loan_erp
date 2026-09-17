<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\CollectionTransaction;
use App\Models\Customer;
use App\Models\Group;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanDocument;
use App\Models\OtsProposal;
use App\Models\RecoveryCallLog;
use App\Models\RecoveryCase;
use App\Models\RepaymentSchedule;
use App\Models\StatutoryNotice;
use App\Models\User;
use App\Services\AmortizationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * IndianMfiSeeder
 *
 * Populates comprehensive, realistic Indian MFI demo data:
 * - 4 Staff users (admin, manager, 2 agents)
 * - 3 Centers (Assam geography)
 * - 6 JLG Groups, 25 female micro-entrepreneurs
 * - 18 Loans: 10 healthy, 4 SMA-0/1, 2 SMA-2, 2 NPA
 * - 5 Loan Applications (LOS demo data)
 * - Pre-seeded recovery cases, call logs, statutory notices
 */
class IndianMfiSeeder extends Seeder
{
    private AmortizationService $amortization;

    public function __construct(AmortizationService $amortization)
    {
        $this->amortization = $amortization;
    }

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('loan_documents')->truncate();
        DB::table('loan_applications')->truncate();
        DB::table('ots_proposals')->truncate();
        DB::table('statutory_notices')->truncate();
        DB::table('recovery_call_logs')->truncate();
        DB::table('recovery_cases')->truncate();
        DB::table('collection_transactions')->truncate();
        DB::table('repayment_schedules')->truncate();
        DB::table('loans')->truncate();
        DB::table('customers')->truncate();
        DB::table('groups')->truncate();
        DB::table('centers')->truncate();
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('🏦 Seeding IndiaLend MFI Demo Data (v2.0 — RBAC + LOS)...');

        // ────────────────────────────────────────────────────────────────────
        // 0. USERS (RBAC)
        // ────────────────────────────────────────────────────────────────────
        $admin = User::create([
            'name'        => 'Suresh Patel',
            'email'       => 'admin@sfb.in',
            'password'    => Hash::make('password'),
            'role'        => 'admin',
            'branch_name' => 'Head Office',
        ]);

        $manager = User::create([
            'name'        => 'Meera Sharma',
            'email'       => 'manager@sfb.in',
            'password'    => Hash::make('password'),
            'role'        => 'manager',
            'branch_name' => 'Guwahati',
        ]);

        $agent1 = User::create([
            'name'        => 'Pranab Borah',
            'email'       => 'agent1@sfb.in',
            'password'    => Hash::make('password'),
            'role'        => 'agent',
            'branch_name' => 'Guwahati',
        ]);

        $agent2 = User::create([
            'name'        => 'Dipali Kalita',
            'email'       => 'agent2@sfb.in',
            'password'    => Hash::make('password'),
            'role'        => 'agent',
            'branch_name' => 'Guwahati',
        ]);

        $this->command->info('  → 4 Users: admin, manager, agent1, agent2');

        // ────────────────────────────────────────────────────────────────────
        // 1. CENTERS
        // ────────────────────────────────────────────────────────────────────
        $centersData = [
            [
                'center_name'  => 'Hajo Kendra',
                'center_code'  => 'GHY-AS-001',
                'branch_name'  => 'Guwahati Branch',
                'meeting_day'  => 'Monday',
                'meeting_time' => '09:30:00',
                'field_officer'=> 'Pranab Borah',
            ],
            [
                'center_name'  => 'Nalbari Kendra',
                'center_code'  => 'NBR-AS-007',
                'branch_name'  => 'Nalbari Branch',
                'meeting_day'  => 'Wednesday',
                'meeting_time' => '10:00:00',
                'field_officer'=> 'Dipali Kalita',
            ],
            [
                'center_name'  => 'Rangia Center',
                'center_code'  => 'KDR-AS-014',
                'branch_name'  => 'Kamrup Branch',
                'meeting_day'  => 'Friday',
                'meeting_time' => '10:30:00',
                'field_officer'=> 'Hemanta Das',
            ],
        ];

        $centers = [];
        foreach ($centersData as $cd) {
            $centers[] = Center::create($cd);
        }

        // ────────────────────────────────────────────────────────────────────
        // 2. GROUPS
        // ────────────────────────────────────────────────────────────────────
        $groupsData = [
            ['center_id' => $centers[0]->id, 'group_name' => 'Lakshmi JLG Group 1',  'group_leader_name' => 'Rekha Devi'],
            ['center_id' => $centers[0]->id, 'group_name' => 'Durga JLG Group 2',    'group_leader_name' => 'Purnima Borah'],
            ['center_id' => $centers[1]->id, 'group_name' => 'Saraswati JLG Group 1','group_leader_name' => 'Minakshi Das'],
            ['center_id' => $centers[1]->id, 'group_name' => 'Kali JLG Group 2',     'group_leader_name' => 'Anita Sharma'],
            ['center_id' => $centers[2]->id, 'group_name' => 'Savitri JLG Group 1',  'group_leader_name' => 'Mamoni Gogoi'],
            ['center_id' => $centers[2]->id, 'group_name' => 'Meera JLG Group 2',    'group_leader_name' => 'Basanti Roy'],
        ];

        $groups = [];
        foreach ($groupsData as $gd) {
            $groups[] = Group::create($gd);
        }

        // ────────────────────────────────────────────────────────────────────
        // 3. CUSTOMERS (25 female micro-entrepreneurs)
        // ────────────────────────────────────────────────────────────────────
        $customersRaw = [
            // Group 0 — Hajo Center, Lakshmi Group
            ['Rekha Devi',         'G0', 'Female', '8474000001', 'ABCDE1234F', '1234', 'Hajo Bazar, Ward No. 3',     'Kamrup (M)',      'Assam', '781039', 180000, 3200],
            ['Bina Kalita',        'G0', 'Female', '9435000002', 'BCDFE5678G', '5678', 'Village Pub Hajo',           'Kamrup',          'Assam', '781104', 144000, 2800],
            ['Malati Sharma',      'G0', 'Female', '8822000003', 'CDEFG9012H', '9012', 'Bezpara, Hajo',              'Kamrup',          'Assam', '781104', 156000, 2500],
            ['Parvati Nath',       'G0', 'Female', '7002000004', 'DEFGH3456I', '3456', 'Hajo Road, Rampur',          'Kamrup',          'Assam', '781039', 192000, 4000],
            // Group 1 — Hajo Center, Durga Group
            ['Purnima Borah',      'G1', 'Female', '9365000005', 'EFGHI7890J', '7890', 'Changsari, Hajo',            'Kamrup',          'Assam', '781101', 168000, 3000],
            ['Sunita Das',         'G1', 'Female', '6002000006', 'FGHIJ2345K', '2345', 'Keotpara, North Guwahati',   'Kamrup',          'Assam', '781028', 120000, 2200],
            ['Dipali Gogoi',       'G1', 'Female', '8403000007', 'GHIJK6789L', '6789', 'Amingaon, Kamrup',           'Kamrup',          'Assam', '781031', 204000, 3500],
            // Group 2 — Nalbari Center, Saraswati Group
            ['Minakshi Das',       'G2', 'Female', '9706000008', 'HIJKL1234M', '1234', 'Nalbari Town, Ward 5',       'Nalbari',         'Assam', '781335', 144000, 2600],
            ['Lakhimi Boro',       'G2', 'Female', '8822000009', 'IJKLM5678N', '5678', 'Tihu Road, Nalbari',         'Nalbari',         'Assam', '781371', 132000, 2400],
            ['Gita Deka',          'G2', 'Female', '7002000010', 'JKLMN9012O', '9012', 'Ghograpar, Nalbari',         'Nalbari',         'Assam', '781360', 168000, 3100],
            ['Priya Baruah',       'G2', 'Female', '9365000011', 'KLMNO3456P', '3456', 'Sarthebari, Barpeta',        'Barpeta',         'Assam', '781307', 156000, 2900],
            // Group 3 — Nalbari Center, Kali Group
            ['Anita Sharma',       'G3', 'Female', '6002000012', 'LMNOP7890Q', '7890', 'Jalah, Nalbari',             'Nalbari',         'Assam', '781344', 150000, 2700],
            ['Saraswati Devi',     'G3', 'Female', '9706000013', 'MNOPQ2345R', '2345', 'Pub Nalbari',                'Nalbari',         'Assam', '781335', 144000, 2800],
            ['Kamala Das',         'G3', 'Female', '8474000014', 'NOPQR6789S', '6789', 'Rampur, Nalbari',            'Nalbari',         'Assam', '781335', 120000, 2200],
            // Group 4 — Rangia Center, Savitri Group
            ['Mamoni Gogoi',       'G4', 'Female', '9435000015', 'OPQRS1234T', '1234', 'Rangia Town, Ward 2',        'Kamrup',          'Assam', '781354', 192000, 3800],
            ['Rita Bora',          'G4', 'Female', '8403000016', 'PQRST5678U', '5678', 'Barama, Kamrup',             'Kamrup',          'Assam', '781346', 156000, 2500],
            ['Shanti Nath',        'G4', 'Female', '7002000017', 'QRSTU9012V', '9012', 'Sualkuchi, Kamrup',          'Kamrup',          'Assam', '781102', 144000, 2400],
            ['Hema Devi',          'G4', 'Female', '8822000018', 'RSTUV3456W', '3456', 'Barkhetry, Nalbari',         'Nalbari',         'Assam', '781335', 168000, 3000],
            // Group 5 — Rangia Center, Meera Group
            ['Basanti Roy',        'G5', 'Female', '6002000019', 'STUVW7890X', '7890', 'Bezpara, Rangia',            'Kamrup',          'Assam', '781354', 180000, 3200],
            ['Nandita Das',        'G5', 'Female', '9365000020', 'TUVWX2345Y', '2345', 'Chaygaon, Kamrup',           'Kamrup',          'Assam', '781124', 162000, 2900],
            ['Padma Borah',        'G5', 'Female', '8474000021', 'UVWXY6789Z', '6789', 'Nagaon Road, Rangia',        'Kamrup',          'Assam', '781354', 150000, 2700],
            ['Jyotsna Kalita',     'G5', 'Female', '9706000022', 'VWXYZ1234A', '1234', 'Palasbari, Kamrup',          'Kamrup (M)',      'Assam', '781026', 190000, 3300],
            // Extra members spread across groups for realism
            ['Usha Devi',          'G1', 'Female', '8822000023', 'WXYZ12345B', '2345', 'Kamarkuchi, Barpeta',        'Barpeta',         'Assam', '781301', 138000, 2600],
            ['Renu Bora',          'G3', 'Female', '7002000024', 'XYZA23456C', '3456', 'Dakhinkhal, Kamrup',         'Kamrup',          'Assam', '781031', 120000, 2100],
            ['Mina Gogoi',         'G2', 'Female', '9435000025', 'YZAB34567D', '4567', 'Kalaigaon, Darrang',         'Darrang',         'Assam', '784145', 156000, 2800],
        ];

        $customers = [];
        $groupMap  = ['G0'=>0,'G1'=>1,'G2'=>2,'G3'=>3,'G4'=>4,'G5'=>5];

        foreach ($customersRaw as $idx => $c) {
            $customers[] = Customer::create([
                'group_id'                => $groups[$groupMap[$c[1]]]->id,
                'customer_code'           => 'CUST' . str_pad($idx + 1, 5, '0', STR_PAD_LEFT),
                'full_name'               => $c[0],
                'gender'                  => $c[2],
                'phone'                   => $c[3],
                'pan_number'              => $c[4],
                'aadhaar_last4'           => $c[5],
                'address'                 => $c[6],
                'district'                => $c[7],
                'state'                   => $c[8],
                'pincode'                 => $c[9],
                'annual_household_income' => $c[10],
                'monthly_debt_obligations'=> $c[11],
                'bank_account_no'         => '30' . rand(10000000000, 99999999999),
                'ifsc_code'               => 'SBIN0' . rand(10000, 99999),
            ]);
        }

        // ────────────────────────────────────────────────────────────────────
        // 4. LOANS + REPAYMENT SCHEDULES
        // ────────────────────────────────────────────────────────────────────

        $today      = Carbon::today();
        $loansSpec  = [
            // [customer_idx, principal, rate, tenure, freq, disburse_months_ago, daysOverdue, status_preset]
            // ── Healthy accounts ──
            [0,  50000, 22.00, 12, 'monthly', 10, 0,   'healthy'],
            [1,  40000, 21.50, 24, 'monthly', 8,  0,   'healthy'],
            [2,  30000, 22.00, 12, 'monthly', 6,  0,   'healthy'],
            [3,  60000, 23.00, 18, 'monthly', 14, 0,   'healthy'],
            [4,  45000, 22.00, 12, 'monthly', 5,  0,   'healthy'],
            [5,  35000, 21.00, 12, 'monthly', 9,  0,   'healthy'],
            [6,  70000, 24.00, 24, 'monthly', 12, 0,   'healthy'],
            [7,  55000, 22.50, 18, 'monthly', 7,  0,   'healthy'],
            [8,  40000, 22.00, 12, 'monthly', 3,  0,   'healthy'],
            [9,  80000, 23.00, 24, 'monthly', 11, 0,   'healthy'],
            // ── SMA-0 (1–30 DPD) ──
            [10, 50000, 22.00, 12, 'monthly', 11, 20,  'sma0'],
            [11, 40000, 21.50, 12, 'monthly', 9,  10,  'sma0'],
            // ── SMA-1 (31–60 DPD) ──
            [12, 45000, 22.00, 18, 'monthly', 14, 45,  'sma1'],
            [13, 60000, 23.00, 24, 'monthly', 16, 50,  'sma1'],
            // ── SMA-2 (61–90 DPD) ──
            [14, 55000, 24.00, 18, 'monthly', 18, 75,  'sma2'],
            [15, 70000, 22.50, 24, 'monthly', 20, 80,  'sma2'],
            // ── NPA (91+ DPD) ──
            [16, 60000, 23.00, 18, 'monthly', 22, 110, 'npa'],
            [17, 80000, 24.00, 24, 'monthly', 25, 130, 'npa'],
        ];

        $loans = [];
        foreach ($loansSpec as $i => $spec) {
            [$custIdx, $principal, $rate, $tenure, $freq, $monthsAgo, $daysOverdue, $preset] = $spec;

            $disbDate   = $today->copy()->subMonths($monthsAgo)->startOfMonth();
            $maturity   = $disbDate->copy()->addMonths($tenure);
            $loanStatus = $preset === 'npa' ? 'npa' : 'active';

            $loan = Loan::create([
                'customer_id'         => $customers[$custIdx]->id,
                'loan_account_no'     => 'SFB' . $disbDate->format('Y') . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'principal_amount'    => $principal,
                'annual_interest_rate'=> $rate,
                'tenure'              => $tenure,
                'repayment_frequency' => $freq,
                'interest_type'       => 'reducing',
                'disbursement_date'   => $disbDate,
                'maturity_date'       => $maturity,
                'status'              => $loanStatus,
                'disbursement_mode'   => 'cash',
                'processing_fee'      => round($principal * 0.01, 2),
                'processing_fee_gst'  => round($principal * 0.01 * 0.18, 2),
                'purpose'             => ['Dairy Business', 'Poultry Farming', 'Vegetable Vending', 'Tailoring Unit', 'Grocery Shop', 'Pottery', 'Handloom Weaving'][$i % 7],
            ]);

            // Generate amortization schedule (bypass FOIR validation for seeder)
            $scheduleRows = $this->amortization->generate($loan, false);

            foreach ($scheduleRows as $row) {
                RepaymentSchedule::create(array_merge(['loan_id' => $loan->id], $row));
            }

            // ── Mark paid installments for healthy accounts ──
            if ($preset === 'healthy') {
                $paidCount = max(0, $monthsAgo - 1);
                $schedules = RepaymentSchedule::where('loan_id', $loan->id)->orderBy('installment_no')->get();

                foreach ($schedules->take($paidCount) as $s) {
                    $collectionDate = $disbDate->copy()->addMonths($s->installment_no)->startOfMonth()->addDays(2);
                    $totalDue = (float)$s->principal_due + (float)$s->interest_due;
                    $s->update([
                        'principal_paid' => $s->principal_due,
                        'interest_paid'  => $s->interest_due,
                        'total_paid'     => $totalDue,
                        'status'         => 'paid',
                    ]);
                    CollectionTransaction::create([
                        'loan_id'          => $loan->id,
                        'schedule_id'      => $s->id,
                        'receipt_no'       => 'RCP' . strtoupper(substr(md5($loan->id . $s->id), 0, 8)),
                        'amount_collected' => $totalDue,
                        'collection_date'  => $collectionDate->toDateString(),
                        'collected_by'     => $loan->customer->group->center->field_officer ?? 'Field Officer',
                        'payment_mode'     => ['cash', 'upi_qr', 'nach'][array_rand(['cash', 'upi_qr', 'nach'])],
                    ]);
                }
            }

            // ── Mark overdue installments for delinquent accounts ──
            if (in_array($preset, ['sma0', 'sma1', 'sma2', 'npa'])) {
                $overdueInstallments = (int) ceil($daysOverdue / 30);
                $schedules = RepaymentSchedule::where('loan_id', $loan->id)->orderBy('installment_no')->get();
                $paidUpTo  = max(0, $schedules->count() - $overdueInstallments - 1);

                foreach ($schedules->take($paidUpTo) as $s) {
                    $totalDue = (float)$s->principal_due + (float)$s->interest_due;
                    $s->update([
                        'principal_paid' => $s->principal_due,
                        'interest_paid'  => $s->interest_due,
                        'total_paid'     => $totalDue,
                        'status'         => 'paid',
                    ]);
                }

                foreach ($schedules->slice($paidUpTo) as $s) {
                    $penal = $this->amortization->penalChargeForBounce();
                    $totalWithPenal = (float)$s->total_due + $penal['total'];
                    $s->update([
                        'status'            => 'overdue',
                        'penal_charges_due' => $penal['penal'],
                        'penal_gst_due'     => $penal['gst'],
                        'total_due'         => $totalWithPenal,
                    ]);
                }
            }

            $loans[] = $loan;
        }

        // ────────────────────────────────────────────────────────────────────
        // 5. RECOVERY CASES (with assigned_officer_id FK)
        // ────────────────────────────────────────────────────────────────────
        $recoverySpecs = [
            // [loan_idx, dpd, classification, agent_user]
            [10, 20,  'SMA-0',          $agent1],
            [11, 10,  'SMA-0',          $agent1],
            [12, 45,  'SMA-1',          $agent2],
            [13, 50,  'SMA-1',          $agent2],
            [14, 75,  'SMA-2',          $agent1],
            [15, 80,  'SMA-2',          $agent2],
            [16, 110, 'NPA_SubStandard',$agent1],
            [17, 130, 'NPA_SubStandard',$agent2],
        ];

        $recoveryCases = [];
        foreach ($recoverySpecs as $rs) {
            [$loanIdx, $dpd, $classification, $agentUser] = $rs;
            $loan = $loans[$loanIdx];

            $overduePrincipal = RepaymentSchedule::where('loan_id', $loan->id)
                ->where('status', 'overdue')
                ->sum(DB::raw('principal_due - principal_paid'));

            $overdueInterest = RepaymentSchedule::where('loan_id', $loan->id)
                ->where('status', 'overdue')
                ->sum(DB::raw('interest_due - interest_paid'));

            $totalPenal = RepaymentSchedule::where('loan_id', $loan->id)
                ->where('status', 'overdue')
                ->sum(DB::raw('penal_charges_due + penal_gst_due - penal_paid - gst_paid'));

            $recoveryCases[$loanIdx] = RecoveryCase::create([
                'loan_id'                => $loan->id,
                'dpd'                    => $dpd,
                'asset_classification'   => $classification,
                'total_overdue_principal'=> round($overduePrincipal, 2),
                'total_overdue_interest' => round($overdueInterest, 2),
                'total_penal_charges'    => round($totalPenal, 2),
                'total_outstanding'      => round($overduePrincipal + $overdueInterest + $totalPenal, 2),
                'assigned_officer'       => $agentUser->name,
                'assigned_officer_id'    => $agentUser->id,
                'last_contacted_at'      => $today->copy()->subDays(rand(3,10)),
                'remarks'               => 'Auto-created by IndianMfiSeeder',
            ]);
        }

        // ────────────────────────────────────────────────────────────────────
        // 6. RECOVERY CALL LOGS (SMA-2 and NPA accounts)
        // ────────────────────────────────────────────────────────────────────
        $callLogsSpec = [
            [14, 'telecalling', '-5 days', '10:30', 'PTP',           '-3 days', 8000,  'Borrower promised payment on {ptp_date}'],
            [14, 'field_visit', '-3 days', '15:00', 'Broken_PTP',    null,      null,  'Visited residence. Borrower not home, neighbour says she is unwell.'],
            [14, 'telecalling', '-1 days', '09:45', 'Crop_Failure',  null,      null,  'Borrower states crop damaged due to recent floods.'],
            [15, 'telecalling', '-7 days', '11:00', 'PTP',           '-5 days', 12000, 'Promised payment via NACH next cycle.'],
            [15, 'field_visit', '-4 days', '14:30', 'Dispute',       null,      null,  'Borrower disputes interest calculation. Referred to branch.'],
            [16, 'telecalling', '-15 days','10:00', 'Absconding',    null,      null,  'Phone switched off. Neighbour says family moved.'],
            [16, 'field_visit', '-10 days','11:30', 'Absconding',    null,      null,  'Residence locked. No information from neighbours.'],
            [16, 'telecalling', '-5 days', '14:00', 'RNR',           null,      null,  'Ringing, no response.'],
            [17, 'telecalling', '-20 days','09:00', 'Medical_Emergency', null,  null,  'Borrower hospitalised. Husband informed about dues.'],
            [17, 'field_visit', '-12 days','16:00', 'PTP',           '-9 days', 15000, 'Met borrower at home. She is recovering. PTP given.'],
            [17, 'telecalling', '-8 days', '10:15', 'Broken_PTP',    null,      null,  'PTP broken. Borrower says she needs more time.'],
        ];

        foreach ($callLogsSpec as $cl) {
            [$loanIdx, $type, $daysAgo, $timeStr, $disposition, $ptpDaysAgo, $ptpAmount, $notes] = $cl;
            if (!isset($recoveryCases[$loanIdx])) continue;

            $contactDate = $today->copy()->modify($daysAgo);
            [$hour, $min] = explode(':', $timeStr);
            $contactTime = $contactDate->copy()->setTime((int)$hour, (int)$min);

            $ptpDate = $ptpDaysAgo ? $today->copy()->modify($ptpDaysAgo)->toDateString() : null;

            RecoveryCallLog::create([
                'recovery_case_id' => $recoveryCases[$loanIdx]->id,
                'interaction_type' => $type,
                'contact_time'     => $contactTime,
                'disposition'      => $disposition,
                'ptp_date'         => $ptpDate,
                'ptp_amount'       => $ptpAmount,
                'logged_by'        => $recoveryCases[$loanIdx]->assigned_officer,
                'notes'            => $notes,
            ]);
        }

        // ────────────────────────────────────────────────────────────────────
        // 7. STATUTORY NOTICES — NPA accounts (Loans 16 & 17)
        // ────────────────────────────────────────────────────────────────────
        StatutoryNotice::create([
            'loan_id'               => $loans[16]->id,
            'notice_type'           => 'Sec_25_PSSA_AutoDebit_Bounce',
            'notice_ref_no'         => 'ILP/SEC25/' . $today->format('Y') . '/0001',
            'dispatch_date'         => $today->copy()->subDays(30),
            'tracking_speedpost_no' => 'EW' . rand(100000000, 999999999) . 'IN',
            'status'                => 'dispatched',
            'remarks'               => 'First notice. Awaiting service confirmation.',
        ]);

        StatutoryNotice::create([
            'loan_id'               => $loans[16]->id,
            'notice_type'           => 'Loan_Recall_Notice',
            'notice_ref_no'         => 'ILP/RECALL/' . $today->format('Y') . '/0001',
            'dispatch_date'         => $today->copy()->subDays(10),
            'tracking_speedpost_no' => 'EW' . rand(100000000, 999999999) . 'IN',
            'status'                => 'generated',
            'remarks'               => 'Recall notice generated pending dispatch.',
        ]);

        StatutoryNotice::create([
            'loan_id'               => $loans[17]->id,
            'notice_type'           => 'Sec_25_PSSA_AutoDebit_Bounce',
            'notice_ref_no'         => 'ILP/SEC25/' . $today->format('Y') . '/0002',
            'dispatch_date'         => $today->copy()->subDays(25),
            'tracking_speedpost_no' => 'EW' . rand(100000000, 999999999) . 'IN',
            'status'                => 'served',
            'remarks'               => 'Served via Speed Post. Proof of delivery received.',
        ]);

        StatutoryNotice::create([
            'loan_id'               => $loans[17]->id,
            'notice_type'           => 'Sec_138_NI_Act',
            'notice_ref_no'         => 'ILP/SEC138/' . $today->format('Y') . '/0001',
            'dispatch_date'         => $today->copy()->subDays(5),
            'tracking_speedpost_no' => null,
            'status'                => 'generated',
            'remarks'               => 'Advocate notice to be issued pending approval.',
        ]);

        // ────────────────────────────────────────────────────────────────────
        // 8. OTS PROPOSAL — NPA Loan 17
        // ────────────────────────────────────────────────────────────────────
        $npaLoan = $loans[17];
        $outstanding = $recoveryCases[17]->total_outstanding ?? 50000;
        $proposed    = round($outstanding * 0.70, 2); // 30% haircut
        $haircut     = round((($outstanding - $proposed) / $outstanding) * 100, 2);

        OtsProposal::create([
            'loan_id'            => $npaLoan->id,
            'total_outstanding'  => $outstanding,
            'proposed_amount'    => $proposed,
            'waiver_penal_gst'   => $recoveryCases[17]->total_penal_charges ?? 1180,
            'waiver_interest'    => round($outstanding * 0.15, 2),
            'waiver_principal'   => round($outstanding * 0.15 - ($recoveryCases[17]->total_penal_charges ?? 0), 2),
            'haircut_pct'        => $haircut,
            'approval_authority' => 'Regional_Credit_Committee',
            'status'             => 'pending',
            'remarks'            => 'Borrower under medical treatment. RCC meeting scheduled.',
        ]);

        // ────────────────────────────────────────────────────────────────────
        // 9. LOAN APPLICATIONS (LOS demo data)
        // ────────────────────────────────────────────────────────────────────
        $year = $today->format('Y');

        // App 1: Submitted — ready for manager review (Agent 1)
        $app1 = LoanApplication::create([
            'application_no'      => "APP-{$year}-001",
            'customer_id'         => $customers[18]->id, // Basanti Roy
            'agent_id'            => $agent1->id,
            'applied_amount'      => 45000,
            'annual_interest_rate'=> 22.00,
            'tenure'              => 12,
            'repayment_frequency' => 'monthly',
            'purpose'             => 'Handloom Weaving Expansion',
            'stage'               => 'submitted',
        ]);

        // App 2: Submitted — ready for review (Agent 2)
        $app2 = LoanApplication::create([
            'application_no'      => "APP-{$year}-002",
            'customer_id'         => $customers[19]->id, // Nandita Das
            'agent_id'            => $agent2->id,
            'applied_amount'      => 35000,
            'annual_interest_rate'=> 21.50,
            'tenure'              => 18,
            'repayment_frequency' => 'monthly',
            'purpose'             => 'Pottery Business Equipment',
            'stage'               => 'submitted',
        ]);

        // App 3: Rejected (Agent 1)
        $app3 = LoanApplication::create([
            'application_no'      => "APP-{$year}-003",
            'customer_id'         => $customers[22]->id, // Usha Devi
            'agent_id'            => $agent1->id,
            'applied_amount'      => 80000,
            'annual_interest_rate'=> 23.00,
            'tenure'              => 12,
            'repayment_frequency' => 'monthly',
            'purpose'             => 'Grocery Shop',
            'stage'               => 'rejected',
            'reviewed_by'         => $manager->id,
            'rejection_reason'    => 'FOIR exceeds 50% RBI cap. Customer already has high monthly obligations.',
        ]);

        // App 4: Approved — linked to Loan #0 (Rekha Devi)
        $app4 = LoanApplication::create([
            'application_no'      => "APP-{$year}-004",
            'customer_id'         => $customers[0]->id, // Rekha Devi
            'agent_id'            => $agent1->id,
            'applied_amount'      => 50000,
            'annual_interest_rate'=> 22.00,
            'tenure'              => 12,
            'repayment_frequency' => 'monthly',
            'purpose'             => 'Dairy Business',
            'stage'               => 'approved',
            'reviewed_by'         => $manager->id,
            'review_notes'        => 'All documents verified. FOIR within limits. Approved for disbursement.',
            'approved_at'         => $today->copy()->subMonths(10),
        ]);

        // Link loan #0 to app4
        $loans[0]->update(['loan_application_id' => $app4->id]);

        // App 5: Approved — linked to Loan #1 (Bina Kalita)
        $app5 = LoanApplication::create([
            'application_no'      => "APP-{$year}-005",
            'customer_id'         => $customers[1]->id, // Bina Kalita
            'agent_id'            => $agent2->id,
            'applied_amount'      => 40000,
            'annual_interest_rate'=> 21.50,
            'tenure'              => 24,
            'repayment_frequency' => 'monthly',
            'purpose'             => 'Poultry Farming',
            'stage'               => 'approved',
            'reviewed_by'         => $manager->id,
            'review_notes'        => 'Customer has good repayment history. Approved.',
            'approved_at'         => $today->copy()->subMonths(8),
        ]);

        $loans[1]->update(['loan_application_id' => $app5->id]);

        // ── Documents for each application ──
        $allApps = [$app1, $app2, $app3, $app4, $app5];
        $docTypes = ['aadhaar_card', 'pan_card', 'bank_passbook', 'income_declaration'];

        foreach ($allApps as $appIdx => $app) {
            foreach ($docTypes as $docType) {
                $docNumber = match($docType) {
                    'aadhaar_card' => 'XXXX-XXXX-' . ($app->customer->aadhaar_last4 ?? '1234'),
                    'pan_card'     => $app->customer->pan_number ?? 'ABCDE1234F',
                    default        => null,
                };

                $isVerified = in_array($app->stage, ['approved', 'rejected']);

                LoanDocument::create([
                    'loan_application_id' => $app->id,
                    'document_type'       => $docType,
                    'document_number'     => $docNumber,
                    'file_path'           => "documents/dummy_{$docType}.pdf",
                    'verification_status' => $isVerified ? 'verified' : 'pending',
                    'verified_by'         => $isVerified ? $manager->id : null,
                ]);
            }
        }

        $this->command->info('✅ Seeded: 4 Users, 3 Centers, 6 Groups, 25 Customers, 18 Loans');
        $this->command->info('   → 10 healthy | 4 SMA-0/1 | 2 SMA-2 | 2 NPA');
        $this->command->info('   → 4 Statutory Notices | 1 OTS Proposal | 11 Call Logs');
        $this->command->info('   → 5 Loan Applications (2 submitted, 1 rejected, 2 approved)');
        $this->command->info('   → 20 KYC Documents attached');
        $this->command->info('');
        $this->command->info('🔑 Login credentials (password: password):');
        $this->command->info('   admin@sfb.in   — Admin (Full Access)');
        $this->command->info('   manager@sfb.in — Manager (Underwriting + Recovery)');
        $this->command->info('   agent1@sfb.in  — Agent (Origination + Field)');
        $this->command->info('   agent2@sfb.in  — Agent (Origination + Field)');
    }
}
