@extends('layouts.app')

@section('title', 'Savings Management — RD Accounts')
@section('page-title', 'Savings Management System (SMS)')

@push('head')
<style>
.progress-mini  { height: 6px; background: var(--border-light); border-radius: 4px; overflow: hidden; }
.progress-mini-fill { height: 100%; background: linear-gradient(90deg, var(--brand-500), var(--brand-400)); border-radius: 4px; }
/* Fix for Laravel Tailwind pagination SVG icons size */
.w-5 { width: 1.25rem; }
.h-5 { height: 1.25rem; }
nav[role="navigation"] { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
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
        @forelse($accounts as $account)
            @php
                $paidCount  = $account->paid_installments ?? 0;
                $totalCount = $account->total_installments ?? 1;
                $pct        = $totalCount > 0 ? round(($paidCount / $totalCount) * 100) : 0;
                $missed     = $account->missed_installments ?? 0;
            @endphp
            <tr>
                <td>
                    <span class="mono-code" style="font-size:.82rem;">{{ $account->account_no }}</span>
                </td>
                <td>
                    <div class="member-info">
                        <strong>{{ $account->customer->full_name }}</strong>
                        <small class="text-muted">{{ $account->customer->customer_code }}</small>
                    </div>
                </td>
                <td>
                    <small>{{ $account->customer->group->group_name ?? '—' }}</small><br>
                    <small class="text-muted">{{ $account->customer->group->center->center_name ?? '—' }}</small>
                </td>
                <td>
                    <strong>{{ \App\Helpers\IndianCurrency::format($account->deposit_amount) }}</strong>
                    <span class="text-muted"> / {{ $account->frequency }}</span>
                </td>
                <td>{{ $account->interest_rate }}% p.a.</td>
                <td><strong>{{ \App\Helpers\IndianCurrency::format($account->total_principal_collected) }}</strong></td>
                <td><span style="color:#10b981;">{{ \App\Helpers\IndianCurrency::format($account->total_interest_accrued) }}</span></td>
                <td style="min-width:120px;">
                    <div style="font-size:.75rem;color:var(--text-secondary);margin-bottom:3px;">
                        {{ $paidCount }}/{{ $totalCount }} installments
                        @if($missed > 0) <span style="color:#ef4444;">({{ $missed }} missed)</span>@endif
                    </div>
                    <div class="progress-mini">
                        <div class="progress-mini-fill" style="width:{{ $pct }}%;"></div>
                    </div>
                </td>
                <td>
                    @if($account->status === 'active')
                        <span class="badge-std">Active</span>
                    @elseif($account->status === 'closed')
                        <span class="badge-sma0">Closed</span>
                    @else
                        <span class="badge-npa">{{ ucfirst($account->status) }}</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('sms.savings.show', $account->id) }}" class="btn-primary-sm">View</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="10" style="text-align:center;color:var(--text-muted);padding:2rem;">No RD accounts found.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div style="padding:1rem 1.5rem;">
        {{ $accounts->withQueryString()->links() }}
    </div>
</div>

@endsection

@push('scripts')
<script>
$('#savingsTable').DataTable({ paging: false, searching: false, ordering: true });
</script>
@endpush
