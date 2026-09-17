@extends('layouts.app')
@section('title', 'Kendra Collection Day Sheet')
@section('page-title', '📋 Kendra Collection Day Sheet')

@section('content')
@php
    function inrFmt(float $a): string {
        $a = round($a,2); $p = explode('.', number_format($a,2)); $i = str_replace(',','',$p[0]);
        $l = strlen($i); if ($l<=3) return '₹'.$i.'.'.$p[1];
        $r = substr($i,0,$l-3); $r = preg_replace('/\B(?=(\d{2})+(?!\d))/',',',$r);
        return '₹'.$r.','.substr($i,-3).'.'.$p[1];
    }
@endphp

<!-- Filter Bar -->
<div class="panel mb-4">
    <form method="GET" action="{{ route('lms.cds') }}" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end;">
        <div>
            <label class="form-label mb-1">Center / Kendra</label>
            <select name="center_id" id="center_id" class="form-input" style="width: 240px; padding: 8px 12px;">
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
            <input type="date" name="collection_date" value="{{ $collectionDate }}" class="form-input" style="padding: 8px 12px;">
        </div>
        <button type="submit" class="btn-primary" style="padding: 8px 20px;">
            Load Sheet
        </button>
    </form>
</div>

@if($selectedCenter)
<!-- Center Info Banner -->
<div style="background: linear-gradient(135deg, var(--brand-600) 0%, var(--brand-800) 100%); border-radius: var(--border-radius-xl); padding: 16px; margin-bottom: 24px; color: white; display: flex; align-items: center; gap: 24px;">
    <div>
        <p style="font-size: 11px; font-weight: 600; opacity: 0.7; margin: 0;">CENTER</p>
        <p style="font-weight: 700; font-size: 18px; margin: 0;">{{ $selectedCenter->center_name }}</p>
        <p style="font-size: 11px; opacity: 0.7; margin: 0;">{{ $selectedCenter->center_code }}</p>
    </div>
    <div>
        <p style="font-size: 11px; font-weight: 600; opacity: 0.7; margin: 0;">BRANCH</p>
        <p style="font-weight: 600; margin: 0;">{{ $selectedCenter->branch_name }}</p>
    </div>
    <div>
        <p style="font-size: 11px; font-weight: 600; opacity: 0.7; margin: 0;">MEETING DAY</p>
        <p style="font-weight: 600; margin: 0;">{{ $selectedCenter->meeting_day }} @ {{ $selectedCenter->meeting_time }}</p>
    </div>
    <div>
        <p style="font-size: 11px; font-weight: 600; opacity: 0.7; margin: 0;">COLLECTION DATE</p>
        <p style="font-weight: 600; margin: 0;">{{ \Carbon\Carbon::parse($collectionDate)->format('d-M-Y') }}</p>
    </div>
</div>

@foreach($groups as $group)
<!-- Group Block -->
<div class="panel mb-4" style="padding: 0; overflow: hidden;">
    <!-- Group Header -->
    <div style="background: var(--bg-primary); border-bottom: 1px solid var(--border-light); padding: 12px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <span style="font-weight: 600; color: var(--text-primary);">{{ $group->group_name }}</span>
            <span class="text-muted" style="margin-left: 8px;">Leader: {{ $group->group_leader_name }}</span>
        </div>
        <span class="text-muted">{{ $group->customers->count() }} members</span>
    </div>

    <!-- Members Table -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr style="background: rgba(15, 23, 42, 0.02);">
                    <th class="text-left" style="padding-left: 20px;">#</th>
                    <th class="text-left">Member</th>
                    <th class="text-left">Loan A/c</th>
                    <th class="text-right">Installment Due</th>
                    <th class="text-right">Penal+GST</th>
                    <th class="text-right">Total Due</th>
                    <th class="text-center">Status</th>
                    <th class="text-center" style="padding-right: 20px;">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($group->customers as $idx => $customer)
                @php
                    $loan = $customer->loans->first();
                    if (!$loan) continue;
                    $schedule = $loan->repaymentSchedules->sortBy('installment_no')->first(fn($s) => in_array($s->status, ['pending','overdue','partial']));
                    if (!$schedule) continue;
                    $instDue  = (float)$schedule->principal_due + (float)$schedule->interest_due - (float)$schedule->principal_paid - (float)$schedule->interest_paid;
                    $penalDue = (float)$schedule->penal_charges_due + (float)$schedule->penal_gst_due - (float)$schedule->penal_paid - (float)$schedule->gst_paid;
                    $totalDue = $instDue + $penalDue;
                    $isLeader = $customer->full_name === $group->group_leader_name;
                @endphp
                <tr style="{{ $schedule->status === 'overdue' ? 'background-color: #fff1f2;' : '' }}">
                    <td class="text-muted" style="padding-left: 20px;">{{ $idx + 1 }}</td>
                    <td>
                        <span class="font-medium text-slate-800">{{ $customer->full_name }}</span>
                        @if($isLeader)
                            <span style="display: inline-flex; align-items: center; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; background: var(--brand-100); color: var(--brand-700); margin-left: 4px;">GL</span>
                        @endif
                        <span style="display: block; font-size: 11px; color: var(--text-tertiary); margin-top: 2px;">{{ $customer->customer_code }}</span>
                    </td>
                    <td class="font-mono" style="color: var(--text-secondary);">
                        <a href="{{ route('lms.loans.show', $loan->id) }}" class="text-link">{{ $loan->loan_account_no }}</a>
                    </td>
                    <td class="text-right font-semibold text-slate-800">{{ inrFmt($instDue) }}</td>
                    <td class="text-right {{ $penalDue > 0 ? 'text-rose font-semibold' : 'text-muted' }}">
                        {{ $penalDue > 0 ? inrFmt($penalDue) : '—' }}
                    </td>
                    <td class="text-right font-bold" style="color: #0f172a;">{{ inrFmt($totalDue) }}</td>
                    <td class="text-center">
                        @if($schedule->status === 'paid')
                            <span class="badge-std">✓ Paid</span>
                        @elseif($schedule->status === 'overdue')
                            <span class="badge-npa">Overdue</span>
                        @elseif($schedule->status === 'partial')
                            <span class="badge-sma1">Partial</span>
                        @else
                            <span class="badge-std">Pending</span>
                        @endif
                    </td>
                    <td class="text-center" style="padding-right: 20px;">
                        <a href="{{ route('lms.loans.show', $loan->id) }}" class="btn-primary-sm">
                            Collect
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty-state">No members with dues found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endforeach

@else
<div class="empty-state" style="border: 1px dashed var(--border-light); border-radius: var(--border-radius-xl); padding: 64px 20px;">
    <div style="font-size: 48px; margin-bottom: 16px;">🏘️</div>
    <p style="font-weight: 500; color: var(--text-secondary); margin-bottom: 4px;">Select a Kendra / Center to load today's collection sheet</p>
    <p style="font-size: 12px; color: var(--text-tertiary);">Displays group-wise member dues, overdue amounts, and penal charges</p>
</div>
@endif
@endsection
