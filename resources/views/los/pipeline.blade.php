@extends('layouts.app')
@section('title', 'LOS Pipeline')
@section('page-title', '📊 Loan Origination Pipeline')

@section('content')

{{-- ── Stage Cards ── --}}
<div class="dashboard-kpi-grid">
    @php
        $stages = [
            'draft'        => ['label' => 'Draft',        'color' => 'slate',   'icon' => '📝'],
            'submitted'    => ['label' => 'Submitted',    'color' => 'blue',    'icon' => '📤'],
            'under_review' => ['label' => 'Under Review', 'color' => 'yellow',  'icon' => '🔍'],
            'approved'     => ['label' => 'Approved',     'color' => 'emerald', 'icon' => '✅'],
            'rejected'     => ['label' => 'Rejected',     'color' => 'red',     'icon' => '❌'],
        ];
    @endphp
    @foreach($stages as $key => $meta)
    <a href="{{ route('los.pipeline', ['stage' => $key]) }}"
       class="metric-card" style="text-decoration: none; {{ $stageFilter === $key ? 'border: 2px solid var(--brand-500); box-shadow: var(--shadow-md);' : 'cursor: pointer;' }}">
        <div class="metric-card-content">
            <div class="metric-info">
                <span style="font-size: 24px;">{{ $meta['icon'] }}</span>
                <p class="metric-value">{{ $stageCounts[$key] ?? 0 }}</p>
                <p class="metric-subtitle">{{ $meta['label'] }}</p>
            </div>
            @if($stageFilter === $key)
            <div>
                <span style="font-size: 11px; background: var(--brand-100); color: var(--brand-700); padding: 2px 8px; border-radius: 20px; font-weight: 600;">Active</span>
            </div>
            @endif
        </div>
    </a>
    @endforeach
</div>

{{-- Reset filter --}}
@if($stageFilter !== 'all')
<div style="margin-bottom: 16px;">
    <a href="{{ route('los.pipeline') }}" class="text-link">← Show all stages</a>
</div>
@endif

{{-- ── Applications Table ── --}}
<div class="panel">
    <div class="panel-header-action">
        <h3 class="panel-title">
            {{ $stageFilter !== 'all' ? ($stages[$stageFilter]['label'] ?? 'All') . ' Applications' : 'All Applications' }}
        </h3>
    </div>

    <div class="table-responsive">
        <table id="pipelineTable" class="data-table">
            <thead>
                <tr>
                    <th class="text-left">App No.</th>
                    <th class="text-left">Customer</th>
                    <th class="text-left">Center</th>
                    <th class="text-left">Agent</th>
                    <th class="text-right">Amount</th>
                    <th class="text-center">Stage</th>
                    <th class="text-center">Docs</th>
                    <th class="text-left">Filed</th>
                    <th class="text-center">Action</th>
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
/* ── DataTable Polish (matching center.blade.php) ── */
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
    if (!$.fn.DataTable.isDataTable('#pipelineTable')) {
        $('#pipelineTable').DataTable({
            "processing": true,
            "serverSide": false,
            "ajax": {
                "url": "{{ route('los.pipeline.data', ['stage' => $stageFilter]) }}",
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
                        let centerName = (row.customer && row.customer.group && row.customer.group.center) ? row.customer.group.center.center_name : '—';
                        return '<span class="text-muted">' + centerName + '</span>';
                    }
                },
                {
                    "data": "agent.name",
                    "render": function(data, type, row) {
                        let agentName = (row.agent && row.agent.name) ? row.agent.name : '—';
                        return '<span class="text-muted">' + agentName + '</span>';
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
                    "data": "stage",
                    "className": "text-center",
                    "render": function(data, type, row) {
                        let badgeClass = data === 'approved' ? 'std' : (data === 'rejected' ? 'npa' : 'sma0');
                        let label = row.stage_label || data;
                        return '<span class="badge-' + badgeClass + '">' + label + '</span>';
                    }
                },
                {
                    "data": "documents",
                    "className": "text-center text-sm",
                    "render": function(data, type, row) {
                        let docs = data || [];
                        let totalDocs = docs.length;
                        let verifiedDocs = docs.filter(d => d.verification_status === 'verified').length;
                        let colorClass = (verifiedDocs === totalDocs && totalDocs > 0) ? 'text-emerald' : 'text-muted';
                        return '<span class="' + colorClass + '">' + verifiedDocs + '/' + totalDocs + '</span>';
                    }
                },
                {
                    "data": "created_at",
                    "className": "text-muted",
                    "render": function(data, type, row) {
                        if (!data) return '';
                        let d = new Date(data);
                        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
                    }
                },
                {
                    "data": "stage",
                    "className": "text-center",
                    "orderable": false,
                    "render": function(data, type, row) {
                        if (['submitted', 'under_review'].includes(data)) {
                            return '<a href="/los/applications/' + row.id + '/review" class="btn-primary-sm" style="display:inline-block; padding: 4px 12px; font-size: 12px; text-decoration:none;">Review →</a>';
                        } else if (data === 'approved') {
                            return '<span class="text-emerald text-xs font-medium">Disbursed</span>';
                        } else if (data === 'rejected') {
                            let reason = row.rejection_reason ? row.rejection_reason.replace(/"/g, '&quot;') : '';
                            return '<span class="text-rose text-xs font-medium" title="' + reason + '">Declined</span>';
                        }
                        return '<span class="text-muted">—</span>';
                    }
                }
            ],
            pageLength: 20,
            ordering: true,
            order: [[ 7, "desc" ]],
            language: { 
                search: "", 
                searchPlaceholder: "🔍 Filter...",
                emptyTable: "No applications match the filter" 
            }
        });
    }
});
</script>
@endpush
@endsection
