@extends('layouts.app')
@section('title', 'Kendra Collection Day Sheet')
@section('page-title', '📋 Unified Kendra Collection Day Sheet')

@push('head')
<style>
.cds-summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.cds-stat-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem; text-align: center; }
.cds-stat-value { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin: .5rem 0 0 0; }
.cds-stat-label { font-size: .8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }

.settle-modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); backdrop-filter: blur(4px); z-index:1000; align-items:center; justify-content:center; opacity: 0; transition: opacity 0.3s ease; }
.settle-modal-overlay.show { display:flex; opacity: 1; }
.settle-modal { background: var(--bg-card); border-radius: 16px; padding: 2rem; width: 450px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); transform: translateY(20px) scale(0.95); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid var(--border-light); }
.settle-modal-overlay.show .settle-modal { transform: translateY(0) scale(1); }

.amount-input-group { display:flex; align-items:center; margin-bottom:1.25rem; background: var(--bg-primary); padding: 10px 16px; border-radius: 8px; border: 1px solid var(--border-light); transition: border-color 0.2s, box-shadow 0.2s; }
.amount-input-group:focus-within { border-color: var(--brand-400); box-shadow: 0 0 0 3px var(--brand-100); }
.amount-input-group label { width: 140px; font-weight: 600; font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin: 0; }
.amount-input-group input { flex: 1; text-align: right; font-weight: 700; font-size: 1.25rem; background: transparent; border: none; color: var(--text-primary); outline: none; padding: 0; }
.amount-input-group input:focus { color: var(--brand-600); }

