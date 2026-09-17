@extends('layouts.app')
@section('title', 'Loan Ledger — ' . $loan->loan_account_no)
@section('page-title', '📒 Loan Account Ledger')

@section('content')
@php
    function inrL(float $a): string {
        $a = round($a,2); $p = explode('.', number_format($a,2)); $i = str_replace(',','',$p[0]);
        $l = strlen($i); if ($l<=3) return '₹'.$i.'.'.$p[1];
        $r = substr($i,0,$l-3); $r = preg_replace('/\B(?=(\d{2})+(?!\d))/',',',$r);
        return '₹'.$r.','.substr($i,-3).'.'.$p[1];
    }
    $customer = $loan->customer;
    $statusColors = [
        'active'     => 'std',
        'npa'        => 'npa',
        'closed'     => 'sma0',
        'written_off'=> 'sma2',
    ];
@endphp

<div class="dashboard-panels-grid" style="grid-template-columns: repeat(4, 1fr);">

    <!-- ── Left: KYC + Loan Summary ───────────────────────────────────── -->
    <div style="grid-column: span 1; display: flex; flex-direction: column; gap: 16px;">

        <!-- KYC Card -->
        <div class="panel" style="padding: 20px; margin-bottom: 0;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, var(--brand-600) 0%, var(--brand-800) 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 18px;">
                    {{ substr($customer->full_name, 0, 1) }}
                </div>
                <div>
                    <p style="font-weight: 700; color: var(--text-primary); margin: 0;">{{ $customer->full_name }}</p>
                    <p style="font-size: 12px; color: var(--text-tertiary); margin: 0;">{{ $customer->customer_code }}</p>
                </div>
            </div>
            <div style="font-size: 14px; display: flex; flex-direction: column; gap: 8px;">
                <div style="display: flex; justify-content: space-between;">
                    <span class="text-muted">Aadhaar</span>
                    <span class="font-mono font-medium text-slate-700">{{ $customer->masked_aadhaar }}</span>
                </div>
                @if($customer->pan_number)
                <div style="display: flex; justify-content: space-between;">
                    <span class="text-muted">PAN</span>
                    <span class="font-mono font-medium text-slate-700">{{ $customer->pan_number }}</span>
                </div>
                @endif
                <div style="display: flex; justify-content: space-between;">
                    <span class="text-muted">Phone</span>
                    <span class="font-medium text-slate-700">{{ $customer->phone }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span class="text-muted">District</span>
                    <span class="font-medium text-slate-700">{{ $customer->district }}, {{ $customer->state }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span class="text-muted">Ann. Income</span>
                    <span class="font-semibold text-slate-700">{{ inrL((float)$customer->annual_household_income) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span class="text-muted">FOIR</span>
                    <span class="font-semibold {{ $customer->foir_percent > 50 ? 'text-rose' : 'text-emerald' }}">
                        {{ $customer->foir_percent }}%
                    </span>
                </div>
                <div style="padding-top: 8px; border-top: 1px solid var(--border-light);">
                    <span class="text-muted" style="font-size: 12px;">JLG Group:</span>
                    <span style="font-weight: 500; font-size: 12px; color: var(--text-secondary); margin-left: 4px;">{{ $customer->group->group_name }}</span>
                </div>
                <div>
                    <span class="text-muted" style="font-size: 12px;">Center:</span>
                    <span style="font-weight: 500; font-size: 12px; color: var(--text-secondary); margin-left: 4px;">{{ $customer->group->center->center_name }}</span>
                </div>
            </div>
        </div>

        <!-- Loan Summary Card -->
        <div class="panel" style="padding: 20px; margin-bottom: 0;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <p style="font-weight: 600; color: var(--text-secondary); font-size: 14px; margin: 0;">Loan Details</p>
                <span class="badge-{{ $statusColors[$loan->status] ?? 'sma0' }}">
                    {{ strtoupper($loan->status) }}
                </span>
            </div>
            <div style="font-size: 14px; display: flex; flex-direction: column; gap: 8px;">
                <div style="display: flex; justify-content: space-between;"><span class="text-muted">A/c No.</span><span class="font-mono font-bold text-slate-800" style="font-size: 12px;">{{ $loan->loan_account_no }}</span></div>
                <div style="display: flex; justify-content: space-between;"><span class="text-muted">Principal</span><span class="font-bold text-slate-800">{{ inrL((float)$loan->principal_amount) }}</span></div>
                <div style="display: flex; justify-content: space-between;"><span class="text-muted">Rate</span><span class="font-medium text-slate-800">{{ $loan->annual_interest_rate }}% p.a. ({{ $loan->interest_type }})</span></div>
                <div style="display: flex; justify-content: space-between;"><span class="text-muted">Tenure</span><span class="font-medium text-slate-800">{{ $loan->tenure }} {{ $loan->repayment_frequency === 'weekly' ? 'weeks' : 'months' }}</span></div>
                <div style="display: flex; justify-content: space-between;"><span class="text-muted">Disbursed</span><span class="font-medium text-slate-800">{{ $loan->disbursement_date->format('d-M-Y') }}</span></div>
                @if($loan->maturity_date)
                <div style="display: flex; justify-content: space-between;"><span class="text-muted">Maturity</span><span class="font-medium text-slate-800">{{ $loan->maturity_date->format('d-M-Y') }}</span></div>
                @endif
                <div style="display: flex; justify-content: space-between;"><span class="text-muted">Frequency</span><span class="font-medium text-slate-800" style="text-transform: capitalize;">{{ $loan->repayment_frequency }}</span></div>
                @if($loan->recoveryCase)
                <div style="display: flex; justify-content: space-between; padding-top: 4px; border-top: 1px solid var(--border-light);">
                    <span class="text-muted">DPD</span>
                    <span style="font-weight: 700; color: #e11d48;">{{ $loan->recoveryCase->dpd }} days</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Total Outstanding Banner -->
        <div style="background: linear-gradient(135deg, var(--brand-700) 0%, var(--brand-900) 100%); border-radius: var(--border-radius-xl); padding: 16px; color: white;">
            <p style="font-size: 12px; font-weight: 600; opacity: 0.7; margin: 0 0 4px 0;">Total Outstanding</p>
            <p style="font-size: 24px; font-weight: 700; margin: 0;">{{ inrL((float)$totalOutstanding) }}</p>
            @if($overdueSchedules > 0)
            <p style="font-size: 12px; margin-top: 8px; color: #fca5a5; margin-bottom: 0;">{{ $overdueSchedules }} overdue installment(s)</p>
            @endif
        </div>
    </div>

    <!-- ── Right: Amortization Schedule + History ──────────────────────── -->
    <div style="grid-column: span 3; display: flex; flex-direction: column; gap: 20px;">

        <!-- Collect Repayment Button -->
        @if($loan->status === 'active' || $loan->status === 'npa')
        <div style="display: flex; justify-content: flex-end;">
            <button id="btnCollect" class="btn-success" style="display: flex; align-items: center; gap: 8px;">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Collect Repayment
            </button>
        </div>

        <!-- Collect Modal -->
        <div id="collectModal" class="modal-overlay" style="display: none;">
            <div class="modal-content">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <h3 style="font-weight: 700; color: var(--text-primary); font-size: 18px; margin: 0;">Collect Repayment</h3>
                    <button type="button" class="modal-close-btn" style="background: none; border: none; cursor: pointer; color: var(--text-tertiary);">
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('lms.loans.collect', $loan->id) }}">
                    @csrf
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div>
                            <label class="form-label">Amount Collected (₹) *</label>
                            <input type="number" name="amount_collected" step="0.01" min="1" required class="form-input" placeholder="Enter amount">
                        </div>
                        <div>
                            <label class="form-label">Payment Mode *</label>
                            <select name="payment_mode" required class="form-input">
                                <option value="cash">💵 Cash (at Kendra)</option>
                                <option value="upi_qr">📱 UPI QR</option>
                                <option value="nach">🏦 e-NACH / AutoPay</option>
                                <option value="neft">NEFT</option>
                                <option value="rtgs">RTGS</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Collection Date *</label>
                            <input type="date" name="collection_date" required value="{{ date('Y-m-d') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Collected By *</label>
                            <select name="collected_by" required class="form-input">
                                <option value="">— Select Officer —</option>
                                @foreach(\App\Models\User::all() as $user)
                                    <option value="{{ $user->name }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">JLG Peer Payer (if any)</label>
                            <select name="peer_payer_customer_id" class="form-input">
                                <option value="">— Self Payment —</option>
                                @foreach($loan->customer->group->customers as $peer)
                                    @if($peer->id !== $loan->customer_id)
                                        <option value="{{ $peer->id }}">{{ $peer->full_name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 12px; margin-top: 16px; font-size: 12px; color: #92400e;">
                        ⚠️ Waterfall order: Penal GST → Penal Charges → Interest → Principal (RBI FPC)
                    </div>

                    <div style="display: flex; gap: 12px; margin-top: 20px;">
                        <button type="button" class="btn-secondary modal-close-btn" style="flex: 1;">Cancel</button>
                        <button type="submit" class="btn-success" style="flex: 1;">Process Payment</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Amortization Schedule -->
        <div class="panel" style="padding: 0; overflow: hidden; margin-bottom: 0;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-light); display: flex; align-items: center; justify-content: space-between;">
                <h2 style="font-weight: 600; color: var(--text-secondary); margin: 0; font-size: 16px;">Repayment Schedule</h2>
                <span style="font-size: 12px; color: var(--text-tertiary);">{{ $loan->repaymentSchedules->count() }} installments</span>
            </div>
            <div class="table-responsive">
                <table id="scheduleTable" class="data-table">
                    <thead>
                        <tr style="background: rgba(15, 23, 42, 0.02);">
                            <th class="text-center" style="padding-left: 12px;">#</th>
                            <th class="text-left">Due Date</th>
                            <th class="text-right">Opening Bal</th>
                            <th class="text-right">Principal</th>
                            <th class="text-right">Interest</th>
                            <th class="text-right">Penal+GST</th>
                            <th class="text-right">Total Due</th>
                            <th class="text-right">Paid</th>
                            <th class="text-center" style="padding-right: 12px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loan->repaymentSchedules as $s)
                        <tr style="{{ $s->status === 'overdue' ? 'background-color: #fff1f2;' : '' }} {{ $s->status === 'paid' ? 'opacity: 0.6;' : '' }}">
                            <td class="text-center text-muted" style="padding-left: 12px; font-size: 12px;">{{ $s->installment_no }}</td>
                            <td style="font-size: 12px; {{ $s->status === 'overdue' ? 'font-weight: 600; color: #b91c1c;' : 'color: var(--text-secondary);' }}">
                                {{ $s->due_date->format('d-M-Y') }}
                            </td>
                            <td class="text-right text-muted" style="font-size: 12px;">{{ inrL((float)$s->opening_balance) }}</td>
                            <td class="text-right text-muted" style="font-size: 12px;">{{ inrL((float)$s->principal_due) }}</td>
                            <td class="text-right text-muted" style="font-size: 12px;">{{ inrL((float)$s->interest_due) }}</td>
                            <td class="text-right" style="font-size: 12px; {{ $s->penal_charges_due > 0 ? 'color: #e11d48; font-weight: 600;' : 'color: var(--text-tertiary);' }}">
                                @if($s->penal_charges_due > 0)
                                    {{ inrL((float)$s->penal_charges_due + (float)$s->penal_gst_due) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-right font-bold" style="font-size: 12px; color: var(--text-primary);">{{ inrL((float)$s->total_due + (float)$s->penal_charges_due + (float)$s->penal_gst_due) }}</td>
                            <td class="text-right font-semibold" style="font-size: 12px; color: #059669;">{{ inrL((float)$s->total_paid) }}</td>
                            <td class="text-center" style="padding-right: 12px;">
                                @if($s->status === 'paid')
                                    <span class="badge-std">✓ Paid</span>
                                @elseif($s->status === 'overdue')
                                    <span class="badge-npa">Overdue</span>
                                @elseif($s->status === 'partial')
                                    <span class="badge-sma1">Partial</span>
                                @else
                                    <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; background: var(--border-light); color: var(--text-secondary);">Pending</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Collection History -->
        @if($loan->collectionTransactions->isNotEmpty())
        <div class="panel" style="padding: 0; overflow: hidden; margin-bottom: 0;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-light);">
                <h2 style="font-weight: 600; color: var(--text-secondary); margin: 0; font-size: 16px;">Collection History</h2>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr style="background: rgba(15, 23, 42, 0.02);">
                            <th class="text-left" style="padding-left: 20px;">Receipt No.</th>
                            <th class="text-left">Date</th>
                            <th class="text-right">Amount</th>
                            <th class="text-left">Mode</th>
                            <th class="text-left">Collected By</th>
                            <th class="text-left" style="padding-right: 20px;">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loan->collectionTransactions->sortByDesc('collection_date') as $txn)
                        <tr>
                            <td class="font-mono text-muted" style="font-size: 12px; padding-left: 20px;">{{ $txn->receipt_no }}</td>
                            <td style="font-size: 12px; color: var(--text-secondary);">{{ $txn->collection_date->format('d-M-Y') }}</td>
                            <td class="text-right font-semibold" style="font-size: 12px; color: #047857;">{{ inrL((float)$txn->amount_collected) }}</td>
                            <td style="font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">{{ str_replace('_',' ',$txn->payment_mode) }}</td>
                            <td style="font-size: 12px; color: var(--text-secondary);">{{ $txn->collected_by }}
                                @if($txn->peerPayer)
                                    <span style="margin-left: 4px; color: var(--brand-600); font-size: 12px;">(Peer: {{ $txn->peerPayer->full_name }})</span>
                                @endif
                            </td>
                            <td class="text-muted" style="font-size: 12px; padding-right: 20px;">{{ $txn->remarks }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Statutory Notices -->
        @if($loan->statutoryNotices->isNotEmpty())
        <div class="panel" style="padding: 0; overflow: hidden; margin-bottom: 0;">
            <div style="padding: 16px 20px; border-bottom: 1px solid #fecdd3; background: #fff1f2;">
                <h2 style="font-weight: 600; color: #9f1239; margin: 0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                    <svg style="width: 16px; height: 16px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    Statutory Notices Issued
                </h2>
            </div>
            <div style="display: flex; flex-direction: column;">
                @foreach($loan->statutoryNotices as $notice)
                <div style="padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-light);">
                    <div>
                        <p style="font-weight: 500; color: var(--text-primary); font-size: 14px; margin: 0;">{{ $notice->notice_type_label }}</p>
                        <p class="text-muted" style="font-size: 12px; margin: 0;">Ref: {{ $notice->notice_ref_no }} · Dispatched: {{ $notice->dispatch_date->format('d-M-Y') }}</p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; background: var(--border-light); color: var(--text-secondary);">
                            {{ str_replace('_',' ', $notice->status) }}
                        </span>
                        <a href="{{ route('recovery.legal.notice', $notice->id) }}" target="_blank" class="text-link">Preview →</a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<style>
/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity 0.3s ease;
}
.modal-overlay.show {
    opacity: 1;
}
.modal-content {
    background: var(--surface-light);
    border-radius: var(--border-radius-xl);
    padding: 24px;
    width: 100%;
    max-width: 450px;
    box-shadow: var(--shadow-glass);
    transform: scale(0.95);
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.modal-overlay.show .modal-content {
    transform: scale(1);
}
</style>
<script>
    $(document).ready(function() {
        $('#scheduleTable').DataTable({
            paging: false, searching: false, info: false,
            order: [[0, 'asc']],
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        const btnCollect = document.getElementById('btnCollect');
        const collectModal = document.getElementById('collectModal');
        const closeBtns = document.querySelectorAll('.modal-close-btn');

        function openModal(modal) {
            modal.style.display = 'flex';
            void modal.offsetWidth;
            modal.classList.add('show');
        }

        function closeModal(modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        if(btnCollect) btnCollect.addEventListener('click', () => openModal(collectModal));

        closeBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal-overlay');
                closeModal(modal);
            });
        });

        if (collectModal) {
            collectModal.addEventListener('click', (e) => {
                if (e.target === collectModal) {
                    closeModal(collectModal);
                }
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && collectModal.classList.contains('show')) {
                    closeModal(collectModal);
                }
            });
        }
    });
</script>
@endpush
@endsection
