@extends('layouts.app')
@section('title', 'Dashboard — IndiaLend Pro')
@section('page-title', '📊 Portfolio Dashboard')

@section('content')
@php
    function inr(float $amount): string {
        // Indian numbering: lakhs and crores
        $amount = round($amount, 2);
        $parts  = explode('.', number_format($amount, 2));
        $intPart = $parts[0];
        $decPart = $parts[1];

        // Remove default commas, apply Indian format
        $intPart = str_replace(',', '', $intPart);
        $len = strlen($intPart);
        if ($len <= 3) {
            return '₹' . $intPart . '.' . $decPart;
        }
        $last3 = substr($intPart, -3);
        $rest   = substr($intPart, 0, $len - 3);
        $rest   = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        return '₹' . $rest . ',' . $last3 . '.' . $decPart;
    }

    $glpLakhs = round($glp / 100000, 2);
    $par30Lakhs = round($par30OutstandingPrincipal / 100000, 2);
@endphp

<!-- ── KPI Metric Cards ─────────────────────────────────────────────── -->
<div class="dashboard-kpi-grid">

    <!-- GLP -->
    <div class="metric-card">
        <div class="metric-card-content">
            <div class="metric-info">
                <p class="metric-title">Gross Loan Portfolio</p>
                <p class="metric-value">₹{{ number_format($glpLakhs, 2) }}L</p>
                <p class="metric-subtitle">{{ $activeLoanCount }} active accounts</p>
            </div>
            <div class="metric-icon metric-icon-brand">
                <span>GLP</span>
            </div>
        </div>
    </div>

    <!-- Today's Due -->
    <div class="metric-card">
        <div class="metric-card-content">
            <div class="metric-info">
                <p class="metric-title">Today's Collection Due</p>
                <p class="metric-value text-saffron">{{ inr((float)$todayDue) }}</p>
                <p class="metric-subtitle">{{ now()->format('d M Y') }}</p>
            </div>
            <div class="metric-icon metric-icon-saffron">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
        </div>
    </div>

    <!-- PAR 30 -->
    <div class="metric-card">
        <div class="metric-card-content">
            <div class="metric-info">
                <p class="metric-title">PAR 30</p>
                <p class="metric-value {{ $par30Pct > 5 ? 'text-rose' : 'text-emerald' }}">{{ $par30Pct }}%</p>
                <p class="metric-subtitle">₹{{ number_format($par30Lakhs, 2) }}L at risk</p>
            </div>
            <div class="metric-icon metric-icon-rose">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
        </div>
    </div>

    <!-- Gross NPA % -->
    <div class="metric-card">
        <div class="metric-card-content">
            <div class="metric-info">
                <p class="metric-title">Gross NPA %</p>
                <p class="metric-value {{ $grossNpaPct > 3 ? 'text-rose' : 'text-emerald' }}">{{ $grossNpaPct }}%</p>
                <p class="metric-subtitle">91+ DPD accounts</p>
            </div>
            <div class="metric-icon metric-icon-red">
                <span>NPA</span>
            </div>
        </div>
    </div>

    <!-- Collection Efficiency -->
    <div class="metric-card">
        <div class="metric-card-content">
            <div class="metric-info">
                <p class="metric-title">Collection Efficiency</p>
                <p class="metric-value {{ $collectionEfficiency >= 95 ? 'text-emerald' : 'text-saffron' }}">{{ $collectionEfficiency }}%</p>
                <p class="metric-subtitle">Last 30 days</p>
            </div>
            <div class="metric-icon metric-icon-emerald">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
        </div>
    </div>
</div>

<!-- ── Charts & NPA Alerts ─────────────────────────────────────────── -->
<div class="dashboard-panels-grid">

    <!-- Asset Quality Chart -->
    <div class="panel">
        <h2 class="panel-title">Portfolio Bucket Distribution</h2>
        <canvas id="bucketChart" height="220"></canvas>
    </div>

    <!-- NPA Accounts Table -->
    <div class="panel panel-span-2">
        <div class="panel-header-action">
            <h2 class="panel-title">⚠️ NPA Accounts</h2>
            <a href="{{ route('recovery.console') }}?bucket=NPA_SubStandard" class="text-link">View all →</a>
        </div>
        @if($npaLoans->isEmpty())
            <div class="empty-state">No NPA accounts 🎉</div>
        @else
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-left">Loan A/c</th>
                        <th class="text-left">Customer</th>
                        <th class="text-left">Center</th>
                        <th class="text-right">Principal</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($npaLoans as $loan)
                    <tr>
                        <td class="font-mono">{{ $loan->loan_account_no }}</td>
                        <td class="font-medium">{{ $loan->customer->full_name }}</td>
                        <td class="text-muted">{{ $loan->customer->group->center->center_name ?? '—' }}</td>
                        <td class="text-right font-bold">
                            @php
                                $princ = $loan->repaymentSchedules->whereIn('status',['overdue','pending','partial'])->sum(fn($s) => (float)$s->principal_due - (float)$s->principal_paid);
                            @endphp
                            {{ inr($princ) }}
                        </td>
                        <td class="text-right">
                            <a href="{{ route('lms.loans.show', $loan->id) }}" class="btn-primary-sm">Ledger</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    const ctx = document.getElementById('bucketChart').getContext('2d');
    const bucketData = @json($buckets);
    const labels = Object.keys(bucketData);
    const data   = Object.values(bucketData);
    const colors = {
        'Standard'       : '#10b981',
        'SMA-0'          : '#fbbf24',
        'SMA-1'          : '#f97316',
        'SMA-2'          : '#ef4444',
        'NPA_SubStandard': '#b91c1c',
        'Doubtful'       : '#7f1d1d',
    };
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{ data: data, backgroundColor: labels.map(l => colors[l] || '#94a3b8'), borderWidth: 2, borderColor: '#fff' }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } }
            }
        }
    });
</script>
@endpush
@endsection
