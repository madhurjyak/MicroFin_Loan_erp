@extends('layouts.app')

@section('title', 'Savings Management — RD Accounts')
@section('page-title', 'Savings Management System (SMS)')

@push('head')
<style>
.progress-mini  { height: 6px; background: var(--border-light); border-radius: 4px; overflow: hidden; }
.progress-mini-fill { height: 100%; background: linear-gradient(90deg, var(--brand-500), var(--brand-400)); border-radius: 4px; }
</style>
@endpush

@section('content')

{{-- ── Summary Stats ────────────────────────────────────────────────────────── --}}
<div class="dashboard-kpi-grid">
    <!-- Total Savings Mobilized -->
    <div class="metric-card">
        <div class="metric-card-content">
            <div class="metric-info">
                <p class="metric-title">Total Savings Mobilized</p>
                <p class="metric-value text-brand">{{ \App\Helpers\IndianCurrency::format($totalSavingsMobilized) }}</p>
                <p class="metric-subtitle">Across {{ $totalSavingsAccounts }} active RD accounts</p>
            </div>
            <div class="metric-icon metric-icon-brand">💰</div>
        </div>
    </div>
    
    <!-- Total Interest Accrued -->
    <div class="metric-card">
        <div class="metric-card-content">
            <div class="metric-info">
                <p class="metric-title">Total Interest Accrued</p>
                <p class="metric-value text-emerald">{{ \App\Helpers\IndianCurrency::format($totalInterestAccrued) }}</p>
                <p class="metric-subtitle">On all active RD balances</p>
            </div>
            <div class="metric-icon metric-icon-emerald">📈</div>
        </div>
    </div>
    
    <!-- Active RD Accounts -->
    <div class="metric-card">
        <div class="metric-card-content">
            <div class="metric-info">
                <p class="metric-title">Active RD Accounts</p>
                <p class="metric-value text-saffron">{{ $activeCount }}</p>
                <p class="metric-subtitle">Recurring Deposit accounts</p>
            </div>
            <div class="metric-icon metric-icon-saffron">🗓️</div>
        </div>
    </div>
    
    <!-- Maturing This Month -->
    <div class="metric-card">
        <div class="metric-card-content">
            <div class="metric-info">
                <p class="metric-title">Maturing This Month</p>
                <p class="metric-value text-rose">{{ $maturingThisMonth }}</p>
                <p class="metric-subtitle">Accounts reaching maturity</p>
            </div>
            <div class="metric-icon metric-icon-rose">⏳</div>
        </div>
    </div>
</div>

