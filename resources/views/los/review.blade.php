@extends('layouts.app')
@section('title', 'Review — ' . $application->application_no)
@section('page-title', '🔍 Review Docket — ' . $application->application_no)

@section('content')
@php
    function inrRev(float $a): string {
        $a = round($a,2); $p = explode('.', number_format($a, 2));
        $int = str_replace(',', '', $p[0]); $dec = $p[1];
        if (strlen($int) <= 3) return '₹' . $int . '.' . $dec;
        $last3 = substr($int, -3); $rest = substr($int, 0, strlen($int) - 3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        return '₹' . $rest . ',' . $last3 . '.' . $dec;
    }
@endphp

<div id="reviewApp">

{{-- ── Top Bar with Actions ── --}}
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
    <a href="{{ route('los.pipeline') }}" class="text-link">← Back to Pipeline</a>

    @if(in_array($application->stage, ['submitted', 'under_review']))
    <div style="display: flex; align-items: center; gap: 12px;">
        <button id="btnReject" class="btn-secondary" style="border-color: #fca5a5; color: #b91c1c;">
            ✕ Reject
        </button>
        <button id="btnApprove" class="btn-success">
            ✓ Approve & Disburse
        </button>
    </div>
    @else
    <span class="badge-{{ $application->stage === 'approved' ? 'std' : ($application->stage === 'rejected' ? 'npa' : 'sma0') }}">
        {{ $application->stage_label }}
    </span>
    @endif
</div>

<div class="dashboard-panels-grid">

    {{-- ── Left Panel: Customer Profile ── --}}
    <div class="panel-span-1" style="display: flex; flex-direction: column; gap: 20px;">

        {{-- Customer KYC Card --}}
        <div class="panel" style="margin-bottom: 0;">
            <p class="preview-title">👤 Customer Profile</p>

            <div style="text-align: center; margin-bottom: 16px;">
                <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--brand-100); display: flex; align-items: center; justify-content: center; margin: 0 auto 8px auto;">
                    <span style="color: var(--brand-700); font-weight: 700; font-size: 20px;">{{ strtoupper(substr($customer->full_name, 0, 2)) }}</span>
                </div>
                <h3 style="font-weight: 700; color: var(--text-primary); font-size: 18px; margin: 0;">{{ $customer->full_name }}</h3>
                <p class="text-muted" style="margin: 0;">{{ $customer->group->center->center_name ?? '' }} / {{ $customer->group->group_name ?? '' }}</p>
            </div>

            <div style="font-size: 14px; display: flex; flex-direction: column; gap: 8px;">
                <div style="display: flex; justify-content: space-between;">
                    <span class="text-muted">Aadhaar</span>
                    <span class="font-medium text-slate-800">{{ $customer->masked_aadhaar }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span class="text-muted">PAN</span>
                    <span class="font-medium text-slate-800">{{ $customer->pan_number ?? '—' }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span class="text-muted">Phone</span>
                    <span class="font-medium text-slate-800">{{ $customer->phone }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span class="text-muted">District</span>
                    <span class="font-medium text-slate-800">{{ $customer->district }}, {{ $customer->state }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span class="text-muted">Gender</span>
                    <span class="font-medium text-slate-800">{{ $customer->gender }}</span>
                </div>
            </div>
        </div>

        {{-- Income & Debt --}}
        <div class="panel" style="margin-bottom: 0;">
            <p class="preview-title">💰 Financial Summary</p>
            <div style="font-size: 14px; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; justify-content: space-between;">
                    <span class="text-muted">Annual Household Income</span>
                    <span class="font-bold {{ $customer->annual_household_income > 300000 ? 'text-rose' : 'text-emerald' }}">
                        {!! inrRev((float)$customer->annual_household_income) !!}
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span class="text-muted">Monthly Income</span>
                    <span class="font-medium text-slate-800">{!! inrRev($monthlyIncome) !!}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span class="text-muted">Existing Monthly Obligations</span>
                    <span class="font-medium text-slate-800">{!! inrRev((float)$customer->monthly_debt_obligations) !!}</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--border-light); padding-top: 8px;">
                    <span class="text-muted">Current FOIR</span>
                    <span class="font-bold text-slate-800">{{ $customer->foir_percent }}%</span>
                </div>
            </div>

            @if($customer->annual_household_income > 300000)
            <div class="alert-danger mt-3" style="font-size: 12px; padding: 8px;">
                ⚠️ Exceeds RBI ₹3,00,000 annual income cap
            </div>
            @endif
        </div>

        {{-- Existing Loans --}}
        <div class="panel" style="margin-bottom: 0;">
            <p class="preview-title">📦 Existing Loans ({{ $existingLoans->count() }})</p>
            <div style="display: flex; flex-direction: column;">
            @forelse($existingLoans as $el)
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 14px; padding: 8px 0; border-bottom: 1px solid var(--border-light);">
                <div>
                    <span class="font-mono" style="color: var(--brand-600); font-size: 12px;">{{ $el->loan_account_no }}</span>
                    <p class="text-muted" style="margin: 0; font-size: 12px;">{!! inrRev((float)$el->principal_amount) !!} · {{ $el->tenure }} mo</p>
                </div>
                <span class="badge-{{ $el->status === 'active' ? 'std' : 'npa' }}">
                    {{ ucfirst($el->status) }}
                </span>
            </div>
            @empty
            <p class="text-muted">No existing loans</p>
            @endforelse
            </div>
        </div>
    </div>

    {{-- ── Right Panel: Loan Details + FOIR + Documents ── --}}
    <div class="panel-span-2" style="display: flex; flex-direction: column; gap: 20px;">

        {{-- Application Details --}}
        <div class="panel" style="margin-bottom: 0;">
            <p class="preview-title">📋 Application Details</p>

            <div class="review-grid" style="grid-template-columns: repeat(3, 1fr);">
                <div class="review-box" style="background: var(--bg-primary);">
                    <p class="text-muted" style="font-size: 12px;">Application No</p>
                    <p class="font-bold" style="color: var(--brand-600); margin: 0;">{{ $application->application_no }}</p>
                </div>
                <div class="review-box" style="background: var(--bg-primary);">
                    <p class="text-muted" style="font-size: 12px;">Applied Amount</p>
                    <p class="font-bold text-slate-800" style="margin: 0;">{!! inrRev((float)$application->applied_amount) !!}</p>
                </div>
                <div class="review-box" style="background: var(--bg-primary);">
                    <p class="text-muted" style="font-size: 12px;">Interest Rate</p>
                    <p class="font-bold text-slate-800" style="margin: 0;">{{ $application->annual_interest_rate }}% p.a.</p>
                </div>
                <div class="review-box" style="background: var(--bg-primary);">
                    <p class="text-muted" style="font-size: 12px;">Tenure</p>
                    <p class="font-bold text-slate-800" style="margin: 0;">{{ $application->tenure }} {{ $application->repayment_frequency === 'weekly' ? 'weeks' : 'months' }}</p>
                </div>
                <div class="review-box" style="background: var(--bg-primary);">
                    <p class="text-muted" style="font-size: 12px;">Projected EMI</p>
                    <p class="font-bold text-slate-800" style="margin: 0;">{!! inrRev($monthlyEmi) !!}</p>
                </div>
                <div class="review-box" style="background: var(--bg-primary);">
                    <p class="text-muted" style="font-size: 12px;">Purpose</p>
                    <p class="font-bold text-slate-800" style="margin: 0;">{{ $application->purpose ?? '—' }}</p>
                </div>
                <div class="review-box" style="background: var(--bg-primary);">
                    <p class="text-muted" style="font-size: 12px;">Filed By</p>
                    <p class="font-bold text-slate-800" style="margin: 0;">{{ $application->agent->name ?? '—' }}</p>
                </div>
                <div class="review-box" style="background: var(--bg-primary);">
                    <p class="text-muted" style="font-size: 12px;">Filed On</p>
                    <p class="font-bold text-slate-800" style="margin: 0;">{{ $application->created_at->format('d M Y, h:i A') }}</p>
                </div>
            </div>
        </div>

        {{-- FOIR Gauge --}}
        <div class="panel" style="margin-bottom: 0;">
            <p class="preview-title">📊 FOIR Analysis</p>

            <div class="calculator-grid" style="margin-bottom: 20px;">
                <div style="text-align: center;">
                    <p class="text-muted" style="font-size: 12px;">Projected EMI</p>
                    <p class="calculator-value">{!! inrRev($monthlyEmi) !!}</p>
                </div>
                <div style="text-align: center;">
                    <p class="text-muted" style="font-size: 12px;">Monthly Income</p>
                    <p class="calculator-value">{!! inrRev($monthlyIncome) !!}</p>
                </div>
                <div style="text-align: center;">
                    <p class="text-muted" style="font-size: 12px;">New Total Obligation</p>
                    <p class="calculator-value">{!! inrRev((float)$customer->monthly_debt_obligations + $monthlyEmi) !!}</p>
                </div>
            </div>

            {{-- Gauge --}}
            <div class="gauge-container">
                @php
                    $gaugeColor = $projectedFoir <= 40 ? 'bg-emerald' : ($projectedFoir <= 50 ? 'bg-yellow' : 'bg-red');
                    $gaugeWidth = min($projectedFoir, 100);
                @endphp
                <div class="gauge-bar {{ $gaugeColor }}" style="width: {{ $gaugeWidth }}%">
                    <span class="gauge-text">{{ $projectedFoir }}%</span>
                </div>
                <div class="gauge-marker"></div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 4px;">
                <span class="text-muted" style="font-size: 12px;">0%</span>
                <span style="font-size: 11px; color: #dc2626; font-weight: 600;">50% RBI Cap</span>
                <span class="text-muted" style="font-size: 12px;">100%</span>
            </div>

            <div class="mt-4 {{ $projectedFoir <= 50 ? 'alert-success' : 'alert-danger' }}">
                <p class="font-bold" style="margin: 0; font-size: 14px;">
                    @if($projectedFoir <= 50)
                        ✅ FOIR {{ $projectedFoir }}% — Within RBI 50% limit. Eligible for approval.
                    @else
                        ❌ FOIR {{ $projectedFoir }}% — Exceeds RBI 50% cap. Cannot approve.
                    @endif
                </p>
            </div>
        </div>

        {{-- Document Checklist --}}
        <div class="panel" style="margin-bottom: 0;">
            <p class="preview-title">📄 KYC Document Verification</p>

            @forelse($application->documents as $doc)
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-light);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: {{ $doc->verification_status === 'verified' ? '#d1fae5' : 'var(--bg-primary)' }};">
                        @if($doc->verification_status === 'verified')
                        <svg style="width: 20px; height: 20px; color: #059669;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        @else
                        <svg style="width: 20px; height: 20px; color: var(--text-tertiary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        @endif
                    </div>
                    <div>
                        <p class="font-medium" style="margin: 0; font-size: 14px;">{{ $doc->type_label }}</p>
                        <p class="text-muted" style="margin: 0;">{{ $doc->document_number ?? 'No number' }}</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="badge-{{ $doc->verification_status === 'verified' ? 'std' : 'sma1' }}">
                        {{ ucfirst($doc->verification_status) }}
                    </span>
                    @if($doc->verification_status === 'pending' && in_array($application->stage, ['submitted', 'under_review']))
                    <form method="POST" action="{{ route('los.verify-document', $doc->id) }}" style="margin: 0;">
                        @csrf
                        <input type="hidden" name="status" value="verified">
                        <button type="submit" class="btn-success" style="padding: 4px 12px; font-size: 12px; border-radius: 8px;">
                            ✓ Verify
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <p class="text-muted">No documents attached to this application.</p>
            @endforelse
        </div>

        {{-- Rejection info (if rejected) --}}
        @if($application->stage === 'rejected')
        <div class="alert-danger" style="margin-top: 0;">
            <p class="preview-title" style="color: #b91c1c;">Rejection Details</p>
            <p style="font-size: 14px; font-weight: 500; margin: 0;">{{ $application->rejection_reason }}</p>
            @if($application->reviewer)
            <p style="font-size: 12px; color: #ef4444; margin-top: 8px; margin-bottom: 0;">Reviewed by {{ $application->reviewer->name }}</p>
            @endif
        </div>
        @endif
    </div>
</div>

{{-- ── Approve Modal ── --}}
<div id="approveModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">✅ Approve & Disburse Loan</h3>
        <p class="text-muted" style="margin-bottom: 16px;">
            This will create an active loan for <strong>{{ $customer->full_name }}</strong>
            worth <strong>{!! inrRev((float)$application->applied_amount) !!}</strong>, generate the repayment schedule,
            and create a recovery case entry.
        </p>
        <form method="POST" action="{{ route('los.approve', $application->id) }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Review Notes (Optional)</label>
                <textarea name="review_notes" rows="3" class="form-input" placeholder="e.g. All documents verified. FOIR within limits."></textarea>
            </div>
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn-secondary modal-close-btn">Cancel</button>
                <button type="submit" class="btn-success">✓ Approve & Disburse</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Reject Modal ── --}}
