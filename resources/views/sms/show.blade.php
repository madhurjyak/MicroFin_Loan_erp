@extends('layouts.app')

@section('title', 'RD Ledger — ' . $account->account_no)
@section('page-title', 'RD Account Ledger')

@push('head')
<style>
.rd-header-grid { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 20px; margin-bottom: 24px; }
.kyc-row        { display: flex; justify-content: space-between; padding: .5rem 0; border-bottom: 1px solid var(--border-light); font-size: .85rem; }
.kyc-row:last-child { border: none; }
.kyc-label      { color: var(--text-muted); }
.kyc-value      { font-weight: 500; color: var(--text-primary); }
.sch-status-paid    { color: #10b981; font-weight: 600; }
.sch-status-pending { color: #f59e0b; font-weight: 600; }
.sch-status-missed  { color: #ef4444; font-weight: 600; }
.sch-status-partial { color: #3b82f6; font-weight: 600; }
.collect-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; align-items:center; justify-content:center; }
.collect-modal-overlay.show { display:flex; }
.collect-modal  { background: var(--bg-card); border-radius: var(--border-radius-lg); padding: 2rem; width: 420px; box-shadow: var(--shadow-xl); }
.progress-bar   { height: 10px; background: var(--border-light); border-radius: 6px; overflow: hidden; margin: .5rem 0; }
.progress-bar-fill { height: 100%; border-radius: 6px; background: linear-gradient(90deg, #10b981, #06b6d4); transition: width .6s ease; }
@media (max-width:900px) { .rd-header-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')

{{-- ── Breadcrumb ────────────────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1.5rem;font-size:.85rem;color:var(--text-muted);">
    <a href="{{ route('sms.savings.index') }}" style="color:var(--accent-primary);">Savings</a>
    <span>›</span>
    <span style="color:var(--text-primary);">{{ $account->account_no }}</span>
</div>

{{-- ── Header Grid ──────────────────────────────────────────────────────────── --}}
<div class="rd-header-grid">
    {{-- KYC Card --}}
    <div class="panel">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <div>
                <h2 style="font-size:1.2rem;font-weight:700;margin:0;">{{ $account->customer->full_name }}</h2>
                <small style="color:var(--text-muted);">{{ $account->customer->customer_code }} · {{ $account->customer->group->group_name ?? '' }}</small>
            </div>
            <span style="background:rgba(16,185,129,.12);color:#10b981;padding:.3rem .8rem;border-radius:999px;font-size:.8rem;font-weight:600;">
                {{ strtoupper($account->status) }}
            </span>
        </div>
        <div class="kyc-row"><span class="kyc-label">RD Account No</span><span class="kyc-value mono-code">{{ $account->account_no }}</span></div>
        <div class="kyc-row"><span class="kyc-label">Phone</span><span class="kyc-value">{{ $account->customer->phone }}</span></div>
        <div class="kyc-row"><span class="kyc-label">Aadhaar</span><span class="kyc-value">{{ $account->customer->masked_aadhaar }}</span></div>
        <div class="kyc-row"><span class="kyc-label">PAN</span><span class="kyc-value">{{ $account->customer->pan_number }}</span></div>
        <div class="kyc-row"><span class="kyc-label">IFSC</span><span class="kyc-value">{{ $account->customer->ifsc_code ?? '—' }}</span></div>
        <div class="kyc-row"><span class="kyc-label">District / State</span><span class="kyc-value">{{ $account->customer->district }}, {{ $account->customer->state }}</span></div>
        <div class="kyc-row"><span class="kyc-label">Center</span><span class="kyc-value">{{ $account->customer->group->center->center_name ?? '—' }}</span></div>
    </div>

    {{-- Deposit Terms --}}
    <div class="panel" style="text-align: center;">
        <p style="color:var(--text-muted);font-size:.78rem;margin-bottom:.5rem;">PERIODIC DEPOSIT</p>
        <div class="rd-stat-big" style="color:var(--accent-primary);">{{ \App\Helpers\IndianCurrency::format($account->deposit_amount) }}</div>
        <p style="font-size:.85rem;color:var(--text-secondary);">{{ ucfirst($account->frequency) }}</p>
        <hr style="border:none;border-top:1px solid var(--border);margin:.75rem 0;">
        <div style="font-size:.82rem;text-align:left;">
            <div class="kyc-row"><span class="kyc-label">Interest Rate</span><span class="kyc-value">{{ $account->interest_rate }}% p.a.</span></div>
            <div class="kyc-row"><span class="kyc-label">Tenure</span><span class="kyc-value">{{ $account->tenure }} months</span></div>
            <div class="kyc-row"><span class="kyc-label">Opened</span><span class="kyc-value">{{ $account->opening_date->format('d M Y') }}</span></div>
            <div class="kyc-row"><span class="kyc-label">Maturity</span><span class="kyc-value">{{ $account->maturity_date?->format('d M Y') ?? '—' }}</span></div>
        </div>
        @if($account->status === 'active')
        <button onclick="document.getElementById('collectModal').classList.add('show')"
            class="btn btn-primary" style="width:100%;margin-top:1rem;">+ Record Deposit</button>
        @endif
    </div>

    {{-- Balance Summary --}}
    <div class="panel" style="text-align: center;">
        <p style="color:var(--text-muted);font-size:.78rem;margin-bottom:.5rem;">BALANCE SUMMARY</p>
        @php
            $totalInstallments  = $account->schedules->count();
            $collectedPct = $totalInstallments > 0 ? round(($paidInstallments / $totalInstallments) * 100) : 0;
        @endphp
        <div class="progress-bar"><div class="progress-bar-fill" style="width:{{ $collectedPct }}%;"></div></div>
        <p style="font-size:.75rem;color:var(--text-muted);text-align:right;">{{ $collectedPct }}% collected</p>

        <div style="font-size:.82rem;text-align:left;margin-top:.5rem;">
            <div class="kyc-row">
                <span class="kyc-label">Principal Collected</span>
                <span class="kyc-value" style="color:#10b981;">{{ \App\Helpers\IndianCurrency::format($account->total_principal_collected) }}</span>
            </div>
            <div class="kyc-row">
                <span class="kyc-label">Interest Accrued</span>
                <span class="kyc-value" style="color:#6366f1;">{{ \App\Helpers\IndianCurrency::format($account->total_interest_accrued) }}</span>
            </div>
            <div class="kyc-row">
                <span class="kyc-label">Projected Maturity</span>
                <span class="kyc-value" style="font-weight:700;">{{ \App\Helpers\IndianCurrency::format($account->maturity_amount ?? 0) }}</span>
            </div>
        </div>
        <hr style="border:none;border-top:1px solid var(--border);margin:.75rem 0;">
        <div style="font-size:.82rem;text-align:left;">
            <div class="kyc-row"><span class="kyc-label">✅ Paid</span><span class="kyc-value sch-status-paid">{{ $paidInstallments }}</span></div>
            <div class="kyc-row"><span class="kyc-label">🕐 Pending</span><span class="kyc-value sch-status-pending">{{ $pendingInstallments }}</span></div>
            <div class="kyc-row"><span class="kyc-label">❌ Missed</span><span class="kyc-value sch-status-missed">{{ $missedInstallments }}</span></div>
        </div>
    </div>
</div>

{{-- ── Deposit Schedule ─────────────────────────────────────────────────────── --}}
<div class="panel">
    <div class="panel-header-action mb-4">
        <h3 class="panel-title">Deposit Schedule</h3>
    </div>
    <table class="data-table" id="schedTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Due Date</th>
                <th>Expected</th>
                <th>Collected</th>
                <th>Interest Accrued</th>
                <th>Balance Due</th>
                <th>Collection Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        @foreach($account->schedules as $s)
            <tr @class(['overdue-row' => $s->status === 'missed'])>
                <td>{{ $s->installment_no }}</td>
                <td>{{ \Carbon\Carbon::parse($s->due_date)->format('d-M-Y') }}</td>
                <td>{{ \App\Helpers\IndianCurrency::format($s->amount_expected) }}</td>
                <td>{{ \App\Helpers\IndianCurrency::format($s->amount_collected) }}</td>
                <td><span style="color:#6366f1;">{{ \App\Helpers\IndianCurrency::format($s->interest_accrued) }}</span></td>
                <td>
                    @php $balDue = max(0, (float)$s->amount_expected - (float)$s->amount_collected); @endphp
                    @if($balDue > 0)
                        <span style="color:#ef4444;font-weight:600;">{{ \App\Helpers\IndianCurrency::format($balDue) }}</span>
                    @else
                        <span style="color:#10b981;">—</span>
                    @endif
                </td>
                <td>{{ $s->collection_date ? \Carbon\Carbon::parse($s->collection_date)->format('d-M-Y') : '—' }}</td>
                <td><span class="sch-status-{{ $s->status }}">{{ ucfirst($s->status) }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- ── Transaction History ──────────────────────────────────────────────────── --}}
@if($transactions->count())
<div class="panel" style="margin-top:24px;">
    <div class="panel-header-action mb-4"><h3 class="panel-title">Collection Transactions</h3></div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Receipt No</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Mode</th>
                <th>Collected By</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
        @foreach($transactions as $txn)
            <tr>
                <td><span class="mono-code" style="font-size:.78rem;">{{ $txn->receipt_no }}</span></td>
                <td>{{ $txn->collection_date->format('d-M-Y') }}</td>
                <td><strong style="color:#10b981;">{{ \App\Helpers\IndianCurrency::format($txn->amount_collected) }}</strong></td>
                <td>{{ strtoupper(str_replace('_',' ', $txn->payment_mode)) }}</td>
                <td>{{ $txn->collected_by }}</td>
                <td style="font-size:.8rem;color:var(--text-muted);">{{ $txn->remarks }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Collect Modal ────────────────────────────────────────────────────────── --}}
<div id="collectModal" class="collect-modal-overlay">
    <div class="collect-modal">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
            <h3 style="margin:0;font-size:1.1rem;">Record RD Deposit</h3>
            <button onclick="document.getElementById('collectModal').classList.remove('show')"
                style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:1.4rem;">✕</button>
        </div>
        <form method="POST" action="{{ route('sms.savings.collect', $account->id) }}">
            @csrf
            <div class="form-group mb-4">
                <label class="form-label mb-1">Amount (₹)</label>
                <input type="number" name="amount" class="form-input"
                    value="{{ $account->deposit_amount }}" step="0.01" min="1" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label mb-1">Payment Mode</label>
                <select name="payment_mode" class="form-input">
                    <option value="cash">Cash (Kendra)</option>
                    <option value="upi_qr">UPI / QR</option>
                    <option value="nach">e-NACH / AutoPay</option>
                </select>
            </div>
            <div class="form-group mb-4">
                <label class="form-label mb-1">Collection Date</label>
                <input type="date" name="collection_date" class="form-input"
                    value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label mb-1">Collected By</label>
                <input type="text" name="collected_by" class="form-input"
                    value="{{ auth()->user()->name }}" required>
            </div>
            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit" class="btn-primary" style="flex:1;">Record Deposit</button>
                <button type="button" onclick="document.getElementById('collectModal').classList.remove('show')"
                    class="btn-secondary" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
$('#schedTable').DataTable({ paging: false, searching: false, ordering: false });
</script>
@endpush
