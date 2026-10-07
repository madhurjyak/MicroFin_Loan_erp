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
            <!-- Loaded via AJAX -->
        </tbody>
    </table>
</div>

{{-- ── Transaction History ──────────────────────────────────────────────────── --}}
<div class="panel" style="margin-top:24px;">
    <div class="panel-header-action mb-4"><h3 class="panel-title">Collection Transactions</h3></div>
    <table class="data-table" id="txnTable">
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
            <!-- Loaded via AJAX -->
        </tbody>
    </table>
</div>

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
<style>
/* ── DataTable Polish (Outfit font & rounded aesthetics) ── */
.dataTables_wrapper {
    margin-top: 12px;
}

.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter {
    margin-bottom: 16px;
    font-size: 13px;
    color: var(--text-secondary);
}

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid var(--border-light);
    border-radius: 12px;
    padding: 8px 16px;
    font-family: inherit;
    font-size: 13px;
    background: var(--surface-light);
    outline: none;
    margin-left: 8px;
    transition: var(--transition-smooth);
}

.dataTables_wrapper .dataTables_filter input:focus {
    border-color: var(--brand-500);
    box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
}

.dataTables_wrapper .dataTables_length select {
    border: 1px solid var(--border-light);
    border-radius: 8px;
    padding: 6px 12px;
    font-family: inherit;
    font-size: 13px;
    margin: 0 4px;
    background: var(--surface-light);
}

.dataTables_wrapper .dataTables_info {
    font-size: 13px;
    color: var(--text-tertiary);
    padding-top: 16px;
}

.dataTables_wrapper .dataTables_paginate {
    padding-top: 16px;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: 8px !important;
    border: 1px solid transparent !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    padding: 6px 12px !important;
    color: var(--text-secondary) !important;
    transition: var(--transition-smooth);
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: var(--brand-50) !important;
    color: var(--brand-600) !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background: var(--brand-600) !important;
    color: white !important;
    box-shadow: 0 2px 6px rgba(29, 78, 216, 0.3) !important;
}
</style>

<script>
    $(document).ready(function() {
        $('#schedTable').DataTable({
            "processing": true,
            "serverSide": false,
            "ajax": {
                "url": "{{ route('sms.savings.schedule.data', $account->id) }}",
                "type": "GET",
            },
            "columns": [
                { "data": "installment_no" },
                { 
                    "data": "due_date",
                    "render": function(data) {
                        if (!data) return '—';
                        let d = new Date(data);
                        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).replace(/ /g, '-');
                    }
                },
                { 
                    "data": "amount_expected",
                    "render": function(data) {
                        return data ? parseFloat(data).toLocaleString('en-IN', { style: 'currency', currency: 'INR' }) : '—';
                    }
                },
                { 
                    "data": "amount_collected",
                    "render": function(data) {
                        return data ? parseFloat(data).toLocaleString('en-IN', { style: 'currency', currency: 'INR' }) : '—';
                    }
                },
                { 
                    "data": "interest_accrued",
                    "render": function(data) {
                        let amt = data ? parseFloat(data).toLocaleString('en-IN', { style: 'currency', currency: 'INR' }) : '—';
                        return '<span style="color:#6366f1;">' + amt + '</span>';
                    }
                },
                { 
                    "data": null,
                    "render": function(data, type, row) {
                        let expected = row.amount_expected ? parseFloat(row.amount_expected) : 0;
                        let collected = row.amount_collected ? parseFloat(row.amount_collected) : 0;
                        let balDue = Math.max(0, expected - collected);
                        if (balDue > 0) {
                            return '<span style="color:#ef4444;font-weight:600;">' + balDue.toLocaleString('en-IN', { style: 'currency', currency: 'INR' }) + '</span>';
                        }
                        return '<span style="color:#10b981;">—</span>';
                    }
                },
                { 
                    "data": "collection_date",
                    "render": function(data) {
                        if (!data) return '—';
                        let d = new Date(data);
                        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).replace(/ /g, '-');
                    }
                },
                { 
                    "data": "status",
                    "render": function(data) {
                        let status = data ? data.charAt(0).toUpperCase() + data.slice(1) : '';
                        return '<span class="sch-status-' + data + '">' + status + '</span>';
                    }
                }
            ],
            "createdRow": function(row, data, dataIndex) {
                if (data.status === 'missed') {
                    $(row).addClass('overdue-row');
                }
            },
            "paging": true,
            "searching": false,
            "ordering": false
        });

        $('#txnTable').DataTable({
            "processing": true,
            "serverSide": false,
            "ajax": {
                "url": "{{ route('sms.savings.transactions.data', $account->id) }}",
                "type": "GET",
            },
            "columns": [
                { 
                    "data": "receipt_no",
                    "render": function(data) {
                        return '<span class="mono-code" style="font-size:.78rem;">' + (data || '') + '</span>';
                    }
                },
                { 
                    "data": "collection_date",
                    "render": function(data) {
                        if (!data) return '—';
                        let d = new Date(data);
                        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).replace(/ /g, '-');
                    }
                },
                { 
                    "data": "amount_collected",
                    "render": function(data) {
                        let amt = data ? parseFloat(data).toLocaleString('en-IN', { style: 'currency', currency: 'INR' }) : '—';
                        return '<strong style="color:#10b981;">' + amt + '</strong>';
                    }
                },
                { 
                    "data": "payment_mode",
                    "render": function(data) {
                        return data ? data.replace(/_/g, ' ').toUpperCase() : '';
                    }
                },
                { "data": "collected_by" },
                { 
                    "data": "remarks",
                    "render": function(data) {
                        return '<span style="font-size:.8rem;color:var(--text-muted);">' + (data || '') + '</span>';
                    }
                }
            ],
            "paging": true,
            "searching": false,
            "ordering": false
        });
    });
</script>
@endpush