.modal-header-title { display: flex; align-items: center; gap: 12px; margin:0; font-size:1.25rem; font-weight: 700; color: var(--text-primary); }
.modal-header-icon { background: var(--brand-100); color: var(--brand-600); width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
</style>
@endpush

@section('content')
@php
    function inrFmt(float $a): string { return \App\Helpers\IndianCurrency::format($a); }
@endphp

<!-- Filter Bar -->
<div class="panel mb-4">
    <form method="GET" action="{{ route('lms.cds') }}" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end;">
        <div>
            <label class="form-label mb-1">Center / Kendra</label>
            <select name="center_id" id="center_id" class="form-input" style="width: 260px; padding: 8px 12px;" required>
                <option value="">— Select Center —</option>
                @foreach($centers as $c)
                    <option value="{{ $c->id }}" {{ request('center_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->branch_name }} › {{ $c->center_name }} ({{ $c->meeting_day }})
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label mb-1">Collection Date</label>
            <input type="date" name="collection_date" value="{{ $collectionDate }}" class="form-input" style="padding: 8px 12px;" required>
        </div>
        <button type="submit" class="btn-primary" style="padding: 8px 20px;">Load Unified Sheet</button>
    </form>
</div>

@if($selectedCenter)
<!-- Center Info & Summary -->
<div class="cds-summary-grid">
    <div class="cds-stat-card" style="background: linear-gradient(135deg, var(--brand-600), var(--brand-800)); color: white; text-align: left;">
        <p style="font-size:.75rem; opacity:.8; margin:0; font-weight:600;">{{ strtoupper($selectedCenter->branch_name) }}</p>
        <p style="font-size:1.2rem; font-weight:700; margin:.25rem 0;">{{ $selectedCenter->center_name }}</p>
        <p style="font-size:.8rem; opacity:.9; margin:0;">{{ $selectedCenter->meeting_day }} @ {{ $selectedCenter->meeting_time }}</p>
        <p style="font-size:.8rem; opacity:.9; margin-top:.5rem;">{{ \Carbon\Carbon::parse($collectionDate)->format('d-M-Y') }}</p>
    </div>
    <div class="cds-stat-card">
        <p class="cds-stat-label">Total Members</p>
        <p class="cds-stat-value">{{ $cdsSummary['total_members'] }}</p>
    </div>
    <div class="cds-stat-card">
        <p class="cds-stat-label">Expected (Loan + RD)</p>
        <p class="cds-stat-value" style="color:var(--brand-600);">{{ inrFmt($cdsSummary['total_due']) }}</p>
        <p style="font-size:.75rem; color:var(--text-muted); margin-top:.25rem;">
            L: {{ inrFmt($cdsSummary['total_loan_due']) }} | S: {{ inrFmt($cdsSummary['total_saving_due']) }}
        </p>
    </div>
    <div class="cds-stat-card">
        <p class="cds-stat-label">Collection Status</p>
        <p class="cds-stat-value" id="overall-status" style="color:var(--text-muted);">Pending</p>
    </div>
</div>

@foreach($groups as $group)
<div class="panel mb-4" style="padding: 0; overflow: hidden;">
    <!-- Group Header -->
    <div style="background: var(--bg-primary); border-bottom: 1px solid var(--border-light); padding: 12px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <span style="font-weight: 600; color: var(--text-primary);">{{ $group->group_name }}</span>
            <span class="text-muted" style="margin-left: 8px;">Leader: {{ $group->group_leader_name }}</span>
        </div>
        <span class="text-muted">{{ $group->customers->count() }} members</span>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr style="background: rgba(15, 23, 42, 0.02);">
                    <th class="text-left" style="padding-left: 20px;">#</th>
                    <th class="text-left">Member</th>
                    <th class="text-right">Loan EMI + Penal</th>
                    <th class="text-right">RD Savings</th>
                    <th class="text-right">Total Expected</th>
                    <th class="text-right" style="padding-right: 20px;">Action</th>
                </tr>
            </thead>
            <tbody>
            @foreach($group->customers as $idx => $customer)
                @php
                    $loan = $customer->loans->first();
                    $savings = $customer->savingsAccounts->first();

                    $loanDue = 0; $penalDue = 0; $loanHasOverdue = false;
                    if ($loan && $loan->repaymentSchedules->count()) {
                        $ls = $loan->repaymentSchedules->first();
                        $loanDue = (float)$ls->principal_due + (float)$ls->interest_due - (float)$ls->total_paid;
                        $penalDue = (float)$ls->penal_charges_due + (float)$ls->penal_gst_due;
                        $loanHasOverdue = $ls->status === 'overdue';
                    }

                    $rdDue = 0;
                    if ($savings && $savings->schedules->count()) {
                        $ss = $savings->schedules->first();
                        $rdDue = (float)$ss->amount_expected - (float)$ss->amount_collected;
                    }

                    $totalDue = $loanDue + $penalDue + $rdDue;
                    $isLeader = $customer->full_name === $group->group_leader_name;
                    $rowId = 'row-cust-' . $customer->id;
                @endphp
                <tr id="{{ $rowId }}" style="{{ $loanHasOverdue ? 'background-color: #fff1f2;' : '' }}">
                    <td class="text-muted" style="padding-left: 20px;">{{ $idx + 1 }}</td>
                    <td>
                        <span class="font-medium text-slate-800">{{ $customer->full_name }}</span>
                        @if($isLeader) <span style="background: var(--brand-100); color: var(--brand-700); font-size:10px; padding:2px 6px; border-radius:4px; font-weight:600; margin-left:4px;">GL</span> @endif
                        <span style="display: block; font-size: 11px; color: var(--text-tertiary);">{{ $customer->customer_code }}</span>
                    </td>
                    <td class="text-right">
                        @if($loanDue > 0 || $penalDue > 0)
                            <span class="font-semibold" style="color:var(--text-primary);">{{ inrFmt($loanDue) }}</span>
                            @if($penalDue > 0) <span style="color:#ef4444; font-size:.8rem; display:block;">+ {{ inrFmt($penalDue) }} (Penal)</span> @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($rdDue > 0)
                            <span class="font-semibold text-emerald">{{ inrFmt($rdDue) }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-right font-bold" style="font-size:1.05rem;">{{ inrFmt($totalDue) }}</td>
                    <td class="text-right" style="padding-right: 20px;">
                        @if($totalDue > 0)
                        <button onclick="openSettleModal({{ $customer->id }}, '{{ addslashes($customer->full_name) }}', {{ $loanDue + $penalDue }}, {{ $rdDue }})"
                            class="btn-primary" style="padding: 6px 16px; font-size: 0.85rem; border-radius: 6px; font-weight: 600; transition: all 0.2s; box-shadow: 0 2px 4px rgba(14,165,233,0.2);">Collect</button>
                        @else
                        <span style="color:#10b981; font-weight:600; font-size:.85rem; display: inline-flex; align-items: center; gap: 4px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Cleared
                        </span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach

@else
<div class="empty-state" style="border: 1px dashed var(--border-light); border-radius: var(--border-radius-xl); padding: 64px 20px;">
    <div style="font-size: 48px; margin-bottom: 16px;">🏘️</div>
    <p style="font-weight: 500; color: var(--text-secondary); margin-bottom: 4px;">Select a Kendra / Center to load today's collection sheet</p>
    <p style="font-size: 12px; color: var(--text-tertiary);">Displays unified group-wise member dues (Loan EMI + RD Savings)</p>
</div>
@endif

<!-- Bulk Settle Modal -->
<div id="settleModal" class="settle-modal-overlay">
    <div class="settle-modal">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem;">
            <div class="modal-header-title">
                <div class="modal-header-icon">💸</div>
                <div>
                    <h3 style="margin:0; font-size:1.25rem; font-weight:700;">Collect Payment</h3>
                    <p id="modalCustomerName" style="font-size:0.9rem; font-weight:500; color:var(--text-secondary); margin:4px 0 0 0;"></p>
                </div>
            </div>
            <button onclick="closeSettleModal()" style="background:var(--bg-primary); border:1px solid var(--border-light); cursor:pointer; color:var(--text-muted); font-size:1.2rem; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">✕</button>
        </div>

        <div id="modalAlert" style="display:none; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:.9rem; font-weight: 500;"></div>

        <form id="settleForm" onsubmit="submitSettle(event)">
            @csrf
            <input type="hidden" id="modalCustomerId" name="customer_id">
            <input type="hidden" name="collection_date" value="{{ $collectionDate }}">

            <div class="amount-input-group">
                <label>Loan EMI (₹)</label>
                <input type="number" id="modalLoanAmt" name="loan_amount" step="0.01" min="0">
            </div>

            <div class="amount-input-group">
                <label>RD Savings (₹)</label>
                <input type="number" id="modalRdAmt" name="savings_amount" step="0.01" min="0">
            </div>

            <div class="amount-input-group" style="background: linear-gradient(to right, var(--brand-50), transparent); border-color: var(--brand-200);">
                <label style="color: var(--brand-700);">Total Receive (₹)</label>
                <input type="text" id="modalTotalAmt" readonly style="color: var(--brand-700);">
            </div>

            <div class="form-group" style="margin-top:1.5rem;">
                <label class="form-label" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Payment Mode</label>
                <select name="payment_mode" class="form-control" style="font-weight: 500; padding: 10px 14px; border-radius: 8px;">
                    <option value="cash">💵 Cash (Center Meeting)</option>
                    <option value="upi_qr">📱 UPI / QR Code</option>
                </select>
            </div>

            <div style="display:flex; gap:1rem; margin-top:2rem;">
                <button type="button" onclick="closeSettleModal()" class="btn-secondary" style="flex:1; padding: 12px; font-weight: 600; border-radius: 8px;">Cancel</button>
                <button type="submit" class="btn-primary" style="flex:2; padding: 12px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(14,165,233,0.2), 0 2px 4px -1px rgba(14,165,233,0.1);" id="btnSubmitSettle">Confirm Payment</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function updateModalTotal() {
    let l = parseFloat(document.getElementById('modalLoanAmt').value) || 0;
    let s = parseFloat(document.getElementById('modalRdAmt').value) || 0;
    document.getElementById('modalTotalAmt').value = '₹ ' + (l + s).toLocaleString('en-IN', {minimumFractionDigits:2});
}

document.getElementById('modalLoanAmt').addEventListener('input', updateModalTotal);
document.getElementById('modalRdAmt').addEventListener('input', updateModalTotal);

function openSettleModal(custId, custName, loanDue, rdDue) {
    document.getElementById('modalCustomerId').value = custId;
    document.getElementById('modalCustomerName').textContent = custName;
    document.getElementById('modalLoanAmt').value = loanDue.toFixed(2);
    document.getElementById('modalRdAmt').value = rdDue.toFixed(2);
    updateModalTotal();

    document.getElementById('modalAlert').style.display = 'none';
    document.getElementById('btnSubmitSettle').disabled = false;
    document.getElementById('settleModal').classList.add('show');
}

function closeSettleModal() {
    document.getElementById('settleModal').classList.remove('show');
}

async function submitSettle(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitSettle');
    const alertBox = document.getElementById('modalAlert');
    btn.disabled = true;
    btn.textContent = 'Processing...';

    const form = e.target;
    const formData = new FormData(form);

    try {
        const response = await fetch("{{ route('lms.cds.bulk-settle') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();

        if (response.ok && result.success) {
            alertBox.style.display = 'block';
            alertBox.style.backgroundColor = 'rgba(16,185,129,.1)';
            alertBox.style.color = '#059669';
            alertBox.textContent = 'Settled! Receipt: ' + result.receipt;

            // Update UI row
            const custId = document.getElementById('modalCustomerId').value;
            const row = document.getElementById('row-cust-' + custId);
            if(row) {
                const actionCell = row.cells[row.cells.length - 1];
                actionCell.innerHTML = '<span style="color:#10b981; font-weight:600; font-size:.85rem;">✓ ' + result.receipt + '</span>';
            }

            setTimeout(closeSettleModal, 1500);
        } else {
            throw new Error(result.message || 'Validation error');
        }
    } catch (error) {
        alertBox.style.display = 'block';
        alertBox.style.backgroundColor = 'rgba(239,68,68,.1)';
        alertBox.style.color = '#dc2626';
        alertBox.textContent = 'Error: ' + error.message;
        btn.disabled = false;
    }
    btn.textContent = 'Confirm Settlement';
}
</script>
@endpush
