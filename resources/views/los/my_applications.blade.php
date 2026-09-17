@extends('layouts.app')
@section('title', 'My Applications')
@section('page-title', '📋 My Loan Applications')

@section('content')
@php
    function inrApp(float $a): string {
        $a = round($a,2); $p = explode('.', number_format($a, 2));
        $int = str_replace(',', '', $p[0]); $dec = $p[1];
        if (strlen($int) <= 3) return '₹' . $int . '.' . $dec;
        $last3 = substr($int, -3); $rest = substr($int, 0, strlen($int) - 3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        return '₹' . $rest . ',' . $last3 . '.' . $dec;
    }
@endphp

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
                    <td class="text-right font-medium text-slate-800">
                        {!! inrApp((float)$app->applied_amount) !!}
                    </td>
                    <td class="text-center text-muted">
                        {{ $app->tenure }} {{ $app->repayment_frequency === 'weekly' ? 'wks' : 'mo' }}
                    </td>
                    <td class="text-center">
                        <span class="badge-{{ $app->stage === 'approved' ? 'std' : ($app->stage === 'rejected' ? 'npa' : 'sma0') }}">
                            {{ $app->stage_label }}
                        </span>
                        @if($app->stage === 'rejected' && $app->rejection_reason)
                        <p class="text-rose text-muted mt-1" style="max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $app->rejection_reason }}">
                            {{ $app->rejection_reason }}
                        </p>
                        @endif
                    </td>
                    <td class="text-muted">{{ $app->created_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="empty-state">
                        <p style="font-size: 18px; margin-bottom: 8px;">📝 No applications yet</p>
                        <a href="{{ route('los.apply') }}" class="text-link">File your first loan application →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#myAppsTable').DataTable({
        pageLength: 15,
        ordering: true,
        responsive: true,
        language: { search: "🔍 Filter:", emptyTable: "No applications found" }
    });
});
</script>
@endpush
@endsection
