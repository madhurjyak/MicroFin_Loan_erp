@extends('layouts.app')
@section('title', 'My Applications')
@section('page-title', '📋 My Loan Applications')

@section('content')

{{-- ── Quick Stats ── --}}
<div class="dashboard-kpi-grid">
    <div class="metric-card">
        <p class="metric-title">Total Filed</p>
        <p class="metric-value">{{ $stats['total'] }}</p>
    </div>
    <div class="metric-card">
        <p class="metric-title">Pending Review</p>
        <p class="metric-value" style="color: #2563eb;">{{ $stats['submitted'] }}</p>
    </div>
    <div class="metric-card">
        <p class="metric-title">Approved</p>
        <p class="metric-value text-emerald">{{ $stats['approved'] }}</p>
    </div>
    <div class="metric-card">
        <p class="metric-title">Rejected</p>
        <p class="metric-value text-rose">{{ $stats['rejected'] }}</p>
    </div>
</div>

{{-- ── Applications Table ── --}}
<div class="panel">
    <div class="panel-header-action">
        <h3 class="panel-title">Application History</h3>
        <a href="{{ route('los.apply') }}" class="btn-primary-sm">
            + New Application
        </a>
    </div>

    <div class="table-responsive">
        <table id="myAppsTable" class="data-table">
            <thead>
                <tr>
                    <th class="text-left">App No.</th>
                    <th class="text-left">Customer</th>
                    <th class="text-left">Center</th>
                    <th class="text-right">Amount</th>
                    <th class="text-center">Tenure</th>
                    <th class="text-center">Stage</th>
                    <th class="text-left">Filed On</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be loaded via AJAX -->
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<style>
/* ── DataTable Polish ── */
.dataTables_wrapper { margin-top: 12px; }
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter { margin-bottom: 16px; font-size: 13px; color: var(--text-secondary); }
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
.dataTables_wrapper .dataTables_info { font-size: 13px; color: var(--text-tertiary); padding-top: 16px; }
.dataTables_wrapper .dataTables_paginate { padding-top: 16px; }
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
</style>
<script>
$(document).ready(function() {
    $('#myAppsTable').DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "{{ route('los.my-applications.data') }}",
            "type": "GET"
        },
        "columns": [
            {
                "data": "application_no",
                "render": function(data, type, row) {
                    return '<span class="font-mono font-medium" style="color: var(--brand-600);">' + (data || '') + '</span>';
                }
            },
            {
                "data": "customer.full_name",
                "render": function(data, type, row) {
                    let name = data || '—';
                    let aadhaar = (row.customer && row.customer.masked_aadhaar) ? row.customer.masked_aadhaar : '';
                    return '<div class="font-medium text-slate-800">' + name + '</div>' +
                           '<div class="text-muted">' + aadhaar + '</div>';
                }
            },
            {
                "data": "customer.group.center.center_name",
                "render": function(data, type, row) {
                    return '<span class="text-muted">' + (data || '—') + '</span>';
                }
            },
            {
                "data": "applied_amount",
                "className": "text-right",
                "render": function(data, type, row) {
                    let amount = parseFloat(data || 0).toLocaleString('en-IN', { maximumFractionDigits: 2, minimumFractionDigits: 2 });
                    return '<span class="font-medium text-slate-800">₹' + amount + '</span>';
                }
            },
            {
                "data": "tenure",
                "className": "text-center text-muted",
                "render": function(data, type, row) {
                    let freq = row.repayment_frequency === 'weekly' ? 'wks' : 'mo';
                    return (data || '0') + ' ' + freq;
                }
            },
            {
                "data": "stage",
                "className": "text-center",
                "render": function(data, type, row) {
                    let badgeClass = data === 'approved' ? 'std' : (data === 'rejected' ? 'npa' : 'sma0');
                    let label = row.stage_label || data;
                    let html = '<span class="badge-' + badgeClass + '">' + label + '</span>';
                    if (data === 'rejected' && row.rejection_reason) {
                        let reason = row.rejection_reason.replace(/"/g, '&quot;');
                        html += '<p class="text-rose text-muted mt-1" style="max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 4px auto 0;" title="' + reason + '">' + reason + '</p>';
                    }
                    return html;
                }
            },
            {
                "data": "created_at",
                "className": "text-muted",
                "render": function(data, type, row) {
                    if (!data) return '';
                    let d = new Date(data);
                    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                }
            }
        ],
        "order": [[ 6, "desc" ]],
        "pageLength": 15,
        "language": {
            "search": "",
            "searchPlaceholder": "🔍 Filter...",
            "emptyTable": '<div class="empty-state" style="padding: 40px 20px; text-align: center;"><p style="font-size: 18px; margin-bottom: 8px;">📝 No applications yet</p><a href="{{ route("los.apply") }}" class="text-link">File your first loan application →</a></div>'
        }
    });
});
</script>
@endpush
@endsection