{{-- ── Toolbar ──────────────────────────────────────────────────────────────── --}}
<div class="panel">
    <div class="panel-header-action mb-4">
        <h2 class="panel-title">RD Account Register</h2>
        <div style="display:flex;gap:12px;align-items:center;">
            <form method="GET" style="display:flex;gap:8px;align-items:center;">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search member / account..." class="form-input" style="width:240px; padding:8px 12px;">
                <select name="status" class="form-input" style="width:140px; padding:8px 12px;">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status')==='active'?'selected':'' }}>Active</option>
                    <option value="matured" {{ request('status')==='matured'?'selected':'' }}>Matured</option>
                    <option value="closed" {{ request('status')==='closed'?'selected':'' }}>Closed</option>
                </select>
                <button type="submit" class="btn-secondary" style="padding:8px 16px;">Filter</button>
            </form>
            <a href="{{ route('sms.savings.create') }}" class="btn-primary" style="padding:8px 16px;">+ Open RD Account</a>
        </div>
    </div>

    <table id="savingsTable" class="data-table">
        <thead>
            <tr>
                <th>Account No</th>
                <th>Member</th>
                <th>Group / Center</th>
                <th>Deposit / Freq</th>
                <th>Interest Rate</th>
                <th>Principal Collected</th>
                <th>Interest Accrued</th>
                <th>Progress</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <!-- Data will be loaded via AJAX -->
        </tbody>
    </table>

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
        $('#savingsTable').DataTable({
            "processing": true,
            "serverSide": false,
            "ajax": {
                "url": "{{ route('sms.savings.data') }}",
                "type": "GET",
            },
            "columns": [
                { 
                    "data": "account_no",
                    "render": function(data, type, row) {
                        return '<span class="mono-code" style="font-size:.82rem;">' + (data || '') + '</span>';
                    }
                },
                { 
                    "data": "customer",
                    "render": function(data, type, row) {
                        let fullName = data ? (data.full_name || '') : '';
                        let code = data ? (data.customer_code || '') : '';
                        return '<div class="member-info">' +
                               '<strong>' + fullName + '</strong>' +
                               '<small class="text-muted" style="display:block;">' + code + '</small>' +
                               '</div>';
                    }
                },
                { 
                    "data": "customer",
                    "render": function(data, type, row) {
                        let groupName = (data && data.group) ? (data.group.group_name || '—') : '—';
                        let centerName = (data && data.group && data.group.center) ? (data.group.center.center_name || '—') : '—';
                        return '<small>' + groupName + '</small><br>' +
                               '<small class="text-muted">' + centerName + '</small>';
                    }
                },
                { 
                    "data": "deposit_amount",
                    "render": function(data, type, row) {
                        let amt = data ? parseFloat(data).toLocaleString('en-IN', { style: 'currency', currency: 'INR' }) : '₹0.00';
                        let freq = row.frequency || '';
                        return '<strong>' + amt + '</strong>' +
                               '<span class="text-muted"> / ' + freq + '</span>';
                    }
                },
                { 
                    "data": "interest_rate",
                    "render": function(data, type, row) {
                        return (data || '0') + '% p.a.';
                    }
                },
                { 
                    "data": "total_principal_collected",
                    "render": function(data, type, row) {
                        let amt = data ? parseFloat(data).toLocaleString('en-IN', { style: 'currency', currency: 'INR' }) : '₹0.00';
                        return '<strong>' + amt + '</strong>';
                    }
                },
                { 
                    "data": "total_interest_accrued",
                    "render": function(data, type, row) {
                        let amt = data ? parseFloat(data).toLocaleString('en-IN', { style: 'currency', currency: 'INR' }) : '₹0.00';
                        return '<span style="color:#10b981;">' + amt + '</span>';
                    }
                },
                { 
                    "data": null,
                    "render": function(data, type, row) {
                        let paidCount = row.paid_installments || 0;
                        let totalCount = row.total_installments || 1;
                        let pct = totalCount > 0 ? Math.round((paidCount / totalCount) * 100) : 0;
                        let missed = row.missed_installments || 0;
                        let missedHtml = missed > 0 ? ' <span style="color:#ef4444;">(' + missed + ' missed)</span>' : '';
                        
                        return '<div style="font-size:.75rem;color:var(--text-secondary);margin-bottom:3px;">' +
                               paidCount + '/' + totalCount + ' installments' + missedHtml +
                               '</div>' +
                               '<div class="progress-mini">' +
                               '<div class="progress-mini-fill" style="width:' + pct + '%;"></div>' +
                               '</div>';
                    }
                },
                { 
                    "data": "status",
                    "render": function(data, type, row) {
                        if(data === 'active') {
                            return '<span class="badge-std">Active</span>';
                        } else if(data === 'closed') {
                            return '<span class="badge-sma0">Closed</span>';
                        } else {
                            let status = data ? data.charAt(0).toUpperCase() + data.slice(1) : '';
                            return '<span class="badge-npa">' + status + '</span>';
                        }
                    }
                },
                { 
                    "data": "id",
                    "orderable": false,
                    "render": function(data, type, row) {
                        let url = '{{ route("sms.savings.show", ":id") }}'.replace(':id', data);
                        return '<a href="' + url + '" class="btn-primary-sm">View</a>';
                    }
                }
            ],
            "order": [[ 0, "desc" ]],
            "pageLength": 10,
            "language": {
                "search": "",
                "searchPlaceholder": "🔍 Search accounts...",
                "emptyTable": '<div style="text-align:center;color:var(--text-muted);padding:2rem;">No RD accounts found.</div>'
            }
        });
    });
</script>
@endpush
