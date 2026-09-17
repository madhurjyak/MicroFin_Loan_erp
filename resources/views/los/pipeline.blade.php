@extends('layouts.app')
@section('title', 'LOS Pipeline')
@section('page-title', '📊 Loan Origination Pipeline')

@section('content')
@php
    function inrPipe(float $a): string {
        $a = round($a,2); $p = explode('.', number_format($a, 2));
        $int = str_replace(',', '', $p[0]); $dec = $p[1];
        if (strlen($int) <= 3) return '₹' . $int . '.' . $dec;
        $last3 = substr($int, -3); $rest = substr($int, 0, strlen($int) - 3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        return '₹' . $rest . ',' . $last3 . '.' . $dec;
    }
@endphp

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
                @forelse($applications as $app)
                <tr>
                    <td class="font-mono font-medium" style="color: var(--brand-600);">{{ $app->application_no }}</td>
                    <td>
                        <div class="font-medium text-slate-800">{{ $app->customer->full_name }}</div>
                        <div class="text-muted">{{ $app->customer->masked_aadhaar }}</div>
                    </td>
                    <td class="text-muted">
                        {{ $app->customer->group->center->center_name ?? '—' }}
                    </td>
                    <td class="text-muted">
                        {{ $app->agent->name ?? '—' }}
                    </td>
                    <td class="text-right font-medium text-slate-800">
                        {!! inrPipe((float)$app->applied_amount) !!}
                    </td>
                    <td class="text-center">
                        <span class="badge-{{ $app->stage === 'approved' ? 'std' : ($app->stage === 'rejected' ? 'npa' : 'sma0') }}">
                            {{ $app->stage_label }}
                        </span>
                    </td>
                    <td class="text-center text-sm">
                        @php
                            $totalDocs = $app->documents->count();
                            $verifiedDocs = $app->documents->where('verification_status', 'verified')->count();
                        @endphp
                        <span class="{{ $verifiedDocs === $totalDocs && $totalDocs > 0 ? 'text-emerald' : 'text-muted' }}">
                            {{ $verifiedDocs }}/{{ $totalDocs }}
                        </span>
                    </td>
                    <td class="text-muted">{{ $app->created_at->format('d M') }}</td>
                    <td class="text-center">
                        @if(in_array($app->stage, ['submitted', 'under_review']))
                        <a href="{{ route('los.review', $app->id) }}" class="btn-primary-sm">
                            Review →
                        </a>
                        @elseif($app->stage === 'approved')
                        <span class="text-emerald text-xs font-medium">Disbursed</span>
                        @elseif($app->stage === 'rejected')
                        <span class="text-rose text-xs font-medium" title="{{ $app->rejection_reason }}">Declined</span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="empty-state">No applications found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($applications->hasPages())
    <div style="padding: 16px; border-top: 1px solid var(--border-light);">
        {{ $applications->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
$(document).ready(function() {
    if (!$.fn.DataTable.isDataTable('#pipelineTable')) {
        $('#pipelineTable').DataTable({
            pageLength: 20,
            ordering: true,
            language: { search: "🔍 Filter:", emptyTable: "No applications match the filter" }
        });
    }
});
</script>
@endpush
@endsection
