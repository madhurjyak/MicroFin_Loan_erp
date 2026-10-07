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

<div class="panel mb-4" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table id="cdsTable" class="data-table" style="width: 100%;">
            <thead>
                <tr style="background: rgba(15, 23, 42, 0.02);">
                    <th class="text-left" style="padding-left: 20px;">Group</th>
                    <th class="text-left">Member</th>
                    <th class="text-right">Loan EMI + Penal</th>
                    <th class="text-right">RD Savings</th>
                    <th class="text-right">Total Expected</th>
                    <th class="text-right" style="padding-right: 20px;">Action</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

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
<style>
/* ── DataTable Polish ── */
.dataTables_wrapper { margin-top: 12px; }
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter { margin-bottom: 16px; font-size: 13px; color: var(--text-secondary); padding: 0 20px; }
.dataTables_wrapper .dataTables_filter input {
    border: 1px solid var(--border-light); border-radius: 12px; padding: 8px 16px;
    font-family: inherit; font-size: 13px; background: var(--surface-light);
    outline: none; margin-left: 8px; transition: var(--transition-smooth);
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color: var(--brand-500); box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
}
.dataTables_wrapper .dataTables_length select {
    border: 1px solid var(--border-light); border-radius: 8px; padding: 6px 12px;
    font-family: inherit; font-size: 13px; margin: 0 4px; background: var(--surface-light);
}
.dataTables_wrapper .dataTables_info { font-size: 13px; color: var(--text-tertiary); padding: 16px 20px; }
.dataTables_wrapper .dataTables_paginate { padding: 16px 20px; }
.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: 8px !important; border: 1px solid transparent !important;
    font-size: 13px !important; font-weight: 500 !important; padding: 6px 12px !important;
    color: var(--text-secondary) !important; transition: var(--transition-smooth);
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: var(--brand-50) !important; color: var(--brand-600) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background: var(--brand-600) !important; color: white !important;
    box-shadow: 0 2px 6px rgba(29, 78, 216, 0.3) !important;
}
.group-header {
    background: var(--bg-primary);
    border-bottom: 1px solid var(--border-light);
    border-top: 1px solid var(--border-light);
    font-weight: 600;
    color: var(--text-primary);
    padding: 12px 20px !important;
}
</style>
<script>
@if($selectedCenter)
$(document).ready(function() {
    let fmt = (amt) => '₹' + parseFloat(amt || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    $('#cdsTable').DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "{{ route('lms.cds.data', ['center_id' => request('center_id'), 'collection_date' => request('collection_date')]) }}",
            "type": "GET"
        },
        "pageLength": 50,
        "ordering": false,
        "language": {
            "search": "",
            "searchPlaceholder": "🔍 Search Member...",
            "emptyTable": "No members found for this center."
        },
        "columns": [
            { "data": "group_name", "visible": false },
            {
                "data": "customer_name",
                "render": function(data, type, row) {
                    let gl = row.is_leader ? '<span style="background: var(--brand-100); color: var(--brand-700); font-size:10px; padding:2px 6px; border-radius:4px; font-weight:600; margin-left:4px;">GL</span>' : '';
                    return '<span class="font-medium text-slate-800">' + data + '</span>' + gl +
                           '<span style="display: block; font-size: 11px; color: var(--text-tertiary);">' + (row.customer_code || '') + '</span>';
                }
            },
            {
                "data": "loan_due",
                "className": "text-right",
                "render": function(data, type, row) {
                    let ld = parseFloat(row.loan_due || 0);
                    let pd = parseFloat(row.penal_due || 0);
                    if (ld > 0 || pd > 0) {
                        let html = '<span class="font-semibold" style="color:var(--text-primary);">' + fmt(ld) + '</span>';
                        if (pd > 0) html += '<span style="color:#ef4444; font-size:.8rem; display:block;">+ ' + fmt(pd) + ' (Penal)</span>';
                        return html;
                    }
                    return '<span class="text-muted">—</span>';
                }
            },
            {
                "data": "rd_due",
                "className": "text-right",
                "render": function(data, type, row) {
                    let rd = parseFloat(row.rd_due || 0);
                    if (rd > 0) return '<span class="font-semibold text-emerald">' + fmt(rd) + '</span>';
                    return '<span class="text-muted">—</span>';
                }
            },
            {
                "data": "total_due",
                "className": "text-right font-bold",
                "render": function(data, type, row) {
                    return '<span style="font-size:1.05rem;">' + fmt(row.total_due) + '</span>';
                }
            },
            {
                "data": "total_due",
                "className": "text-right",
                "render": function(data, type, row) {
                    if (parseFloat(row.total_due || 0) > 0) {
                        let ld = parseFloat(row.loan_due || 0) + parseFloat(row.penal_due || 0);
                        let rd = parseFloat(row.rd_due || 0);
                        let nameEscaped = row.customer_name.replace(/'/g, "\\'");
                        return `<button onclick="openSettleModal(${row.id}, '${nameEscaped}', ${ld}, ${rd})" class="btn-primary" style="padding: 6px 16px; font-size: 0.85rem; border-radius: 6px; font-weight: 600; transition: all 0.2s; box-shadow: 0 2px 4px rgba(14,165,233,0.2);">Collect</button>`;
                    }
                    return '<span style="color:#10b981; font-weight:600; font-size:.85rem; display: inline-flex; align-items: center; gap: 4px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Cleared</span>';
                }
            }
        ],
        "createdRow": function(row, data, dataIndex) {
            row.id = 'row-cust-' + data.id;
            if (data.loan_has_overdue) {
                $(row).css('background-color', '#fff1f2');
            }
        },
        "drawCallback": function (settings) {
            let api = this.api();
            let rows = api.rows({page:'current'}).nodes();
            let last = null;
 
            api.column(0, {page:'current'}).data().each(function (group, i) {
                if (last !== group) {
                    // Find the leader for this group from the data
                    let rowData = api.row(rows[i]).data();
                    let leaderStr = rowData.leader_name ? `<span class="text-muted" style="margin-left: 8px; font-weight: normal; font-size: 13px;">Leader: ${rowData.leader_name}</span>` : '';
                    
                    $(rows[i]).before(
                        '<tr class="group"><td colspan="5" class="group-header">' + group + leaderStr + '</td></tr>'
                    );
                    last = group;
                }
            });
        }
    });
});
@endif

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