<div id="rejectModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">❌ Reject Application</h3>
        <p class="text-muted" style="margin-bottom: 16px;">
            Provide a reason for rejecting {{ $application->application_no }}.
        </p>
        <form method="POST" action="{{ route('los.reject', $application->id) }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Rejection Reason <span style="color: #ef4444;">*</span></label>
                <textarea name="rejection_reason" rows="3" required class="form-input" placeholder="e.g. FOIR exceeds 50%. Customer already has 3 active loans."></textarea>
            </div>
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn-secondary modal-close-btn">Cancel</button>
                <button type="submit" class="btn-primary" style="background-color: #dc2626;">✕ Reject Application</button>
            </div>
        </form>
    </div>
</div>

</div>

@push('scripts')
<style>
/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity 0.3s ease;
}
.modal-overlay.show {
    opacity: 1;
}
.modal-content {
    background: var(--surface-light);
    border-radius: var(--border-radius-xl);
    padding: 24px;
    width: 100%;
    max-width: 450px;
    box-shadow: var(--shadow-glass);
    transform: scale(0.95);
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.modal-overlay.show .modal-content {
    transform: scale(1);
}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const btnApprove = document.getElementById('btnApprove');
    const btnReject = document.getElementById('btnReject');
    const approveModal = document.getElementById('approveModal');
    const rejectModal = document.getElementById('rejectModal');
    const closeBtns = document.querySelectorAll('.modal-close-btn');

    function openModal(modal) {
        modal.style.display = 'flex';
        // trigger reflow
        void modal.offsetWidth;
        modal.classList.add('show');
    }

    function closeModal(modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }

    if(btnApprove) btnApprove.addEventListener('click', () => openModal(approveModal));
    if(btnReject) btnReject.addEventListener('click', () => openModal(rejectModal));

    closeBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal-overlay');
            closeModal(modal);
        });
    });

    [approveModal, rejectModal].forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
    });
});
</script>
@endpush
@endsection
